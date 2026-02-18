<?php

namespace App\Services;

use App\Models\Backlink;
use App\Models\Project;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;

class BacklinkCsvImportService
{
    /** Colonnes requises du format natif LinkTracker */
    public const REQUIRED_COLUMNS = ['source_url', 'target_url'];

    /** Colonnes optionnelles du format natif avec leur valeur par défaut */
    public const OPTIONAL_COLUMNS = [
        'anchor_text'  => null,
        'status'       => 'active',
        'tier_level'   => 'tier1',
        'spot_type'    => 'external',
        'price'        => null,
        'currency'     => 'EUR',
    ];

    /**
     * Signature du format "outil tiers" (ex: WL, Collaborator, etc.)
     * Colonnes caractéristiques présentes dans l'en-tête.
     */
    public const THIRD_PARTY_SIGNATURE = ['spot', 'target', 'anchor', 'rel'];

    /**
     * Importe des backlinks depuis un fichier CSV.
     * Détecte automatiquement le format (natif ou outil tiers).
     *
     * @param  UploadedFile  $file
     * @param  Project|null  $project  Projet cible (natif). Null accepté si format tiers (projets créés via Operator).
     * @param  int|null      $userId   ID utilisateur pour création automatique de projets (format tiers).
     * @return array{imported: int, skipped: int, errors: array<string>, format: string}
     */
    public function import(UploadedFile $file, ?Project $project = null, ?int $userId = null): array
    {
        $handle = fopen($file->getRealPath(), 'r');
        if ($handle === false) {
            return ['imported' => 0, 'skipped' => 0, 'errors' => ['Impossible d\'ouvrir le fichier CSV.'], 'format' => 'unknown'];
        }

        // Lire et normaliser l'en-tête
        $rawHeader = fgetcsv($handle, 0, ',', '"', '');
        if ($rawHeader === false || $rawHeader === null) {
            fclose($handle);
            return ['imported' => 0, 'skipped' => 0, 'errors' => ['Le fichier CSV est vide.'], 'format' => 'unknown'];
        }

        $header = array_map('trim', $rawHeader);
        $headerLower = array_map('strtolower', $header);

        // Détecter le format
        $isThirdParty = $this->isThirdPartyFormat($headerLower);

        if ($isThirdParty) {
            fclose($handle);
            // Ré-ouvrir pour repasser depuis le début
            $handle = fopen($file->getRealPath(), 'r');
            fgetcsv($handle, 0, ',', '"', ''); // sauter l'en-tête
            $result = $this->importThirdParty($handle, $headerLower, $project, $userId);
        } else {
            // Vérifier colonnes requises format natif
            foreach (self::REQUIRED_COLUMNS as $col) {
                if (!in_array($col, $headerLower, true)) {
                    fclose($handle);
                    return [
                        'imported' => 0,
                        'skipped'  => 0,
                        'errors'   => ["Colonne requise manquante : '{$col}'. En-têtes : " . implode(', ', $headerLower)],
                        'format'   => 'native',
                    ];
                }
            }

            if ($project === null) {
                fclose($handle);
                return [
                    'imported' => 0,
                    'skipped'  => 0,
                    'errors'   => ['Un site cible est requis pour le format natif.'],
                    'format'   => 'native',
                ];
            }

            $result = $this->importNative($handle, $headerLower, $project);
        }

        fclose($handle);

        return $result;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Détection de format
    // ──────────────────────────────────────────────────────────────────────────

    public function isThirdPartyFormat(array $headerLower): bool
    {
        $found = 0;
        foreach (self::THIRD_PARTY_SIGNATURE as $col) {
            if (in_array($col, $headerLower, true)) {
                $found++;
            }
        }
        // Au moins 3 colonnes signature présentes
        return $found >= 3;
    }

    /**
     * Détecte le format d'un fichier CSV sans l'importer.
     * @return 'native'|'third_party'|'unknown'
     */
    public function detectFormat(UploadedFile $file): string
    {
        $handle = fopen($file->getRealPath(), 'r');
        if ($handle === false) {
            return 'unknown';
        }
        $rawHeader = fgetcsv($handle, 0, ',', '"', '');
        fclose($handle);

        if ($rawHeader === false || $rawHeader === null) {
            return 'unknown';
        }

        $headerLower = array_map('strtolower', array_map('trim', $rawHeader));

        return $this->isThirdPartyFormat($headerLower) ? 'third_party' : 'native';
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Import format natif LinkTracker
    // ──────────────────────────────────────────────────────────────────────────

    private function importNative($handle, array $headerLower, Project $project): array
    {
        $imported   = 0;
        $skipped    = 0;
        $errors     = [];
        $lineNumber = 1;

        while (($row = fgetcsv($handle, 0, ',', '"', '')) !== false) {
            $lineNumber++;

            if (count($row) < count($headerLower)) {
                $row = array_pad($row, count($headerLower), '');
            }

            $data = array_combine($headerLower, $row);
            $data = array_map('trim', $data);

            foreach (self::OPTIONAL_COLUMNS as $col => $default) {
                if (!isset($data[$col]) || $data[$col] === '') {
                    $data[$col] = $default;
                }
            }

            $validator = Validator::make($data, [
                'source_url'  => ['required', 'url', 'max:2048'],
                'target_url'  => ['required', 'url', 'max:2048'],
                'anchor_text' => ['nullable', 'string', 'max:500'],
                'status'      => ['nullable', 'in:active,lost,changed'],
                'tier_level'  => ['nullable', 'in:tier1,tier2'],
                'spot_type'   => ['nullable', 'in:external,internal'],
                'price'       => ['nullable', 'numeric', 'min:0'],
                'currency'    => ['nullable', 'string', 'max:10'],
            ]);

            if ($validator->fails()) {
                $errors[] = "Ligne {$lineNumber} : " . implode(', ', $validator->errors()->all());
                $skipped++;
                continue;
            }

            $exists = Backlink::where('project_id', $project->id)
                ->where('source_url', $data['source_url'])
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            Backlink::create([
                'project_id'    => $project->id,
                'source_url'    => $data['source_url'],
                'target_url'    => $data['target_url'],
                'anchor_text'   => $data['anchor_text'] ?: null,
                'status'        => $data['status'] ?? 'active',
                'tier_level'    => $data['tier_level'] ?? 'tier1',
                'spot_type'     => $data['spot_type'] ?? 'external',
                'price'         => isset($data['price']) && $data['price'] !== '' ? (float) $data['price'] : null,
                'currency'      => $data['currency'] ?: 'EUR',
                'first_seen_at' => now(),
            ]);

            $imported++;
        }

        return compact('imported', 'skipped', 'errors') + ['format' => 'native'];
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Import format outil tiers
    // Colonnes : Id, Spot, Lang, Status Code, Target, Anchor, Rel, Price,
    //            Invoice Number, Payment processed, Status, Network, Indexed,
    //            Contact, User, Operator, Checked At, Created At, Order Reference
    // ──────────────────────────────────────────────────────────────────────────

    private function importThirdParty($handle, array $headerLower, ?Project $forcedProject, ?int $userId = null): array
    {
        $imported        = 0;
        $skipped         = 0;
        $errors          = [];
        $lineNumber      = 1;
        $projectCache    = []; // ['Operator name' => Project]
        $createdProjects = 0;

        // Index des colonnes pour accès rapide
        $col = array_flip($headerLower);

        while (($row = fgetcsv($handle, 0, ',', '"', '')) !== false) {
            $lineNumber++;

            if (count($row) < count($headerLower)) {
                $row = array_pad($row, count($headerLower), '');
            }

            $raw = array_map('trim', array_combine($headerLower, $row));

            // ── Champs obligatoires ──
            $sourceUrl = $raw['spot'] ?? '';
            $targetUrl = $raw['target'] ?? '';

            if (empty($sourceUrl) || empty($targetUrl)) {
                $errors[] = "Ligne {$lineNumber} : URL source ou cible manquante.";
                $skipped++;
                continue;
            }

            if (!filter_var($sourceUrl, FILTER_VALIDATE_URL) || !filter_var($targetUrl, FILTER_VALIDATE_URL)) {
                $errors[] = "Ligne {$lineNumber} : URL invalide ({$sourceUrl}).";
                $skipped++;
                continue;
            }

            // ── Résoudre le projet ──
            if ($forcedProject !== null) {
                $project = $forcedProject;
            } else {
                $operatorName = $raw['operator'] ?? '';
                if (empty($operatorName)) {
                    $operatorName = 'Import CSV';
                }

                if (!isset($projectCache[$operatorName])) {
                    // Chercher ou créer le projet
                    $attributes = ['name' => $operatorName];
                    if ($userId !== null) {
                        $attributes['user_id'] = $userId;
                    }
                    $proj = Project::firstOrCreate(
                        $attributes,
                        [
                            'url'    => $this->inferProjectUrl($targetUrl),
                            'status' => 'active',
                        ]
                    );

                    if ($proj->wasRecentlyCreated) {
                        $createdProjects++;
                    }

                    $projectCache[$operatorName] = $proj;
                }

                $project = $projectCache[$operatorName];
            }

            // ── Doublon ──
            $exists = Backlink::where('project_id', $project->id)
                ->where('source_url', $sourceUrl)
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            // ── Mapping des champs ──
            $relRaw    = strtoupper($raw['rel'] ?? 'DF');
            $isDofollow = ($relRaw === 'DF');

            $statusRaw = strtolower($raw['status'] ?? 'checked');
            $status    = match ($statusRaw) {
                'checked'   => 'active',
                'dead link'  => 'lost',
                default      => 'active',
            };

            $networkRaw = strtoupper($raw['network'] ?? '');
            $spotType   = match (true) {
                str_contains($networkRaw, 'EXTERNAL') => 'external',
                str_contains($networkRaw, 'NO NETWORK') => 'internal',
                default => 'external',
            };

            $price = null;
            if (isset($raw['price']) && $raw['price'] !== '') {
                $priceVal = (float) str_replace(',', '.', $raw['price']);
                if ($priceVal > 0) {
                    $price = $priceVal;
                }
            }

            $publishedAt = null;
            if (!empty($raw['created at'])) {
                try {
                    $publishedAt = \Carbon\Carbon::parse($raw['created at'])->toDateString();
                } catch (\Exception $e) {
                    // ignorer date invalide
                }
            }

            $lastCheckedAt = null;
            if (!empty($raw['checked at'])) {
                try {
                    $lastCheckedAt = \Carbon\Carbon::parse($raw['checked at']);
                } catch (\Exception $e) {
                    // ignorer date invalide
                }
            }

            $httpStatus = null;
            if (!empty($raw['status code'])) {
                $httpStatus = (int) $raw['status code'];
            }

            Backlink::create([
                'project_id'     => $project->id,
                'source_url'     => $sourceUrl,
                'target_url'     => $targetUrl,
                'anchor_text'    => $raw['anchor'] ?: null,
                'status'         => $status,
                'is_dofollow'    => $isDofollow,
                'tier_level'     => 'tier1',
                'spot_type'      => $spotType,
                'price'          => $price,
                'currency'       => 'EUR',
                'contact_info'   => $raw['contact'] ?: null,
                'http_status'    => $httpStatus,
                'published_at'   => $publishedAt,
                'last_checked_at' => $lastCheckedAt,
                'first_seen_at'  => now(),
            ]);

            $imported++;
        }

        $meta = [];
        if ($createdProjects > 0) {
            $meta['info'] = "{$createdProjects} site(s) créé(s) automatiquement depuis la colonne Operator.";
        }

        return compact('imported', 'skipped', 'errors') + ['format' => 'third_party'] + $meta;
    }

    /**
     * Infère l'URL racine du projet à partir d'une URL cible.
     * Ex: https://footballgroundguide.com/betting → https://footballgroundguide.com
     */
    private function inferProjectUrl(string $targetUrl): string
    {
        $parsed = parse_url($targetUrl);
        if ($parsed && isset($parsed['scheme'], $parsed['host'])) {
            return $parsed['scheme'] . '://' . $parsed['host'];
        }
        return $targetUrl;
    }
}

<?php

namespace App\Services;

use App\Models\Backlink;
use App\Models\Project;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class BacklinkCsvImportService
{
    /** Colonnes acceptées dans l'ordre minimal requis */
    public const REQUIRED_COLUMNS = ['source_url', 'target_url'];

    /** Colonnes optionnelles avec leur valeur par défaut */
    public const OPTIONAL_COLUMNS = [
        'anchor_text'  => null,
        'status'       => 'active',
        'tier_level'   => 'tier1',
        'spot_type'    => 'external',
        'price'        => null,
        'currency'     => 'EUR',
    ];

    /**
     * Importe des backlinks depuis un fichier CSV dans le projet donné.
     *
     * @return array{imported: int, skipped: int, errors: array<string>}
     */
    public function import(UploadedFile $file, Project $project): array
    {
        $imported = 0;
        $skipped  = 0;
        $errors   = [];

        $handle = fopen($file->getRealPath(), 'r');
        if ($handle === false) {
            return ['imported' => 0, 'skipped' => 0, 'errors' => ['Impossible d\'ouvrir le fichier CSV.']];
        }

        // Lire et normaliser l'en-tête
        $header = fgetcsv($handle, 0, ',');
        if ($header === false || $header === null) {
            fclose($handle);
            return ['imported' => 0, 'skipped' => 0, 'errors' => ['Le fichier CSV est vide.']];
        }

        $header = array_map('trim', $header);
        $header = array_map('strtolower', $header);

        // Vérifier colonnes requises
        foreach (self::REQUIRED_COLUMNS as $col) {
            if (!in_array($col, $header, true)) {
                fclose($handle);
                return [
                    'imported' => 0,
                    'skipped'  => 0,
                    'errors'   => ["Colonne requise manquante : '{$col}'. En-têtes trouvées : " . implode(', ', $header)],
                ];
            }
        }

        $lineNumber = 1;

        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            $lineNumber++;

            if (count($row) < count($header)) {
                $row = array_pad($row, count($header), '');
            }

            $data = array_combine($header, $row);
            $data = array_map('trim', $data);

            // Remplir les valeurs par défaut pour les colonnes optionnelles absentes
            foreach (self::OPTIONAL_COLUMNS as $col => $default) {
                if (!isset($data[$col]) || $data[$col] === '') {
                    $data[$col] = $default;
                }
            }

            // Valider la ligne
            $validator = Validator::make($data, [
                'source_url' => ['required', 'url', 'max:2048'],
                'target_url' => ['required', 'url', 'max:2048'],
                'anchor_text' => ['nullable', 'string', 'max:500'],
                'status'     => ['nullable', 'in:active,lost,changed'],
                'tier_level' => ['nullable', 'in:tier1,tier2'],
                'spot_type'  => ['nullable', 'in:external,internal'],
                'price'      => ['nullable', 'numeric', 'min:0'],
                'currency'   => ['nullable', 'string', 'max:10'],
            ]);

            if ($validator->fails()) {
                $errors[] = "Ligne {$lineNumber} : " . implode(', ', $validator->errors()->all());
                $skipped++;
                continue;
            }

            // Vérifier les doublons (même source_url + project)
            $exists = Backlink::where('project_id', $project->id)
                ->where('source_url', $data['source_url'])
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            Backlink::create([
                'project_id'   => $project->id,
                'source_url'   => $data['source_url'],
                'target_url'   => $data['target_url'],
                'anchor_text'  => $data['anchor_text'] ?: null,
                'status'       => $data['status'] ?? 'active',
                'tier_level'   => $data['tier_level'] ?? 'tier1',
                'spot_type'    => $data['spot_type'] ?? 'external',
                'price'        => isset($data['price']) && $data['price'] !== '' ? (float) $data['price'] : null,
                'currency'     => $data['currency'] ?: 'EUR',
                'first_seen_at' => now(),
            ]);

            $imported++;
        }

        fclose($handle);

        return compact('imported', 'skipped', 'errors');
    }
}

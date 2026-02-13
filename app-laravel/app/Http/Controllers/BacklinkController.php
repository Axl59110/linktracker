<?php

namespace App\Http\Controllers;

use App\Models\Backlink;
use App\Models\Project;
use App\Services\Backlink\BacklinkCheckerService;
use App\Services\Alert\AlertService;
use Illuminate\Http\Request;

class BacklinkController extends Controller
{
    /**
     * Display a listing of all backlinks (global view).
     */
    public function index(Request $request)
    {
        // Validation des paramètres de filtrage
        $validated = $request->validate([
            'search' => 'nullable|string|max:255',
            'status' => 'nullable|in:active,lost,changed',
            'project_id' => 'nullable|integer|exists:projects,id',
            'tier_level' => 'nullable|in:tier1,tier2',
            'spot_type' => 'nullable|in:external,internal',
            'sort' => 'nullable|in:created_at,source_url,status,tier_level,spot_type,last_checked_at',
            'direction' => 'nullable|in:asc,desc',
        ]);

        $query = Backlink::with('project');

        // Filtrer par recherche textuelle (avec échappement des wildcards SQL)
        if (!empty($validated['search'])) {
            $search = str_replace(['%', '_'], ['\%', '\_'], $validated['search']);
            $query->where(function($q) use ($search) {
                $q->where('source_url', 'like', "%{$search}%")
                  ->orWhere('anchor_text', 'like', "%{$search}%")
                  ->orWhere('target_url', 'like', "%{$search}%");
            });
        }

        // Filtrer par status
        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        // Filtrer par projet
        if (!empty($validated['project_id'])) {
            $query->where('project_id', $validated['project_id']);
        }

        // Filtrer par tier level
        if (!empty($validated['tier_level'])) {
            $query->where('tier_level', $validated['tier_level']);
        }

        // Filtrer par spot type
        if (!empty($validated['spot_type'])) {
            $query->where('spot_type', $validated['spot_type']);
        }

        // Tri (déjà validé par la validation)
        $sortField = $validated['sort'] ?? 'created_at';
        $sortDirection = $validated['direction'] ?? 'desc';

        $query->orderBy($sortField, $sortDirection);

        // Pagination (15 items par page)
        $backlinks = $query->paginate(15)->withQueryString();

        // Charger tous les projets pour le filtre
        $projects = Project::orderBy('name')->get();

        // Compter les filtres actifs
        $activeFiltersCount = collect(['search', 'status', 'project_id', 'tier_level', 'spot_type'])
            ->filter(fn($filter) => !empty($validated[$filter]))
            ->count();

        return view('pages.backlinks.index', compact('backlinks', 'projects', 'activeFiltersCount'));
    }

    /**
     * Show the form for creating a new backlink.
     */
    public function create(Request $request)
    {
        $projects = Project::all();
        $platforms = \App\Models\Platform::active()->orderBy('name')->get();
        $tier1Backlinks = Backlink::where('tier_level', 'tier1')
            ->with('project')
            ->orderBy('source_url')
            ->get();
        $selectedProjectId = $request->query('project_id');

        return view('pages.backlinks.create', compact('projects', 'platforms', 'tier1Backlinks', 'selectedProjectId'));
    }

    /**
     * Store a newly created backlink in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            // Champs existants
            'project_id' => 'required|exists:projects,id',
            'source_url' => 'required|url|max:500',
            'target_url' => 'required|url|max:500',
            'anchor_text' => 'nullable|string|max:255',
            'rel_attributes' => 'nullable|string|max:100',
            'is_dofollow' => 'nullable|boolean',
            'status' => 'nullable|in:active', // Only 'active' allowed on creation

            // Champs extended
            'tier_level' => 'required|in:tier1,tier2',
            'parent_backlink_id' => [
                'nullable',
                'exists:backlinks,id',
                function ($attribute, $value, $fail) use ($request) {
                    // Validation: parent_backlink_id requis si tier2
                    if ($request->tier_level === 'tier2' && !$value) {
                        $fail('Un lien parent est requis pour un backlink Tier 2.');
                    }

                    // Validation: le parent doit être tier1
                    if ($value) {
                        $parentBacklink = Backlink::find($value);
                        if ($parentBacklink && $parentBacklink->tier_level !== 'tier1') {
                            $fail('Le lien parent doit être un lien Tier 1.');
                        }

                        // Prévention: un tier1 ne peut pas avoir de parent
                        if ($request->tier_level === 'tier1') {
                            $fail('Un lien Tier 1 ne peut pas avoir de lien parent.');
                        }
                    }
                },
            ],
            'spot_type' => 'required|in:external,internal',
            'published_at' => 'nullable|date',
            'expires_at' => [
                'nullable',
                'date',
                'after_or_equal:published_at',
            ],
            'price' => 'nullable|numeric|min:0|max:999999.99',
            'currency' => [
                'nullable',
                'string',
                'size:3',
                'in:EUR,USD,GBP,CAD,BRL,MXN,ARS,COP,CLP,PEN',
                function ($attribute, $value, $fail) use ($request) {
                    // Validation bidirectionnelle: prix et devise doivent être ensemble
                    if ($request->filled('price') && !$value) {
                        $fail('La devise est requise lorsqu\'un prix est renseigné.');
                    }
                    if ($value && !$request->filled('price')) {
                        $fail('Le prix est requis lorsqu\'une devise est sélectionnée.');
                    }
                },
            ],
            'invoice_paid' => 'nullable|boolean',
            'platform_id' => 'nullable|exists:platforms,id',
            'contact_info' => 'nullable|string|max:1000',
            'contact_name' => [
                'nullable',
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($request) {
                    // Requis si externe et pas de plateforme
                    if ($request->spot_type === 'external' && !$request->filled('platform_id') && !$value) {
                        $fail('Le nom du contact est requis pour un lien externe sans plateforme.');
                    }
                },
            ],
            'contact_email' => [
                'nullable',
                'email',
                'max:255',
                function ($attribute, $value, $fail) use ($request) {
                    // Requis si externe et pas de plateforme
                    if ($request->spot_type === 'external' && !$request->filled('platform_id') && !$value) {
                        $fail('L\'email du contact est requis pour un lien externe sans plateforme.');
                    }
                },
            ],
        ]);

        // Use DB transaction for data integrity
        $backlink = \DB::transaction(function () use ($validated, $request) {
            // Handle checkbox values (convert to boolean)
            $validated['invoice_paid'] = $request->boolean('invoice_paid');

            // Note: is_dofollow sera récupéré automatiquement par le système de monitoring
            // On ne le met plus dans le formulaire
            unset($validated['is_dofollow']);

            // Si réseau interne (PBN), on retire les infos financières et contact
            if ($validated['spot_type'] === 'internal') {
                unset($validated['price']);
                unset($validated['currency']);
                unset($validated['invoice_paid']);
                unset($validated['platform_id']);
                unset($validated['contact_info']);
                unset($validated['contact_name']);
                unset($validated['contact_email']);
            }

            // Set default values
            $validated['status'] = $validated['status'] ?? 'active';
            $validated['first_seen_at'] = now();
            $validated['last_checked_at'] = now();

            // Ajouter l'utilisateur connecté si disponible
            if (auth()->check()) {
                $validated['created_by_user_id'] = auth()->id();
            }

            return Backlink::create($validated);
        });

        return redirect()
            ->route('backlinks.index')
            ->with('success', 'Backlink créé avec succès.');
    }

    /**
     * Display the specified backlink.
     */
    public function show(Backlink $backlink)
    {
        $backlink->load(['project', 'checks']);

        return view('pages.backlinks.show', compact('backlink'));
    }

    /**
     * Show the form for editing the specified backlink.
     */
    public function edit(Backlink $backlink)
    {
        $projects = Project::all();
        $platforms = \App\Models\Platform::active()->orderBy('name')->get();
        $tier1Backlinks = Backlink::where('tier_level', 'tier1')
            ->where('id', '!=', $backlink->id) // Exclure le backlink en cours d'édition
            ->with('project')
            ->orderBy('source_url')
            ->get();

        return view('pages.backlinks.edit', compact('backlink', 'projects', 'platforms', 'tier1Backlinks'));
    }

    /**
     * Update the specified backlink in storage.
     */
    public function update(Request $request, Backlink $backlink)
    {
        $validated = $request->validate([
            // Champs existants
            'project_id' => 'required|exists:projects,id',
            'source_url' => 'required|url|max:500',
            'target_url' => 'required|url|max:500',
            'anchor_text' => 'nullable|string|max:255',
            'rel_attributes' => 'nullable|string|max:100',
            'is_dofollow' => 'nullable|boolean',
            'status' => 'nullable|in:active,lost,changed',

            // Champs extended
            'tier_level' => 'required|in:tier1,tier2',
            'parent_backlink_id' => [
                'nullable',
                'exists:backlinks,id',
                function ($attribute, $value, $fail) use ($request, $backlink) {
                    // Validation: parent_backlink_id requis si tier2
                    if ($request->tier_level === 'tier2' && !$value) {
                        $fail('Un lien parent est requis pour un backlink Tier 2.');
                    }

                    // Validation: le parent doit être tier1
                    if ($value) {
                        $parentBacklink = Backlink::find($value);
                        if ($parentBacklink && $parentBacklink->tier_level !== 'tier1') {
                            $fail('Le lien parent doit être un lien Tier 1.');
                        }

                        // Prévention: un tier1 ne peut pas avoir de parent
                        if ($request->tier_level === 'tier1') {
                            $fail('Un lien Tier 1 ne peut pas avoir de lien parent.');
                        }

                        // Prévention: empêcher les références circulaires (self-reference)
                        if ($value == $backlink->id) {
                            $fail('Un backlink ne peut pas être son propre parent.');
                        }

                        // Prévention: empêcher les références circulaires (indirect)
                        // Si ce backlink est le parent d'un autre, il ne peut pas devenir tier2
                        if ($backlink->childBacklinks()->exists() && $request->tier_level === 'tier2') {
                            $fail('Ce backlink ne peut pas devenir Tier 2 car il a déjà des backlinks enfants.');
                        }

                        // Prévention: empêcher de pointer vers un enfant direct
                        // Si le parent proposé a ce backlink comme parent, c'est circulaire
                        if ($parentBacklink && $parentBacklink->parent_backlink_id == $backlink->id) {
                            $fail('Référence circulaire détectée : le lien parent sélectionné pointe déjà vers ce backlink.');
                        }
                    }
                },
            ],
            'spot_type' => 'required|in:external,internal',
            'published_at' => 'nullable|date',
            'expires_at' => [
                'nullable',
                'date',
                'after_or_equal:published_at',
            ],
            'price' => 'nullable|numeric|min:0|max:999999.99',
            'currency' => [
                'nullable',
                'string',
                'size:3',
                'in:EUR,USD,GBP,CAD,BRL,MXN,ARS,COP,CLP,PEN',
                function ($attribute, $value, $fail) use ($request) {
                    // Validation bidirectionnelle: prix et devise doivent être ensemble
                    if ($request->filled('price') && !$value) {
                        $fail('La devise est requise lorsqu\'un prix est renseigné.');
                    }
                    if ($value && !$request->filled('price')) {
                        $fail('Le prix est requis lorsqu\'une devise est sélectionnée.');
                    }
                },
            ],
            'invoice_paid' => 'nullable|boolean',
            'platform_id' => 'nullable|exists:platforms,id',
            'contact_info' => 'nullable|string|max:1000',
            'contact_name' => [
                'nullable',
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($request) {
                    // Requis si externe et pas de plateforme
                    if ($request->spot_type === 'external' && !$request->filled('platform_id') && !$value) {
                        $fail('Le nom du contact est requis pour un lien externe sans plateforme.');
                    }
                },
            ],
            'contact_email' => [
                'nullable',
                'email',
                'max:255',
                function ($attribute, $value, $fail) use ($request) {
                    // Requis si externe et pas de plateforme
                    if ($request->spot_type === 'external' && !$request->filled('platform_id') && !$value) {
                        $fail('L\'email du contact est requis pour un lien externe sans plateforme.');
                    }
                },
            ],
        ]);

        // Use DB transaction for data integrity
        \DB::transaction(function () use ($validated, $request, $backlink) {
            // Handle checkbox values (convert to boolean)
            $validated['invoice_paid'] = $request->boolean('invoice_paid');

            // Note: is_dofollow sera récupéré automatiquement par le système de monitoring
            // On ne le met plus dans le formulaire
            unset($validated['is_dofollow']);

            // Si réseau interne (PBN), on retire les infos financières et contact
            if ($validated['spot_type'] === 'internal') {
                $validated['price'] = null;
                $validated['currency'] = null;
                $validated['invoice_paid'] = false;
                $validated['platform_id'] = null;
                $validated['contact_info'] = null;
                $validated['contact_name'] = null;
                $validated['contact_email'] = null;
            }

            $backlink->update($validated);
        });

        return redirect()
            ->route('backlinks.index')
            ->with('success', 'Backlink mis à jour avec succès.');
    }

    /**
     * Remove the specified backlink from storage.
     */
    public function destroy(Backlink $backlink)
    {
        $backlink->delete();

        return redirect()
            ->route('backlinks.index')
            ->with('success', 'Backlink supprimé avec succès.');
    }

    /**
     * Check a backlink manually (on-demand verification).
     */
    public function check(Backlink $backlink, BacklinkCheckerService $checkerService, AlertService $alertService)
    {
        try {
            // Vérifier le backlink avec le service
            $result = $checkerService->check($backlink);

            // Créer un enregistrement BacklinkCheck avec les résultats
            $check = $backlink->checks()->create([
                'checked_at' => now(),
                'is_present' => $result['is_present'],
                'http_status' => $result['http_status'],
                'error_message' => $result['error_message'],
            ]);

            // Mettre à jour le backlink
            $updateData = [
                'last_checked_at' => now(),
            ];

            $oldStatus = $backlink->status;

            if ($result['is_present']) {
                // Mettre à jour l'ancre si elle a changé
                if ($result['anchor_text'] !== null && $result['anchor_text'] !== $backlink->anchor_text) {
                    $updateData['anchor_text'] = $result['anchor_text'];
                }

                $updateData['rel_attributes'] = $result['rel_attributes'];
                $updateData['is_dofollow'] = $result['is_dofollow'];
                $updateData['http_status'] = $result['http_status'];

                // Gestion des changements de statut
                if ($backlink->status === 'lost') {
                    $updateData['status'] = 'active';
                    $alertService->createBacklinkRecoveredAlert($backlink);
                } elseif ($backlink->status === 'active') {
                    $changes = $this->getAttributesChanges($backlink, $result);
                    if (!empty($changes)) {
                        $updateData['status'] = 'changed';
                        $alertService->createBacklinkChangedAlert($backlink, $changes);
                    }
                }
            } else {
                // Backlink non trouvé
                if ($backlink->status !== 'lost') {
                    $updateData['status'] = 'lost';
                    $alertService->createBacklinkLostAlert($backlink, $result['error_message']);
                }
            }

            $backlink->update($updateData);

            // Message de succès
            if ($result['is_present']) {
                $message = '✅ Backlink vérifié avec succès. Le lien est présent et actif.';
            } else {
                $message = '⚠️ Backlink vérifié : le lien n\'a pas été trouvé sur la page.';
            }

            // Ajouter info sur changement de statut
            if ($oldStatus !== $backlink->fresh()->status) {
                $message .= " Le statut a été mis à jour : {$oldStatus} → {$backlink->fresh()->status}.";
            }

            return redirect()
                ->route('backlinks.show', $backlink)
                ->with($result['is_present'] ? 'success' : 'warning', $message);

        } catch (\Exception $e) {
            // En cas d'erreur, créer un check avec erreur
            $backlink->checks()->create([
                'checked_at' => now(),
                'is_present' => false,
                'http_status' => null,
                'error_message' => 'Manual check failed: ' . $e->getMessage(),
            ]);

            return redirect()
                ->route('backlinks.show', $backlink)
                ->with('error', '❌ Erreur lors de la vérification : ' . $e->getMessage());
        }
    }

    /**
     * Get the changes in backlink attributes
     *
     * @param Backlink $backlink
     * @param array $result
     * @return array
     */
    protected function getAttributesChanges(Backlink $backlink, array $result): array
    {
        $changes = [];

        if ($result['anchor_text'] !== null && $backlink->anchor_text !== $result['anchor_text']) {
            $changes['anchor_text'] = [
                'old' => $backlink->anchor_text,
                'new' => $result['anchor_text'],
            ];
        }

        if ($backlink->rel_attributes !== $result['rel_attributes']) {
            $changes['rel_attributes'] = [
                'old' => $backlink->rel_attributes,
                'new' => $result['rel_attributes'],
            ];
        }

        if ($backlink->is_dofollow !== $result['is_dofollow']) {
            $changes['is_dofollow'] = [
                'old' => $backlink->is_dofollow ? 'Oui' : 'Non',
                'new' => $result['is_dofollow'] ? 'Oui' : 'Non',
            ];
        }

        return $changes;
    }
}

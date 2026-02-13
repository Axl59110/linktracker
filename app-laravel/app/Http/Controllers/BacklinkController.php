<?php

namespace App\Http\Controllers;

use App\Models\Backlink;
use App\Models\Project;
use Illuminate\Http\Request;

class BacklinkController extends Controller
{
    /**
     * Display a listing of all backlinks (global view).
     */
    public function index(Request $request)
    {
        $query = Backlink::with('project')->latest();

        // Filtrer par status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filtrer par projet
        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        // Pagination (15 items par page)
        $backlinks = $query->paginate(15)->withQueryString();

        // Charger tous les projets pour le filtre
        $projects = Project::orderBy('name')->get();

        return view('pages.backlinks.index', compact('backlinks', 'projects'));
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
}

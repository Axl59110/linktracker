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
        $selectedProjectId = $request->query('project_id');

        return view('pages.backlinks.create', compact('projects', 'selectedProjectId'));
    }

    /**
     * Store a newly created backlink in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'source_url' => 'required|url|max:500',
            'target_url' => 'required|url|max:500',
            'anchor_text' => 'nullable|string|max:255',
            'rel_attributes' => 'nullable|string|max:100',
            'is_dofollow' => 'boolean',
        ]);

        // Set default values
        $validated['status'] = 'active';
        $validated['first_seen_at'] = now();
        $validated['last_checked_at'] = now();

        $backlink = Backlink::create($validated);

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

        return view('pages.backlinks.edit', compact('backlink', 'projects'));
    }

    /**
     * Update the specified backlink in storage.
     */
    public function update(Request $request, Backlink $backlink)
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'source_url' => 'required|url|max:500',
            'target_url' => 'required|url|max:500',
            'anchor_text' => 'nullable|string|max:255',
            'rel_attributes' => 'nullable|string|max:100',
            'is_dofollow' => 'boolean',
            'status' => 'nullable|in:active,lost,changed',
        ]);

        $backlink->update($validated);

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

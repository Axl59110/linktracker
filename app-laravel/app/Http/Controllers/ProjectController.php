<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    /**
     * Display a listing of the projects.
     */
    public function index()
    {
        $projects = Project::withCount('backlinks')
            ->latest()
            ->paginate(15);

        return view('pages.projects.index', compact('projects'));
    }

    /**
     * Show the form for creating a new project.
     */
    public function create()
    {
        return view('pages.projects.create');
    }

    /**
     * Store a newly created project in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url|max:255',
            'status' => 'nullable|in:active,inactive',
        ]);

        // TODO: Ajouter user_id quand l'auth sera implémentée
        // $validated['user_id'] = auth()->id();

        $project = Project::create($validated);

        return redirect()
            ->route('projects.index')
            ->with('success', 'Projet créé avec succès.');
    }

    /**
     * Display the specified project.
     */
    public function show(Project $project)
    {
        $project->loadCount('backlinks');

        // Stats avancées pour le pilotage
        $allBacklinks = $project->backlinks()->get();

        $stats = [
            'total'            => $allBacklinks->count(),
            'active'           => $allBacklinks->where('status', 'active')->count(),
            'lost'             => $allBacklinks->where('status', 'lost')->count(),
            'changed'          => $allBacklinks->where('status', 'changed')->count(),
            // Qualité : actif + indexé + dofollow
            'quality'          => $allBacklinks->where('status', 'active')->where('is_indexed', true)->where('is_dofollow', true)->count(),
            'not_indexed'      => $allBacklinks->where('is_indexed', false)->count(),
            'not_dofollow'     => $allBacklinks->where('is_dofollow', false)->count(),
            'unknown_indexed'  => $allBacklinks->whereNull('is_indexed')->count(),
            'budget_total'     => $allBacklinks->sum('price'),
            'budget_active'    => $allBacklinks->where('status', 'active')->sum('price'),
        ];

        // Score de santé : 0-100 basé sur actifs, indexés, dofollow
        $stats['health_score'] = $stats['total'] > 0 ? (int) round(
            ($stats['active'] / $stats['total']) * 60 +
            ($stats['total'] > 0 && ($stats['total'] - $stats['unknown_indexed']) > 0
                ? ($stats['quality'] / max(1, $stats['total'] - $stats['unknown_indexed'])) * 40
                : 0)
        ) : 0;

        // 10 derniers backlinks pour le tableau
        $recentBacklinks = $project->backlinks()->latest()->take(10)->get();

        return view('pages.projects.show', compact('project', 'stats', 'recentBacklinks'));
    }

    /**
     * Show the form for editing the specified project.
     */
    public function edit(Project $project)
    {
        return view('pages.projects.edit', compact('project'));
    }

    /**
     * Update the specified project in storage.
     */
    public function update(Request $request, Project $project)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url|max:255',
            'status' => 'nullable|in:active,inactive',
        ]);

        $project->update($validated);

        return redirect()
            ->route('projects.index')
            ->with('success', 'Projet mis à jour avec succès.');
    }

    /**
     * Remove the specified project from storage.
     */
    public function destroy(Project $project)
    {
        $project->delete();

        return redirect()
            ->route('projects.index')
            ->with('success', 'Projet supprimé avec succès.');
    }

    /**
     * STORY-039 : Rapport HTML imprimable du projet
     */
    public function report(Project $project)
    {
        $project->load(['backlinks' => function ($query) {
            $query->with('checks')->orderBy('status')->orderBy('source_url');
        }]);

        $stats = [
            'total'   => $project->backlinks->count(),
            'active'  => $project->backlinks->where('status', 'active')->count(),
            'lost'    => $project->backlinks->where('status', 'lost')->count(),
            'changed' => $project->backlinks->where('status', 'changed')->count(),
        ];

        // Enrichir avec les métriques de domaine disponibles
        $domains = $project->backlinks->map(function ($backlink) {
            return \App\Models\DomainMetric::extractDomain($backlink->source_url);
        })->unique()->filter()->values();

        $domainMetrics = \App\Models\DomainMetric::whereIn('domain', $domains)
            ->get()
            ->keyBy('domain');

        $generatedAt = now();

        return view('pages.projects.report', compact('project', 'stats', 'domainMetrics', 'generatedAt'));
    }
}

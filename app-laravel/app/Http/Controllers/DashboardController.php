<?php

namespace App\Http\Controllers;

use App\Models\Backlink;
use App\Models\Project;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Display the dashboard.
     */
    public function index()
    {
        // Statistiques des backlinks
        $activeBacklinks = Backlink::where('status', 'active')->count();
        $lostBacklinks = Backlink::where('status', 'lost')->count();
        $changedBacklinks = Backlink::where('status', 'changed')->count();
        $totalBacklinks = Backlink::count();

        // Statistiques des projets
        $totalProjects = Project::count();

        // Projets récents avec nombre de backlinks
        $recentProjects = Project::withCount('backlinks')
            ->latest()
            ->take(5)
            ->get();

        // Backlinks récents
        $recentBacklinks = Backlink::with('project')
            ->latest()
            ->take(5)
            ->get();

        // TODO: Ajouter alertes récentes quand EPIC-004 sera implémenté
        // $recentAlerts = Alert::latest()->take(5)->get();
        $recentAlerts = [];

        return view('pages.dashboard', compact(
            'activeBacklinks',
            'lostBacklinks',
            'changedBacklinks',
            'totalBacklinks',
            'totalProjects',
            'recentProjects',
            'recentBacklinks',
            'recentAlerts'
        ));
    }
}

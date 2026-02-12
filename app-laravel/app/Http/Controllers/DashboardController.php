<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Display the dashboard.
     *
     * TODO: Ajouter les données réelles (stats, projets, alertes)
     */
    public function index()
    {
        // PLACEHOLDERS - À remplacer par données réelles
        // $activeBacklinks = Backlink::where('status', 'active')->count();
        // $lostBacklinks = Backlink::where('status', 'lost')->count();
        // $totalProjects = Project::count();
        // $recentProjects = Project::latest()->take(3)->get();
        // $recentAlerts = Alert::latest()->take(5)->get();

        return view('pages.dashboard');
    }
}

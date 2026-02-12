<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Display the dashboard.
     *
     * TODO: Ajouter les données réelles (stats, projets, alertes)
     * TODO: Implémenter les requêtes vers models Backlink, Project, Alert
     */
    public function index()
    {
        // PLACEHOLDERS - À remplacer par données réelles quand les models seront prêts
        // $activeBacklinks = Backlink::where('status', 'active')->count();
        // $lostBacklinks = Backlink::where('status', 'lost')->count();
        // $totalProjects = Project::count();
        // $recentProjects = Project::latest()->take(3)->get();
        // $recentAlerts = Alert::latest()->take(5)->get();

        $data = [
            'activeBacklinks' => 0,  // PLACEHOLDER
            'lostBacklinks' => 0,    // PLACEHOLDER
            'totalProjects' => 0,    // PLACEHOLDER
            'recentProjects' => [],  // PLACEHOLDER
            'recentAlerts' => [],    // PLACEHOLDER
        ];

        return view('pages.dashboard', $data);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Backlink;
use App\Models\BacklinkCheck;
use App\Models\Project;
use App\Models\Alert;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Display the dashboard.
     */
    public function index()
    {
        // Statistiques mises en cache 5 minutes
        $stats = Cache::remember('dashboard_stats', 300, function () {
            $activeBacklinks  = Backlink::where('status', 'active')->count();
            $lostBacklinks    = Backlink::where('status', 'lost')->count();
            $changedBacklinks = Backlink::where('status', 'changed')->count();
            $totalBacklinks   = Backlink::count();
            $totalProjects    = Project::count();

            $totalChecks   = BacklinkCheck::where('checked_at', '>=', now()->subDays(30))->count();
            $presentChecks = BacklinkCheck::where('checked_at', '>=', now()->subDays(30))->where('is_present', true)->count();
            $uptimeRate    = $totalChecks > 0 ? round(($presentChecks / $totalChecks) * 100, 1) : null;

            // Stats avancées (pilotage)
            $qualityLinks    = Backlink::where('status', 'active')->where('is_indexed', true)->where('is_dofollow', true)->count();
            $notIndexed      = Backlink::where('is_indexed', false)->count();
            $notDofollow     = Backlink::where('is_dofollow', false)->count();
            $unknownIndexed  = Backlink::whereNull('is_indexed')->count();
            $budgetTotal     = Backlink::sum('price');
            $budgetActive    = Backlink::where('status', 'active')->sum('price');

            $healthScore = $totalBacklinks > 0 ? (int) round(
                ($activeBacklinks / $totalBacklinks) * 60 +
                ($totalBacklinks - $unknownIndexed > 0
                    ? ($qualityLinks / max(1, $totalBacklinks - $unknownIndexed)) * 40
                    : 0)
            ) : 0;

            return compact(
                'activeBacklinks', 'lostBacklinks', 'changedBacklinks',
                'totalBacklinks', 'totalProjects',
                'totalChecks', 'uptimeRate',
                'qualityLinks', 'notIndexed', 'notDofollow', 'unknownIndexed',
                'budgetTotal', 'budgetActive', 'healthScore'
            );
        });

        // Données fraîches (pas de cache) : projets récents, backlinks récents, alertes
        $recentProjects = Project::withCount('backlinks')
            ->latest()
            ->take(5)
            ->get();

        $recentBacklinks = Backlink::with('project')
            ->latest()
            ->take(10)
            ->get();

        $recentAlerts = Alert::with('backlink.project')
            ->latest()
            ->take(5)
            ->get();

        return view('pages.dashboard', array_merge($stats, compact(
            'recentProjects',
            'recentBacklinks',
            'recentAlerts'
        )));
    }

    /**
     * Retourne les données du graphique d'évolution (JSON pour Chart.js).
     * GET /api/dashboard/chart?days=30&project_id=
     */
    public function chartData(Request $request)
    {
        $days      = (int) $request->get('days', 30);
        $projectId = $request->get('project_id');

        // Valider les paramètres
        $days = in_array($days, [7, 30, 90]) ? $days : 30;

        $cacheKey = "dashboard_chart_" . ($projectId ?? 'all') . "_{$days}";

        $data = Cache::remember($cacheKey, 600, function () use ($days, $projectId) {
            $query = Backlink::query();

            if ($projectId) {
                $query->where('project_id', $projectId);
            }

            // Données groupées par jour pour les 30/7/90 derniers jours
            $dates = [];
            for ($i = $days - 1; $i >= 0; $i--) {
                $dates[] = now()->subDays($i)->format('Y-m-d');
            }

            $active  = [];
            $lost    = [];
            $changed = [];
            $gained  = [];
            $delta   = [];

            foreach ($dates as $date) {
                $dayQuery = clone $query;
                // Pour "gained" : utiliser published_at si renseigné, sinon created_at
                $gainedVal = (clone $dayQuery)->where(function ($q) use ($date) {
                    $q->whereNotNull('published_at')->whereDate('published_at', $date)
                      ->orWhere(function ($q2) use ($date) {
                          $q2->whereNull('published_at')->whereDate('created_at', $date);
                      });
                })->count();
                // Courbe "active" : cumulatif basé sur published_at ou created_at
                $activeVal  = (clone $dayQuery)->where('status', 'active')->where(function ($q) use ($date) {
                    $q->where(function ($q2) use ($date) {
                        $q2->whereNotNull('published_at')->whereDate('published_at', '<=', $date);
                    })->orWhere(function ($q2) use ($date) {
                        $q2->whereNull('published_at')->whereDate('created_at', '<=', $date);
                    });
                })->count();
                $lostVal    = (clone $dayQuery)->where('status', 'lost')->whereDate('last_checked_at', $date)->count();
                $changedVal = (clone $dayQuery)->where('status', 'changed')->whereDate('last_checked_at', $date)->count();

                $active[]   = $activeVal;
                $lost[]     = $lostVal;
                $changed[]  = $changedVal;
                $gained[]   = $gainedVal;
                // delta = gained - lost (positif = gain net, négatif = perte nette)
                $delta[]    = $gainedVal - $lostVal;
            }

            return [
                'labels'   => array_map(fn($d) => \Carbon\Carbon::parse($d)->format('d/m'), $dates),
                'active'   => $active,
                'lost'     => $lost,
                'changed'  => $changed,
                'gained'   => $gained,
                'delta'    => $delta,
            ];
        });

        return response()->json($data);
    }
}

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

        // Valider les paramètres (ajout 180j et 365j pour voir l'historique réel)
        $days = in_array($days, [7, 30, 90, 180, 365]) ? $days : 30;

        $cacheKey = "dashboard_chart_" . ($projectId ?? 'all') . "_{$days}";

        $data = Cache::remember($cacheKey, 600, function () use ($days, $projectId) {

            // Fenêtre temporelle
            $startDate = now()->subDays($days - 1)->startOfDay();
            $endDate   = now()->endOfDay();

            // Générer toutes les dates de la fenêtre
            $dates = [];
            for ($i = $days - 1; $i >= 0; $i--) {
                $dates[] = now()->subDays($i)->format('Y-m-d');
            }

            // --- Requête 1 : gains groupés par jour (published_at dans la fenêtre) ---
            // Un backlink est "acquis" à sa date de publication réelle.
            // Si published_at est NULL, on utilise created_at (lien ajouté manuellement).
            $gainedRaw = Backlink::query()
                ->when($projectId, fn($q) => $q->where('project_id', $projectId))
                ->selectRaw("DATE(COALESCE(published_at, DATE(created_at))) as day, COUNT(*) as cnt")
                ->whereBetween(DB::raw("DATE(COALESCE(published_at, DATE(created_at)))"), [
                    $startDate->format('Y-m-d'),
                    $endDate->format('Y-m-d'),
                ])
                ->groupByRaw("DATE(COALESCE(published_at, DATE(created_at)))")
                ->pluck('cnt', 'day')
                ->toArray();

            // --- Requête 2 : pertes groupées par jour (last_checked_at dans la fenêtre) ---
            $lostRaw = Backlink::query()
                ->when($projectId, fn($q) => $q->where('project_id', $projectId))
                ->where('status', 'lost')
                ->whereNotNull('last_checked_at')
                ->selectRaw("DATE(last_checked_at) as day, COUNT(*) as cnt")
                ->whereBetween(DB::raw("DATE(last_checked_at)"), [
                    $startDate->format('Y-m-d'),
                    $endDate->format('Y-m-d'),
                ])
                ->groupByRaw("DATE(last_checked_at)")
                ->pluck('cnt', 'day')
                ->toArray();

            // --- Requête 3 : cumulatif actifs avant le début de la fenêtre ---
            // Base de départ : liens actifs publiés AVANT la fenêtre
            $baseActive = Backlink::query()
                ->when($projectId, fn($q) => $q->where('project_id', $projectId))
                ->where('status', 'active')
                ->where(DB::raw("DATE(COALESCE(published_at, DATE(created_at)))"), '<', $startDate->format('Y-m-d'))
                ->count();

            // Construire les séries jour par jour (cumulatif = base + gains accumulés)
            $active  = [];
            $gained  = [];
            $lost    = [];
            $changed = [];
            $delta   = [];

            $cumulative = $baseActive;

            foreach ($dates as $date) {
                $gainedVal  = (int) ($gainedRaw[$date] ?? 0);
                $lostVal    = (int) ($lostRaw[$date]   ?? 0);

                $cumulative += $gainedVal - $lostVal;

                $gained[]  = $gainedVal;
                $lost[]    = $lostVal;
                $changed[] = 0; // placeholder (changed n'affecte pas le cumulatif)
                $active[]  = max(0, $cumulative);
                $delta[]   = $gainedVal - $lostVal;
            }

            // Format des labels : condensé pour les longues périodes
            $labelFormat = $days <= 90 ? 'd/m' : 'd/m/y';

            return [
                'labels'   => array_map(fn($d) => \Carbon\Carbon::parse($d)->format($labelFormat), $dates),
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

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
            $startStr  = $startDate->format('Y-m-d');
            $endStr    = $endDate->format('Y-m-d');

            // Générer toutes les dates de la fenêtre
            $dates = [];
            for ($i = $days - 1; $i >= 0; $i--) {
                $dates[] = now()->subDays($i)->format('Y-m-d');
            }

            // Helper : applique le filtre projet si fourni
            $base = fn() => Backlink::query()->when($projectId, fn($q) => $q->where('project_id', $projectId));

            // Colonne de date de publication (published_at ou created_at en fallback)
            $pubDate = "DATE(COALESCE(published_at, DATE(created_at)))";

            // --- Requête 1 : tous les gains groupés par jour ---
            $gainedRaw = $base()
                ->selectRaw("{$pubDate} as day, COUNT(*) as cnt")
                ->whereBetween(DB::raw($pubDate), [$startStr, $endStr])
                ->groupByRaw($pubDate)
                ->pluck('cnt', 'day')
                ->toArray();

            // --- Requête 2 : gains "parfaits" (actif + indexé + dofollow) ---
            $gainedPerfectRaw = $base()
                ->where('status', 'active')
                ->where('is_indexed', true)
                ->where('is_dofollow', true)
                ->selectRaw("{$pubDate} as day, COUNT(*) as cnt")
                ->whereBetween(DB::raw($pubDate), [$startStr, $endStr])
                ->groupByRaw($pubDate)
                ->pluck('cnt', 'day')
                ->toArray();

            // --- Requête 3 : gains "non indexés" ---
            $gainedNotIdxRaw = $base()
                ->where('is_indexed', false)
                ->selectRaw("{$pubDate} as day, COUNT(*) as cnt")
                ->whereBetween(DB::raw($pubDate), [$startStr, $endStr])
                ->groupByRaw($pubDate)
                ->pluck('cnt', 'day')
                ->toArray();

            // --- Requête 4 : gains "nofollow" ---
            $gainedNofollowRaw = $base()
                ->where('is_dofollow', false)
                ->selectRaw("{$pubDate} as day, COUNT(*) as cnt")
                ->whereBetween(DB::raw($pubDate), [$startStr, $endStr])
                ->groupByRaw($pubDate)
                ->pluck('cnt', 'day')
                ->toArray();

            // --- Requête 5 : pertes groupées par jour ---
            $lostRaw = $base()
                ->where('status', 'lost')
                ->whereNotNull('last_checked_at')
                ->selectRaw("DATE(last_checked_at) as day, COUNT(*) as cnt")
                ->whereBetween(DB::raw("DATE(last_checked_at)"), [$startStr, $endStr])
                ->groupByRaw("DATE(last_checked_at)")
                ->pluck('cnt', 'day')
                ->toArray();

            // --- Bases cumulatives AVANT la fenêtre (état actuel projeté) ---
            $baseTotal    = $base()->where(DB::raw($pubDate), '<', $startStr)->count();
            $basePerfect  = $base()->where('status', 'active')->where('is_indexed', true)->where('is_dofollow', true)
                                   ->where(DB::raw($pubDate), '<', $startStr)->count();
            $baseNotIdx   = $base()->where('is_indexed', false)
                                   ->where(DB::raw($pubDate), '<', $startStr)->count();
            $baseNofollow = $base()->where('is_dofollow', false)
                                   ->where(DB::raw($pubDate), '<', $startStr)->count();

            // --- Construire les séries jour par jour ---
            $active     = [];
            $perfect    = [];
            $not_indexed = [];
            $nofollow   = [];
            $gained     = [];
            $lost       = [];
            $delta      = [];

            $cumTotal    = $baseTotal;
            $cumPerfect  = $basePerfect;
            $cumNotIdx   = $baseNotIdx;
            $cumNofollow = $baseNofollow;

            foreach ($dates as $date) {
                $gainedVal        = (int) ($gainedRaw[$date]         ?? 0);
                $gainedPerfect    = (int) ($gainedPerfectRaw[$date]  ?? 0);
                $gainedNotIdx     = (int) ($gainedNotIdxRaw[$date]   ?? 0);
                $gainedNofollow   = (int) ($gainedNofollowRaw[$date] ?? 0);
                $lostVal          = (int) ($lostRaw[$date]           ?? 0);

                $cumTotal    += $gainedVal - $lostVal;
                $cumPerfect  += $gainedPerfect;
                $cumNotIdx   += $gainedNotIdx;
                $cumNofollow += $gainedNofollow;

                $active[]      = max(0, $cumTotal);
                $perfect[]     = max(0, $cumPerfect);
                $not_indexed[] = max(0, $cumNotIdx);
                $nofollow[]    = max(0, $cumNofollow);
                $gained[]      = $gainedVal;
                $lost[]        = $lostVal;
                $delta[]       = $gainedVal - $lostVal;
            }

            // Format des labels : condensé pour les longues périodes
            $labelFormat = $days <= 90 ? 'd/m' : 'd/m/y';

            return [
                'labels'      => array_map(fn($d) => \Carbon\Carbon::parse($d)->format($labelFormat), $dates),
                'active'      => $active,
                'perfect'     => $perfect,
                'not_indexed' => $not_indexed,
                'nofollow'    => $nofollow,
                'gained'      => $gained,
                'lost'        => $lost,
                'delta'       => $delta,
            ];
        });

        return response()->json($data);
    }
}

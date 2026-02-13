<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    /**
     * Display a listing of alerts with filters.
     */
    public function index(Request $request)
    {
        // Validation des paramètres de filtrage
        $validated = $request->validate([
            'type' => 'nullable|in:backlink_lost,backlink_changed,backlink_recovered',
            'severity' => 'nullable|in:low,medium,high,critical',
            'is_read' => 'nullable|in:read,unread',
            'days' => 'nullable|integer|min:1|max:365',
        ]);

        $query = Alert::with('backlink.project')->orderBy('created_at', 'desc');

        // Filtrer par type
        if (!empty($validated['type'])) {
            $query->where('type', $validated['type']);
        }

        // Filtrer par sévérité
        if (!empty($validated['severity'])) {
            $query->where('severity', $validated['severity']);
        }

        // Filtrer par statut de lecture
        if (!empty($validated['is_read'])) {
            if ($validated['is_read'] === 'unread') {
                $query->where('is_read', false);
            } else {
                $query->where('is_read', true);
            }
        }

        // Filtrer par période
        if (!empty($validated['days'])) {
            $query->where('created_at', '>=', now()->subDays($validated['days']));
        }

        // Pagination
        $alerts = $query->paginate(20)->withQueryString();

        // Compter les filtres actifs
        $activeFiltersCount = count(array_filter([
            $validated['type'] ?? null,
            $validated['severity'] ?? null,
            $validated['is_read'] ?? null,
            $validated['days'] ?? null,
        ]));

        // Stats pour l'affichage
        $stats = [
            'total' => Alert::count(),
            'unread' => Alert::unread()->count(),
            'critical' => Alert::where('severity', 'critical')->count(),
            'today' => Alert::whereDate('created_at', today())->count(),
        ];

        return view('pages.alerts.index', compact('alerts', 'activeFiltersCount', 'stats'));
    }

    /**
     * Mark a single alert as read.
     */
    public function markAsRead(Alert $alert)
    {
        $alert->markAsRead();

        return back()->with('success', 'Alerte marquée comme lue.');
    }

    /**
     * Mark all alerts as read.
     */
    public function markAllAsRead()
    {
        $count = Alert::where('is_read', false)->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

        return back()->with('success', "{$count} alerte(s) marquée(s) comme lue(s).");
    }

    /**
     * Delete an alert.
     */
    public function destroy(Alert $alert)
    {
        $alert->delete();

        return back()->with('success', 'Alerte supprimée avec succès.');
    }

    /**
     * Delete all read alerts.
     */
    public function destroyAllRead()
    {
        $count = Alert::where('is_read', true)->delete();

        return back()->with('success', "{$count} alerte(s) lue(s) supprimée(s).");
    }
}

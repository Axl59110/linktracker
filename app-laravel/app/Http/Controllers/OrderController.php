<?php

namespace App\Http\Controllers;

use App\Models\Backlink;
use App\Models\Order;
use App\Models\Platform;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with(['project', 'platform']);

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $orders   = $query->latest()->paginate(20)->withQueryString();
        $projects = Project::orderBy('name')->get();

        return view('pages.orders.index', compact('orders', 'projects'));
    }

    public function create()
    {
        $projects  = Project::orderBy('name')->get();
        $platforms = Platform::orderBy('name')->get();

        return view('pages.orders.create', compact('projects', 'platforms'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'project_id'   => 'required|exists:projects,id',
            'platform_id'  => 'nullable|exists:platforms,id',
            'target_url'   => 'required|url|max:2048',
            'source_url'   => 'nullable|url|max:2048',
            'anchor_text'  => 'nullable|string|max:255',
            'tier_level'   => 'required|in:tier1,tier2',
            'spot_type'    => 'required|in:external,internal',
            'price'        => 'nullable|numeric|min:0',
            'currency'     => 'nullable|string|max:10',
            'invoice_paid' => 'nullable|boolean',
            'ordered_at'   => 'nullable|date',
            'expected_at'  => 'nullable|date|after_or_equal:ordered_at',
            'contact_name' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'notes'        => 'nullable|string|max:5000',
        ]);

        $validated['status'] = 'pending';
        $validated['invoice_paid'] = $request->boolean('invoice_paid');

        Order::create($validated);

        return redirect()->route('orders.index')->with('success', 'Commande créée avec succès.');
    }

    public function show(Order $order)
    {
        $order->load(['project', 'platform', 'backlink']);
        return view('pages.orders.show', compact('order'));
    }

    public function edit(Order $order)
    {
        $projects  = Project::orderBy('name')->get();
        $platforms = Platform::orderBy('name')->get();

        return view('pages.orders.edit', compact('order', 'projects', 'platforms'));
    }

    public function update(Request $request, Order $order)
    {
        $validated = $request->validate([
            'project_id'   => 'required|exists:projects,id',
            'platform_id'  => 'nullable|exists:platforms,id',
            'target_url'   => 'required|url|max:2048',
            'source_url'   => 'nullable|url|max:2048',
            'anchor_text'  => 'nullable|string|max:255',
            'tier_level'   => 'required|in:tier1,tier2',
            'spot_type'    => 'required|in:external,internal',
            'price'        => 'nullable|numeric|min:0',
            'currency'     => 'nullable|string|max:10',
            'invoice_paid' => 'nullable|boolean',
            'ordered_at'   => 'nullable|date',
            'expected_at'  => 'nullable|date',
            'published_at' => 'nullable|date',
            'contact_name' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'notes'        => 'nullable|string|max:5000',
        ]);

        $validated['invoice_paid'] = $request->boolean('invoice_paid');

        $order->update($validated);

        return redirect()->route('orders.show', $order)->with('success', 'Commande mise à jour.');
    }

    public function destroy(Order $order)
    {
        $order->delete();
        return redirect()->route('orders.index')->with('success', 'Commande supprimée.');
    }

    public function updateStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|in:pending,in_progress,published,cancelled,refunded',
        ]);

        $newStatus = $request->status;
        $order->update(['status' => $newStatus]);

        // Auto-création du backlink quand la commande est publiée
        if ($newStatus === 'published' && $order->source_url && !$order->backlink_id) {
            $backlink = Backlink::firstOrCreate(
                [
                    'project_id' => $order->project_id,
                    'source_url' => $order->source_url,
                ],
                [
                    'target_url'  => $order->target_url ?? '',
                    'anchor_text' => $order->anchor_text,
                    'tier_level'  => $order->tier_level ?? 'tier1',
                    'spot_type'   => $order->spot_type ?? 'external',
                    'price'       => $order->price,
                    'currency'    => $order->currency,
                    'platform_id' => $order->platform_id,
                    'status'      => 'active',
                    'first_seen_at'   => now(),
                    'last_checked_at' => now(),
                ]
            );

            $order->update(['backlink_id' => $backlink->id]);

            Log::info('Backlink auto-créé depuis commande publiée', [
                'order_id'   => $order->id,
                'backlink_id' => $backlink->id,
                'created'    => $backlink->wasRecentlyCreated,
            ]);

            $message = $backlink->wasRecentlyCreated
                ? "Statut mis à jour et backlink créé automatiquement."
                : "Statut mis à jour et backlink existant lié.";

            return back()->with('success', $message);
        }

        return back()->with('success', "Statut mis à jour : {$order->status_label}.");
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Platform;
use App\Models\Project;
use Illuminate\Http\Request;

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

        $order->update(['status' => $request->status]);

        return back()->with('success', "Statut mis à jour : {$order->status_label}.");
    }
}

@extends('layouts.app')

@section('title', 'Commandes - Link Tracker')

@section('breadcrumb')
    <span class="text-neutral-900 font-medium">Commandes</span>
@endsection

@section('content')
    <x-page-header title="Commandes" subtitle="Gérez vos achats de backlinks">
        <x-slot:actions>
            <x-button variant="primary" href="{{ route('orders.create') }}">+ Nouvelle commande</x-button>
        </x-slot:actions>
    </x-page-header>

    {{-- Filtres rapides --}}
    <div class="bg-white rounded-lg border border-neutral-200 p-4 mb-6">
        <form method="GET" action="{{ route('orders.index') }}" class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs font-medium text-neutral-700 mb-1">Site</label>
                <select name="project_id" class="px-3 py-2 border border-neutral-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                    <option value="">Tous les sites</option>
                    @foreach($projects as $project)
                        <option value="{{ $project->id }}" {{ request('project_id') == $project->id ? 'selected' : '' }}>
                            {{ $project->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-neutral-700 mb-1">Statut</label>
                <select name="status" class="px-3 py-2 border border-neutral-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                    <option value="">Tous</option>
                    <option value="pending"     {{ request('status') === 'pending' ? 'selected' : '' }}>En attente</option>
                    <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>En cours</option>
                    <option value="published"   {{ request('status') === 'published' ? 'selected' : '' }}>Publié</option>
                    <option value="cancelled"   {{ request('status') === 'cancelled' ? 'selected' : '' }}>Annulé</option>
                    <option value="refunded"    {{ request('status') === 'refunded' ? 'selected' : '' }}>Remboursé</option>
                </select>
            </div>
            <x-button variant="primary" type="submit" size="sm">Filtrer</x-button>
            @if(request()->hasAny(['project_id', 'status']))
                <x-button variant="secondary" size="sm" href="{{ route('orders.index') }}">Réinitialiser</x-button>
            @endif
        </form>
    </div>

    @if($orders->count() > 0)
        <div class="bg-white rounded-lg border border-neutral-200 overflow-hidden">
            <div class="overflow-x-auto">
                <x-table>
                    <x-slot:header>
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase">Site</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase">URL cible</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase">Plateforme</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase">Prix</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase">Statut</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase">Commandé le</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-neutral-500 uppercase w-24">Actions</th>
                        </tr>
                    </x-slot:header>
                    <x-slot:body>
                        @foreach($orders as $order)
                            <tr class="hover:bg-neutral-50">
                                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-neutral-900">
                                    {{ $order->project->name }}
                                </td>
                                <td class="px-4 py-4 max-w-xs">
                                    <a href="{{ $order->target_url }}" target="_blank" class="text-sm text-brand-500 hover:text-brand-600 truncate block">
                                        {{ Str::limit($order->target_url, 40) }}
                                    </a>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-neutral-600">
                                    {{ $order->platform?->name ?? '–' }}
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-neutral-900">
                                    @if($order->price)
                                        {{ number_format($order->price, 2) }} {{ $order->currency }}
                                    @else
                                        <span class="text-neutral-400">–</span>
                                    @endif
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <x-badge variant="{{ $order->status_badge }}">
                                        {{ $order->status_label }}
                                    </x-badge>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-neutral-500">
                                    {{ $order->ordered_at?->format('d/m/Y') ?? '–' }}
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <a href="{{ route('orders.show', $order) }}" class="inline-flex items-center justify-center w-8 h-8 rounded hover:bg-neutral-100 text-neutral-600 hover:text-brand-600 transition-colors" title="Voir">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </a>
                                        <a href="{{ route('orders.edit', $order) }}" class="inline-flex items-center justify-center w-8 h-8 rounded hover:bg-neutral-100 text-neutral-600 hover:text-brand-600 transition-colors" title="Modifier">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </a>
                                        <form action="{{ route('orders.destroy', $order) }}" method="POST" class="inline-block" onsubmit="return confirm('Supprimer cette commande ?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex items-center justify-center w-8 h-8 rounded hover:bg-red-50 text-neutral-600 hover:text-red-600 transition-colors" title="Supprimer">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </x-slot:body>
                </x-table>
            </div>
        </div>

        @if($orders->hasPages())
            <div class="mt-6">
                {{ $orders->links() }}
            </div>
        @endif
    @else
        <div class="bg-white p-12 rounded-lg border border-neutral-200 text-center">
            <span class="text-6xl mb-4 block">📋</span>
            <h3 class="text-lg font-semibold text-neutral-900 mb-2">Aucune commande</h3>
            <p class="text-neutral-500 mb-6">Commencez par créer une commande de backlink.</p>
            <x-button variant="primary" href="{{ route('orders.create') }}">Créer une commande</x-button>
        </div>
    @endif
@endsection

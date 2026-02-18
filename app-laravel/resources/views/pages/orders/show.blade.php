@extends('layouts.app')

@section('title', 'Commande #{{ $order->id }} - Link Tracker')

@section('breadcrumb')
    <a href="{{ route('orders.index') }}" class="text-brand-500 hover:text-brand-600">Commandes</a>
    <span class="mx-2 text-neutral-400">/</span>
    <span class="text-neutral-900 font-medium">Commande #{{ $order->id }}</span>
@endsection

@section('content')
    @if(session('success'))
        <x-alert variant="success" class="mb-6">{{ session('success') }}</x-alert>
    @endif

    <x-page-header title="Commande #{{ $order->id }}" subtitle="{{ $order->project->name }}">
        <x-slot:actions>
            <x-badge variant="{{ $order->status_badge }}" class="text-sm px-3 py-1">
                {{ $order->status_label }}
            </x-badge>
            <x-button variant="secondary" href="{{ route('orders.edit', $order) }}">Modifier</x-button>
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Détails principaux --}}
        <div class="lg:col-span-2 space-y-4">
            <div class="bg-white rounded-lg border border-neutral-200 p-6">
                <h3 class="text-sm font-semibold text-neutral-900 mb-4">Informations du lien</h3>

                <dl class="space-y-3">
                    <div class="flex justify-between text-sm">
                        <dt class="text-neutral-500">URL cible</dt>
                        <dd>
                            <a href="{{ $order->target_url }}" target="_blank" class="text-brand-500 hover:text-brand-600 truncate max-w-xs block text-right">
                                {{ $order->target_url }}
                            </a>
                        </dd>
                    </div>
                    @if($order->source_url)
                        <div class="flex justify-between text-sm">
                            <dt class="text-neutral-500">URL source</dt>
                            <dd>
                                <a href="{{ $order->source_url }}" target="_blank" class="text-brand-500 hover:text-brand-600">
                                    {{ Str::limit($order->source_url, 50) }}
                                </a>
                            </dd>
                        </div>
                    @endif
                    @if($order->anchor_text)
                        <div class="flex justify-between text-sm">
                            <dt class="text-neutral-500">Ancre</dt>
                            <dd class="font-medium text-neutral-900">{{ $order->anchor_text }}</dd>
                        </div>
                    @endif
                    <div class="flex justify-between text-sm">
                        <dt class="text-neutral-500">Niveau</dt>
                        <dd>
                            <x-badge variant="{{ $order->tier_level === 'tier1' ? 'neutral' : 'warning' }}">
                                {{ $order->tier_level === 'tier1' ? 'Tier 1' : 'Tier 2' }}
                            </x-badge>
                        </dd>
                    </div>
                    <div class="flex justify-between text-sm">
                        <dt class="text-neutral-500">Type</dt>
                        <dd>
                            <x-badge variant="{{ $order->spot_type === 'internal' ? 'success' : 'neutral' }}">
                                {{ $order->spot_type === 'internal' ? 'Interne' : 'Externe' }}
                            </x-badge>
                        </dd>
                    </div>
                </dl>
            </div>

            @if($order->notes)
                <div class="bg-white rounded-lg border border-neutral-200 p-6">
                    <h3 class="text-sm font-semibold text-neutral-900 mb-3">Notes</h3>
                    <p class="text-sm text-neutral-600 whitespace-pre-wrap">{{ $order->notes }}</p>
                </div>
            @endif

            {{-- Changer le statut --}}
            <div class="bg-white rounded-lg border border-neutral-200 p-6">
                <h3 class="text-sm font-semibold text-neutral-900 mb-4">Changer le statut</h3>
                <form action="{{ route('orders.status', $order) }}" method="POST" class="flex gap-3 flex-wrap">
                    @csrf
                    @method('PATCH')
                    <select name="status" class="px-3 py-2 border border-neutral-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                        @foreach(['pending' => 'En attente', 'in_progress' => 'En cours', 'published' => 'Publié', 'cancelled' => 'Annulé', 'refunded' => 'Remboursé'] as $val => $label)
                            <option value="{{ $val }}" {{ $order->status === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-button variant="primary" type="submit" size="sm">Mettre à jour</x-button>
                </form>
            </div>

            {{-- Timeline historique statuts --}}
            @if($order->statusLogs->isNotEmpty())
            <div class="bg-white rounded-lg border border-neutral-200 p-6">
                <h3 class="text-sm font-semibold text-neutral-900 mb-4">Historique des statuts</h3>
                <ol class="relative border-l border-neutral-200 ml-2">
                    @foreach($order->statusLogs as $log)
                    <li class="mb-6 ml-6 last:mb-0">
                        <span class="absolute flex items-center justify-center w-6 h-6 rounded-full -left-3 ring-4 ring-white
                            @if($log->new_status === 'published') bg-success-100 text-success-700
                            @elseif($log->new_status === 'cancelled' || $log->new_status === 'refunded') bg-danger-100 text-danger-700
                            @elseif($log->new_status === 'in_progress') bg-brand-100 text-brand-700
                            @else bg-neutral-100 text-neutral-600
                            @endif
                        ">
                            <span class="text-xs">
                                @if($log->new_status === 'published') ✓
                                @elseif($log->new_status === 'cancelled') ✕
                                @elseif($log->new_status === 'in_progress') →
                                @elseif($log->new_status === 'refunded') ↩
                                @else ○
                                @endif
                            </span>
                        </span>
                        <div class="pl-2">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="text-sm font-medium text-neutral-900">{{ $log->new_status_label }}</span>
                                @if($log->old_status)
                                    <span class="text-xs text-neutral-400">← {{ $log->old_status_label }}</span>
                                @endif
                            </div>
                            <time class="text-xs text-neutral-500">
                                {{ $log->changed_at->format('d/m/Y à H:i') }}
                            </time>
                            @if($log->notes)
                                <p class="text-xs text-neutral-600 mt-1">{{ $log->notes }}</p>
                            @endif
                        </div>
                    </li>
                    @endforeach
                </ol>
            </div>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="space-y-4">
            <div class="bg-white rounded-lg border border-neutral-200 p-5">
                <h3 class="text-xs font-semibold text-neutral-500 uppercase mb-3">Informations</h3>
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-xs text-neutral-500">Site</dt>
                        <dd class="font-medium text-neutral-900">{{ $order->project->name }}</dd>
                    </div>
                    @if($order->platform)
                        <div>
                            <dt class="text-xs text-neutral-500">Plateforme</dt>
                            <dd class="font-medium text-neutral-900">{{ $order->platform->name }}</dd>
                        </div>
                    @endif
                    @if($order->price)
                        <div>
                            <dt class="text-xs text-neutral-500">Prix</dt>
                            <dd class="font-medium text-neutral-900">{{ number_format($order->price, 2) }} {{ $order->currency }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-neutral-500">Facture payée</dt>
                            <dd>
                                <x-badge variant="{{ $order->invoice_paid ? 'success' : 'neutral' }}">
                                    {{ $order->invoice_paid ? 'Oui' : 'Non' }}
                                </x-badge>
                            </dd>
                        </div>
                    @endif
                    @if($order->ordered_at)
                        <div>
                            <dt class="text-xs text-neutral-500">Commandé le</dt>
                            <dd class="text-neutral-700">{{ $order->ordered_at->format('d/m/Y') }}</dd>
                        </div>
                    @endif
                    @if($order->expected_at)
                        <div>
                            <dt class="text-xs text-neutral-500">Publication prévue</dt>
                            <dd class="text-neutral-700">{{ $order->expected_at->format('d/m/Y') }}</dd>
                        </div>
                    @endif
                    @if($order->published_at)
                        <div>
                            <dt class="text-xs text-neutral-500">Publié le</dt>
                            <dd class="text-green-600 font-medium">{{ $order->published_at->format('d/m/Y') }}</dd>
                        </div>
                    @endif
                </dl>
            </div>

            @if($order->contact_name || $order->contact_email)
                <div class="bg-white rounded-lg border border-neutral-200 p-5">
                    <h3 class="text-xs font-semibold text-neutral-500 uppercase mb-3">Contact</h3>
                    <dl class="space-y-2 text-sm">
                        @if($order->contact_name)
                            <div>
                                <dt class="text-xs text-neutral-500">Nom</dt>
                                <dd class="font-medium text-neutral-900">{{ $order->contact_name }}</dd>
                            </div>
                        @endif
                        @if($order->contact_email)
                            <div>
                                <dt class="text-xs text-neutral-500">Email</dt>
                                <dd><a href="mailto:{{ $order->contact_email }}" class="text-brand-500 hover:text-brand-600">{{ $order->contact_email }}</a></dd>
                            </div>
                        @endif
                    </dl>
                </div>
            @endif

            <form action="{{ route('orders.destroy', $order) }}" method="POST" onsubmit="return confirm('Supprimer cette commande ?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="w-full px-4 py-2 text-sm text-red-600 border border-red-200 rounded-lg hover:bg-red-50 transition-colors">
                    Supprimer la commande
                </button>
            </form>
        </div>
    </div>
@endsection

@extends('layouts.app')

@section('title', 'Alertes - Link Tracker')

@section('breadcrumb')
    <span class="text-neutral-900 font-medium">Alertes</span>
@endsection

@section('content')
    @if(session('success'))
        <x-alert variant="success" class="mb-6">{{ session('success') }}</x-alert>
    @endif

    <x-page-header title="Alertes" subtitle="Surveillez les changements de vos backlinks">
        <x-slot:actions>
            @if($stats['unread'] > 0)
                <form action="{{ route('alerts.mark-all-read') }}" method="POST" class="inline-block">
                    @csrf
                    @method('PATCH')
                    <x-button variant="secondary" type="submit">
                        Tout marquer comme lu
                    </x-button>
                </form>
            @endif
            <form action="{{ route('alerts.destroy-all-read') }}" method="POST" class="inline-block" onsubmit="return confirm('Supprimer toutes les alertes lues ?');">
                @csrf
                @method('DELETE')
                <x-button variant="danger" type="submit">
                    Supprimer les alertes lues
                </x-button>
            </form>
        </x-slot:actions>
    </x-page-header>

    {{-- Statistiques --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white p-4 rounded-lg border border-neutral-200">
            <div class="text-sm text-neutral-500 mb-1">Total</div>
            <div class="text-2xl font-bold text-neutral-900">{{ $stats['total'] }}</div>
        </div>
        <div class="bg-white p-4 rounded-lg border border-neutral-200">
            <div class="text-sm text-neutral-500 mb-1">Non lues</div>
            <div class="text-2xl font-bold text-brand-600">{{ $stats['unread'] }}</div>
        </div>
        <div class="bg-white p-4 rounded-lg border border-neutral-200">
            <div class="text-sm text-neutral-500 mb-1">Critiques</div>
            <div class="text-2xl font-bold text-danger-600">{{ $stats['critical'] }}</div>
        </div>
        <div class="bg-white p-4 rounded-lg border border-neutral-200">
            <div class="text-sm text-neutral-500 mb-1">Aujourd'hui</div>
            <div class="text-2xl font-bold text-neutral-900">{{ $stats['today'] }}</div>
        </div>
    </div>

    {{-- Filtres --}}
    <div class="bg-white rounded-lg border border-neutral-200 p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-neutral-900">
                Filtres
                @if($activeFiltersCount > 0)
                    <x-badge variant="brand" class="ml-2">{{ $activeFiltersCount }} actif(s)</x-badge>
                @endif
            </h3>
            @if(request()->hasAny(['type', 'severity', 'is_read', 'days']))
                <x-button variant="secondary" size="sm" href="{{ route('alerts.index') }}">
                    Réinitialiser tous les filtres
                </x-button>
            @endif
        </div>

        <form method="GET" action="{{ route('alerts.index') }}" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                {{-- Type Filter --}}
                <div>
                    <label for="type" class="block text-sm font-medium text-neutral-700 mb-1">Type d'alerte</label>
                    <select
                        id="type"
                        name="type"
                        class="block w-full px-4 py-2 border border-neutral-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500"
                    >
                        <option value="">Tous</option>
                        <option value="backlink_lost" {{ request('type') === 'backlink_lost' ? 'selected' : '' }}>Backlink perdu</option>
                        <option value="backlink_changed" {{ request('type') === 'backlink_changed' ? 'selected' : '' }}>Backlink modifié</option>
                        <option value="backlink_recovered" {{ request('type') === 'backlink_recovered' ? 'selected' : '' }}>Backlink récupéré</option>
                    </select>
                </div>

                {{-- Severity Filter --}}
                <div>
                    <label for="severity" class="block text-sm font-medium text-neutral-700 mb-1">Sévérité</label>
                    <select
                        id="severity"
                        name="severity"
                        class="block w-full px-4 py-2 border border-neutral-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500"
                    >
                        <option value="">Tous</option>
                        <option value="critical" {{ request('severity') === 'critical' ? 'selected' : '' }}>Critique</option>
                        <option value="high" {{ request('severity') === 'high' ? 'selected' : '' }}>Élevé</option>
                        <option value="medium" {{ request('severity') === 'medium' ? 'selected' : '' }}>Moyen</option>
                        <option value="low" {{ request('severity') === 'low' ? 'selected' : '' }}>Faible</option>
                    </select>
                </div>

                {{-- Read Status Filter --}}
                <div>
                    <label for="is_read" class="block text-sm font-medium text-neutral-700 mb-1">Statut</label>
                    <select
                        id="is_read"
                        name="is_read"
                        class="block w-full px-4 py-2 border border-neutral-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500"
                    >
                        <option value="">Tous</option>
                        <option value="unread" {{ request('is_read') === 'unread' ? 'selected' : '' }}>Non lues</option>
                        <option value="read" {{ request('is_read') === 'read' ? 'selected' : '' }}>Lues</option>
                    </select>
                </div>

                {{-- Period Filter --}}
                <div>
                    <label for="days" class="block text-sm font-medium text-neutral-700 mb-1">Période</label>
                    <select
                        id="days"
                        name="days"
                        class="block w-full px-4 py-2 border border-neutral-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500"
                    >
                        <option value="">Toute la période</option>
                        <option value="1" {{ request('days') == '1' ? 'selected' : '' }}>Dernières 24h</option>
                        <option value="7" {{ request('days') == '7' ? 'selected' : '' }}>7 derniers jours</option>
                        <option value="30" {{ request('days') == '30' ? 'selected' : '' }}>30 derniers jours</option>
                        <option value="90" {{ request('days') == '90' ? 'selected' : '' }}>90 derniers jours</option>
                    </select>
                </div>
            </div>

            <div class="flex justify-end">
                <x-button variant="primary" type="submit">
                    Appliquer les filtres
                </x-button>
            </div>
        </form>
    </div>

    {{-- Résultats --}}
    <div class="mb-4">
        <p class="text-sm text-neutral-600">
            <span class="font-semibold text-neutral-900">{{ $alerts->total() }}</span> alerte(s) trouvée(s)
            @if($activeFiltersCount > 0)
                <span class="text-neutral-500">({{ $activeFiltersCount }} filtre(s) actif(s))</span>
            @endif
        </p>
    </div>

    @if($alerts->count() > 0)
        <div class="bg-white rounded-lg border border-neutral-200 overflow-hidden">
            <div class="overflow-x-auto">
                <x-table>
                    <x-slot:header>
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase w-12"></th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase">Alerte</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase">Type</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase">Sévérité</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase">Site</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase">Date</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-neutral-500 uppercase w-24">Actions</th>
                        </tr>
                    </x-slot:header>
                    <x-slot:body>
                        @foreach($alerts as $alert)
                            <tr class="hover:bg-neutral-50 {{ $alert->is_read ? 'opacity-60' : '' }}">
                                <td class="px-4 py-4 text-center">
                                    <span class="text-2xl">{{ $alert->type_icon }}</span>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-medium text-neutral-900">{{ $alert->title }}</span>
                                        @if(!$alert->is_read)
                                            <x-badge variant="brand">Nouveau</x-badge>
                                        @endif
                                    </div>
                                    <a href="{{ route('backlinks.show', $alert->backlink) }}" class="text-xs text-brand-600 hover:text-brand-700 hover:underline">
                                        Voir le backlink →
                                    </a>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <x-badge variant="{{ $alert->type_badge_color }}">
                                        {{ $alert->type_label }}
                                    </x-badge>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <x-badge variant="{{ $alert->severity_badge_color }}">
                                        {{ ucfirst($alert->severity) }}
                                    </x-badge>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-neutral-900">
                                    {{ $alert->backlink->project?->name ?? 'N/A' }}
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-neutral-600">
                                    {{ $alert->created_at->diffForHumans() }}
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        @if(!$alert->is_read)
                                            <form action="{{ route('alerts.mark-read', $alert) }}" method="POST" class="inline-block">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="inline-flex items-center justify-center w-8 h-8 rounded hover:bg-brand-100 text-brand-600 hover:text-brand-700 transition-colors" title="Marquer comme lu">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                    </svg>
                                                </button>
                                            </form>
                                        @endif
                                        <form action="{{ route('alerts.destroy', $alert) }}" method="POST" class="inline-block" onsubmit="return confirm('Supprimer cette alerte ?');">
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

        {{-- Pagination --}}
        @if($alerts->hasPages())
            <div class="mt-6">
                {{ $alerts->links() }}
            </div>
        @endif
    @else
        <div class="bg-white p-12 rounded-lg border border-neutral-200 text-center">
            <span class="text-6xl mb-4 block">🔔</span>
            <h3 class="text-lg font-semibold text-neutral-900 mb-2">Aucune alerte</h3>
            <p class="text-neutral-500">Vous n'avez aucune alerte pour le moment.</p>
        </div>
    @endif
@endsection

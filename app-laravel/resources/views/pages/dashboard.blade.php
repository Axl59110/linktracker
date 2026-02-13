@extends('layouts.app')

@section('title', 'Dashboard - Link Tracker')

@section('breadcrumb')
    <span class="text-neutral-900 font-medium">Dashboard</span>
@endsection

@section('content')
    {{-- Page Header --}}
    <x-page-header title="Dashboard" subtitle="Vue d'ensemble de vos backlinks et projets">
        <x-slot:actions>
            <x-button variant="primary" href="{{ url('/projects/create') }}">
                + Nouveau projet
            </x-button>
        </x-slot:actions>
    </x-page-header>

    {{-- Stats Cards Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        {{-- Active Backlinks --}}
        <x-stats-card
            label="Backlinks actifs"
            :value="$activeBacklinks ?? 0"
            change=""
            icon="🔗"
        />

        {{-- Lost Backlinks --}}
        <x-stats-card
            label="Backlinks perdus"
            :value="$lostBacklinks ?? 0"
            change=""
            icon="⚠️"
        />

        {{-- Changed Backlinks --}}
        <x-stats-card
            label="Backlinks modifiés"
            :value="$changedBacklinks ?? 0"
            change=""
            icon="🔄"
        />

        {{-- Total Projects --}}
        <x-stats-card
            label="Projets"
            :value="$totalProjects ?? 0"
            change=""
            icon="📁"
        />
    </div>

    {{-- Content Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Recent Alerts Section --}}
        <div class="bg-white p-6 rounded-lg border border-neutral-200">
            <h2 class="text-lg font-semibold text-neutral-900 mb-4">Alertes récentes</h2>

            @if(count($recentAlerts ?? []) > 0)
                {{-- TODO: Afficher la liste des alertes récentes --}}
                <div class="space-y-3">
                    @foreach($recentAlerts as $alert)
                        <div class="flex items-start space-x-3 p-3 bg-neutral-50 rounded-lg">
                            <span class="text-2xl">{{ $alert->icon ?? '🔔' }}</span>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-neutral-900">{{ $alert->title ?? 'Alerte' }}</p>
                                <p class="text-xs text-neutral-500">{{ $alert->created_at?->diffForHumans() ?? 'Date inconnue' }}</p>
                            </div>
                            <x-badge variant="{{ $alert->severity ?? 'neutral' }}">
                                {{ ucfirst($alert->severity ?? 'info') }}
                            </x-badge>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4 text-center">
                    <x-button variant="secondary" size="sm" href="{{ url('/alerts') }}">
                        Voir toutes les alertes
                    </x-button>
                </div>
            @else
                {{-- Empty State --}}
                <div class="text-center py-8">
                    <span class="text-4xl mb-2 block">🔕</span>
                    <p class="text-sm text-neutral-500">Aucune alerte récente</p>
                </div>
            @endif
        </div>

        {{-- Recent Projects Section --}}
        <div class="bg-white p-6 rounded-lg border border-neutral-200">
            <h2 class="text-lg font-semibold text-neutral-900 mb-4">Projets récents</h2>

            @if(count($recentProjects ?? []) > 0)
                {{-- TODO: Afficher la liste des projets récents --}}
                <div class="space-y-3">
                    @foreach($recentProjects as $project)
                        <div class="flex items-center justify-between p-3 bg-neutral-50 rounded-lg hover:bg-neutral-100 transition-colors">
                            <div>
                                <p class="text-sm font-medium text-neutral-900">{{ $project->name }}</p>
                                <p class="text-xs text-neutral-500">{{ $project->url }}</p>
                            </div>
                            <x-badge variant="success">
                                {{ $project->backlinks_count ?? 0 }} backlinks
                            </x-badge>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4 text-center">
                    <x-button variant="secondary" size="sm" href="{{ url('/projects') }}">
                        Voir tous les projets
                    </x-button>
                </div>
            @else
                {{-- Empty State --}}
                <div class="text-center py-8">
                    <span class="text-4xl mb-2 block">📂</span>
                    <p class="text-sm text-neutral-500 mb-4">Aucun projet configuré</p>
                    <x-button variant="primary" size="sm" href="{{ url('/projects/create') }}">
                        Créer votre premier projet
                    </x-button>
                </div>
            @endif
        </div>
    </div>

    {{-- Recent Backlinks Section --}}
    @if(count($recentBacklinks ?? []) > 0)
    <div class="mt-6 bg-white p-6 rounded-lg border border-neutral-200">
        <h2 class="text-lg font-semibold text-neutral-900 mb-4">Backlinks récents</h2>

        <x-table>
            <x-slot:header>
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase">Projet</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase">URL Source</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase">Statut</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase">Ajouté</th>
                </tr>
            </x-slot:header>
            <x-slot:body>
                @foreach($recentBacklinks as $backlink)
                    <tr class="hover:bg-neutral-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-neutral-900">
                            {{ $backlink->project?->name ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-4">
                            <a href="{{ $backlink->source_url }}" target="_blank" class="text-sm text-brand-500 hover:text-brand-600">
                                {{ Str::limit($backlink->source_url, 50) }}
                            </a>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <x-badge variant="{{ $backlink->status === 'active' ? 'success' : ($backlink->status === 'lost' ? 'danger' : 'neutral') }}">
                                {{ ucfirst($backlink->status) }}
                            </x-badge>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-neutral-500">
                            {{ $backlink->created_at->diffForHumans() }}
                        </td>
                    </tr>
                @endforeach
            </x-slot:body>
        </x-table>

        <div class="mt-4 text-center">
            <x-button variant="secondary" size="sm" href="{{ route('backlinks.index') }}">
                Voir tous les backlinks
            </x-button>
        </div>
    </div>
    @endif

    {{-- Quick Actions (Optional) --}}
    <div class="mt-8 bg-brand-50 p-6 rounded-lg border border-brand-100">
        <div class="flex items-start justify-between">
            <div>
                <h3 class="text-base font-semibold text-neutral-900 mb-1">Prêt à démarrer ?</h3>
                <p class="text-sm text-neutral-600 mb-4">Créez votre premier projet pour commencer à suivre vos backlinks.</p>
            </div>
        </div>
        <div class="flex items-center space-x-3">
            <x-button variant="primary" href="{{ url('/projects/create') }}">
                Créer un projet
            </x-button>
            <x-button variant="secondary" href="{{ url('/backlinks') }}">
                Voir les backlinks
            </x-button>
        </div>
    </div>
@endsection

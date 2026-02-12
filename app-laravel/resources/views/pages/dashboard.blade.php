{{--
    Dashboard Page

    Vue d'ensemble de l'application avec stats et liens rapides.

    TODO: Cette page sera connectée à un DashboardController dans phase ultérieure
    TODO: Remplacer les placeholders par données réelles
--}}

@extends('layouts.app')

@section('title', 'Dashboard - Link Tracker')

@section('breadcrumb')
    <span class="text-neutral-900 font-medium">Dashboard</span>
@endsection

@section('content')
    {{-- Page Header --}}
    <x-page-header
        title="Dashboard"
        subtitle="Vue d'ensemble de vos backlinks et projets">
        <x-slot:actions>
            <x-button variant="primary" href="/projects/create">
                + Nouveau projet
            </x-button>
        </x-slot:actions>
    </x-page-header>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        {{-- TODO: Remplacer par données réelles depuis DashboardController --}}
        @php
            // PLACEHOLDERS - Seront remplacés par des données réelles
            $activeBacklinks = 0;  // Backlink::where('status', 'active')->count()
            $lostBacklinks = 0;    // Backlink::where('status', 'lost')->count()
            $totalProjects = 0;    // Project::count()
        @endphp

        <x-stats-card
            label="Backlinks actifs"
            :value="$activeBacklinks"
            change="+12 ce mois"
            icon="✅"
        />

        <x-stats-card
            label="Backlinks perdus"
            :value="$lostBacklinks"
            change="-2 vs mois dernier"
            icon="❌"
        />

        <x-stats-card
            label="Projets"
            :value="$totalProjects"
            icon="📁"
        />
    </div>

    {{-- Recent Alerts Section --}}
    {{-- TODO: Cette section sera implémentée dans EPIC-004 (Alertes) --}}
    <div class="bg-white border border-neutral-200 rounded-lg p-6 mb-8">
        <h2 class="text-lg font-semibold text-neutral-900 mb-4">Alertes récentes</h2>

        {{-- Placeholder: Aucune alerte --}}
        <div class="text-center py-8 text-neutral-500">
            <p class="text-sm">Aucune alerte récente</p>
            <p class="text-xs mt-1">Les alertes apparaîtront ici quand des changements seront détectés</p>
        </div>

        {{-- TODO: Remplacer par liste réelle d'alertes quand EPIC-004 sera implémenté --}}
        {{--
        <div class="space-y-3">
            @foreach($recentAlerts as $alert)
                <div class="flex items-start space-x-3 p-3 hover:bg-neutral-50 rounded-lg">
                    <div class="flex-shrink-0 w-8 h-8 rounded-full bg-danger-50 flex items-center justify-center">
                        <span class="text-danger-600 text-sm">🔔</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-neutral-900">{{ $alert->title }}</p>
                        <p class="text-xs text-neutral-500 mt-1">{{ $alert->created_at->diffForHumans() }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        --}}
    </div>

    {{-- Recent Projects Section --}}
    {{-- TODO: Récupérer les 3 derniers projets depuis database --}}
    <div class="bg-white border border-neutral-200 rounded-lg p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-neutral-900">Projets récents</h2>
            <a href="/projects" class="text-sm text-brand-500 hover:text-brand-600 font-medium">
                Voir tous →
            </a>
        </div>

        {{-- Placeholder: Aucun projet --}}
        <div class="text-center py-8 text-neutral-500">
            <p class="text-sm">Aucun projet configuré</p>
            <p class="text-xs mt-1">Créez votre premier projet pour commencer</p>
            <x-button variant="primary" href="/projects/create" class="mt-4">
                + Créer un projet
            </x-button>
        </div>

        {{-- TODO: Remplacer par liste réelle de projets --}}
        {{--
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @foreach($recentProjects as $project)
                <a href="/projects/{{ $project->id }}" class="block p-4 border border-neutral-200 rounded-lg hover:border-neutral-300 transition-colors">
                    <div class="flex items-start justify-between mb-2">
                        <h3 class="font-medium text-neutral-900">{{ $project->name }}</h3>
                        <x-badge variant="{{ $project->status === 'active' ? 'success' : 'neutral' }}">
                            {{ ucfirst($project->status) }}
                        </x-badge>
                    </div>
                    <p class="text-sm text-neutral-500 truncate">{{ $project->url }}</p>
                    <p class="text-xs text-neutral-400 mt-2">
                        {{ $project->backlinks_count ?? 0 }} backlinks
                    </p>
                </a>
            @endforeach
        </div>
        --}}
    </div>
@endsection

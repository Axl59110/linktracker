@extends('layouts.app')

@section('title', 'Dashboard — Link Tracker')

@section('breadcrumb')
    <span class="text-neutral-900 font-semibold">Dashboard</span>
@endsection

@section('content')

{{-- ═══════════════════════════════════════════════════════════
     KPI STRIP — données critiques au premier coup d'œil
     ═══════════════════════════════════════════════════════════ --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

    {{-- Backlinks actifs --}}
    <div class="bg-white rounded-xl border border-neutral-200 p-5 relative overflow-hidden group hover:border-emerald-200 hover:shadow-sm transition-all duration-200">
        <div class="absolute inset-0 bg-gradient-to-br from-emerald-50/60 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-200"></div>
        <div class="relative">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold uppercase tracking-widest text-neutral-400">Actifs</span>
                <span class="w-2 h-2 rounded-full bg-emerald-400 shadow-[0_0_6px_rgba(52,211,153,0.6)]"></span>
            </div>
            <p class="text-4xl font-black text-neutral-900 tabular-nums leading-none">{{ $activeBacklinks ?? 0 }}</p>
            <p class="text-xs text-neutral-400 mt-2">backlinks en ligne</p>
        </div>
    </div>

    {{-- Backlinks perdus --}}
    <div class="bg-white rounded-xl border border-neutral-200 p-5 relative overflow-hidden group hover:border-red-200 hover:shadow-sm transition-all duration-200">
        <div class="absolute inset-0 bg-gradient-to-br from-red-50/60 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-200"></div>
        <div class="relative">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold uppercase tracking-widest text-neutral-400">Perdus</span>
                @if(($lostBacklinks ?? 0) > 0)
                    <span class="w-2 h-2 rounded-full bg-red-400 shadow-[0_0_6px_rgba(248,113,113,0.6)]"></span>
                @else
                    <span class="w-2 h-2 rounded-full bg-neutral-200"></span>
                @endif
            </div>
            <p class="text-4xl font-black tabular-nums leading-none {{ ($lostBacklinks ?? 0) > 0 ? 'text-red-500' : 'text-neutral-300' }}">{{ $lostBacklinks ?? 0 }}</p>
            <p class="text-xs text-neutral-400 mt-2">introuvables</p>
        </div>
    </div>

    {{-- Backlinks modifiés --}}
    <div class="bg-white rounded-xl border border-neutral-200 p-5 relative overflow-hidden group hover:border-amber-200 hover:shadow-sm transition-all duration-200">
        <div class="absolute inset-0 bg-gradient-to-br from-amber-50/60 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-200"></div>
        <div class="relative">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold uppercase tracking-widest text-neutral-400">Modifiés</span>
                @if(($changedBacklinks ?? 0) > 0)
                    <span class="w-2 h-2 rounded-full bg-amber-400 shadow-[0_0_6px_rgba(251,191,36,0.6)]"></span>
                @else
                    <span class="w-2 h-2 rounded-full bg-neutral-200"></span>
                @endif
            </div>
            <p class="text-4xl font-black tabular-nums leading-none {{ ($changedBacklinks ?? 0) > 0 ? 'text-amber-500' : 'text-neutral-300' }}">{{ $changedBacklinks ?? 0 }}</p>
            <p class="text-xs text-neutral-400 mt-2">attributs changés</p>
        </div>
    </div>

    {{-- Uptime + sites --}}
    <div class="bg-white rounded-xl border border-neutral-200 p-5 relative overflow-hidden group hover:border-brand-200 hover:shadow-sm transition-all duration-200">
        <div class="absolute inset-0 bg-gradient-to-br from-blue-50/60 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-200"></div>
        <div class="relative">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold uppercase tracking-widest text-neutral-400">Uptime</span>
                <span class="text-xs text-neutral-400 font-medium">{{ $totalProjects ?? 0 }} sites</span>
            </div>
            @if(!is_null($uptimeRate ?? null))
                <p class="text-4xl font-black tabular-nums leading-none {{ ($uptimeRate >= 90) ? 'text-emerald-500' : (($uptimeRate >= 70) ? 'text-amber-500' : 'text-red-500') }}">{{ $uptimeRate }}<span class="text-xl font-bold text-neutral-400">%</span></p>
                <p class="text-xs text-neutral-400 mt-2">{{ $totalChecks ?? 0 }} vérifs · 30j</p>
            @else
                <p class="text-4xl font-black tabular-nums leading-none text-neutral-200">—</p>
                <p class="text-xs text-neutral-400 mt-2">pas de données</p>
            @endif
        </div>
    </div>

</div>

{{-- ═══════════════════════════════════════════════════════════
     GRAPHIQUE PRINCIPAL — histogramme gains/pertes + courbe
     ═══════════════════════════════════════════════════════════ --}}
<div class="bg-white rounded-xl border border-neutral-200 mb-6 overflow-hidden"
     x-data="backlinkChart()">

    {{-- Header du graphique --}}
    <div class="flex items-center justify-between px-6 py-4 border-b border-neutral-100">
        <div class="flex items-center gap-4">
            <h2 class="text-sm font-bold text-neutral-900 uppercase tracking-wide">Évolution des backlinks</h2>
            <div class="hidden sm:flex items-center gap-3 text-xs text-neutral-400">
                <span class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-sm bg-emerald-400 inline-block"></span>Gains
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-sm bg-red-400 inline-block"></span>Pertes
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="w-5 h-0.5 bg-blue-400 inline-block"></span>Actifs
                </span>
            </div>
        </div>
        {{-- Sélecteur période --}}
        <div class="flex gap-1 bg-neutral-100 p-1 rounded-lg">
            @foreach([7 => '7j', 30 => '30j', 90 => '90j'] as $d => $label)
                <button @click="loadChart({{ $d }})"
                    :class="days === {{ $d }} ? 'bg-white text-neutral-900 shadow-sm font-semibold' : 'text-neutral-500 hover:text-neutral-700'"
                    class="px-3 py-1 text-xs rounded-md transition-all duration-150">
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </div>

    {{-- Zone graphique --}}
    <div class="px-6 py-4">
        {{-- Indicateur de charge --}}
        <div x-show="loading" class="flex items-center justify-center h-64">
            <div class="flex gap-1.5">
                <span class="w-1.5 h-6 bg-neutral-200 rounded-full animate-pulse" style="animation-delay:0ms"></span>
                <span class="w-1.5 h-10 bg-neutral-300 rounded-full animate-pulse" style="animation-delay:100ms"></span>
                <span class="w-1.5 h-8 bg-neutral-200 rounded-full animate-pulse" style="animation-delay:200ms"></span>
                <span class="w-1.5 h-12 bg-neutral-300 rounded-full animate-pulse" style="animation-delay:300ms"></span>
                <span class="w-1.5 h-6 bg-neutral-200 rounded-full animate-pulse" style="animation-delay:400ms"></span>
            </div>
        </div>
        <div x-show="!loading" class="relative h-64">
            <canvas id="backlinkChart"></canvas>
        </div>
    </div>

    {{-- Bande de stats sous le graphique --}}
    <div class="grid grid-cols-3 divide-x divide-neutral-100 border-t border-neutral-100">
        <div class="px-6 py-3">
            <p class="text-xs text-neutral-400 mb-0.5">Total backlinks</p>
            <p class="text-lg font-black text-neutral-900 tabular-nums">{{ ($activeBacklinks ?? 0) + ($lostBacklinks ?? 0) + ($changedBacklinks ?? 0) }}</p>
        </div>
        <div class="px-6 py-3">
            <p class="text-xs text-neutral-400 mb-0.5">Taux de succès</p>
            @php
                $total = ($activeBacklinks ?? 0) + ($lostBacklinks ?? 0) + ($changedBacklinks ?? 0);
                $successRate = $total > 0 ? round(($activeBacklinks ?? 0) / $total * 100) : 0;
            @endphp
            <p class="text-lg font-black tabular-nums {{ $successRate >= 80 ? 'text-emerald-600' : ($successRate >= 60 ? 'text-amber-500' : 'text-red-500') }}">{{ $successRate }}%</p>
        </div>
        <div class="px-6 py-3">
            <p class="text-xs text-neutral-400 mb-0.5">Sites actifs</p>
            <p class="text-lg font-black text-neutral-900 tabular-nums">{{ $totalProjects ?? 0 }}</p>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════
     ONGLETS — Alertes / Nouveaux Backlinks / Portfolio
     ═══════════════════════════════════════════════════════════ --}}
<div class="bg-white rounded-xl border border-neutral-200 overflow-hidden"
     x-data="{ tab: 'alerts' }">

    {{-- Barre d'onglets --}}
    <div class="flex border-b border-neutral-100">

        {{-- Onglet Alertes --}}
        <button @click="tab = 'alerts'"
                :class="tab === 'alerts' ? 'border-b-2 border-brand-500 text-brand-600 bg-brand-50/40' : 'text-neutral-400 hover:text-neutral-600 hover:bg-neutral-50'"
                class="flex items-center gap-2 px-5 py-3.5 text-sm font-semibold transition-all duration-150 relative">
            <svg style="width:16px;height:16px" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
            </svg>
            <span>Alertes récentes</span>
            @if(count($recentAlerts ?? []) > 0)
                <span class="inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 text-xs font-bold text-white bg-red-500 rounded-full">
                    {{ count($recentAlerts) }}
                </span>
            @endif
        </button>

        {{-- Onglet Nouveaux Backlinks --}}
        <button @click="tab = 'backlinks'"
                :class="tab === 'backlinks' ? 'border-b-2 border-brand-500 text-brand-600 bg-brand-50/40' : 'text-neutral-400 hover:text-neutral-600 hover:bg-neutral-50'"
                class="flex items-center gap-2 px-5 py-3.5 text-sm font-semibold transition-all duration-150">
            <svg style="width:16px;height:16px" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
            </svg>
            <span>Nouveaux backlinks</span>
            @if(count($recentBacklinks ?? []) > 0)
                <span class="inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 text-xs font-bold text-white bg-brand-500 rounded-full">
                    {{ count($recentBacklinks) }}
                </span>
            @endif
        </button>

        {{-- Onglet Portfolio --}}
        <button @click="tab = 'portfolio'"
                :class="tab === 'portfolio' ? 'border-b-2 border-brand-500 text-brand-600 bg-brand-50/40' : 'text-neutral-400 hover:text-neutral-600 hover:bg-neutral-50'"
                class="flex items-center gap-2 px-5 py-3.5 text-sm font-semibold transition-all duration-150">
            <svg style="width:16px;height:16px" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
            </svg>
            <span>Portfolio</span>
            @if(count($recentProjects ?? []) > 0)
                <span class="inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 text-xs font-bold text-neutral-500 bg-neutral-100 rounded-full">
                    {{ count($recentProjects) }}
                </span>
            @endif
        </button>

        {{-- Lien rapide à droite --}}
        <div class="ml-auto flex items-center px-5">
            <a x-show="tab === 'alerts'" href="{{ route('alerts.index') }}"
               class="text-xs text-brand-600 hover:text-brand-700 font-medium">Voir tout →</a>
            <a x-show="tab === 'backlinks'" href="{{ route('backlinks.index') }}"
               class="text-xs text-brand-600 hover:text-brand-700 font-medium">Voir tout →</a>
            <a x-show="tab === 'portfolio'" href="{{ url('/projects') }}"
               class="text-xs text-brand-600 hover:text-brand-700 font-medium">Voir tout →</a>
        </div>
    </div>

    {{-- ── Contenu : Alertes ── --}}
    <div x-show="tab === 'alerts'" x-cloak>
        @if(count($recentAlerts ?? []) > 0)
            <div class="divide-y divide-neutral-50">
                @foreach($recentAlerts as $alert)
                    @php
                        $colors = [
                            'critical' => 'text-red-600 bg-red-50 border-red-200',
                            'high'     => 'text-orange-600 bg-orange-50 border-orange-200',
                            'medium'   => 'text-amber-600 bg-amber-50 border-amber-200',
                            'low'      => 'text-neutral-500 bg-neutral-50 border-neutral-200',
                        ];
                        $dotColors = [
                            'critical' => 'bg-red-500',
                            'high'     => 'bg-orange-400',
                            'medium'   => 'bg-amber-400',
                            'low'      => 'bg-neutral-300',
                        ];
                        $color = $colors[$alert->severity] ?? $colors['low'];
                        $dot   = $dotColors[$alert->severity] ?? $dotColors['low'];
                    @endphp
                    <div class="flex items-center gap-3 px-5 py-3.5 hover:bg-neutral-50 transition-colors">
                        <span class="w-2 h-2 rounded-full flex-shrink-0 {{ $dot }} {{ $alert->severity === 'critical' ? 'shadow-[0_0_5px_rgba(239,68,68,0.7)]' : '' }}"></span>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-neutral-800 truncate">{{ $alert->title }}</p>
                            <p class="text-xs text-neutral-400 truncate">{{ $alert->backlink->project?->name ?? '—' }} · {{ $alert->created_at->diffForHumans() }}</p>
                        </div>
                        <span class="inline-flex items-center px-2 py-0.5 text-xs font-semibold border rounded-full flex-shrink-0 {{ $color }}">
                            {{ ucfirst($alert->severity) }}
                        </span>
                    </div>
                @endforeach
            </div>
        @else
            <div class="flex flex-col items-center justify-center py-14 text-center">
                <div class="w-10 h-10 rounded-full bg-emerald-50 flex items-center justify-center mb-3">
                    <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <p class="text-sm font-medium text-neutral-600">Tout est en ordre</p>
                <p class="text-xs text-neutral-400 mt-1">Aucune alerte récente</p>
            </div>
        @endif
    </div>

    {{-- ── Contenu : Nouveaux Backlinks ── --}}
    <div x-show="tab === 'backlinks'" x-cloak>
        @if(count($recentBacklinks ?? []) > 0)
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-neutral-50 border-b border-neutral-100">
                            <th class="px-5 py-2.5 text-left text-xs font-semibold text-neutral-400 uppercase tracking-wider">Site</th>
                            <th class="px-5 py-2.5 text-left text-xs font-semibold text-neutral-400 uppercase tracking-wider">URL Source</th>
                            <th class="px-5 py-2.5 text-left text-xs font-semibold text-neutral-400 uppercase tracking-wider">Statut</th>
                            <th class="px-5 py-2.5 text-left text-xs font-semibold text-neutral-400 uppercase tracking-wider">Ajouté</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-50">
                        @foreach($recentBacklinks as $backlink)
                            <tr class="hover:bg-neutral-50 transition-colors">
                                <td class="px-5 py-3 whitespace-nowrap text-sm font-semibold text-neutral-700">
                                    {{ $backlink->project?->name ?? '—' }}
                                </td>
                                <td class="px-5 py-3">
                                    <a href="{{ $backlink->source_url }}" target="_blank"
                                       class="text-brand-500 hover:text-brand-600 hover:underline font-mono text-xs">
                                        {{ Str::limit($backlink->source_url, 60) }}
                                    </a>
                                </td>
                                <td class="px-5 py-3 whitespace-nowrap">
                                    @php
                                        $statusMap = [
                                            'active'  => ['label' => 'Actif',   'class' => 'text-emerald-700 bg-emerald-50 border-emerald-200'],
                                            'lost'    => ['label' => 'Perdu',   'class' => 'text-red-700 bg-red-50 border-red-200'],
                                            'changed' => ['label' => 'Modifié', 'class' => 'text-amber-700 bg-amber-50 border-amber-200'],
                                        ];
                                        $s = $statusMap[$backlink->status] ?? ['label' => ucfirst($backlink->status), 'class' => 'text-neutral-600 bg-neutral-50 border-neutral-200'];
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-0.5 text-xs font-semibold border rounded-full {{ $s['class'] }}">
                                        {{ $s['label'] }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 whitespace-nowrap text-xs text-neutral-400">
                                    {{ $backlink->created_at->diffForHumans() }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="flex flex-col items-center justify-center py-14 text-center">
                <div class="w-10 h-10 rounded-full bg-brand-50 flex items-center justify-center mb-3">
                    <svg class="w-5 h-5 text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                    </svg>
                </div>
                <p class="text-sm font-medium text-neutral-600">Aucun backlink récent</p>
                <a href="{{ route('backlinks.create') }}" class="mt-3 text-xs text-brand-600 hover:text-brand-700 font-semibold">Ajouter un backlink →</a>
            </div>
        @endif
    </div>

    {{-- ── Contenu : Portfolio ── --}}
    <div x-show="tab === 'portfolio'" x-cloak>
        @if(count($recentProjects ?? []) > 0)
            <div class="divide-y divide-neutral-50">
                @foreach($recentProjects as $project)
                    <a href="{{ route('projects.show', $project) }}" class="flex items-center gap-3 px-5 py-3.5 hover:bg-neutral-50 transition-colors group">
                        <div class="w-8 h-8 rounded-lg bg-brand-50 flex items-center justify-center flex-shrink-0 group-hover:bg-brand-100 transition-colors">
                            <span class="text-xs font-bold text-brand-600">{{ strtoupper(substr($project->name, 0, 2)) }}</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-neutral-800 truncate group-hover:text-brand-600 transition-colors">{{ $project->name }}</p>
                            <p class="text-xs text-neutral-400 truncate">{{ $project->url }}</p>
                        </div>
                        <div class="text-right flex-shrink-0">
                            <p class="text-sm font-bold text-neutral-900 tabular-nums">{{ $project->backlinks_count ?? 0 }}</p>
                            <p class="text-xs text-neutral-400">liens</p>
                        </div>
                        <svg style="width:14px;height:14px" class="text-neutral-300 group-hover:text-brand-400 transition-colors flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                @endforeach
            </div>
            <div class="px-5 py-3 border-t border-neutral-100">
                <a href="{{ url('/projects/create') }}" class="text-xs text-brand-600 hover:text-brand-700 font-semibold">+ Ajouter un site</a>
            </div>
        @else
            <div class="flex flex-col items-center justify-center py-14 text-center">
                <div class="w-10 h-10 rounded-full bg-brand-50 flex items-center justify-center mb-3">
                    <svg class="w-5 h-5 text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                </div>
                <p class="text-sm font-medium text-neutral-600">Portfolio vide</p>
                <a href="{{ url('/projects/create') }}" class="mt-3 text-xs text-brand-600 hover:text-brand-700 font-semibold">Ajouter votre premier site →</a>
            </div>
        @endif
    </div>

</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
function backlinkChart() {
    return {
        days: 30,
        chart: null,
        loading: true,

        init() {
            this.loadChart(30);
        },

        async loadChart(days) {
            this.days = days;
            this.loading = true;

            try {
                const response = await fetch(`/api/dashboard/chart?days=${days}`);
                const data = await response.json();
                await this.$nextTick();
                this.renderChart(data);
            } catch (e) {
                console.error('Erreur chargement graphique:', e);
            } finally {
                this.loading = false;
            }
        },

        renderChart(data) {
            const ctx = document.getElementById('backlinkChart');
            if (!ctx) return;

            if (this.chart) {
                this.chart.destroy();
            }

            // Couleurs des barres : vert si delta > 0, rouge si delta < 0, neutre si 0
            const barColors = (data.delta || []).map(v =>
                v > 0 ? 'rgba(52, 211, 153, 0.75)'   // emerald
                      : v < 0 ? 'rgba(248, 113, 113, 0.75)'  // red
                               : 'rgba(200, 200, 200, 0.5)'
            );
            const barBorders = (data.delta || []).map(v =>
                v > 0 ? 'rgba(16, 185, 129, 1)'
                      : v < 0 ? 'rgba(239, 68, 68, 1)'
                               : 'rgba(180, 180, 180, 1)'
            );

            this.chart = new Chart(ctx, {
                data: {
                    labels: data.labels,
                    datasets: [
                        // ── Barres delta (gains verts / pertes rouges) ──
                        {
                            type: 'bar',
                            label: 'Δ Gain/Perte',
                            data: data.delta || [],
                            backgroundColor: barColors,
                            borderColor: barBorders,
                            borderWidth: 1,
                            borderRadius: 3,
                            borderSkipped: false,
                            yAxisID: 'yDelta',
                            order: 2,
                        },
                        // ── Courbe backlinks actifs ──
                        {
                            type: 'line',
                            label: 'Actifs',
                            data: data.active,
                            borderColor: 'rgba(59, 130, 246, 0.9)',
                            backgroundColor: 'rgba(59, 130, 246, 0.08)',
                            borderWidth: 2.5,
                            pointRadius: 0,
                            pointHoverRadius: 4,
                            pointHoverBackgroundColor: 'rgba(59, 130, 246, 1)',
                            tension: 0.35,
                            fill: true,
                            yAxisID: 'yActive',
                            order: 1,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: 'rgba(15, 23, 42, 0.92)',
                            titleColor: 'rgba(148, 163, 184, 1)',
                            bodyColor: '#fff',
                            borderColor: 'rgba(51, 65, 85, 0.5)',
                            borderWidth: 1,
                            padding: 10,
                            titleFont: { size: 11, weight: '600' },
                            bodyFont: { size: 12, weight: '700' },
                            callbacks: {
                                title: (items) => items[0].label,
                                label: (item) => {
                                    if (item.datasetIndex === 0) {
                                        const v = item.parsed.y;
                                        return ` Δ ${v > 0 ? '+' : ''}${v} backlinks`;
                                    }
                                    return ` ${item.parsed.y} actifs`;
                                },
                                labelColor: (item) => ({
                                    borderColor: 'transparent',
                                    backgroundColor: item.datasetIndex === 0
                                        ? (item.parsed.y >= 0 ? 'rgba(52,211,153,0.9)' : 'rgba(248,113,113,0.9)')
                                        : 'rgba(59,130,246,0.9)',
                                    borderRadius: 2,
                                }),
                            },
                        },
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            border: { display: false },
                            ticks: {
                                font: { size: 10 },
                                color: '#94a3b8',
                                maxTicksLimit: 10,
                            },
                        },
                        yActive: {
                            position: 'left',
                            beginAtZero: false,
                            grid: {
                                color: 'rgba(148, 163, 184, 0.1)',
                                drawBorder: false,
                            },
                            border: { display: false },
                            ticks: {
                                font: { size: 10 },
                                color: 'rgba(59, 130, 246, 0.7)',
                                precision: 0,
                                maxTicksLimit: 5,
                            },
                        },
                        yDelta: {
                            position: 'right',
                            grid: { display: false },
                            border: { display: false },
                            ticks: {
                                font: { size: 10 },
                                color: '#94a3b8',
                                precision: 0,
                                maxTicksLimit: 5,
                                callback: (v) => v > 0 ? `+${v}` : v,
                            },
                        },
                    },
                },
            });
        },
    };
}
</script>
@endpush

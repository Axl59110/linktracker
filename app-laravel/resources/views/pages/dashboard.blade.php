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
        <x-stats-card label="Backlinks actifs"   :value="$activeBacklinks ?? 0"  change="" icon="🔗" />
        <x-stats-card label="Backlinks perdus"   :value="$lostBacklinks ?? 0"    change="" icon="⚠️" />
        <x-stats-card label="Backlinks modifiés" :value="$changedBacklinks ?? 0" change="" icon="🔄" />
        <x-stats-card label="Projets"            :value="$totalProjects ?? 0"    change="" icon="📁" />
    </div>

    {{-- Graphiques STORY-029 / STORY-030 --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        {{-- Graphique évolution 30j --}}
        <div class="lg:col-span-2 bg-white p-6 rounded-lg border border-neutral-200" x-data="backlinkChart()">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-base font-semibold text-neutral-900">Évolution des backlinks</h2>
                <div class="flex gap-1">
                    @foreach([7 => '7j', 30 => '30j', 90 => '90j'] as $d => $label)
                        <button @click="loadChart({{ $d }})"
                            :class="days === {{ $d }} ? 'bg-brand-100 text-brand-700 font-medium' : 'text-neutral-500 hover:bg-neutral-100'"
                            class="px-3 py-1 text-xs rounded transition-colors">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>
            <div class="relative h-48">
                <canvas id="backlinkChart"></canvas>
            </div>
        </div>

        {{-- Graphique donut disponibilité (STORY-030) --}}
        <div class="bg-white p-6 rounded-lg border border-neutral-200">
            <h2 class="text-base font-semibold text-neutral-900 mb-4">Disponibilité globale</h2>

            @if(!is_null($uptimeRate ?? null))
                <div class="text-center mb-4">
                    <span class="text-4xl font-bold {{ ($uptimeRate ?? 0) >= 90 ? 'text-green-600' : (($uptimeRate ?? 0) >= 70 ? 'text-orange-500' : 'text-red-500') }}">
                        {{ $uptimeRate }}%
                    </span>
                    <p class="text-xs text-neutral-500 mt-1">sur 30 derniers jours</p>
                    <p class="text-xs text-neutral-400">{{ $totalChecks ?? 0 }} vérifications</p>
                </div>
                <div class="relative h-32">
                    <canvas id="uptimeChart"
                        data-active="{{ $activeBacklinks ?? 0 }}"
                        data-lost="{{ $lostBacklinks ?? 0 }}"
                        data-changed="{{ $changedBacklinks ?? 0 }}">
                    </canvas>
                </div>
                <div class="mt-3 space-y-1 text-xs">
                    <div class="flex justify-between"><span class="text-green-600">● Actifs</span><span>{{ $activeBacklinks ?? 0 }}</span></div>
                    <div class="flex justify-between"><span class="text-red-500">● Perdus</span><span>{{ $lostBacklinks ?? 0 }}</span></div>
                    <div class="flex justify-between"><span class="text-orange-400">● Modifiés</span><span>{{ $changedBacklinks ?? 0 }}</span></div>
                </div>
            @else
                <div class="text-center py-8 text-neutral-400">
                    <p class="text-sm">Aucune vérification effectuée</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Content Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Recent Alerts Section --}}
        <div class="bg-white p-6 rounded-lg border border-neutral-200">
            <h2 class="text-lg font-semibold text-neutral-900 mb-4">Alertes récentes</h2>

            @if(count($recentAlerts ?? []) > 0)
                <div class="space-y-3">
                    @foreach($recentAlerts as $alert)
                        <div class="flex items-start space-x-3 p-3 bg-neutral-50 rounded-lg hover:bg-neutral-100 transition-colors">
                            <span class="text-2xl">{{ $alert->type_icon }}</span>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-neutral-900 truncate">{{ $alert->title }}</p>
                                <p class="text-xs text-neutral-600 truncate">{{ $alert->backlink->project?->name ?? 'Projet inconnu' }}</p>
                                <p class="text-xs text-neutral-500">{{ $alert->created_at->diffForHumans() }}</p>
                            </div>
                            <x-badge variant="{{ $alert->severity_badge_color }}">
                                {{ ucfirst($alert->severity) }}
                            </x-badge>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4 text-center">
                    <x-button variant="secondary" size="sm" href="{{ route('alerts.index') }}">
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

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    function backlinkChart() {
        return {
            days: 30,
            chart: null,

            init() {
                this.loadChart(30);
            },

            async loadChart(days) {
                this.days = days;

                try {
                    const response = await fetch(`/api/dashboard/chart?days=${days}`);
                    const data = await response.json();
                    this.renderChart(data);
                } catch (e) {
                    console.error('Erreur chargement graphique:', e);
                }
            },

            renderChart(data) {
                const ctx = document.getElementById('backlinkChart');
                if (!ctx) return;

                if (this.chart) {
                    this.chart.destroy();
                }

                this.chart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: data.labels,
                        datasets: [
                            {
                                label: 'Actifs',
                                data: data.active,
                                borderColor: '#22c55e',
                                backgroundColor: 'rgba(34, 197, 94, 0.1)',
                                tension: 0.3,
                                fill: true,
                                pointRadius: 2,
                            },
                            {
                                label: 'Perdus',
                                data: data.lost,
                                borderColor: '#ef4444',
                                backgroundColor: 'rgba(239, 68, 68, 0.1)',
                                tension: 0.3,
                                fill: true,
                                pointRadius: 2,
                            },
                            {
                                label: 'Modifiés',
                                data: data.changed,
                                borderColor: '#f97316',
                                backgroundColor: 'rgba(249, 115, 22, 0.1)',
                                tension: 0.3,
                                fill: true,
                                pointRadius: 2,
                            },
                        ],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    font: { size: 11 },
                                    boxWidth: 12,
                                    padding: 12,
                                },
                            },
                        },
                        scales: {
                            x: {
                                grid: { display: false },
                                ticks: { font: { size: 10 } },
                            },
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    font: { size: 10 },
                                    stepSize: 1,
                                    precision: 0,
                                },
                            },
                        },
                    },
                });
            },
        };
    }

    // Graphique donut disponibilité (STORY-030)
    document.addEventListener('DOMContentLoaded', function () {
        const uptimeCanvas = document.getElementById('uptimeChart');
        if (!uptimeCanvas) return;

        const active  = parseInt(uptimeCanvas.dataset.active  || '0', 10);
        const lost    = parseInt(uptimeCanvas.dataset.lost    || '0', 10);
        const changed = parseInt(uptimeCanvas.dataset.changed || '0', 10);
        const total   = active + lost + changed;

        if (total === 0) return;

        new Chart(uptimeCanvas, {
            type: 'doughnut',
            data: {
                labels: ['Actifs', 'Perdus', 'Modifiés'],
                datasets: [{
                    data: [active, lost, changed],
                    backgroundColor: ['#22c55e', '#ef4444', '#f97316'],
                    borderWidth: 2,
                    borderColor: '#fff',
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function (ctx) {
                                const total2 = active + lost + changed;
                                const pct = total2 > 0 ? Math.round((ctx.parsed / total2) * 100) : 0;
                                return `${ctx.label}: ${ctx.parsed} (${pct}%)`;
                            },
                        },
                    },
                },
            },
        });
    });
</script>
@endpush

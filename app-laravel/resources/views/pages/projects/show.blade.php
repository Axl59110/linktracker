@extends('layouts.app')

@section('title', $project->name . ' - Link Tracker')

@section('breadcrumb')
    <a href="{{ route('projects.index') }}" class="text-neutral-500 hover:text-neutral-700">Portfolio</a>
    <span class="text-neutral-400 mx-2">/</span>
    <span class="text-neutral-900 font-medium">{{ $project->name }}</span>
@endsection

@section('content')
    {{-- Page Header --}}
    <x-page-header :title="$project->name" :subtitle="$project->url">
        <x-slot:actions>
            <x-button variant="secondary" href="{{ route('projects.report', $project) }}" target="_blank">
                📄 Rapport
            </x-button>
            <x-button variant="secondary" href="{{ route('projects.edit', $project) }}">
                Modifier
            </x-button>
            <x-button variant="primary" href="{{ url('/backlinks/create?project_id=' . $project->id) }}">
                + Ajouter un backlink
            </x-button>
        </x-slot:actions>
    </x-page-header>

    {{-- ═══════════════════════════════════════════════════════════
         SCORE DE SANTÉ + KPI CARDS
         ═══════════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-4 mb-6">

        {{-- Score de santé (grande card) --}}
        <div class="bg-white rounded-xl border border-neutral-200 p-5 flex flex-col items-center justify-center">
            @php
                $score = $stats['health_score'];
                $scoreColor = $score >= 75 ? 'text-emerald-600' : ($score >= 50 ? 'text-amber-500' : 'text-red-500');
                $scoreBg    = $score >= 75 ? 'bg-emerald-50' : ($score >= 50 ? 'bg-amber-50' : 'bg-red-50');
                $scoreBorder = $score >= 75 ? 'border-emerald-200' : ($score >= 50 ? 'border-amber-200' : 'border-red-200');
                $scoreLabel = $score >= 75 ? 'Bonne santé' : ($score >= 50 ? 'Attention' : 'Critique');
            @endphp
            <p class="text-xs font-semibold text-neutral-400 uppercase tracking-wide mb-2">Score de santé</p>
            <div class="relative w-24 h-24 mb-2">
                <svg class="w-24 h-24 -rotate-90" viewBox="0 0 36 36">
                    <circle cx="18" cy="18" r="15.9" fill="none" stroke="#f3f4f6" stroke-width="3"/>
                    <circle cx="18" cy="18" r="15.9" fill="none"
                        stroke="{{ $score >= 75 ? '#10b981' : ($score >= 50 ? '#f59e0b' : '#ef4444') }}"
                        stroke-width="3"
                        stroke-dasharray="{{ $score }}, 100"
                        stroke-linecap="round"/>
                </svg>
                <div class="absolute inset-0 flex items-center justify-center">
                    <span class="text-xl font-black {{ $scoreColor }}">{{ $score }}</span>
                </div>
            </div>
            <span class="inline-flex items-center px-2.5 py-0.5 text-xs font-semibold border rounded-full {{ $scoreBg }} {{ $scoreColor }} {{ $scoreBorder }}">
                {{ $scoreLabel }}
            </span>
        </div>

        {{-- KPI cards 3 colonnes --}}
        <div class="lg:col-span-3 grid grid-cols-2 md:grid-cols-3 gap-4">

            {{-- Total backlinks --}}
            <div class="bg-white rounded-xl border border-neutral-200 p-4">
                <p class="text-xs text-neutral-400 mb-1">Total backlinks</p>
                <p class="text-2xl font-black text-neutral-900 tabular-nums">{{ $stats['total'] }}</p>
                <div class="flex gap-2 mt-2 text-xs">
                    <span class="text-emerald-600 font-semibold">{{ $stats['active'] }} actifs</span>
                    @if($stats['lost'] > 0)
                        <span class="text-red-500">· {{ $stats['lost'] }} perdus</span>
                    @endif
                </div>
            </div>

            {{-- Qualité (actif + indexé + dofollow) --}}
            <div class="bg-white rounded-xl border border-neutral-200 p-4">
                <p class="text-xs text-neutral-400 mb-1">Liens de qualité</p>
                <p class="text-2xl font-black text-emerald-600 tabular-nums">{{ $stats['quality'] }}</p>
                <p class="text-xs text-neutral-400 mt-1">Actif + indexé + dofollow</p>
            </div>

            {{-- Non indexés --}}
            <div class="bg-white rounded-xl border border-neutral-200 p-4">
                <p class="text-xs text-neutral-400 mb-1">Non indexés</p>
                <p class="text-2xl font-black {{ $stats['not_indexed'] > 0 ? 'text-amber-500' : 'text-neutral-900' }} tabular-nums">
                    {{ $stats['not_indexed'] }}
                </p>
                @if($stats['unknown_indexed'] > 0)
                    <p class="text-xs text-neutral-400 mt-1">+ {{ $stats['unknown_indexed'] }} inconnus</p>
                @endif
            </div>

            {{-- Nofollow --}}
            <div class="bg-white rounded-xl border border-neutral-200 p-4">
                <p class="text-xs text-neutral-400 mb-1">Nofollow</p>
                <p class="text-2xl font-black {{ $stats['not_dofollow'] > 0 ? 'text-amber-500' : 'text-neutral-900' }} tabular-nums">
                    {{ $stats['not_dofollow'] }}
                </p>
                <p class="text-xs text-neutral-400 mt-1">liens sans jus SEO</p>
            </div>

            {{-- Budget total --}}
            <div class="bg-white rounded-xl border border-neutral-200 p-4">
                <p class="text-xs text-neutral-400 mb-1">Budget total</p>
                <p class="text-2xl font-black text-neutral-900 tabular-nums">
                    @if($stats['budget_total'] > 0)
                        {{ number_format($stats['budget_total'], 0, ',', ' ') }} €
                    @else
                        <span class="text-neutral-300">—</span>
                    @endif
                </p>
                @if($stats['budget_active'] > 0 && $stats['budget_active'] != $stats['budget_total'])
                    <p class="text-xs text-neutral-400 mt-1">{{ number_format($stats['budget_active'], 0, ',', ' ') }} € actifs</p>
                @endif
            </div>

            {{-- Perdus --}}
            <div class="bg-white rounded-xl border border-{{ $stats['lost'] > 0 ? 'red-200' : 'neutral-200' }} p-4 {{ $stats['lost'] > 0 ? 'bg-red-50' : 'bg-white' }}">
                <p class="text-xs {{ $stats['lost'] > 0 ? 'text-red-400' : 'text-neutral-400' }} mb-1">Backlinks perdus</p>
                <p class="text-2xl font-black {{ $stats['lost'] > 0 ? 'text-red-600' : 'text-neutral-900' }} tabular-nums">
                    {{ $stats['lost'] }}
                </p>
                @if($stats['changed'] > 0)
                    <p class="text-xs text-amber-500 mt-1">+ {{ $stats['changed'] }} modifiés</p>
                @endif
            </div>

        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         GRAPHIQUES D'ÉVOLUTION
         ═══════════════════════════════════════════════════════════ --}}
    <div class="bg-white rounded-xl border border-neutral-200 mb-6 overflow-hidden"
         x-data="backlinkChart({{ $project->id }})">

        {{-- Header : titre + sélecteur période --}}
        <div class="flex items-center justify-between px-6 py-4 border-b border-neutral-100">
            <h2 class="text-sm font-bold text-neutral-900 uppercase tracking-wide">Évolution des backlinks</h2>
            <div class="flex gap-1 bg-neutral-100 p-1 rounded-lg">
                @foreach([30 => '30j', 90 => '90j', 180 => '6m', 365 => '1an'] as $d => $label)
                    <button @click="loadCharts({{ $d }})"
                        :class="days === {{ $d }} ? 'bg-white text-neutral-900 shadow-sm font-semibold' : 'text-neutral-500 hover:text-neutral-700'"
                        class="px-3 py-1 text-xs rounded-md transition-all duration-150">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Graphique 1 : courbes cumulatives --}}
        <div class="px-6 pt-4 pb-2">
            {{-- Boutons toggle --}}
            <div class="flex flex-wrap gap-2 mb-3">
                <button @click="toggleSeries(0)" :class="toggles[0] ? 'opacity-100' : 'opacity-40'"
                        class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-full border border-blue-200 bg-blue-50 text-blue-700 transition-opacity">
                    <span class="w-2.5 h-2.5 rounded-full bg-blue-500 inline-block"></span>Total
                </button>
                <button @click="toggleSeries(1)" :class="toggles[1] ? 'opacity-100' : 'opacity-40'"
                        class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-full border border-emerald-200 bg-emerald-50 text-emerald-700 transition-opacity">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 inline-block"></span>Parfaits
                </button>
                <button @click="toggleSeries(2)" :class="toggles[2] ? 'opacity-100' : 'opacity-40'"
                        class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-full border border-amber-200 bg-amber-50 text-amber-700 transition-opacity">
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-400 inline-block"></span>Non indexés
                </button>
                <button @click="toggleSeries(3)" :class="toggles[3] ? 'opacity-100' : 'opacity-40'"
                        class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-full border border-violet-200 bg-violet-50 text-violet-700 transition-opacity">
                    <span class="w-2.5 h-2.5 rounded-full bg-violet-500 inline-block"></span>Nofollow
                </button>
            </div>
            <div x-show="loading" class="flex items-center justify-center h-44">
                <div class="flex gap-1.5">
                    <span class="w-1.5 h-6 bg-neutral-200 rounded-full animate-pulse"></span>
                    <span class="w-1.5 h-10 bg-neutral-300 rounded-full animate-pulse"></span>
                    <span class="w-1.5 h-8 bg-neutral-200 rounded-full animate-pulse"></span>
                    <span class="w-1.5 h-12 bg-neutral-300 rounded-full animate-pulse"></span>
                    <span class="w-1.5 h-6 bg-neutral-200 rounded-full animate-pulse"></span>
                </div>
            </div>
            <div x-show="!loading" x-cloak class="relative h-44">
                <canvas id="projectChartQuality"></canvas>
            </div>
        </div>

        {{-- Séparateur --}}
        <div class="mx-6 border-t border-neutral-100 my-1"></div>

        {{-- Graphique 2 : bougies gains / pertes --}}
        <div class="px-6 pt-2 pb-4">
            <div class="flex items-center gap-3 mb-2">
                <span class="text-xs font-semibold text-neutral-500 uppercase tracking-wide">Gains &amp; Pertes / jour</span>
                <span class="flex items-center gap-1 text-xs text-neutral-400">
                    <span class="w-2.5 h-2.5 rounded-sm bg-emerald-400 inline-block"></span>Gains
                </span>
                <span class="flex items-center gap-1 text-xs text-neutral-400">
                    <span class="w-2.5 h-2.5 rounded-sm bg-red-400 inline-block"></span>Pertes
                </span>
            </div>
            <div x-show="!loading" x-cloak class="relative h-28">
                <canvas id="projectChartCandles"></canvas>
            </div>
            <div x-show="loading" class="h-28"></div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         TABLEAU DES BACKLINKS
         ═══════════════════════════════════════════════════════════ --}}
    <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden">
        <div class="flex items-center justify-between px-6 py-4 border-b border-neutral-200">
            <h2 class="text-sm font-bold text-neutral-900 uppercase tracking-wide">Backlinks récents</h2>
            @if($stats['total'] > 10)
                <a href="{{ url('/backlinks?project_id=' . $project->id) }}"
                   class="text-xs text-brand-600 hover:text-brand-700 font-medium">
                    Voir tous ({{ $stats['total'] }}) →
                </a>
            @endif
        </div>

        @if($recentBacklinks->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-neutral-50 border-b border-neutral-100">
                            <th class="px-5 py-2.5 text-left text-xs font-semibold text-neutral-400 uppercase tracking-wider">URL Source / Ancre</th>
                            <th class="px-5 py-2.5 text-left text-xs font-semibold text-neutral-400 uppercase tracking-wider">Statut</th>
                            <th class="px-5 py-2.5 text-center text-xs font-semibold text-neutral-400 uppercase tracking-wider">Dofollow</th>
                            <th class="px-5 py-2.5 text-center text-xs font-semibold text-neutral-400 uppercase tracking-wider">Indexé</th>
                            <th class="px-5 py-2.5 text-left text-xs font-semibold text-neutral-400 uppercase tracking-wider">Tier</th>
                            <th class="px-5 py-2.5 text-left text-xs font-semibold text-neutral-400 uppercase tracking-wider">Prix</th>
                            <th class="px-5 py-2.5 text-left text-xs font-semibold text-neutral-400 uppercase tracking-wider">Publié</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-50">
                        @foreach($recentBacklinks as $backlink)
                            @php
                                $statusMap = [
                                    'active'  => ['label' => 'Actif',    'class' => 'text-emerald-700 bg-emerald-50 border-emerald-200'],
                                    'lost'    => ['label' => 'Perdu',    'class' => 'text-red-700 bg-red-50 border-red-200'],
                                    'changed' => ['label' => 'Modifié', 'class' => 'text-amber-700 bg-amber-50 border-amber-200'],
                                ];
                                $s = $statusMap[$backlink->status] ?? ['label' => ucfirst($backlink->status), 'class' => 'text-neutral-600 bg-neutral-50 border-neutral-200'];
                            @endphp
                            <tr class="hover:bg-neutral-50 transition-colors">
                                <td class="px-5 py-3">
                                    <a href="{{ $backlink->source_url }}" target="_blank"
                                       class="text-brand-500 hover:text-brand-600 hover:underline font-mono text-xs block truncate max-w-xs">
                                        {{ Str::limit($backlink->source_url, 55) }}
                                    </a>
                                    @if($backlink->anchor_text)
                                        <span class="text-xs text-neutral-400 italic">{{ Str::limit($backlink->anchor_text, 40) }}</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 text-xs font-semibold border rounded-full {{ $s['class'] }}">
                                        {{ $s['label'] }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-center whitespace-nowrap">
                                    @if($backlink->is_dofollow === true)
                                        <span class="inline-flex items-center px-2 py-0.5 text-xs font-semibold border rounded-full text-emerald-700 bg-emerald-50 border-emerald-200">DF</span>
                                    @elseif($backlink->is_dofollow === false)
                                        <span class="inline-flex items-center px-2 py-0.5 text-xs font-semibold border rounded-full text-red-600 bg-red-50 border-red-200">NF</span>
                                    @else
                                        <span class="text-neutral-300 text-xs">—</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-center whitespace-nowrap">
                                    @if($backlink->is_indexed === true)
                                        <span class="inline-flex items-center px-2 py-0.5 text-xs font-semibold border rounded-full text-emerald-700 bg-emerald-50 border-emerald-200">Yes</span>
                                    @elseif($backlink->is_indexed === false)
                                        <span class="inline-flex items-center px-2 py-0.5 text-xs font-semibold border rounded-full text-red-600 bg-red-50 border-red-200">No</span>
                                    @else
                                        <span class="text-neutral-300 text-xs">—</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 text-xs font-semibold border rounded-full text-neutral-600 bg-neutral-50 border-neutral-200">
                                        {{ $backlink->tier_level === 'tier1' ? 'T1' : 'T2' }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 whitespace-nowrap text-xs text-neutral-600">
                                    @if($backlink->price)
                                        {{ number_format($backlink->price, 0, ',', ' ') }} {{ $backlink->currency ?? 'EUR' }}
                                    @else
                                        <span class="text-neutral-300">—</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 whitespace-nowrap text-xs text-neutral-400">
                                    {{ ($backlink->published_at ?? $backlink->created_at)?->format('d/m/Y') ?? '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="flex flex-col items-center justify-center py-16 text-center">
                <div class="w-12 h-12 rounded-full bg-neutral-100 flex items-center justify-center mb-3">
                    <svg class="w-6 h-6 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                    </svg>
                </div>
                <p class="text-sm font-semibold text-neutral-600 mb-1">Aucun backlink</p>
                <p class="text-xs text-neutral-400 mb-4">Ajoutez des backlinks à suivre pour ce site.</p>
                <x-button variant="primary" href="{{ url('/backlinks/create?project_id=' . $project->id) }}">
                    + Ajouter un backlink
                </x-button>
            </div>
        @endif
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
function backlinkChart(projectId) {
    return {
        days: 30,
        loading: true,
        chartQuality: null,
        chartCandles: null,
        toggles: [true, true, true, true],

        init() { this.loadCharts(30); },

        async loadCharts(d) {
            this.days = d;
            this.loading = true;
            try {
                const res = await fetch(`/api/dashboard/chart?days=${d}&project_id=${projectId}`);
                const data = await res.json();
                await this.$nextTick();
                this.renderQuality(data);
                this.renderCandles(data);
            } catch (e) {
                console.error('Erreur chargement graphique:', e);
            } finally {
                this.loading = false;
            }
        },

        toggleSeries(index) {
            this.toggles[index] = !this.toggles[index];
            if (this.chartQuality) {
                this.chartQuality.data.datasets[index].hidden = !this.toggles[index];
                this.chartQuality.update();
            }
        },

        renderQuality(data) {
            const ctx = document.getElementById('projectChartQuality');
            if (!ctx) return;
            if (this.chartQuality) this.chartQuality.destroy();
            const tooltipLabels = ['Total', 'Parfaits', 'Non indexés', 'Nofollow'];
            this.chartQuality = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [
                        { label: 'Total', data: data.active || [], borderColor: 'rgba(59,130,246,0.9)', backgroundColor: 'rgba(59,130,246,0.06)', borderWidth: 2.5, pointRadius: 0, pointHoverRadius: 4, tension: 0.4, fill: true, hidden: !this.toggles[0] },
                        { label: 'Parfaits', data: data.perfect || [], borderColor: 'rgba(16,185,129,0.9)', backgroundColor: 'rgba(16,185,129,0.05)', borderWidth: 2, pointRadius: 0, pointHoverRadius: 4, tension: 0.4, fill: false, hidden: !this.toggles[1] },
                        { label: 'Non indexés', data: data.not_indexed || [], borderColor: 'rgba(245,158,11,0.9)', backgroundColor: 'transparent', borderWidth: 2, borderDash: [4, 3], pointRadius: 0, pointHoverRadius: 4, tension: 0.4, fill: false, hidden: !this.toggles[2] },
                        { label: 'Nofollow', data: data.nofollow || [], borderColor: 'rgba(139,92,246,0.9)', backgroundColor: 'transparent', borderWidth: 2, borderDash: [4, 3], pointRadius: 0, pointHoverRadius: 4, tension: 0.4, fill: false, hidden: !this.toggles[3] },
                    ],
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: 'rgba(15,23,42,0.92)', titleColor: 'rgba(148,163,184,1)', bodyColor: '#fff',
                            borderColor: 'rgba(51,65,85,0.5)', borderWidth: 1, padding: 10,
                            callbacks: { label: (item) => ` ${tooltipLabels[item.datasetIndex]} : ${item.parsed.y}` },
                        },
                    },
                    scales: {
                        x: { grid: { display: false }, border: { display: false }, ticks: { font: { size: 10 }, color: '#94a3b8', maxTicksLimit: 10 } },
                        y: { position: 'left', beginAtZero: false, grid: { color: 'rgba(148,163,184,0.1)' }, border: { display: false }, ticks: { font: { size: 10 }, color: '#94a3b8', precision: 0, maxTicksLimit: 5 } },
                    },
                },
            });
        },

        renderCandles(data) {
            const ctx = document.getElementById('projectChartCandles');
            if (!ctx) return;
            if (this.chartCandles) this.chartCandles.destroy();
            this.chartCandles = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: [
                        { label: 'Gains', data: data.gained || [], backgroundColor: 'rgba(52,211,153,0.8)', borderColor: 'rgba(16,185,129,1)', borderWidth: 1, borderRadius: 3, borderSkipped: false },
                        { label: 'Pertes', data: (data.lost || []).map(v => -v), backgroundColor: 'rgba(248,113,113,0.8)', borderColor: 'rgba(239,68,68,1)', borderWidth: 1, borderRadius: 3, borderSkipped: false },
                    ],
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: 'rgba(15,23,42,0.92)', titleColor: 'rgba(148,163,184,1)', bodyColor: '#fff',
                            borderColor: 'rgba(51,65,85,0.5)', borderWidth: 1, padding: 10,
                            callbacks: { label: (item) => { const v = item.datasetIndex === 0 ? item.parsed.y : -item.parsed.y; return ` ${item.dataset.label} : ${item.datasetIndex === 0 ? '+' : '-'}${v}`; } },
                        },
                    },
                    scales: {
                        x: { grid: { display: false }, border: { display: false }, ticks: { font: { size: 10 }, color: '#94a3b8', maxTicksLimit: 10 } },
                        y: { grid: { color: 'rgba(148,163,184,0.08)' }, border: { display: false }, ticks: { font: { size: 10 }, color: '#94a3b8', precision: 0, maxTicksLimit: 4, callback: (v) => v >= 0 ? `+${v}` : v } },
                    },
                },
            });
        },
    };
}
</script>
@endpush

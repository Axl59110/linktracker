@extends('layouts.app')

@section('title', 'Backlink — ' . parse_url($backlink->source_url, PHP_URL_HOST) . ' - Link Tracker')

@section('breadcrumb')
    <a href="{{ route('backlinks.index') }}" class="text-neutral-500 hover:text-neutral-700 transition-colors">Backlinks</a>
    <span class="mx-2 text-neutral-400">/</span>
    <span class="text-neutral-900 font-medium truncate max-w-xs">{{ parse_url($backlink->source_url, PHP_URL_HOST) }}</span>
@endsection

@section('content')

{{-- ─── HERO STRIP ──────────────────────────────────────────────────────────── --}}
<div class="bg-white border border-neutral-200 rounded-xl mb-6 overflow-hidden">

    {{-- Status bar top --}}
    @php
        $statusColor = match($backlink->status) {
            'active'  => 'bg-emerald-500',
            'lost'    => 'bg-red-500',
            'changed' => 'bg-amber-400',
            default   => 'bg-neutral-300',
        };
    @endphp
    <div class="h-1 w-full {{ $statusColor }}"></div>

    <div class="p-6">
        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">

            {{-- Left: identity --}}
            <div class="flex-1 min-w-0">
                {{-- Badges row --}}
                <div class="flex flex-wrap items-center gap-2 mb-3">
                    @php
                        $statusLabel = match($backlink->status) {
                            'active'  => ['Actif',    'bg-emerald-50 text-emerald-700 border-emerald-200'],
                            'lost'    => ['Perdu',    'bg-red-50 text-red-700 border-red-200'],
                            'changed' => ['Modifié',  'bg-amber-50 text-amber-700 border-amber-200'],
                            default   => ['Inconnu',  'bg-neutral-100 text-neutral-600 border-neutral-200'],
                        };
                    @endphp
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold border {{ $statusLabel[1] }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ $statusColor }} inline-block"></span>
                        {{ $statusLabel[0] }}
                    </span>

                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium border border-neutral-200 bg-neutral-50 text-neutral-600">
                        {{ $backlink->tier_level === 'tier1' ? 'Tier 1' : 'Tier 2' }}
                    </span>

                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium border border-neutral-200 bg-neutral-50 text-neutral-600">
                        {{ $backlink->spot_type === 'internal' ? 'PBN' : 'Externe' }}
                    </span>

                    @if($backlink->is_dofollow)
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium border border-blue-200 bg-blue-50 text-blue-700">dofollow</span>
                    @elseif($backlink->is_dofollow === false)
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium border border-neutral-200 bg-neutral-50 text-neutral-500">nofollow</span>
                    @endif

                    @if($backlink->is_indexed === true)
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium border border-emerald-200 bg-emerald-50 text-emerald-700">indexé</span>
                    @elseif($backlink->is_indexed === false)
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium border border-red-200 bg-red-50 text-red-600">non indexé</span>
                    @endif
                </div>

                {{-- Source URL --}}
                <div class="mb-1">
                    <span class="text-xs font-medium text-neutral-400 uppercase tracking-wide">Source</span>
                </div>
                <a href="{{ $backlink->source_url }}" target="_blank"
                   class="group flex items-center gap-2 text-neutral-900 font-semibold text-base hover:text-brand-600 transition-colors break-all leading-snug mb-3">
                    <span>{{ $backlink->source_url }}</span>
                    <svg class="w-3.5 h-3.5 flex-shrink-0 text-neutral-400 group-hover:text-brand-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                    </svg>
                </a>

                {{-- Target URL --}}
                <div class="mb-1">
                    <span class="text-xs font-medium text-neutral-400 uppercase tracking-wide">Cible</span>
                </div>
                <a href="{{ $backlink->target_url }}" target="_blank"
                   class="group flex items-center gap-2 text-neutral-600 text-sm hover:text-brand-600 transition-colors break-all">
                    <span>{{ $backlink->target_url }}</span>
                    <svg class="w-3 h-3 flex-shrink-0 text-neutral-400 group-hover:text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                    </svg>
                </a>
            </div>

            {{-- Right: KPIs + Actions --}}
            <div class="flex flex-col gap-4 lg:items-end flex-shrink-0">

                {{-- KPI row --}}
                <div class="flex items-stretch gap-3">
                    {{-- Uptime --}}
                    @php
                        $totalChecks   = $backlink->checks->count();
                        $successChecks = $backlink->checks->where('is_present', true)->count();
                        $uptime        = $totalChecks > 0 ? round(($successChecks / $totalChecks) * 100, 1) : null;
                        $uptimeColor   = $uptime === null ? 'text-neutral-400'
                            : ($uptime >= 95 ? 'text-emerald-600' : ($uptime >= 80 ? 'text-amber-600' : 'text-red-600'));
                    @endphp
                    <div class="bg-neutral-50 border border-neutral-200 rounded-lg px-4 py-3 text-center min-w-[80px]">
                        <div class="text-xl font-bold {{ $uptimeColor }}">
                            {{ $uptime !== null ? $uptime . '%' : '—' }}
                        </div>
                        <div class="text-xs text-neutral-500 mt-0.5">Uptime</div>
                    </div>

                    {{-- Checks count --}}
                    <div class="bg-neutral-50 border border-neutral-200 rounded-lg px-4 py-3 text-center min-w-[80px]">
                        <div class="text-xl font-bold text-neutral-900">{{ $totalChecks }}</div>
                        <div class="text-xs text-neutral-500 mt-0.5">Checks</div>
                    </div>

                    {{-- Price --}}
                    @if($backlink->price)
                        <div class="bg-neutral-50 border border-neutral-200 rounded-lg px-4 py-3 text-center min-w-[80px]">
                            <div class="text-xl font-bold text-neutral-900">{{ number_format($backlink->price, 0) }}</div>
                            <div class="text-xs text-neutral-500 mt-0.5">{{ $backlink->currency ?? '€' }}</div>
                        </div>
                    @endif
                </div>

                {{-- Action buttons --}}
                <div class="flex items-center gap-2">
                    <form action="{{ route('backlinks.check', $backlink) }}" method="POST" class="inline-block"
                          onsubmit="return confirm('Lancer une vérification manuelle maintenant ?');">
                        @csrf
                        <button type="submit"
                            class="inline-flex items-center gap-1.5 px-3 py-2 bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium rounded-lg transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            Vérifier
                        </button>
                    </form>

                    <a href="{{ route('backlinks.edit', $backlink) }}"
                       class="inline-flex items-center gap-1.5 px-3 py-2 bg-white hover:bg-neutral-50 border border-neutral-200 text-neutral-700 text-sm font-medium rounded-lg transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Modifier
                    </a>

                    {{-- More actions --}}
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" @click.outside="open = false"
                            class="inline-flex items-center px-2.5 py-2 bg-white hover:bg-neutral-50 border border-neutral-200 text-neutral-500 rounded-lg transition-colors">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 5a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm0 5.5a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm0 5.5a1.5 1.5 0 110 3 1.5 1.5 0 010-3z"/>
                            </svg>
                        </button>
                        <div x-show="open" x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                             x-cloak
                             class="absolute right-0 top-full mt-1 w-44 bg-white border border-neutral-200 rounded-lg shadow-lg z-20">
                            <a href="{{ route('projects.show', $backlink->project) }}"
                               class="flex items-center gap-2 px-3 py-2.5 text-sm text-neutral-700 hover:bg-neutral-50 rounded-t-lg transition-colors">
                                <svg class="w-4 h-4 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                                </svg>
                                Voir le projet
                            </a>
                            <div class="border-t border-neutral-100"></div>
                            <form action="{{ route('backlinks.destroy', $backlink) }}" method="POST"
                                  onsubmit="return confirm('Supprimer ce backlink définitivement ?');">
                                @csrf @method('DELETE')
                                <button type="submit"
                                    class="w-full flex items-center gap-2 px-3 py-2.5 text-sm text-red-600 hover:bg-red-50 rounded-b-lg transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                    Supprimer
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- Last check info --}}
                @if($backlink->last_checked_at)
                    <div class="text-xs text-neutral-400 text-right">
                        Vérifié {{ $backlink->last_checked_at->diffForHumans() }}
                        · {{ $backlink->last_checked_at->format('d/m/Y à H:i') }}
                    </div>
                @else
                    <div class="text-xs text-neutral-400">Jamais vérifié</div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ─── MAIN GRID ───────────────────────────────────────────────────────────── --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- ── COL PRINCIPALE (2/3) ──────────────────────────────────────────── --}}
    <div class="lg:col-span-2 space-y-6">

        {{-- Graphique historique Up/Down --}}
        <div class="bg-white border border-neutral-200 rounded-xl p-6">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h2 class="text-sm font-semibold text-neutral-900">Historique de disponibilité</h2>
                    <p class="text-xs text-neutral-500 mt-0.5">Chaque barre = une vérification</p>
                </div>
                @if($totalChecks > 0)
                    <div class="flex items-center gap-3 text-xs text-neutral-500">
                        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm bg-emerald-400 inline-block"></span>Présent</span>
                        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm bg-red-400 inline-block"></span>Absent</span>
                    </div>
                @endif
            </div>

            @if($totalChecks > 0)
                @php
                    $checksForChart = $backlink->checks->sortBy('checked_at')->values();
                    $maxDisplay = 90;
                    $displayChecks = $checksForChart->takeLast($maxDisplay)->values();
                @endphp

                {{-- Timeline barchart --}}
                <div class="flex items-end gap-px h-16 mb-3 overflow-hidden" id="uptime-chart">
                    @foreach($displayChecks as $check)
                        @php
                            $barColor = $check->is_present ? 'bg-emerald-400' : 'bg-red-400';
                            $barTitle = ($check->is_present ? '✓ Présent' : '✗ Absent')
                                . ' — ' . $check->checked_at->format('d/m/Y H:i')
                                . ($check->http_status ? ' (HTTP ' . $check->http_status . ')' : '');
                        @endphp
                        <div class="flex-1 min-w-0 group relative cursor-default"
                             title="{{ $barTitle }}">
                            <div class="{{ $barColor }} w-full rounded-sm transition-opacity group-hover:opacity-70"
                                 style="height: {{ $check->is_present ? '100%' : '40%' }}"></div>
                            {{-- Tooltip --}}
                            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1.5 px-2 py-1 bg-neutral-900 text-white text-xs rounded whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-10">
                                {{ $check->checked_at->format('d/m H:i') }} · {{ $check->is_present ? 'UP' : 'DOWN' }}
                                @if($check->http_status) · {{ $check->http_status }}@endif
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Axis labels --}}
                <div class="flex justify-between text-xs text-neutral-400">
                    <span>{{ $displayChecks->first()?->checked_at->format('d/m/Y') }}</span>
                    <span>{{ $displayChecks->last()?->checked_at->format('d/m/Y') }}</span>
                </div>

                {{-- Stats row --}}
                <div class="flex items-center gap-6 mt-4 pt-4 border-t border-neutral-100">
                    <div>
                        <span class="text-lg font-bold {{ $uptimeColor }}">{{ $uptime }}%</span>
                        <span class="text-xs text-neutral-500 ml-1">uptime</span>
                    </div>
                    <div class="text-xs text-neutral-500">
                        <span class="font-semibold text-emerald-600">{{ $successChecks }}</span> succès
                        · <span class="font-semibold text-red-500">{{ $totalChecks - $successChecks }}</span> échecs
                        · <span class="font-medium text-neutral-600">{{ $totalChecks }}</span> total
                        @if($totalChecks > $maxDisplay)
                            <span class="text-neutral-400"> ({{ $maxDisplay }} derniers affichés)</span>
                        @endif
                    </div>
                </div>

            @else
                <div class="flex flex-col items-center justify-center py-10 text-center">
                    <div class="w-12 h-12 rounded-full bg-neutral-100 flex items-center justify-center mb-3">
                        <svg class="w-6 h-6 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <p class="text-sm text-neutral-500 font-medium">Aucune vérification effectuée</p>
                    <p class="text-xs text-neutral-400 mt-1">Le graphique apparaîtra après le premier check</p>
                </div>
            @endif
        </div>

        {{-- Attributs du lien --}}
        <div class="bg-white border border-neutral-200 rounded-xl p-6">
            <h2 class="text-sm font-semibold text-neutral-900 mb-4">Attributs du lien</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <div class="text-xs font-medium text-neutral-400 uppercase tracking-wide mb-1">Texte d'ancre</div>
                    <div class="text-sm font-medium text-neutral-900 bg-neutral-50 border border-neutral-200 rounded-lg px-3 py-2 break-words">
                        {{ $backlink->anchor_text ?? '—' }}
                    </div>
                </div>
                <div>
                    <div class="text-xs font-medium text-neutral-400 uppercase tracking-wide mb-1">Attributs rel</div>
                    <div class="text-sm font-mono bg-neutral-50 border border-neutral-200 rounded-lg px-3 py-2">
                        {{ $backlink->rel_attributes ?? '—' }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Journal des vérifications --}}
        @if($totalChecks > 0)
        <div class="bg-white border border-neutral-200 rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm font-semibold text-neutral-900">Journal des vérifications</h2>
                <span class="text-xs text-neutral-400">10 dernières sur {{ $totalChecks }}</span>
            </div>

            <div class="space-y-1">
                @foreach($backlink->checks->sortByDesc('checked_at')->take(10) as $check)
                    <div class="flex items-start gap-3 px-3 py-2.5 rounded-lg hover:bg-neutral-50 transition-colors group">
                        {{-- Status dot --}}
                        <div class="flex-shrink-0 mt-0.5">
                            @if($check->is_present)
                                <div class="w-5 h-5 rounded-full bg-emerald-100 flex items-center justify-center">
                                    <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </div>
                            @else
                                <div class="w-5 h-5 rounded-full bg-red-100 flex items-center justify-center">
                                    <svg class="w-3 h-3 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </div>
                            @endif
                        </div>

                        {{-- Content --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-xs font-semibold {{ $check->is_present ? 'text-emerald-700' : 'text-red-700' }}">
                                    {{ $check->is_present ? 'Présent' : 'Absent' }}
                                </span>
                                @if($check->http_status)
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-mono
                                        {{ $check->http_status < 300 ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}">
                                        {{ $check->http_status }}
                                    </span>
                                @endif
                                @if($check->anchor_text && $check->anchor_text !== $backlink->anchor_text)
                                    <span class="text-xs text-amber-600 bg-amber-50 px-1.5 py-0.5 rounded">ancre modifiée</span>
                                @endif
                            </div>
                            @if($check->error_message)
                                <p class="text-xs text-red-600 mt-0.5 truncate">{{ $check->error_message }}</p>
                            @endif
                        </div>

                        {{-- Date --}}
                        <div class="flex-shrink-0 text-right">
                            <div class="text-xs text-neutral-500">{{ $check->checked_at->format('d/m/Y') }}</div>
                            <div class="text-xs text-neutral-400">{{ $check->checked_at->format('H:i') }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Infos commerciales (si spot_type = external et données présentes) --}}
        @if($backlink->spot_type === 'external' && ($backlink->price || $backlink->platform || $backlink->contact_name || $backlink->contact_email))
        <div class="bg-white border border-neutral-200 rounded-xl p-6">
            <h2 class="text-sm font-semibold text-neutral-900 mb-4">Informations commerciales</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                @if($backlink->price)
                    <div>
                        <div class="text-xs font-medium text-neutral-400 uppercase tracking-wide mb-1">Prix</div>
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-semibold text-neutral-900">{{ number_format($backlink->price, 2) }} {{ $backlink->currency ?? 'EUR' }}</span>
                            @if($backlink->invoice_paid)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">Payé</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200">Non payé</span>
                            @endif
                        </div>
                    </div>
                @endif

                @if($backlink->platform)
                    <div>
                        <div class="text-xs font-medium text-neutral-400 uppercase tracking-wide mb-1">Plateforme</div>
                        <div class="text-sm font-medium text-neutral-900">{{ $backlink->platform->name }}</div>
                    </div>
                @endif

                @if($backlink->contact_name)
                    <div>
                        <div class="text-xs font-medium text-neutral-400 uppercase tracking-wide mb-1">Contact</div>
                        <div class="text-sm font-medium text-neutral-900">{{ $backlink->contact_name }}</div>
                    </div>
                @endif

                @if($backlink->contact_email)
                    <div>
                        <div class="text-xs font-medium text-neutral-400 uppercase tracking-wide mb-1">Email</div>
                        <a href="mailto:{{ $backlink->contact_email }}" class="text-sm text-brand-600 hover:underline">{{ $backlink->contact_email }}</a>
                    </div>
                @endif

                @if($backlink->contact_info)
                    <div class="sm:col-span-2">
                        <div class="text-xs font-medium text-neutral-400 uppercase tracking-wide mb-1">Notes</div>
                        <p class="text-sm text-neutral-700 bg-neutral-50 border border-neutral-200 rounded-lg px-3 py-2 whitespace-pre-line">{{ $backlink->contact_info }}</p>
                    </div>
                @endif
            </div>
        </div>
        @endif

    </div>

    {{-- ── SIDEBAR (1/3) ──────────────────────────────────────────────────── --}}
    <div class="space-y-4">

        {{-- Projet --}}
        <div class="bg-white border border-neutral-200 rounded-xl p-5">
            <div class="text-xs font-medium text-neutral-400 uppercase tracking-wide mb-2">Projet</div>
            <a href="{{ route('projects.show', $backlink->project) }}"
               class="flex items-center gap-2 text-sm font-semibold text-neutral-900 hover:text-brand-600 transition-colors group">
                <svg class="w-4 h-4 text-neutral-400 group-hover:text-brand-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                </svg>
                {{ $backlink->project->name }}
            </a>
            @if($backlink->tier_level === 'tier2' && $backlink->parentBacklink)
                <div class="mt-3 pt-3 border-t border-neutral-100">
                    <div class="text-xs text-neutral-400 mb-1">Lien parent (Tier 1)</div>
                    <a href="{{ route('backlinks.show', $backlink->parentBacklink) }}"
                       class="text-xs text-brand-600 hover:underline truncate block">
                        {{ $backlink->parentBacklink->source_url }}
                    </a>
                </div>
            @endif
        </div>

        {{-- Classification --}}
        <div class="bg-white border border-neutral-200 rounded-xl p-5">
            <div class="text-xs font-medium text-neutral-400 uppercase tracking-wide mb-3">Classification</div>
            <div class="space-y-2.5">
                <div class="flex items-center justify-between">
                    <span class="text-xs text-neutral-600">Niveau</span>
                    <span class="text-xs font-semibold text-neutral-800 bg-neutral-100 px-2 py-0.5 rounded">
                        {{ $backlink->tier_level === 'tier1' ? 'Tier 1' : 'Tier 2' }}
                    </span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-xs text-neutral-600">Réseau</span>
                    <span class="text-xs font-semibold text-neutral-800 bg-neutral-100 px-2 py-0.5 rounded">
                        {{ $backlink->spot_type === 'internal' ? 'PBN' : 'Externe' }}
                    </span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-xs text-neutral-600">Dofollow</span>
                    <span class="text-xs font-semibold px-2 py-0.5 rounded
                        {{ $backlink->is_dofollow === true ? 'bg-blue-50 text-blue-700' : ($backlink->is_dofollow === false ? 'bg-neutral-100 text-neutral-500' : 'bg-neutral-50 text-neutral-400') }}">
                        {{ $backlink->is_dofollow === true ? 'Oui' : ($backlink->is_dofollow === false ? 'Non' : '—') }}
                    </span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-xs text-neutral-600">Indexé</span>
                    <span class="text-xs font-semibold px-2 py-0.5 rounded
                        {{ $backlink->is_indexed === true ? 'bg-emerald-50 text-emerald-700' : ($backlink->is_indexed === false ? 'bg-red-50 text-red-600' : 'bg-neutral-50 text-neutral-400') }}">
                        {{ $backlink->is_indexed === true ? 'Oui' : ($backlink->is_indexed === false ? 'Non' : '—') }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Dates --}}
        <div class="bg-white border border-neutral-200 rounded-xl p-5">
            <div class="text-xs font-medium text-neutral-400 uppercase tracking-wide mb-3">Chronologie</div>
            <div class="space-y-3">
                @if($backlink->published_at)
                    <div class="flex items-start gap-3">
                        <div class="w-1.5 h-1.5 rounded-full bg-brand-400 flex-shrink-0 mt-1.5"></div>
                        <div>
                            <div class="text-xs font-medium text-neutral-700">Publication du lien</div>
                            <div class="text-xs text-neutral-500">{{ $backlink->published_at->format('d/m/Y') }}</div>
                        </div>
                    </div>
                @endif
                <div class="flex items-start gap-3">
                    <div class="w-1.5 h-1.5 rounded-full bg-neutral-300 flex-shrink-0 mt-1.5"></div>
                    <div>
                        <div class="text-xs font-medium text-neutral-700">Ajouté dans LinkTracker</div>
                        <div class="text-xs text-neutral-500">{{ $backlink->created_at->format('d/m/Y à H:i') }}</div>
                    </div>
                </div>
                @if($backlink->first_seen_at)
                    <div class="flex items-start gap-3">
                        <div class="w-1.5 h-1.5 rounded-full bg-neutral-300 flex-shrink-0 mt-1.5"></div>
                        <div>
                            <div class="text-xs font-medium text-neutral-700">Première détection</div>
                            <div class="text-xs text-neutral-500">{{ $backlink->first_seen_at->format('d/m/Y à H:i') }}</div>
                        </div>
                    </div>
                @endif
                @if($backlink->last_checked_at)
                    <div class="flex items-start gap-3">
                        <div class="w-1.5 h-1.5 rounded-full bg-neutral-300 flex-shrink-0 mt-1.5"></div>
                        <div>
                            <div class="text-xs font-medium text-neutral-700">Dernière vérification</div>
                            <div class="text-xs text-neutral-500">{{ $backlink->last_checked_at->format('d/m/Y à H:i') }}</div>
                        </div>
                    </div>
                @endif
                @if($backlink->expires_at)
                    <div class="flex items-start gap-3">
                        <div class="w-1.5 h-1.5 rounded-full {{ $backlink->expires_at->isPast() ? 'bg-red-400' : 'bg-amber-400' }} flex-shrink-0 mt-1.5"></div>
                        <div>
                            <div class="text-xs font-medium {{ $backlink->expires_at->isPast() ? 'text-red-700' : 'text-neutral-700' }}">
                                Expiration {{ $backlink->expires_at->isPast() ? '(expiré)' : '' }}
                            </div>
                            <div class="text-xs {{ $backlink->expires_at->isPast() ? 'text-red-500' : 'text-neutral-500' }}">
                                {{ $backlink->expires_at->format('d/m/Y') }}
                                @if($backlink->expires_at->isFuture())
                                    · dans {{ $backlink->expires_at->diffForHumans(null, true) }}
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Métriques SEO --}}
        <div class="bg-white border border-neutral-200 rounded-xl p-5" x-data="seoMetricsWidget({{ $backlink->id }})">
            <div class="flex items-center justify-between mb-3">
                <div class="text-xs font-medium text-neutral-400 uppercase tracking-wide">Métriques SEO</div>
                @if($domainMetric)
                    <span class="text-xs text-neutral-400">{{ $domainMetric->last_updated_at?->diffForHumans() ?? 'jamais' }}</span>
                @endif
            </div>

            @if($domainMetric && $domainMetric->hasData())
                <div class="mb-3">
                    <span class="inline-flex items-center px-2 py-0.5 text-xs rounded-full bg-neutral-100 text-neutral-500">
                        via {{ strtoupper($domainMetric->provider) }}
                    </span>
                </div>
                <div class="space-y-3">
                    @if(!is_null($domainMetric->da))
                        <div>
                            <div class="flex justify-between text-xs mb-1">
                                <span class="text-neutral-600">Domain Authority</span>
                                <span class="font-bold {{ $domainMetric->authority_color === 'green' ? 'text-emerald-600' : ($domainMetric->authority_color === 'orange' ? 'text-amber-500' : 'text-red-500') }}">{{ $domainMetric->da }}/100</span>
                            </div>
                            <div class="h-1.5 bg-neutral-100 rounded-full overflow-hidden">
                                <div class="h-full rounded-full {{ $domainMetric->authority_color === 'green' ? 'bg-emerald-400' : ($domainMetric->authority_color === 'orange' ? 'bg-amber-400' : 'bg-red-400') }}" style="width:{{ $domainMetric->da }}%"></div>
                            </div>
                        </div>
                    @endif
                    @if(!is_null($domainMetric->dr))
                        <div>
                            <div class="flex justify-between text-xs mb-1">
                                <span class="text-neutral-600">Domain Rating</span>
                                <span class="font-bold text-neutral-700">{{ $domainMetric->dr }}/100</span>
                            </div>
                            <div class="h-1.5 bg-neutral-100 rounded-full overflow-hidden">
                                <div class="h-full rounded-full bg-blue-400" style="width:{{ $domainMetric->dr }}%"></div>
                            </div>
                        </div>
                    @endif
                    @if(!is_null($domainMetric->spam_score))
                        <div class="flex justify-between text-xs">
                            <span class="text-neutral-600">Spam Score</span>
                            <span class="font-bold {{ $domainMetric->spam_color === 'green' ? 'text-emerald-600' : ($domainMetric->spam_color === 'orange' ? 'text-amber-500' : 'text-red-500') }}">{{ $domainMetric->spam_score }}%</span>
                        </div>
                    @endif
                    @if(!is_null($domainMetric->backlinks_count))
                        <div class="flex justify-between text-xs">
                            <span class="text-neutral-600">Backlinks domaine</span>
                            <span class="font-bold text-neutral-700">{{ number_format($domainMetric->backlinks_count) }}</span>
                        </div>
                    @endif
                </div>

                <button @click="refresh()" :disabled="loading"
                    class="mt-4 w-full text-xs text-neutral-500 hover:text-neutral-700 py-1.5 border border-neutral-200 rounded-lg hover:bg-neutral-50 transition-colors"
                    :class="loading ? 'opacity-50 cursor-not-allowed' : ''">
                    <span x-text="loading ? 'Actualisation…' : '↻ Actualiser'"></span>
                </button>

            @elseif((auth()->user()->seo_provider ?? 'custom') !== 'custom')
                <div class="text-center py-3">
                    <p class="text-xs text-neutral-500 mb-2">Métriques non chargées.</p>
                    <button @click="refresh()" :disabled="loading" class="text-xs text-brand-600 hover:underline">
                        <span x-text="loading ? 'Récupération…' : 'Récupérer les métriques'"></span>
                    </button>
                </div>
            @else
                <div class="text-center py-3">
                    <p class="text-xs text-neutral-500 mb-2">Aucun provider SEO configuré.</p>
                    <a href="{{ route('settings.index') }}?tab=seo" class="text-xs text-brand-600 hover:underline">Configurer →</a>
                </div>
            @endif

            <p x-show="message" x-text="message" x-cloak class="mt-2 text-xs text-center" :class="success ? 'text-emerald-600' : 'text-red-600'"></p>
        </div>

    </div>
</div>

@endsection

@push('scripts')
<script>
function seoMetricsWidget(backlinkId) {
    return {
        loading: false,
        message: null,
        success: false,
        async refresh() {
            this.loading = true;
            this.message = null;
            try {
                const res = await fetch(`/backlinks/${backlinkId}/seo-metrics`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                });
                const data = await res.json();
                this.success = data.success;
                this.message = data.message;
                if (data.success) setTimeout(() => location.reload(), 1500);
            } catch (e) {
                this.success = false;
                this.message = 'Erreur de connexion.';
            } finally {
                this.loading = false;
            }
        },
    };
}
</script>
@endpush

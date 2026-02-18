@extends('layouts.app')

@section('title', 'Backlinks - Link Tracker')

@section('breadcrumb')
    <span class="text-neutral-900 font-medium">Backlinks</span>
@endsection

@section('content')
    @if(session('success'))
        <x-alert variant="success" class="mb-6">{{ session('success') }}</x-alert>
    @endif

    <x-page-header title="Backlinks" subtitle="Tous vos backlinks surveillés">
        <x-slot:actions>
            <x-button variant="primary" href="{{ route('backlinks.create') }}">+ Nouveau backlink</x-button>
        </x-slot:actions>
    </x-page-header>

    {{-- Filters --}}
    <div class="bg-white rounded-lg border border-neutral-200 p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-neutral-900">
                Filtres
                @if($activeFiltersCount > 0)
                    <x-badge variant="brand" class="ml-2">{{ $activeFiltersCount }} actif(s)</x-badge>
                @endif
            </h3>
            @if(request()->hasAny(['search', 'status', 'project_id', 'tier_level', 'spot_type', 'sort']))
                <x-button variant="secondary" size="sm" href="{{ route('backlinks.index') }}">
                    Réinitialiser tous les filtres
                </x-button>
            @endif
        </div>

        <form method="GET" action="{{ route('backlinks.index') }}" class="space-y-4">
            {{-- Recherche textuelle --}}
            <div>
                <label for="search" class="block text-sm font-medium text-neutral-700 mb-1">Recherche</label>
                <input
                    type="text"
                    id="search"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Rechercher dans URL source, ancre ou URL cible..."
                    class="block w-full px-4 py-2 border border-neutral-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500"
                />
            </div>

            {{-- Filtres en grille --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                {{-- Status Filter --}}
                <div>
                    <label for="status" class="block text-sm font-medium text-neutral-700 mb-1">Statut</label>
                    <select
                        id="status"
                        name="status"
                        class="block w-full px-4 py-2 border border-neutral-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500"
                    >
                        <option value="">Tous</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Actif</option>
                        <option value="lost" {{ request('status') === 'lost' ? 'selected' : '' }}>Perdu</option>
                        <option value="changed" {{ request('status') === 'changed' ? 'selected' : '' }}>Modifié</option>
                    </select>
                </div>

                {{-- Project Filter --}}
                <div>
                    <label for="project_id" class="block text-sm font-medium text-neutral-700 mb-1">Site</label>
                    <select
                        id="project_id"
                        name="project_id"
                        class="block w-full px-4 py-2 border border-neutral-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500"
                    >
                        <option value="">Tous</option>
                        @foreach($projects as $project)
                            <option value="{{ $project->id }}" {{ request('project_id') == $project->id ? 'selected' : '' }}>
                                {{ $project->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Tier Level Filter --}}
                <div>
                    <label for="tier_level" class="block text-sm font-medium text-neutral-700 mb-1">Tiers</label>
                    <select
                        id="tier_level"
                        name="tier_level"
                        class="block w-full px-4 py-2 border border-neutral-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500"
                    >
                        <option value="">Tous</option>
                        <option value="tier1" {{ request('tier_level') === 'tier1' ? 'selected' : '' }}>Tier 1</option>
                        <option value="tier2" {{ request('tier_level') === 'tier2' ? 'selected' : '' }}>Tier 2</option>
                    </select>
                </div>

                {{-- Spot Type Filter --}}
                <div>
                    <label for="spot_type" class="block text-sm font-medium text-neutral-700 mb-1">Type de réseau</label>
                    <select
                        id="spot_type"
                        name="spot_type"
                        class="block w-full px-4 py-2 border border-neutral-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500"
                    >
                        <option value="">Tous</option>
                        <option value="external" {{ request('spot_type') === 'external' ? 'selected' : '' }}>Externe</option>
                        <option value="internal" {{ request('spot_type') === 'internal' ? 'selected' : '' }}>Interne (PBN)</option>
                    </select>
                </div>
            </div>

            {{-- Submit Button --}}
            <div class="flex justify-end">
                <x-button variant="primary" type="submit">
                    Appliquer les filtres
                </x-button>
            </div>
        </form>
    </div>

    {{-- Résultats --}}
    <div class="mb-4 flex items-center justify-between">
        <p class="text-sm text-neutral-600">
            <span class="font-semibold text-neutral-900">{{ $backlinks->total() }}</span> backlink(s) trouvé(s)
            @if($activeFiltersCount > 0)
                <span class="text-neutral-500">({{ $activeFiltersCount }} filtre(s) actif(s))</span>
            @endif
        </p>
        @if(request('sort'))
            @php
                $sortLabels = [
                    'created_at' => 'Date de création',
                    'source_url' => 'URL Source',
                    'status' => 'Statut',
                    'tier_level' => 'Niveau',
                    'spot_type' => 'Type de réseau',
                    'last_checked_at' => 'Dernière vérification'
                ];
            @endphp
            <p class="text-xs text-neutral-500">
                Tri : {{ $sortLabels[request('sort')] ?? 'Date de création' }} ({{ request('direction') === 'asc' ? 'croissant' : 'décroissant' }})
            </p>
        @endif
    </div>

    @if($backlinks->count() > 0)
        <div x-data="bulkActions()" class="space-y-3">

        {{-- Barre d'actions en masse (visible quand sélection > 0) --}}
        <div x-show="selected.length > 0" x-cloak
             class="bg-brand-600 text-white rounded-xl px-5 py-3 flex items-center gap-4 flex-wrap shadow-lg">
            <span class="text-sm font-semibold" x-text="selected.length + ' sélectionné(s)'"></span>

            {{-- Bulk delete --}}
            <form :action="'{{ route('backlinks.bulk-delete') }}'" method="POST" @submit.prevent="confirmBulkDelete($event)">
                @csrf
                <template x-for="id in selected" :key="id">
                    <input type="hidden" name="ids[]" :value="id">
                </template>
                <button type="submit"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold bg-red-500 hover:bg-red-600 text-white rounded-lg transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Supprimer
                </button>
            </form>

            {{-- Bulk edit : date publication --}}
            <form :action="'{{ route('backlinks.bulk-edit') }}'" method="POST" class="flex items-center gap-2">
                @csrf
                <template x-for="id in selected" :key="id">
                    <input type="hidden" name="ids[]" :value="id">
                </template>
                <input type="hidden" name="field" value="published_at">
                <input type="date" name="value"
                       class="px-2 py-1 text-xs text-neutral-800 border border-brand-400 rounded-lg bg-white focus:outline-none focus:ring-1 focus:ring-white">
                <button type="submit"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold bg-white/20 hover:bg-white/30 text-white rounded-lg transition-colors">
                    Définir date pub.
                </button>
            </form>

            {{-- Bulk edit : statut --}}
            <form :action="'{{ route('backlinks.bulk-edit') }}'" method="POST" class="flex items-center gap-2">
                @csrf
                <template x-for="id in selected" :key="id">
                    <input type="hidden" name="ids[]" :value="id">
                </template>
                <input type="hidden" name="field" value="status">
                <select name="value"
                        class="px-2 py-1 text-xs text-neutral-800 border border-brand-400 rounded-lg bg-white focus:outline-none focus:ring-1 focus:ring-white">
                    <option value="active">Actif</option>
                    <option value="lost">Perdu</option>
                    <option value="changed">Modifié</option>
                </select>
                <button type="submit"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold bg-white/20 hover:bg-white/30 text-white rounded-lg transition-colors">
                    Changer statut
                </button>
            </form>

            {{-- Bulk edit : indexation --}}
            <form :action="'{{ route('backlinks.bulk-edit') }}'" method="POST" class="flex items-center gap-2">
                @csrf
                <template x-for="id in selected" :key="id">
                    <input type="hidden" name="ids[]" :value="id">
                </template>
                <input type="hidden" name="field" value="is_indexed">
                <select name="value"
                        class="px-2 py-1 text-xs text-neutral-800 border border-brand-400 rounded-lg bg-white focus:outline-none focus:ring-1 focus:ring-white">
                    <option value="1">Indexé</option>
                    <option value="0">Non indexé</option>
                </select>
                <button type="submit"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold bg-white/20 hover:bg-white/30 text-white rounded-lg transition-colors">
                    Indexation
                </button>
            </form>

            <button @click="selected = []"
                    class="ml-auto text-xs text-white/70 hover:text-white underline">
                Désélectionner
            </button>
        </div>

        <div class="bg-white rounded-lg border border-neutral-200 overflow-hidden">
            <div class="overflow-x-auto">
                <x-table>
                    <x-slot:header>
                        <tr>
                            <th class="px-4 py-3 w-10">
                                <input type="checkbox" @change="toggleAll($event)"
                                       :checked="selected.length === allIds.length && allIds.length > 0"
                                       class="w-4 h-4 rounded border-neutral-300 text-brand-600 focus:ring-brand-500 cursor-pointer">
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase">Site</th>
                            <x-sortable-header field="source_url" label="URL Source" />
                            <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase">Ancre</th>
                            <x-sortable-header field="tier_level" label="Tier" />
                            <x-sortable-header field="spot_type" label="Réseau" />
                            <x-sortable-header field="status" label="Statut" />
                            <th class="px-4 py-3 text-center text-xs font-medium text-neutral-500 uppercase">DF</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-neutral-500 uppercase">Indexé</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase">DA</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase">Prix</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-neutral-500 uppercase w-24">Actions</th>
                        </tr>
                    </x-slot:header>
                    <x-slot:body>
                        @foreach($backlinks as $backlink)
                            <tr class="hover:bg-neutral-50" :class="selected.includes({{ $backlink->id }}) ? 'bg-brand-50' : ''">
                                <td class="px-4 py-3 w-10">
                                    <input type="checkbox"
                                           :value="{{ $backlink->id }}"
                                           x-model="selected"
                                           class="w-4 h-4 rounded border-neutral-300 text-brand-600 focus:ring-brand-500 cursor-pointer">
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-neutral-900">
                                    {{ $backlink->project?->name ?? 'N/A' }}
                                </td>
                                <td class="px-4 py-4 max-w-md">
                                    <a href="{{ $backlink->source_url }}" target="_blank" class="text-sm text-brand-500 hover:text-brand-600 hover:underline truncate block">
                                        {{ $backlink->source_url }}
                                    </a>
                                </td>
                                <td class="px-4 py-4 max-w-xs">
                                    @if($backlink->anchor_text)
                                        <span class="text-sm font-semibold text-neutral-900">{{ $backlink->anchor_text }}</span>
                                    @else
                                        <span class="text-xs text-neutral-400 italic">Pas d'ancre</span>
                                    @endif
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <x-badge variant="{{ $backlink->tier_level === 'tier1' ? 'neutral' : 'warning' }}">
                                        {{ $backlink->tier_level === 'tier1' ? 'Tier 1' : 'Tier 2' }}
                                    </x-badge>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <x-badge variant="{{ $backlink->spot_type === 'internal' ? 'success' : 'neutral' }}">
                                        {{ $backlink->spot_type === 'internal' ? 'Interne' : 'Externe' }}
                                    </x-badge>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <x-badge variant="{{ $backlink->status === 'active' ? 'success' : ($backlink->status === 'lost' ? 'danger' : 'warning') }}">
                                        {{ ucfirst($backlink->status) }}
                                    </x-badge>
                                </td>
                                {{-- Dofollow --}}
                                <td class="px-4 py-4 text-center whitespace-nowrap">
                                    @if($backlink->is_dofollow === true)
                                        <span class="inline-flex items-center px-2 py-0.5 text-xs font-semibold border rounded-full text-emerald-700 bg-emerald-50 border-emerald-200">DF</span>
                                    @elseif($backlink->is_dofollow === false)
                                        <span class="inline-flex items-center px-2 py-0.5 text-xs font-semibold border rounded-full text-red-600 bg-red-50 border-red-200">NF</span>
                                    @else
                                        <span class="text-neutral-300 text-xs">—</span>
                                    @endif
                                </td>
                                {{-- Indexé --}}
                                <td class="px-4 py-4 text-center whitespace-nowrap">
                                    @if($backlink->is_indexed === true)
                                        <span class="inline-flex items-center px-2 py-0.5 text-xs font-semibold border rounded-full text-emerald-700 bg-emerald-50 border-emerald-200">Yes</span>
                                    @elseif($backlink->is_indexed === false)
                                        <span class="inline-flex items-center px-2 py-0.5 text-xs font-semibold border rounded-full text-red-600 bg-red-50 border-red-200">No</span>
                                    @else
                                        <span class="text-neutral-300 text-xs">—</span>
                                    @endif
                                </td>
                                {{-- Colonne DA --}}
                                @php
                                    $bDomain = \App\Models\DomainMetric::extractDomain($backlink->source_url);
                                    $bMetric = $domainMetrics[$bDomain] ?? null;
                                @endphp
                                <td class="px-4 py-4 whitespace-nowrap text-sm">
                                    @if($bMetric && !is_null($bMetric->da))
                                        <span class="font-semibold {{ $bMetric->authority_color === 'green' ? 'text-green-600' : ($bMetric->authority_color === 'orange' ? 'text-orange-500' : 'text-red-500') }}">
                                            {{ $bMetric->da }}
                                        </span>
                                    @else
                                        <span class="text-neutral-300">–</span>
                                    @endif
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-neutral-900">
                                    @if($backlink->price && $backlink->currency)
                                        {{ number_format($backlink->price, 2) }} {{ $backlink->currency }}
                                    @else
                                        <span class="text-neutral-400">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <a href="{{ route('backlinks.show', $backlink) }}" class="inline-flex items-center justify-center w-8 h-8 rounded hover:bg-neutral-100 text-neutral-600 hover:text-brand-600 transition-colors" title="Voir">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </a>
                                        <a href="{{ route('backlinks.edit', $backlink) }}" class="inline-flex items-center justify-center w-8 h-8 rounded hover:bg-neutral-100 text-neutral-600 hover:text-brand-600 transition-colors" title="Modifier">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </a>
                                        <form action="{{ route('backlinks.destroy', $backlink) }}" method="POST" class="inline-block" onsubmit="return confirm('Supprimer ce backlink ?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex items-center justify-center w-8 h-8 rounded hover:bg-red-50 text-neutral-600 hover:text-red-600 transition-colors" title="Supprimer">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7 h16"/>
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
        @if($backlinks->hasPages())
            <div class="mt-6">
                {{ $backlinks->links() }}
            </div>
        @endif

        </div>{{-- /x-data bulkActions --}}
    @else
        <div class="bg-white p-12 rounded-lg border border-neutral-200 text-center">
            <span class="text-6xl mb-4 block">🔗</span>
            <h3 class="text-lg font-semibold text-neutral-900 mb-2">Aucun backlink</h3>
            <p class="text-neutral-500 mb-6">Commencez par ajouter des backlinks à surveiller.</p>
            <x-button variant="primary" href="{{ route('backlinks.create') }}">Ajouter un backlink</x-button>
        </div>
    @endif
@endsection

@push('scripts')
<script>
function bulkActions() {
    return {
        selected: [],
        allIds: @json($backlinks->pluck('id')),

        toggleAll(e) {
            this.selected = e.target.checked ? [...this.allIds] : [];
        },

        confirmBulkDelete(e) {
            if (!confirm(`Supprimer définitivement ${this.selected.length} backlink(s) ? Cette action est irréversible.`)) {
                return;
            }
            e.target.submit();
        }
    };
}
</script>
@endpush

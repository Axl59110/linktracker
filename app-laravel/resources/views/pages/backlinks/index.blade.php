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
    <div class="bg-white rounded-lg border border-neutral-200 p-4 mb-6">
        <form method="GET" action="{{ route('backlinks.index') }}" class="flex flex-wrap gap-4 items-end">
            {{-- Status Filter --}}
            <div class="flex-1 min-w-[200px]">
                <label for="status" class="block text-sm font-medium text-neutral-700 mb-1">Statut</label>
                <select
                    id="status"
                    name="status"
                    class="block w-full px-4 py-2 border border-neutral-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500"
                >
                    <option value="">Tous les statuts</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Actif</option>
                    <option value="lost" {{ request('status') === 'lost' ? 'selected' : '' }}>Perdu</option>
                    <option value="changed" {{ request('status') === 'changed' ? 'selected' : '' }}>Modifié</option>
                </select>
            </div>

            {{-- Project Filter --}}
            <div class="flex-1 min-w-[200px]">
                <label for="project_id" class="block text-sm font-medium text-neutral-700 mb-1">Projet</label>
                <select
                    id="project_id"
                    name="project_id"
                    class="block w-full px-4 py-2 border border-neutral-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500"
                >
                    <option value="">Tous les projets</option>
                    @foreach($projects as $project)
                        <option value="{{ $project->id }}" {{ request('project_id') == $project->id ? 'selected' : '' }}>
                            {{ $project->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Filter Actions --}}
            <div class="flex gap-2">
                <x-button variant="primary" type="submit">Filtrer</x-button>
                @if(request()->hasAny(['status', 'project_id']))
                    <x-button variant="secondary" href="{{ route('backlinks.index') }}">Réinitialiser</x-button>
                @endif
            </div>
        </form>
    </div>

    @if($backlinks->count() > 0)
        <div class="bg-white rounded-lg border border-neutral-200 overflow-hidden">
            <div class="overflow-x-auto">
                <x-table>
                    <x-slot:header>
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase">Projet</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase">URL Source</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase">Ancre</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase">Tier</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase">Réseau</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase">Statut</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase">Prix</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-neutral-500 uppercase w-24">Actions</th>
                        </tr>
                    </x-slot:header>
                    <x-slot:body>
                        @foreach($backlinks as $backlink)
                            <tr class="hover:bg-neutral-50">
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
        @if($backlinks->hasPages())
            <div class="mt-6">
                {{ $backlinks->links() }}
            </div>
        @endif
    @else
        <div class="bg-white p-12 rounded-lg border border-neutral-200 text-center">
            <span class="text-6xl mb-4 block">🔗</span>
            <h3 class="text-lg font-semibold text-neutral-900 mb-2">Aucun backlink</h3>
            <p class="text-neutral-500 mb-6">Commencez par ajouter des backlinks à surveiller.</p>
            <x-button variant="primary" href="{{ route('backlinks.create') }}">Ajouter un backlink</x-button>
        </div>
    @endif
@endsection

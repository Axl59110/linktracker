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
            <x-table>
                <x-slot:header>
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase">Projet</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase">URL Source</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase">Ancre</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase">Statut</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase">Type</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-neutral-500 uppercase">Actions</th>
                    </tr>
                </x-slot:header>
                <x-slot:body>
                    @foreach($backlinks as $backlink)
                        <tr class="hover:bg-neutral-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-neutral-900">
                                {{ $backlink->project?->name ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4">
                                <a href="{{ $backlink->source_url }}" target="_blank" class="text-sm text-brand-500 hover:text-brand-600">
                                    {{ Str::limit($backlink->source_url, 40) }}
                                </a>
                            </td>
                            <td class="px-6 py-4 text-sm text-neutral-500">{{ Str::limit($backlink->anchor_text ?? 'N/A', 30) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <x-badge variant="{{ $backlink->status === 'active' ? 'success' : ($backlink->status === 'lost' ? 'danger' : 'neutral') }}">
                                    {{ ucfirst($backlink->status) }}
                                </x-badge>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-neutral-500">
                                {{ $backlink->is_dofollow ? 'Dofollow' : 'Nofollow' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm space-x-2">
                                <x-button variant="secondary" size="sm" href="{{ route('backlinks.show', $backlink) }}">Voir</x-button>
                                <x-button variant="secondary" size="sm" href="{{ route('backlinks.edit', $backlink) }}">Modifier</x-button>
                                <form action="{{ route('backlinks.destroy', $backlink) }}" method="POST" class="inline-block" onsubmit="return confirm('Supprimer ce backlink ?');">
                                    @csrf
                                    @method('DELETE')
                                    <x-button variant="danger" size="sm" type="submit">Supprimer</x-button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </x-slot:body>
            </x-table>
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

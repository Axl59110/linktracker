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
    @else
        <div class="bg-white p-12 rounded-lg border border-neutral-200 text-center">
            <span class="text-6xl mb-4 block">🔗</span>
            <h3 class="text-lg font-semibold text-neutral-900 mb-2">Aucun backlink</h3>
            <p class="text-neutral-500 mb-6">Commencez par ajouter des backlinks à surveiller.</p>
            <x-button variant="primary" href="{{ route('backlinks.create') }}">Ajouter un backlink</x-button>
        </div>
    @endif
@endsection

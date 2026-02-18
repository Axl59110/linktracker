@extends('layouts.app')

@section('title', 'Portfolio - Link Tracker')

@section('breadcrumb')
    <span class="text-neutral-900 font-medium">Portfolio</span>
@endsection

@section('content')
    {{-- Success Message --}}
    @if(session('success'))
        <x-alert variant="success" class="mb-6">
            {{ session('success') }}
        </x-alert>
    @endif

    {{-- Page Header --}}
    <x-page-header title="Portfolio" subtitle="Gérez vos sites et leurs backlinks">
        <x-slot:actions>
            <x-button variant="primary" href="{{ route('projects.create') }}">
                + Nouveau site
            </x-button>
        </x-slot:actions>
    </x-page-header>

    {{-- Projects Table --}}
    @if($projects->count() > 0)
        <div class="bg-white rounded-lg border border-neutral-200 overflow-hidden">
            <x-table>
                <x-slot:header>
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">
                            Nom du site
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">
                            URL
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">
                            Statut
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">
                            Backlinks
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">
                            Date de création
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-neutral-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </x-slot:header>

                <x-slot:body>
                    @foreach($projects as $project)
                        <tr class="hover:bg-neutral-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-neutral-900">
                                    {{ $project->name }}
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <a href="{{ $project->url }}" target="_blank" class="text-sm text-brand-500 hover:text-brand-600">
                                    {{ Str::limit($project->url, 40) }}
                                </a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <x-badge variant="{{ $project->status === 'active' ? 'success' : 'neutral' }}">
                                    {{ ucfirst($project->status ?? 'active') }}
                                </x-badge>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-neutral-500">
                                {{ $project->backlinks_count }} backlinks
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-neutral-500">
                                {{ $project->created_at->format('d/m/Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm space-x-2">
                                <x-button variant="secondary" size="sm" href="{{ route('projects.show', $project) }}">
                                    Voir
                                </x-button>
                                <x-button variant="secondary" size="sm" href="{{ route('projects.edit', $project) }}">
                                    Modifier
                                </x-button>
                                <form action="{{ route('projects.destroy', $project) }}" method="POST" class="inline-block" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce site ?');">
                                    @csrf
                                    @method('DELETE')
                                    <x-button variant="danger" size="sm" type="submit">
                                        Supprimer
                                    </x-button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </x-slot:body>
            </x-table>
        </div>

        {{-- Pagination --}}
        @if($projects->hasPages())
            <div class="mt-6">
                {{ $projects->links() }}
            </div>
        @endif
    @else
        {{-- Empty State --}}
        <div class="bg-white p-12 rounded-lg border border-neutral-200 text-center">
            <span class="text-6xl mb-4 block">📁</span>
            <h3 class="text-lg font-semibold text-neutral-900 mb-2">Aucun site</h3>
            <p class="text-neutral-500 mb-6">Créez votre premier site pour commencer à suivre vos backlinks.</p>
            <x-button variant="primary" href="{{ route('projects.create') }}">
                Créer un site
            </x-button>
        </div>
    @endif
@endsection

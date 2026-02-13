@extends('layouts.app')

@section('title', 'Plateformes - Link Tracker')

@section('breadcrumb')
    <span class="text-neutral-900 font-medium">Plateformes</span>
@endsection

@section('content')
    {{-- Success Message --}}
    @if(session('success'))
        <x-alert variant="success" class="mb-6">
            {{ session('success') }}
        </x-alert>
    @endif

    {{-- Error Message --}}
    @if(session('error'))
        <x-alert variant="danger" class="mb-6">
            {{ session('error') }}
        </x-alert>
    @endif

    {{-- Page Header --}}
    <x-page-header title="Plateformes d'achat" subtitle="Gérez vos plateformes de netlinking">
        <x-slot:actions>
            <x-button variant="primary" href="{{ route('platforms.create') }}">
                + Nouvelle plateforme
            </x-button>
        </x-slot:actions>
    </x-page-header>

    {{-- Platforms Table --}}
    @if($platforms->count() > 0)
        <div class="bg-white rounded-lg border border-neutral-200 overflow-hidden">
            <x-table>
                <x-slot:header>
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">
                            Nom
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">
                            Type
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
                        <th class="px-6 py-3 text-right text-xs font-medium text-neutral-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </x-slot:header>

                <x-slot:body>
                    @foreach($platforms as $platform)
                        <tr class="hover:bg-neutral-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-neutral-900">
                                    {{ $platform->name }}
                                </div>
                                @if($platform->description)
                                    <div class="text-xs text-neutral-500">
                                        {{ Str::limit($platform->description, 50) }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <x-badge variant="neutral">
                                    {{ ucfirst($platform->type) }}
                                </x-badge>
                            </td>
                            <td class="px-6 py-4">
                                @if($platform->url)
                                    <a href="{{ $platform->url }}" target="_blank" class="text-sm text-brand-500 hover:text-brand-600">
                                        {{ Str::limit($platform->url, 30) }}
                                    </a>
                                @else
                                    <span class="text-sm text-neutral-400">N/A</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <x-badge :variant="$platform->is_active ? 'success' : 'neutral'">
                                    {{ $platform->is_active ? 'Actif' : 'Inactif' }}
                                </x-badge>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-neutral-500">
                                {{ $platform->backlinks_count ?? 0 }} liens
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm space-x-2">
                                <x-button variant="secondary" size="sm" href="{{ route('platforms.edit', $platform) }}">
                                    Modifier
                                </x-button>
                                <form action="{{ route('platforms.destroy', $platform) }}" method="POST" class="inline-block" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette plateforme ?');">
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
        @if($platforms->hasPages())
            <div class="mt-6">
                {{ $platforms->links() }}
            </div>
        @endif
    @else
        {{-- Empty State --}}
        <div class="bg-white p-12 rounded-lg border border-neutral-200 text-center">
            <span class="text-6xl mb-4 block">🏪</span>
            <h3 class="text-lg font-semibold text-neutral-900 mb-2">Aucune plateforme</h3>
            <p class="text-neutral-500 mb-6">Ajoutez vos plateformes d'achat de liens pour mieux organiser votre netlinking.</p>
            <x-button variant="primary" href="{{ route('platforms.create') }}">
                Créer une plateforme
            </x-button>
        </div>
    @endif
@endsection

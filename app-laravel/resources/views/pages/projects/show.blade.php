@extends('layouts.app')

@section('title', $project->name . ' - Link Tracker')

@section('breadcrumb')
    <a href="{{ route('projects.index') }}" class="text-neutral-500 hover:text-neutral-700">Projets</a>
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

    {{-- Project Info Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white p-6 rounded-lg border border-neutral-200">
            <p class="text-sm text-neutral-500 mb-1">Statut</p>
            <x-badge variant="{{ $project->status === 'active' ? 'success' : 'neutral' }}">
                {{ ucfirst($project->status ?? 'active') }}
            </x-badge>
        </div>

        <div class="bg-white p-6 rounded-lg border border-neutral-200">
            <p class="text-sm text-neutral-500 mb-1">Backlinks actifs</p>
            <p class="text-2xl font-bold text-neutral-900">{{ $project->backlinks_count ?? 0 }}</p>
        </div>

        <div class="bg-white p-6 rounded-lg border border-neutral-200">
            <p class="text-sm text-neutral-500 mb-1">Créé le</p>
            <p class="text-sm text-neutral-900">{{ $project->created_at->format('d/m/Y à H:i') }}</p>
        </div>
    </div>

    {{-- Backlinks Section --}}
    <div class="bg-white rounded-lg border border-neutral-200">
        <div class="p-6 border-b border-neutral-200">
            <h2 class="text-lg font-semibold text-neutral-900">Backlinks récents</h2>
        </div>

        @if($project->backlinks && $project->backlinks->count() > 0)
            <x-table>
                <x-slot:header>
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">
                            Source
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">
                            Tier
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">
                            Réseau
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">
                            Statut
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">
                            Prix
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">
                            Date
                        </th>
                    </tr>
                </x-slot:header>

                <x-slot:body>
                    @foreach($project->backlinks as $backlink)
                        <tr class="hover:bg-neutral-50">
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <a href="{{ $backlink->source_url }}" target="_blank" class="text-sm text-brand-500 hover:text-brand-600 hover:underline">
                                        {{ Str::limit($backlink->source_url, 40) }}
                                    </a>
                                    @if($backlink->anchor_text)
                                        <span class="text-xs text-neutral-500 mt-0.5">{{ Str::limit($backlink->anchor_text, 30) }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <x-badge variant="{{ $backlink->tier_level === 'tier1' ? 'neutral' : 'warning' }}">
                                    {{ $backlink->tier_level === 'tier1' ? 'T1' : 'T2' }}
                                </x-badge>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <x-badge variant="{{ $backlink->spot_type === 'internal' ? 'success' : 'neutral' }}">
                                    {{ $backlink->spot_type === 'internal' ? 'Int' : 'Ext' }}
                                </x-badge>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <x-badge :variant="$backlink->status === 'active' ? 'success' : ($backlink->status === 'lost' ? 'danger' : 'warning')">
                                    {{ $backlink->status_label ?? ucfirst($backlink->status ?? 'Inconnu') }}
                                </x-badge>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-neutral-900">
                                @if($backlink->price && $backlink->currency)
                                    {{ number_format($backlink->price, 2) }} {{ $backlink->currency }}
                                @else
                                    <span class="text-neutral-400">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-neutral-500">
                                {{ $backlink->created_at->format('d/m/Y') }}
                            </td>
                        </tr>
                    @endforeach
                </x-slot:body>
            </x-table>

            @if($project->backlinks_count > 10)
                <div class="p-4 border-t border-neutral-200 text-center">
                    <a href="{{ url('/backlinks?project_id=' . $project->id) }}" class="text-sm text-brand-500 hover:text-brand-600 font-medium">
                        Voir tous les backlinks ({{ $project->backlinks_count }})
                    </a>
                </div>
            @endif
        @else
            <div class="text-center py-12">
                <span class="text-6xl mb-4 block">🔗</span>
                <h3 class="text-base font-semibold text-neutral-900 mb-2">Aucun backlink</h3>
                <p class="text-sm text-neutral-500 mb-6">Commencez par ajouter des backlinks à suivre pour ce projet.</p>
                <x-button variant="primary" href="{{ url('/backlinks/create?project_id=' . $project->id) }}">
                    Ajouter un backlink
                </x-button>
            </div>
        @endif
    </div>
@endsection

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
            <p class="text-2xl font-bold text-neutral-900">0</p>
            {{-- TODO: Afficher count réel --}}
        </div>

        <div class="bg-white p-6 rounded-lg border border-neutral-200">
            <p class="text-sm text-neutral-500 mb-1">Créé le</p>
            <p class="text-sm text-neutral-900">{{ $project->created_at->format('d/m/Y à H:i') }}</p>
        </div>
    </div>

    {{-- Backlinks Section --}}
    <div class="bg-white p-6 rounded-lg border border-neutral-200">
        <h2 class="text-lg font-semibold text-neutral-900 mb-4">Backlinks</h2>

        {{-- TODO: Afficher la liste des backlinks quand ils seront disponibles --}}
        <div class="text-center py-12">
            <span class="text-6xl mb-4 block">🔗</span>
            <h3 class="text-base font-semibold text-neutral-900 mb-2">Aucun backlink</h3>
            <p class="text-sm text-neutral-500 mb-6">Commencez par ajouter des backlinks à suivre pour ce projet.</p>
            <x-button variant="primary" href="{{ url('/backlinks/create?project_id=' . $project->id) }}">
                Ajouter un backlink
            </x-button>
        </div>
    </div>
@endsection

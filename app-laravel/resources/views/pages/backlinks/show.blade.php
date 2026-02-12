@extends('layouts.app')

@section('title', 'Détails du backlink - Link Tracker')

@section('breadcrumb')
    <a href="{{ route('backlinks.index') }}" class="text-neutral-500 hover:text-neutral-700">Backlinks</a>
    <span class="mx-2 text-neutral-400">/</span>
    <span class="text-neutral-900 font-medium">Détails</span>
@endsection

@section('content')
    <x-page-header title="Détails du backlink" subtitle="Informations complètes sur ce backlink">
        <x-slot:actions>
            <x-button variant="secondary" href="{{ route('backlinks.edit', $backlink) }}">
                Modifier
            </x-button>
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Info --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- URLs --}}
            <div class="bg-white p-6 rounded-lg border border-neutral-200">
                <h2 class="text-lg font-semibold text-neutral-900 mb-4">URLs</h2>

                <div class="space-y-4">
                    <div>
                        <label class="text-sm font-medium text-neutral-700">URL Source</label>
                        <a href="{{ $backlink->source_url }}" target="_blank" class="block text-brand-500 hover:underline">
                            {{ $backlink->source_url }}
                        </a>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-neutral-700">URL Cible</label>
                        <a href="{{ $backlink->target_url }}" target="_blank" class="block text-brand-500 hover:underline">
                            {{ $backlink->target_url }}
                        </a>
                    </div>
                </div>
            </div>

            {{-- Attributes --}}
            <div class="bg-white p-6 rounded-lg border border-neutral-200">
                <h2 class="text-lg font-semibold text-neutral-900 mb-4">Attributs</h2>

                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-neutral-600">Texte d'ancre</span>
                        <span class="font-medium text-neutral-900">{{ $backlink->anchor_text ?? 'N/A' }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-neutral-600">Type</span>
                        <x-badge :variant="$backlink->is_dofollow ? 'success' : 'neutral'">
                            {{ $backlink->is_dofollow ? 'Dofollow' : 'Nofollow' }}
                        </x-badge>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-neutral-600">Attributs rel</span>
                        <span class="font-medium text-neutral-900">{{ $backlink->rel_attributes ?? 'N/A' }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Status --}}
            <div class="bg-white p-6 rounded-lg border border-neutral-200">
                <h3 class="text-sm font-semibold text-neutral-900 mb-3">Statut</h3>
                <x-badge :variant="$backlink->status === 'active' ? 'success' : 'danger'">
                    {{ ucfirst($backlink->status) }}
                </x-badge>
            </div>

            {{-- Project --}}
            <div class="bg-white p-6 rounded-lg border border-neutral-200">
                <h3 class="text-sm font-semibold text-neutral-900 mb-3">Projet</h3>
                <a href="{{ route('projects.show', $backlink->project) }}" class="text-brand-500 hover:underline">
                    {{ $backlink->project->name }}
                </a>
            </div>

            {{-- Dates --}}
            <div class="bg-white p-6 rounded-lg border border-neutral-200">
                <h3 class="text-sm font-semibold text-neutral-900 mb-3">Dates</h3>
                <div class="space-y-2 text-sm">
                    <div>
                        <div class="text-neutral-600">Première détection</div>
                        <div class="text-neutral-900">{{ $backlink->first_seen_at?->format('d/m/Y H:i') ?? 'N/A' }}</div>
                    </div>
                    <div>
                        <div class="text-neutral-600">Dernière vérification</div>
                        <div class="text-neutral-900">{{ $backlink->last_checked_at?->format('d/m/Y H:i') ?? 'N/A' }}</div>
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="bg-white p-6 rounded-lg border border-neutral-200">
                <form action="{{ route('backlinks.destroy', $backlink) }}" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce backlink ?')">
                    @csrf
                    @method('DELETE')
                    <x-button variant="danger" type="submit" class="w-full">
                        Supprimer
                    </x-button>
                </form>
            </div>
        </div>
    </div>
@endsection

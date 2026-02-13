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
            {{-- Type & Classification --}}
            <div class="bg-white p-6 rounded-lg border border-neutral-200">
                <h2 class="text-lg font-semibold text-neutral-900 mb-4">Classification</h2>

                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-neutral-600">Niveau</span>
                        <x-badge variant="{{ $backlink->tier_level === 'tier1' ? 'neutral' : 'warning' }}">
                            {{ $backlink->tier_level === 'tier1' ? 'Tier 1' : 'Tier 2' }}
                        </x-badge>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-neutral-600">Type de réseau</span>
                        <x-badge variant="{{ $backlink->spot_type === 'internal' ? 'success' : 'neutral' }}">
                            {{ $backlink->spot_type === 'internal' ? 'Interne (PBN)' : 'Externe (Site tiers)' }}
                        </x-badge>
                    </div>

                    @if($backlink->tier_level === 'tier2' && $backlink->parentBacklink)
                        <div class="flex justify-between">
                            <span class="text-neutral-600">Lien parent</span>
                            <a href="{{ route('backlinks.show', $backlink->parentBacklink) }}" class="text-brand-500 hover:underline text-sm">
                                {{ Str::limit($backlink->parentBacklink->source_url, 40) }}
                            </a>
                        </div>
                    @endif

                    <div class="flex justify-between">
                        <span class="text-neutral-600">Statut</span>
                        <x-badge variant="{{ $backlink->status === 'active' ? 'success' : ($backlink->status === 'lost' ? 'danger' : 'warning') }}">
                            {{ ucfirst($backlink->status) }}
                        </x-badge>
                    </div>
                </div>
            </div>

            {{-- URLs --}}
            <div class="bg-white p-6 rounded-lg border border-neutral-200">
                <h2 class="text-lg font-semibold text-neutral-900 mb-4">URLs</h2>

                <div class="space-y-4">
                    <div>
                        <label class="text-sm font-medium text-neutral-700">URL Source</label>
                        <a href="{{ $backlink->source_url }}" target="_blank" class="block text-brand-500 hover:underline break-all">
                            {{ $backlink->source_url }}
                        </a>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-neutral-700">URL Cible</label>
                        <a href="{{ $backlink->target_url }}" target="_blank" class="block text-brand-500 hover:underline break-all">
                            {{ $backlink->target_url }}
                        </a>
                    </div>
                </div>
            </div>

            {{-- Attributes --}}
            <div class="bg-white p-6 rounded-lg border border-neutral-200">
                <h2 class="text-lg font-semibold text-neutral-900 mb-4">Attributs du lien</h2>

                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-neutral-600">Texte d'ancre</span>
                        <span class="font-medium text-neutral-900">{{ $backlink->anchor_text ?? 'N/A' }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-neutral-600">Attributs rel</span>
                        @if($backlink->rel_attributes)
                            <span class="font-mono bg-neutral-100 px-2 py-1 rounded text-sm text-neutral-900">{{ $backlink->rel_attributes }}</span>
                        @else
                            <span class="text-neutral-400 text-sm">Non détecté</span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Financial & Contact (only for external) --}}
            @if($backlink->spot_type === 'external')
                <div class="bg-white p-6 rounded-lg border border-neutral-200">
                    <h2 class="text-lg font-semibold text-neutral-900 mb-4">Informations commerciales</h2>

                    <div class="space-y-3">
                        @if($backlink->price && $backlink->currency)
                            <div class="flex justify-between">
                                <span class="text-neutral-600">Prix</span>
                                <div class="text-right">
                                    <span class="font-medium text-neutral-900">{{ number_format($backlink->price, 2) }} {{ $backlink->currency }}</span>
                                    @if($backlink->invoice_paid)
                                        <x-badge variant="success" class="ml-2">Payé</x-badge>
                                    @else
                                        <x-badge variant="warning" class="ml-2">Non payé</x-badge>
                                    @endif
                                </div>
                            </div>
                        @endif

                        @if($backlink->platform)
                            <div class="flex justify-between">
                                <span class="text-neutral-600">Plateforme</span>
                                <span class="font-medium text-neutral-900">{{ $backlink->platform->name }}</span>
                            </div>
                        @endif

                        @if($backlink->contact_name)
                            <div class="flex justify-between">
                                <span class="text-neutral-600">Contact</span>
                                <span class="font-medium text-neutral-900">{{ $backlink->contact_name }}</span>
                            </div>
                        @endif

                        @if($backlink->contact_email)
                            <div class="flex justify-between">
                                <span class="text-neutral-600">Email</span>
                                <a href="mailto:{{ $backlink->contact_email }}" class="text-brand-500 hover:underline">{{ $backlink->contact_email }}</a>
                            </div>
                        @endif

                        @if($backlink->contact_info)
                            <div>
                                <span class="text-neutral-600 block mb-2">Informations de contact</span>
                                <p class="text-sm text-neutral-900 whitespace-pre-line bg-neutral-50 p-3 rounded">{{ $backlink->contact_info }}</p>
                            </div>
                        @endif

                        @if($backlink->published_at)
                            <div class="flex justify-between">
                                <span class="text-neutral-600">Date de publication</span>
                                <span class="text-neutral-900">{{ $backlink->published_at->format('d/m/Y') }}</span>
                            </div>
                        @endif

                        @if($backlink->expires_at)
                            <div class="flex justify-between">
                                <span class="text-neutral-600">Date d'expiration</span>
                                <span class="text-neutral-900">{{ $backlink->expires_at->format('d/m/Y') }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
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
                    <div>
                        <div class="text-neutral-600">Créé le</div>
                        <div class="text-neutral-900">{{ $backlink->created_at->format('d/m/Y H:i') }}</div>
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

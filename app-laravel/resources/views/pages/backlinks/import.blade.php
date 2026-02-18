@extends('layouts.app')

@section('title', 'Importer des backlinks - Link Tracker')

@section('breadcrumb')
    <a href="{{ route('backlinks.index') }}" class="text-brand-500 hover:text-brand-600">Backlinks</a>
    <span class="mx-2 text-neutral-400">/</span>
    <span class="text-neutral-900 font-medium">Import CSV</span>
@endsection

@section('content')
    <x-page-header title="Importer des backlinks" subtitle="Importez des backlinks en masse depuis un fichier CSV" />

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Formulaire d'import --}}
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg border border-neutral-200 p-6">
                @if($errors->any())
                    <x-alert variant="danger" class="mb-6">
                        {{ $errors->first() }}
                    </x-alert>
                @endif

                <form action="{{ route('backlinks.import.process') }}" method="POST" enctype="multipart/form-data" class="space-y-5">
                    @csrf

                    {{-- Fichier CSV --}}
                    <div>
                        <label for="csv_file" class="block text-sm font-medium text-neutral-700 mb-1">
                            Fichier CSV <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="file"
                            id="csv_file"
                            name="csv_file"
                            accept=".csv,.txt"
                            required
                            class="block w-full text-sm text-neutral-700 border border-neutral-300 rounded-lg cursor-pointer bg-white focus:outline-none focus:ring-2 focus:ring-brand-500 file:mr-4 file:py-2 file:px-4 file:border-0 file:text-sm file:font-medium file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100"
                        />
                        <p class="mt-1 text-xs text-neutral-500">Format CSV, maximum 10 Mo. Le format est détecté automatiquement.</p>
                    </div>

                    {{-- Site cible (optionnel si format outil tiers) --}}
                    <div>
                        <label for="project_id" class="block text-sm font-medium text-neutral-700 mb-1">
                            Site cible
                            <span class="ml-1 text-xs font-normal text-neutral-400">(optionnel pour le format outil tiers)</span>
                        </label>
                        <select
                            id="project_id"
                            name="project_id"
                            class="block w-full px-4 py-2 border border-neutral-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500"
                        >
                            <option value="">— Auto (depuis la colonne Operator) —</option>
                            @foreach($projects as $project)
                                <option value="{{ $project->id }}" {{ old('project_id') == $project->id ? 'selected' : '' }}>
                                    {{ $project->name }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-neutral-400">
                            Si vous importez depuis un outil tiers, laissez vide : les sites seront créés automatiquement depuis la colonne <code class="font-mono bg-neutral-100 px-1 rounded">Operator</code>.
                        </p>
                    </div>

                    <div class="flex items-center gap-3 pt-2">
                        <x-button variant="primary" type="submit">
                            Importer
                        </x-button>
                        <x-button variant="secondary" href="{{ route('backlinks.index') }}">
                            Annuler
                        </x-button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Instructions --}}
        <div class="space-y-4">

            {{-- Format outil tiers --}}
            <div class="bg-brand-50 rounded-lg border border-brand-200 p-5">
                <div class="flex items-center gap-2 mb-3">
                    <svg style="width:16px;height:16px" class="text-brand-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <h3 class="text-sm font-semibold text-brand-900">Format outil tiers (auto-détecté)</h3>
                </div>
                <p class="text-xs text-brand-700 mb-3">
                    Si votre fichier contient les colonnes <code class="font-mono bg-brand-100 px-1 rounded">Spot</code>, <code class="font-mono bg-brand-100 px-1 rounded">Target</code>, <code class="font-mono bg-brand-100 px-1 rounded">Anchor</code> et <code class="font-mono bg-brand-100 px-1 rounded">Rel</code>, le format sera reconnu automatiquement.
                </p>
                <div class="space-y-1.5 text-xs text-brand-800">
                    <div class="flex gap-2"><code class="font-mono bg-brand-100 px-1 rounded whitespace-nowrap">Spot</code><span>→ URL source</span></div>
                    <div class="flex gap-2"><code class="font-mono bg-brand-100 px-1 rounded whitespace-nowrap">Target</code><span>→ URL cible</span></div>
                    <div class="flex gap-2"><code class="font-mono bg-brand-100 px-1 rounded whitespace-nowrap">Anchor</code><span>→ Texte d'ancre</span></div>
                    <div class="flex gap-2"><code class="font-mono bg-brand-100 px-1 rounded whitespace-nowrap">Rel</code><span>→ DF = dofollow, NF = nofollow</span></div>
                    <div class="flex gap-2"><code class="font-mono bg-brand-100 px-1 rounded whitespace-nowrap">Status</code><span>→ Checked = actif, Dead link = perdu</span></div>
                    <div class="flex gap-2"><code class="font-mono bg-brand-100 px-1 rounded whitespace-nowrap">Network</code><span>→ External Site / No Network</span></div>
                    <div class="flex gap-2"><code class="font-mono bg-brand-100 px-1 rounded whitespace-nowrap">Price</code><span>→ Prix en EUR</span></div>
                    <div class="flex gap-2"><code class="font-mono bg-brand-100 px-1 rounded whitespace-nowrap">Operator</code><span>→ Nom du site (créé si absent)</span></div>
                    <div class="flex gap-2"><code class="font-mono bg-brand-100 px-1 rounded whitespace-nowrap">Created At</code><span>→ Date de publication</span></div>
                    <div class="flex gap-2"><code class="font-mono bg-brand-100 px-1 rounded whitespace-nowrap">Contact</code><span>→ Contact</span></div>
                </div>
            </div>

            {{-- Format natif --}}
            <div class="bg-white rounded-lg border border-neutral-200 p-5">
                <h3 class="text-sm font-semibold text-neutral-900 mb-3">Format natif LinkTracker</h3>
                <p class="text-xs text-neutral-600 mb-3">Colonnes supportées (un site cible est requis) :</p>
                <div class="space-y-1.5 text-xs">
                    <div class="flex items-start gap-2">
                        <span class="font-mono bg-neutral-100 px-1.5 py-0.5 rounded text-red-600 whitespace-nowrap">source_url *</span>
                        <span class="text-neutral-600">URL de la page source</span>
                    </div>
                    <div class="flex items-start gap-2">
                        <span class="font-mono bg-neutral-100 px-1.5 py-0.5 rounded text-red-600 whitespace-nowrap">target_url *</span>
                        <span class="text-neutral-600">URL cible</span>
                    </div>
                    <div class="flex items-start gap-2">
                        <span class="font-mono bg-neutral-100 px-1.5 py-0.5 rounded whitespace-nowrap">anchor_text</span>
                        <span class="text-neutral-600">Texte d'ancre</span>
                    </div>
                    <div class="flex items-start gap-2">
                        <span class="font-mono bg-neutral-100 px-1.5 py-0.5 rounded whitespace-nowrap">status</span>
                        <span class="text-neutral-600">active / lost / changed</span>
                    </div>
                    <div class="flex items-start gap-2">
                        <span class="font-mono bg-neutral-100 px-1.5 py-0.5 rounded whitespace-nowrap">spot_type</span>
                        <span class="text-neutral-600">external / internal</span>
                    </div>
                    <div class="flex items-start gap-2">
                        <span class="font-mono bg-neutral-100 px-1.5 py-0.5 rounded whitespace-nowrap">price</span>
                        <span class="text-neutral-600">Prix (nombre décimal)</span>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg border border-neutral-200 p-4">
                <h3 class="text-xs font-semibold text-neutral-700 mb-2">Notes importantes</h3>
                <ul class="text-xs text-neutral-600 space-y-1">
                    <li>• Les doublons (même URL source + site) sont ignorés</li>
                    <li>• Les lignes invalides sont ignorées avec un rapport</li>
                    <li>• Encodage UTF-8 recommandé</li>
                    <li>• Maximum 10 Mo par fichier</li>
                </ul>
            </div>
        </div>
    </div>
@endsection

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

                    {{-- Site cible --}}
                    <div>
                        <label for="project_id" class="block text-sm font-medium text-neutral-700 mb-1">
                            Site cible <span class="text-red-500">*</span>
                        </label>
                        <select
                            id="project_id"
                            name="project_id"
                            required
                            class="block w-full px-4 py-2 border border-neutral-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500"
                        >
                            <option value="">Sélectionner un site</option>
                            @foreach($projects as $project)
                                <option value="{{ $project->id }}" {{ old('project_id') == $project->id ? 'selected' : '' }}>
                                    {{ $project->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

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
                        <p class="mt-1 text-xs text-neutral-500">Format CSV, maximum 5 Mo.</p>
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
            <div class="bg-white rounded-lg border border-neutral-200 p-6">
                <h3 class="text-sm font-semibold text-neutral-900 mb-3">Format du fichier CSV</h3>

                <p class="text-xs text-neutral-600 mb-3">La première ligne doit contenir les en-têtes. Colonnes supportées :</p>

                <div class="space-y-2 text-xs">
                    <div class="flex items-start gap-2">
                        <span class="font-mono bg-neutral-100 px-1.5 py-0.5 rounded text-red-600 whitespace-nowrap">source_url *</span>
                        <span class="text-neutral-600">URL de la page source (requis)</span>
                    </div>
                    <div class="flex items-start gap-2">
                        <span class="font-mono bg-neutral-100 px-1.5 py-0.5 rounded text-red-600 whitespace-nowrap">target_url *</span>
                        <span class="text-neutral-600">URL cible (requis)</span>
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
                        <span class="font-mono bg-neutral-100 px-1.5 py-0.5 rounded whitespace-nowrap">tier_level</span>
                        <span class="text-neutral-600">tier1 / tier2</span>
                    </div>
                    <div class="flex items-start gap-2">
                        <span class="font-mono bg-neutral-100 px-1.5 py-0.5 rounded whitespace-nowrap">spot_type</span>
                        <span class="text-neutral-600">external / internal</span>
                    </div>
                    <div class="flex items-start gap-2">
                        <span class="font-mono bg-neutral-100 px-1.5 py-0.5 rounded whitespace-nowrap">price</span>
                        <span class="text-neutral-600">Prix (nombre décimal)</span>
                    </div>
                    <div class="flex items-start gap-2">
                        <span class="font-mono bg-neutral-100 px-1.5 py-0.5 rounded whitespace-nowrap">currency</span>
                        <span class="text-neutral-600">Devise (EUR, USD...)</span>
                    </div>
                </div>
            </div>

            <div class="bg-neutral-50 rounded-lg border border-neutral-200 p-4">
                <h3 class="text-xs font-semibold text-neutral-700 mb-2">Exemple</h3>
                <pre class="text-xs text-neutral-600 overflow-x-auto">source_url,target_url,anchor_text
https://blog.com/post,https://monsite.com,Mon site
https://autre.com/page,https://monsite.com,Cliquez ici</pre>
            </div>

            <div class="bg-white rounded-lg border border-neutral-200 p-4">
                <h3 class="text-xs font-semibold text-neutral-700 mb-2">Notes importantes</h3>
                <ul class="text-xs text-neutral-600 space-y-1">
                    <li>• Les doublons (même URL source + site) sont ignorés</li>
                    <li>• Les lignes invalides sont ignorées avec un rapport</li>
                    <li>• L'encodage UTF-8 est recommandé</li>
                </ul>
            </div>
        </div>
    </div>
@endsection

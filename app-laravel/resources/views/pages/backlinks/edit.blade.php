@extends('layouts.app')

@section('title', 'Modifier un backlink - Link Tracker')

@section('breadcrumb')
    <a href="{{ route('backlinks.index') }}" class="text-neutral-500 hover:text-neutral-700">Backlinks</a>
    <span class="text-neutral-400 mx-2">/</span>
    <a href="{{ route('backlinks.show', $backlink) }}" class="text-neutral-500 hover:text-neutral-700">Détails</a>
    <span class="text-neutral-400 mx-2">/</span>
    <span class="text-neutral-900 font-medium">Modifier</span>
@endsection

@section('content')
    <x-page-header title="Modifier le backlink" subtitle="Mettez à jour les informations du backlink" />

    <div class="bg-white p-8 rounded-lg border border-neutral-200 max-w-2xl">
        <form action="{{ route('backlinks.update', $backlink) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="space-y-6">
                {{-- Project Field --}}
                <div class="space-y-1">
                    <label for="project_id" class="block text-sm font-medium text-neutral-700">Projet <span class="text-danger-600">*</span></label>
                    <select id="project_id" name="project_id" required class="block w-full px-4 py-2 border border-neutral-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500">
                        <option value="">Sélectionner un projet</option>
                        @foreach($projects as $project)
                            <option value="{{ $project->id }}" {{ old('project_id', $backlink->project_id) == $project->id ? 'selected' : '' }}>
                                {{ $project->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Source URL --}}
                <x-form-input
                    label="URL Source"
                    name="source_url"
                    type="url"
                    :value="old('source_url', $backlink->source_url)"
                    required
                    helper="URL de la page contenant le backlink"
                    :error="$errors->first('source_url')"
                />

                {{-- Target URL --}}
                <x-form-input
                    label="URL Cible"
                    name="target_url"
                    type="url"
                    :value="old('target_url', $backlink->target_url)"
                    required
                    helper="URL vers laquelle pointe le backlink"
                    :error="$errors->first('target_url')"
                />

                {{-- Anchor Text --}}
                <x-form-input
                    label="Texte d'ancre"
                    name="anchor_text"
                    type="text"
                    :value="old('anchor_text', $backlink->anchor_text)"
                    helper="Le texte cliquable du lien"
                    :error="$errors->first('anchor_text')"
                />

                {{-- Rel Attributes --}}
                <x-form-input
                    label="Attributs rel"
                    name="rel_attributes"
                    type="text"
                    :value="old('rel_attributes', $backlink->rel_attributes)"
                    helper="Attributs rel du lien (ex: noopener, noreferrer)"
                    :error="$errors->first('rel_attributes')"
                />

                {{-- Dofollow Checkbox --}}
                <div class="flex items-center space-x-2">
                    <input
                        type="checkbox"
                        id="is_dofollow"
                        name="is_dofollow"
                        value="1"
                        {{ old('is_dofollow', $backlink->is_dofollow) ? 'checked' : '' }}
                        class="rounded border-neutral-300 text-brand-500 focus:ring-brand-500"
                    >
                    <label for="is_dofollow" class="text-sm text-neutral-700">Lien Dofollow</label>
                </div>

                {{-- Status Field --}}
                <div class="space-y-1">
                    <label for="status" class="block text-sm font-medium text-neutral-700">
                        Statut
                    </label>
                    <select
                        id="status"
                        name="status"
                        class="block w-full px-4 py-2 border border-neutral-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent"
                    >
                        <option value="active" {{ old('status', $backlink->status) === 'active' ? 'selected' : '' }}>Actif</option>
                        <option value="lost" {{ old('status', $backlink->status) === 'lost' ? 'selected' : '' }}>Perdu</option>
                        <option value="changed" {{ old('status', $backlink->status) === 'changed' ? 'selected' : '' }}>Modifié</option>
                    </select>
                    <p class="text-xs text-neutral-500">Le statut actuel du backlink</p>
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex items-center justify-end space-x-3 mt-8 pt-6 border-t border-neutral-200">
                <x-button variant="secondary" href="{{ route('backlinks.show', $backlink) }}">
                    Annuler
                </x-button>
                <x-button variant="primary" type="submit">
                    Mettre à jour
                </x-button>
            </div>
        </form>
    </div>
@endsection

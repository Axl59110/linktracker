@extends('layouts.app')

@section('title', 'Créer un backlink - Link Tracker')

@section('breadcrumb')
    <a href="{{ route('backlinks.index') }}" class="text-neutral-500 hover:text-neutral-700">Backlinks</a>
    <span class="text-neutral-400 mx-2">/</span>
    <span class="text-neutral-900 font-medium">Nouveau backlink</span>
@endsection

@section('content')
    <x-page-header title="Créer un backlink" subtitle="Ajoutez un nouveau backlink à surveiller" />

    <div class="bg-white p-8 rounded-lg border border-neutral-200 max-w-2xl">
        <form action="{{ route('backlinks.store') }}" method="POST">
            @csrf
            <div class="space-y-6">
                <div class="space-y-1">
                    <label for="project_id" class="block text-sm font-medium text-neutral-700">Projet <span class="text-danger-600">*</span></label>
                    <select id="project_id" name="project_id" required class="block w-full px-4 py-2 border border-neutral-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500">
                        <option value="">Sélectionner un projet</option>
                        @foreach($projects as $project)
                            <option value="{{ $project->id }}" {{ old('project_id', $selectedProjectId) == $project->id ? 'selected' : '' }}>
                                {{ $project->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <x-form-input label="URL Source" name="source_url" type="url" :value="old('source_url')" required helper="URL de la page contenant le backlink" :error="$errors->first('source_url')" />
                <x-form-input label="URL Cible" name="target_url" type="url" :value="old('target_url')" required helper="URL vers laquelle pointe le backlink" :error="$errors->first('target_url')" />
                <x-form-input label="Texte d'ancre" name="anchor_text" type="text" :value="old('anchor_text')" helper="Le texte cliquable du lien" :error="$errors->first('anchor_text')" />
                
                <div class="flex items-center space-x-2">
                    <input type="checkbox" id="is_dofollow" name="is_dofollow" value="1" {{ old('is_dofollow') ? 'checked' : '' }} class="rounded border-neutral-300 text-brand-500 focus:ring-brand-500">
                    <label for="is_dofollow" class="text-sm text-neutral-700">Lien Dofollow</label>
                </div>
            </div>

            <div class="flex items-center justify-end space-x-3 mt-8 pt-6 border-t border-neutral-200">
                <x-button variant="secondary" href="{{ route('backlinks.index') }}">Annuler</x-button>
                <x-button variant="primary" type="submit">Créer le backlink</x-button>
            </div>
        </form>
    </div>
@endsection

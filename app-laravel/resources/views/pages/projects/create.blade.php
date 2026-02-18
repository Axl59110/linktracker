@extends('layouts.app')

@section('title', 'Créer un site - Link Tracker')

@section('breadcrumb')
    <a href="{{ route('projects.index') }}" class="text-neutral-500 hover:text-neutral-700">Portfolio</a>
    <span class="text-neutral-400 mx-2">/</span>
    <span class="text-neutral-900 font-medium">Nouveau site</span>
@endsection

@section('content')
    {{-- Page Header --}}
    <x-page-header title="Créer un site" subtitle="Ajoutez un nouveau site à surveiller" />

    {{-- Form Card --}}
    <div class="bg-white p-8 rounded-lg border border-neutral-200 max-w-2xl">
        <form action="{{ route('projects.store') }}" method="POST">
            @csrf

            <div class="space-y-6">
                {{-- Name Field --}}
                <x-form-input
                    label="Nom du site"
                    name="name"
                    type="text"
                    :value="old('name')"
                    required
                    helper="Le nom de votre site (ex: Mon Site Web)"
                    :error="$errors->first('name')"
                />

                {{-- URL Field --}}
                <x-form-input
                    label="URL du site"
                    name="url"
                    type="url"
                    :value="old('url')"
                    required
                    helper="L'URL complète de votre site (ex: https://example.com)"
                    :error="$errors->first('url')"
                    placeholder="https://example.com"
                />

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
                        <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>Actif</option>
                        <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactif</option>
                    </select>
                    <p class="text-xs text-neutral-500">Le statut du site</p>
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex items-center justify-end space-x-3 mt-8 pt-6 border-t border-neutral-200">
                <x-button variant="secondary" href="{{ route('projects.index') }}">
                    Annuler
                </x-button>
                <x-button variant="primary" type="submit">
                    Créer le site
                </x-button>
            </div>
        </form>
    </div>
@endsection

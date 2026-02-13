@extends('layouts.app')

@section('title', 'Créer une plateforme - Link Tracker')

@section('breadcrumb')
    <a href="{{ route('platforms.index') }}" class="text-neutral-500 hover:text-neutral-700">Plateformes</a>
    <span class="text-neutral-400 mx-2">/</span>
    <span class="text-neutral-900 font-medium">Créer</span>
@endsection

@section('content')
    <x-page-header title="Nouvelle plateforme" subtitle="Ajoutez une nouvelle plateforme d'achat de liens" />

    <div class="bg-white p-8 rounded-lg border border-neutral-200 max-w-2xl">
        <form action="{{ route('platforms.store') }}" method="POST">
            @csrf

            <div class="space-y-6">
                {{-- Name --}}
                <x-form-input
                    label="Nom de la plateforme"
                    name="name"
                    type="text"
                    :value="old('name')"
                    required
                    helper="Ex: Getlinko, Semrush Marketplace, Contact Direct"
                    :error="$errors->first('name')"
                />

                {{-- Type --}}
                <div class="space-y-1">
                    <label for="type" class="block text-sm font-medium text-neutral-700">
                        Type <span class="text-danger-600">*</span>
                    </label>
                    <select
                        id="type"
                        name="type"
                        required
                        class="block w-full px-4 py-2 border border-neutral-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent"
                    >
                        <option value="">Sélectionner un type</option>
                        <option value="marketplace" {{ old('type') === 'marketplace' ? 'selected' : '' }}>Marketplace</option>
                        <option value="direct" {{ old('type') === 'direct' ? 'selected' : '' }}>Contact Direct</option>
                        <option value="other" {{ old('type') === 'other' ? 'selected' : '' }}>Autre</option>
                    </select>
                    @error('type')
                        <p class="text-xs text-danger-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- URL --}}
                <x-form-input
                    label="URL de la plateforme"
                    name="url"
                    type="url"
                    :value="old('url')"
                    helper="Ex: https://getlinko.com"
                    :error="$errors->first('url')"
                />

                {{-- Description --}}
                <div class="space-y-1">
                    <label for="description" class="block text-sm font-medium text-neutral-700">
                        Description
                    </label>
                    <textarea
                        id="description"
                        name="description"
                        rows="3"
                        class="block w-full px-4 py-2 border border-neutral-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent"
                        placeholder="Notes sur cette plateforme..."
                    >{{ old('description') }}</textarea>
                    @error('description')
                        <p class="text-xs text-danger-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Is Active --}}
                <div class="flex items-center space-x-2">
                    <input
                        type="checkbox"
                        id="is_active"
                        name="is_active"
                        value="1"
                        {{ old('is_active', true) ? 'checked' : '' }}
                        class="rounded border-neutral-300 text-brand-500 focus:ring-brand-500"
                    >
                    <label for="is_active" class="text-sm text-neutral-700">Plateforme active</label>
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex items-center justify-end space-x-3 mt-8 pt-6 border-t border-neutral-200">
                <x-button variant="secondary" href="{{ route('platforms.index') }}">
                    Annuler
                </x-button>
                <x-button variant="primary" type="submit">
                    Créer la plateforme
                </x-button>
            </div>
        </form>
    </div>
@endsection

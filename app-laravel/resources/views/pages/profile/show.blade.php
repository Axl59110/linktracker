@extends('layouts.app')

@section('title', 'Mon profil - Link Tracker')

@section('breadcrumb')
    <span class="text-neutral-900 font-medium">Mon profil</span>
@endsection

@section('content')
    <x-page-header title="Mon profil" subtitle="Gérer vos informations et sécurité" />

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 max-w-4xl">
        {{-- Infos utilisateur --}}
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg border border-neutral-200 p-6">
                <div class="flex flex-col items-center text-center">
                    <div class="w-16 h-16 rounded-full bg-brand-100 flex items-center justify-center mb-4">
                        <span class="text-2xl font-bold text-brand-600">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </span>
                    </div>
                    <h2 class="text-lg font-semibold text-neutral-900">{{ $user->name }}</h2>
                    <p class="text-sm text-neutral-500 mt-1">{{ $user->email }}</p>
                    <p class="text-xs text-neutral-400 mt-2">
                        Membre depuis {{ $user->created_at->format('d/m/Y') }}
                    </p>
                </div>
            </div>
        </div>

        {{-- Changement de mot de passe --}}
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg border border-neutral-200 p-6">
                <h3 class="text-sm font-semibold text-neutral-900 mb-6">Changer le mot de passe</h3>

                <form action="{{ route('profile.password') }}" method="POST" class="space-y-4">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label for="current_password" class="block text-sm font-medium text-neutral-700 mb-1">
                            Mot de passe actuel
                        </label>
                        <input
                            type="password"
                            id="current_password"
                            name="current_password"
                            class="w-full px-3 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 {{ $errors->has('current_password') ? 'border-danger-300' : 'border-neutral-300' }}"
                            autocomplete="current-password"
                        >
                        @error('current_password')
                            <p class="text-xs text-danger-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-neutral-700 mb-1">
                            Nouveau mot de passe
                        </label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="w-full px-3 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 {{ $errors->has('password') ? 'border-danger-300' : 'border-neutral-300' }}"
                            autocomplete="new-password"
                        >
                        @error('password')
                            <p class="text-xs text-danger-600 mt-1">{{ $message }}</p>
                        @enderror
                        <p class="text-xs text-neutral-400 mt-1">Minimum 8 caractères</p>
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-neutral-700 mb-1">
                            Confirmer le nouveau mot de passe
                        </label>
                        <input
                            type="password"
                            id="password_confirmation"
                            name="password_confirmation"
                            class="w-full px-3 py-2 border border-neutral-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500"
                            autocomplete="new-password"
                        >
                    </div>

                    <div class="pt-2">
                        <x-button variant="primary" type="submit">
                            Mettre à jour le mot de passe
                        </x-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

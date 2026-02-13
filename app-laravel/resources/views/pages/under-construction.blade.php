@extends('layouts.app')

@section('title', 'Page en construction - Link Tracker')

@section('breadcrumb')
    <span class="text-neutral-900 font-medium">Page en construction</span>
@endsection

@section('content')
    <div class="flex items-center justify-center min-h-[60vh]">
        <div class="text-center max-w-md">
            {{-- Icon --}}
            <div class="mb-6">
                <span class="text-6xl">🚧</span>
            </div>

            {{-- Title --}}
            <h1 class="text-2xl font-semibold text-neutral-900 mb-3">
                Page en construction
            </h1>

            {{-- Description --}}
            <p class="text-neutral-600 mb-8">
                Cette fonctionnalité est actuellement en cours de développement.
                Elle sera bientôt disponible.
            </p>

            {{-- Back button --}}
            <a
                href="{{ url('/dashboard') }}"
                class="inline-flex items-center px-4 py-2 bg-brand-500 text-white text-sm font-medium rounded-lg hover:bg-brand-600 transition-colors"
            >
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Retour au dashboard
            </a>
        </div>
    </div>
@endsection

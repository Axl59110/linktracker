{{--
    Page Header Component

    Affiche le titre de la page avec actions optionnelles.

    Usage:
    <x-page-header
        title="Mes Projets"
        subtitle="8 projets configurés">
        <x-slot:actions>
            <x-button variant="primary" href="/projects/create">
                + Créer un projet
            </x-button>
        </x-slot:actions>
    </x-page-header>
--}}

@props([
    'title' => '',
    'subtitle' => null
])

<div class="mb-8">
    <div class="flex items-center justify-between">
        {{-- Title & Subtitle --}}
        <div>
            <h1 class="text-2xl font-semibold text-neutral-900">{{ $title }}</h1>
            @if($subtitle)
                <p class="mt-1 text-sm text-neutral-500">{{ $subtitle }}</p>
            @endif
        </div>

        {{-- Actions Slot (Optional) --}}
        @if(isset($actions))
            <div class="flex items-center space-x-3">
                {{ $actions }}
            </div>
        @endif
    </div>
</div>

{{--
    Button Component

    Bouton réutilisable avec variants et sizes.

    Variants:
    - primary: Bleu (actions principales)
    - secondary: Gris (actions secondaires)
    - danger: Rouge (suppressions, actions destructrices)

    Sizes:
    - sm: Petit
    - md: Moyen (défaut)
    - lg: Grand

    Usage:
    <x-button variant="primary" href="/projects/create">
        + Créer un projet
    </x-button>

    <x-button variant="danger" type="submit">
        Supprimer
    </x-button>
--}}

@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
    'href' => null
])

@php
    $variants = [
        'primary' => 'bg-brand-500 hover:bg-brand-600 text-white border-transparent',
        'secondary' => 'bg-neutral-100 hover:bg-neutral-200 text-neutral-700 border-neutral-200',
        'danger' => 'bg-danger-600 hover:bg-danger-700 text-white border-transparent',
    ];

    $sizes = [
        'sm' => 'px-3 py-1.5 text-sm',
        'md' => 'px-4 py-2 text-sm',
        'lg' => 'px-5 py-2.5 text-base',
    ];

    $variantClass = $variants[$variant] ?? $variants['primary'];
    $sizeClass = $sizes[$size] ?? $sizes['md'];

    $baseClasses = 'inline-flex items-center justify-center font-medium rounded-lg border transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 disabled:opacity-50 disabled:cursor-not-allowed';
@endphp

@if($href)
    {{-- Render as link --}}
    <a
        href="{{ $href }}"
        {{ $attributes->merge(['class' => "{$baseClasses} {$variantClass} {$sizeClass}"]) }}
    >
        {{ $slot }}
    </a>
@else
    {{-- Render as button --}}
    <button
        type="{{ $type }}"
        {{ $attributes->merge(['class' => "{$baseClasses} {$variantClass} {$sizeClass}"]) }}
    >
        {{ $slot }}
    </button>
@endif

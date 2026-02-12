{{--
    Alert Component

    Affiche une alerte ou notification.

    Variants:
    - success: Vert
    - danger: Rouge
    - warning: Orange (TODO: Ajouter si besoin)
    - info: Bleu (TODO: Ajouter si besoin)

    Usage:
    <x-alert variant="danger">
        Une erreur est survenue lors de la suppression.
    </x-alert>

    <x-alert variant="success">
        Le projet a été créé avec succès.
    </x-alert>
--}}

@props([
    'variant' => 'info'
])

@php
    $variants = [
        'success' => 'bg-success-50 border-success-200 text-success-700',
        'danger' => 'bg-danger-50 border-danger-200 text-danger-700',
        'info' => 'bg-brand-50 border-brand-200 text-brand-700',
        // TODO: Ajouter warning si besoin (orange)
    ];

    $icons = [
        'success' => '✓',
        'danger' => '✗',
        'info' => 'ℹ',
    ];

    $classes = $variants[$variant] ?? $variants['info'];
    $icon = $icons[$variant] ?? $icons['info'];
@endphp

<div {{ $attributes->merge(['class' => "border rounded-lg p-4 {$classes}"]) }}>
    <div class="flex">
        {{-- Icon --}}
        <div class="flex-shrink-0 mr-3 text-lg">
            {{ $icon }}
        </div>

        {{-- Content --}}
        <div class="flex-1 text-sm">
            {{ $slot }}
        </div>
    </div>
</div>

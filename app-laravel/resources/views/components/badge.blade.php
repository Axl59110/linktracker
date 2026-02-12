{{--
    Badge Component

    Affiche un badge coloré selon le variant.

    Variants disponibles:
    - success: Vert (pour statut "Actif")
    - danger: Rouge (pour statut "Perdu", erreurs)
    - neutral: Gris (pour statut "Modifié", "Archivé", etc.)

    Usage:
    <x-badge variant="success">Actif</x-badge>
    <x-badge variant="danger">Perdu</x-badge>
    <x-badge variant="neutral">Modifié</x-badge>
--}}

@props([
    'variant' => 'neutral'
])

@php
    $variants = [
        'success' => 'bg-success-50 text-success-600 border-success-200',
        'danger' => 'bg-danger-50 text-danger-600 border-danger-200',
        'neutral' => 'bg-neutral-100 text-neutral-600 border-neutral-200',
    ];

    $classes = $variants[$variant] ?? $variants['neutral'];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border {$classes}"]) }}>
    {{ $slot }}
</span>

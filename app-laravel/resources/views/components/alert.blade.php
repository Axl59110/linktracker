@props([
    'variant' => 'info',
])

@php
    $variants = [
        'success' => [
            'container' => 'bg-success-50 border-success-200 text-success-900',
            'icon' => '✓',
        ],
        'danger' => [
            'container' => 'bg-danger-50 border-danger-200 text-danger-900',
            'icon' => '✕',
        ],
        'info' => [
            'container' => 'bg-neutral-100 border-neutral-200 text-neutral-900',
            'icon' => 'ℹ',
        ],
    ];

    $config = $variants[$variant] ?? $variants['info'];
@endphp

<div class="p-4 rounded-lg border {{ $config['container'] }}" role="alert">
    <div class="flex items-start">
        <span class="text-lg mr-3">{{ $config['icon'] }}</span>
        <div class="flex-1">
            {{ $slot }}
        </div>
    </div>
</div>

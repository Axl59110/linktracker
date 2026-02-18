@props([
    'variant' => 'info',
    'dismissible' => false,
    'autoDismiss' => 0, // seconds before auto-dismiss (0 = no auto-dismiss)
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
        'warning' => [
            'container' => 'bg-warning-50 border-warning-200 text-warning-900',
            'icon' => '⚠',
        ],
        'info' => [
            'container' => 'bg-neutral-100 border-neutral-200 text-neutral-900',
            'icon' => 'ℹ',
        ],
    ];

    $config = $variants[$variant] ?? $variants['info'];
@endphp

<div
    x-data="{ show: true }"
    x-show="show"
    x-init="{{ $autoDismiss > 0 ? "setTimeout(() => show = false, {$autoDismiss}000)" : '' }}"
    x-transition:leave="transition ease-in duration-300"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="p-4 rounded-lg border {{ $config['container'] }} {{ $attributes->get('class') }}"
    role="alert"
>
    <div class="flex items-start">
        <span class="text-lg mr-3">{{ $config['icon'] }}</span>
        <div class="flex-1">
            {{ $slot }}
        </div>
        @if($dismissible || $autoDismiss > 0)
        <button
            type="button"
            @click="show = false"
            class="ml-3 text-current opacity-60 hover:opacity-100 transition-opacity"
            aria-label="Fermer"
        >
            ✕
        </button>
        @endif
    </div>
</div>

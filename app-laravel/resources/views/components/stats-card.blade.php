{{--
    Stats Card Component

    Affiche une stat avec label, valeur, icône et changement optionnel.

    Usage:
    <x-stats-card
        label="Backlinks actifs"
        value="127"
        change="+12 ce mois"
        icon="✅"
    />
--}}

@props([
    'label' => '',
    'value' => '0',
    'change' => null,
    'icon' => null
])

<div {{ $attributes->merge(['class' => 'bg-white border border-neutral-200 rounded-lg p-5']) }}>
    <div class="flex items-center justify-between">
        {{-- Left: Label + Value + Change --}}
        <div class="flex-1">
            <p class="text-sm text-neutral-500">{{ $label }}</p>
            <p class="text-2xl font-semibold text-neutral-900 mt-1">{{ $value }}</p>
            @if($change)
                <p class="text-xs text-neutral-400 mt-1">{{ $change }}</p>
            @endif
        </div>

        {{-- Right: Icon (Optional) --}}
        @if($icon)
            <div class="text-3xl ml-4">{{ $icon }}</div>
        @endif
    </div>
</div>

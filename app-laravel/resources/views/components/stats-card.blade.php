@props([
    'label',
    'value',
    'change' => null,
    'icon' => null,
])

<div class="bg-white p-6 rounded-lg border border-neutral-200">
    <div class="flex items-start justify-between">
        <div class="flex-1">
            <p class="text-sm text-neutral-500 mb-1">{{ $label }}</p>
            <p class="text-3xl font-bold text-neutral-900">{{ $value }}</p>

            @if($change)
                <p class="text-xs text-neutral-400 mt-2">{{ $change }}</p>
            @endif
        </div>

        @if($icon)
            <span class="text-3xl">{{ $icon }}</span>
        @endif
    </div>
</div>

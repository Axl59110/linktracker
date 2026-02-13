@props([
    'name',
    'value',
    'label',
    'description' => '',
    'checked' => false,
    'icon' => '',
])

<label class="relative flex cursor-pointer rounded-lg border-2 p-4 transition-all duration-200 hover:border-brand-300 hover:bg-brand-50 has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50 has-[:checked]:ring-2 has-[:checked]:ring-brand-500 has-[:checked]:ring-offset-2">
    <input
        type="radio"
        name="{{ $name }}"
        value="{{ $value }}"
        {{ $checked ? 'checked' : '' }}
        class="sr-only"
        {{ $attributes->except(['class']) }}
    />

    <div class="flex items-center w-full">
        @if($icon)
            <div class="flex-shrink-0 text-2xl mr-3">
                {{ $icon }}
            </div>
        @endif

        <div class="flex-1 min-w-0">
            <span class="block text-sm font-semibold text-neutral-900">
                {{ $label }}
            </span>
            @if($description)
                <span class="block text-xs text-neutral-600 mt-0.5">
                    {{ $description }}
                </span>
            @endif
        </div>

        <div class="ml-3 flex h-5 items-center">
            <div class="flex h-5 w-5 items-center justify-center rounded-full border-2 border-neutral-300 transition-all duration-200 peer-checked:border-brand-500 peer-checked:bg-brand-500">
                <div class="h-2 w-2 rounded-full bg-white opacity-0 transition-opacity duration-200 peer-checked:opacity-100"></div>
            </div>
        </div>
    </div>

    <div class="pointer-events-none absolute inset-0 rounded-lg transition-all duration-200 opacity-0 [input:checked~&]:opacity-100">
        <div class="absolute inset-0 rounded-lg bg-gradient-to-br from-brand-500/5 to-transparent"></div>
    </div>
</label>

@props([
    'name',
    'label' => '',
    'checked' => false,
    'helper' => '',
])

<div class="flex items-center justify-between">
    <div class="flex flex-col">
        @if($label)
            <label for="{{ $name }}" class="text-sm font-medium text-neutral-900 cursor-pointer">
                {{ $label }}
            </label>
        @endif
        @if($helper)
            <p class="text-xs text-neutral-500 mt-0.5">{{ $helper }}</p>
        @endif
    </div>

    <button
        type="button"
        role="switch"
        aria-checked="{{ $checked ? 'true' : 'false' }}"
        @click="$refs.{{ $name }}.checked = !$refs.{{ $name }}.checked; $refs.{{ $name }}.dispatchEvent(new Event('change'))"
        :aria-checked="$refs.{{ $name }}.checked.toString()"
        :class="$refs.{{ $name }}.checked ? 'bg-brand-500' : 'bg-neutral-200'"
        class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2"
    >
        <span
            :class="$refs.{{ $name }}.checked ? 'translate-x-5' : 'translate-x-0'"
            class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-lg ring-0 transition duration-200 ease-in-out"
        ></span>
    </button>

    <input
        type="checkbox"
        id="{{ $name }}"
        name="{{ $name }}"
        value="1"
        x-ref="{{ $name }}"
        {{ $checked ? 'checked' : '' }}
        class="sr-only"
    />
</div>

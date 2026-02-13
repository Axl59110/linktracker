@props([
    'name',
    'label' => '',
    'options' => [],
    'selected' => null,
    'placeholder' => 'Sélectionner',
    'required' => false,
    'helper' => '',
    'error' => '',
])

<div>
    @if($label)
        <label for="{{ $name }}" class="block text-sm font-medium text-neutral-700 mb-1">
            {{ $label }}
            @if($required)
                <span class="text-danger-600">*</span>
            @endif
        </label>
    @endif

    <div class="relative">
        <select
            id="{{ $name }}"
            name="{{ $name }}"
            {{ $required ? 'required' : '' }}
            class="appearance-none w-full px-4 py-2.5 pr-10 bg-white border border-neutral-300 rounded-lg text-neutral-900 text-sm transition-all duration-200 hover:border-neutral-400 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent shadow-sm hover:shadow cursor-pointer"
            {{ $attributes->except(['class']) }}
        >
            @if($placeholder)
                <option value="">{{ $placeholder }}</option>
            @endif

            @foreach($options as $value => $label)
                <option value="{{ $value }}" {{ $selected == $value ? 'selected' : '' }}>
                    {{ $label }}
                </option>
            @endforeach
        </select>

        {{-- Chevron icon --}}
        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3">
            <svg class="w-4 h-4 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
        </div>
    </div>

    @if($helper)
        <p class="mt-1 text-xs text-neutral-500">{{ $helper }}</p>
    @endif

    @if($error)
        <p class="mt-1 text-sm text-danger-600">{{ $error }}</p>
    @endif
</div>

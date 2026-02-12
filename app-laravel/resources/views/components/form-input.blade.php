@props([
    'label',
    'name',
    'type' => 'text',
    'value' => '',
    'required' => false,
    'helper' => null,
    'error' => null,
])

<div class="space-y-1">
    <label for="{{ $name }}" class="block text-sm font-medium text-neutral-700">
        {{ $label }}
        @if($required)
            <span class="text-danger-600">*</span>
        @endif
    </label>

    <input
        type="{{ $type }}"
        id="{{ $name }}"
        name="{{ $name }}"
        value="{{ old($name, $value) }}"
        @if($required) required @endif
        class="block w-full px-4 py-2 border border-neutral-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent {{ $error ? 'border-danger-500' : '' }}"
        {{ $attributes }}
    />

    @if($helper)
        <p class="text-xs text-neutral-500">{{ $helper }}</p>
    @endif

    @if($error)
        <p class="text-xs text-danger-600">{{ $error }}</p>
    @endif
</div>

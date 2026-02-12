{{--
    Form Input Component

    Input avec label, error et helper text.

    Usage:
    <x-form-input
        name="name"
        label="Nom du projet"
        type="text"
        placeholder="Mon site web"
        helper="Le nom affiché dans la liste des projets"
        :value="old('name', $project->name ?? '')"
        :error="$errors->first('name')"
        required
    />
--}}

@props([
    'name' => '',
    'label' => '',
    'type' => 'text',
    'placeholder' => '',
    'helper' => null,
    'value' => '',
    'error' => null,
    'required' => false
])

<div {{ $attributes->merge(['class' => 'mb-4']) }}>
    {{-- Label --}}
    @if($label)
        <label for="{{ $name }}" class="block text-sm font-medium text-neutral-700 mb-1">
            {{ $label }}
            @if($required)
                <span class="text-danger-600">*</span>
            @endif
        </label>
    @endif

    {{-- Input --}}
    <input
        type="{{ $type }}"
        id="{{ $name }}"
        name="{{ $name }}"
        value="{{ $value }}"
        placeholder="{{ $placeholder }}"
        {{ $required ? 'required' : '' }}
        class="block w-full px-3 py-2 border rounded-lg shadow-sm text-sm
               transition-colors
               {{ $error ? 'border-danger-500 focus:border-danger-500 focus:ring-danger-500' : 'border-neutral-300 focus:border-brand-500 focus:ring-brand-500' }}
               focus:outline-none focus:ring-1
               disabled:bg-neutral-100 disabled:cursor-not-allowed"
    />

    {{-- Helper Text --}}
    @if($helper && !$error)
        <p class="mt-1 text-xs text-neutral-500">{{ $helper }}</p>
    @endif

    {{-- Error Message --}}
    @if($error)
        <p class="mt-1 text-sm text-danger-600">{{ $error }}</p>
    @endif
</div>

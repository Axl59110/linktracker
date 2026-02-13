@props([
    'name',
    'options' => [],
    'selected' => null,
])

<div
    x-data="{
        selected: '{{ $selected ?? $options[0]['value'] }}',
        getIndex(value) {
            return {{ json_encode(array_column($options, 'value')) }}.indexOf(value);
        }
    }"
    class="inline-flex p-1 bg-neutral-100 rounded-full relative"
>
    {{-- Sliding indicator --}}
    <div
        class="absolute top-1 bottom-1 bg-white rounded-full shadow-sm transition-all duration-300 ease-out"
        :style="`left: ${getIndex(selected) * 50 + 4}px; width: calc(50% - 8px);`"
    ></div>

    @foreach($options as $option)
        <label class="relative z-10 px-6 py-2 text-sm font-medium cursor-pointer transition-colors duration-200"
            :class="selected === '{{ $option['value'] }}' ? 'text-neutral-900' : 'text-neutral-500 hover:text-neutral-700'"
        >
            <input
                type="radio"
                name="{{ $name }}"
                value="{{ $option['value'] }}"
                class="sr-only"
                x-model="selected"
                {{ $attributes->except(['class']) }}
            />
            <span>{{ $option['label'] }}</span>
        </label>
    @endforeach
</div>

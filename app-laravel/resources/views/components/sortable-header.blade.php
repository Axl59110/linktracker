@props(['field', 'label'])

@php
    $currentSort = request('sort');
    $currentDirection = request('direction', 'desc');
    $newDirection = ($currentSort === $field && $currentDirection === 'asc') ? 'desc' : 'asc';

    $queryParams = request()->except(['sort', 'direction']);
    $queryParams['sort'] = $field;
    $queryParams['direction'] = $newDirection;

    $url = route('backlinks.index', $queryParams);
    $isActive = $currentSort === $field;
@endphp

<th {{ $attributes->merge(['class' => 'px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase']) }}>
    <a href="{{ $url }}" class="flex items-center gap-1 hover:text-neutral-700 transition-colors group">
        <span>{{ $label }}</span>
        @if($isActive)
            @if($currentDirection === 'asc')
                <svg class="w-4 h-4 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                </svg>
            @else
                <svg class="w-4 h-4 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            @endif
        @else
            <svg class="w-4 h-4 text-neutral-300 group-hover:text-neutral-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
            </svg>
        @endif
    </a>
</th>

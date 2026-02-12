@props([
    'title',
    'subtitle' => null,
])

<div class="mb-8">
    <div class="flex items-start justify-between">
        <div class="flex-1">
            <h1 class="text-2xl font-bold text-neutral-900 mb-1">{{ $title }}</h1>
            @if($subtitle)
                <p class="text-sm text-neutral-500">{{ $subtitle }}</p>
            @endif
        </div>

        @if(isset($actions))
            <div class="flex items-center space-x-3 ml-4">
                {{ $actions }}
            </div>
        @endif
    </div>
</div>

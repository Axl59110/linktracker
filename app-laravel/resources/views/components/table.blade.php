@props([
    'header' => null,
    'body' => null,
])

<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-neutral-200">
        @if($header)
            <thead class="bg-neutral-50">
                {{ $header }}
            </thead>
        @endif

        @if($body)
            <tbody class="bg-white divide-y divide-neutral-200">
                {{ $body }}
            </tbody>
        @endif
    </table>
</div>

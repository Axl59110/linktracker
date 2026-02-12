{{--
    Table Component

    Table responsive avec header et body slots.

    Usage:
    <x-table>
        <x-slot:header>
            <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase">Nom</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase">Status</th>
        </x-slot:header>

        <x-slot:body>
            <tr class="hover:bg-neutral-50">
                <td class="px-6 py-4 text-sm text-neutral-900">Projet 1</td>
                <td class="px-6 py-4 text-sm"><x-badge variant="success">Actif</x-badge></td>
            </tr>
        </x-slot:body>
    </x-table>
--}}

<div {{ $attributes->merge(['class' => 'bg-white border border-neutral-200 rounded-lg overflow-hidden']) }}>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-neutral-200">
            @if(isset($header))
                <thead>
                    <tr class="bg-neutral-50">
                        {{ $header }}
                    </tr>
                </thead>
            @endif

            @if(isset($body))
                <tbody class="divide-y divide-neutral-200 bg-white">
                    {{ $body }}
                </tbody>
            @endif
        </table>
    </div>
</div>

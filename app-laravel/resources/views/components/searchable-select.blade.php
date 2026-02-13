@props([
    'name',
    'label' => '',
    'placeholder' => 'Rechercher...',
    'items' => [],
    'selected' => null,
    'required' => false,
    'helper' => '',
    'error' => '',
])

<div x-data="{
    open: false,
    search: '',
    selected: '{{ $selected }}',
    selectedLabel: '{{ $selected ? collect($items)->firstWhere('value', $selected)['label'] ?? '' : '' }}',
    get filteredItems() {
        if (!this.search) return {{ json_encode($items) }};
        const searchLower = this.search.toLowerCase();
        return {{ json_encode($items) }}.filter(item =>
            item.label.toLowerCase().includes(searchLower) ||
            (item.badge && item.badge.toLowerCase().includes(searchLower))
        );
    },
    selectItem(value, label) {
        this.selected = value;
        this.selectedLabel = label;
        this.open = false;
        this.search = '';
        this.$refs.input.value = value;
    }
}"
@click.away="open = false"
class="relative">
    @if($label)
        <label class="block text-sm font-medium text-neutral-700 mb-1">
            {{ $label }}
            @if($required)
                <span class="text-danger-600">*</span>
            @endif
        </label>
    @endif

    {{-- Hidden input for form submission --}}
    <input
        type="hidden"
        name="{{ $name }}"
        x-ref="input"
        :value="selected"
    />

    {{-- Trigger button --}}
    <button
        type="button"
        @click="open = !open"
        class="w-full px-4 py-2.5 bg-white border border-neutral-300 rounded-lg text-left flex items-center justify-between transition-all duration-200 hover:border-neutral-400 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent shadow-sm hover:shadow"
        :class="open ? 'ring-2 ring-brand-500 border-transparent shadow' : ''"
    >
        <span class="block truncate" :class="!selected ? 'text-neutral-400' : 'text-neutral-900'">
            <span x-show="!selected">Sélectionner un lien parent</span>
            <span x-show="selected" x-text="selectedLabel"></span>
        </span>
        <svg
            class="w-4 h-4 text-neutral-400 transition-transform duration-200"
            :class="open ? 'rotate-180' : ''"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
        >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
        </svg>
    </button>

    {{-- Dropdown --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute z-50 w-full mt-2 bg-white border border-neutral-200 rounded-lg shadow-xl overflow-hidden"
        x-cloak
    >
        {{-- Search input --}}
        <div class="p-3 border-b border-neutral-100 bg-neutral-50">
            <div class="relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <input
                    type="text"
                    x-model="search"
                    placeholder="{{ $placeholder }}"
                    class="w-full pl-10 pr-4 py-2 text-sm border border-neutral-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent"
                    @click.stop
                />
            </div>
        </div>

        {{-- Results list --}}
        <div class="max-h-64 overflow-y-auto overscroll-contain">
            <template x-if="filteredItems.length === 0">
                <div class="px-4 py-8 text-center text-sm text-neutral-500">
                    <svg class="w-12 h-12 mx-auto mb-2 text-neutral-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p>Aucun résultat trouvé</p>
                </div>
            </template>

            <template x-for="item in filteredItems" :key="item.value">
                <button
                    type="button"
                    @click="selectItem(item.value, item.label)"
                    class="w-full px-4 py-3 text-left hover:bg-neutral-50 transition-colors duration-150 border-b border-neutral-100 last:border-0"
                    :class="selected === item.value ? 'bg-brand-50' : ''"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-neutral-900 truncate" x-text="item.label"></p>
                            <template x-if="item.badge">
                                <span class="inline-flex items-center mt-1 px-2 py-0.5 text-xs font-medium bg-neutral-100 text-neutral-600 rounded">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                                    </svg>
                                    <span x-text="item.badge"></span>
                                </span>
                            </template>
                        </div>
                        <svg
                            x-show="selected === item.value"
                            class="w-5 h-5 text-brand-500 flex-shrink-0"
                            fill="currentColor"
                            viewBox="0 0 20 20"
                        >
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                </button>
            </template>
        </div>
    </div>

    @if($helper)
        <p class="mt-1 text-xs text-neutral-500">{{ $helper }}</p>
    @endif

    @if($error)
        <p class="mt-1 text-sm text-danger-600">{{ $error }}</p>
    @endif
</div>

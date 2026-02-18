@props([
    'action',
    'method' => 'POST',
    'backlink' => null,
    'projects',
    'platforms',
    'tier1Backlinks',
    'selectedProjectId' => null,
    'submitText' => 'Créer le backlink',
    'cancelRoute' => null
])

@php
    $isEdit = $backlink !== null;
    $tierLevel = old('tier_level', $backlink?->tier_level ?? 'tier1');
    $spotType = old('spot_type', $backlink?->spot_type ?? 'external');
    $platformId = old('platform_id', $backlink?->platform_id ?? '');
    $invoicePaid = old('invoice_paid', $backlink?->invoice_paid ?? false);
@endphp

<form action="{{ $action }}" method="POST" x-data="{
    tierLevel: '{{ $tierLevel }}',
    spotType: '{{ $spotType }}',
    platformId: '{{ $platformId }}',
    invoicePaid: {{ $invoicePaid ? 'true' : 'false' }},
    search: ''
}">
    @csrf
    @if($method !== 'POST')
        @method($method)
    @endif

    {{-- Section 0: Type de lien (en premier) --}}
    <div class="mb-8">
        <h3 class="text-lg font-semibold text-neutral-900 mb-4 pb-2 border-b border-neutral-200">
            Type de lien
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Niveau de lien --}}
            <div>
                <label for="tier_level" class="block text-sm font-medium text-neutral-700 mb-1">
                    Niveau de lien <span class="text-danger-600">*</span>
                </label>
                <select
                    id="tier_level"
                    name="tier_level"
                    x-model="tierLevel"
                    required
                    class="block w-full px-4 py-2 border border-neutral-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500"
                >
                    <option value="tier1">Tier 1 - Lien vers site</option>
                    <option value="tier2">Tier 2 - Lien vers autre backlink</option>
                </select>
                @error('tier_level')
                    <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Type de réseau --}}
            <div>
                <label for="spot_type" class="block text-sm font-medium text-neutral-700 mb-1">
                    Type de réseau <span class="text-danger-600">*</span>
                </label>
                <select
                    id="spot_type"
                    name="spot_type"
                    x-model="spotType"
                    required
                    class="block w-full px-4 py-2 border border-neutral-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500"
                >
                    <option value="external">Externe (Site tiers)</option>
                    <option value="internal">Interne (PBN)</option>
                </select>
                @error('spot_type')
                    <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    {{-- Section 1: Informations principales --}}
    <div class="mb-8">
        <h3 class="text-lg font-semibold text-neutral-900 mb-4 pb-2 border-b border-neutral-200">
            Informations principales
        </h3>
        <div class="grid grid-cols-1 gap-6">
            {{-- Site --}}
            <div>
                <label for="project_id" class="block text-sm font-medium text-neutral-700 mb-1">
                    Site <span class="text-danger-600">*</span>
                </label>
                <select
                    id="project_id"
                    name="project_id"
                    required
                    class="block w-full px-4 py-2 border border-neutral-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500 @error('project_id') border-danger-500 @enderror"
                >
                    <option value="">Sélectionner un site</option>
                    @foreach($projects as $project)
                        <option value="{{ $project->id }}" {{ old('project_id', $backlink?->project_id ?? $selectedProjectId) == $project->id ? 'selected' : '' }}>
                            {{ $project->name }}
                        </option>
                    @endforeach
                </select>
                @error('project_id')
                    <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Parent Backlink (visible seulement si Tier 2) --}}
            <div x-show="tierLevel === 'tier2'" x-transition x-cloak>
                <label for="parent_backlink_id" class="block text-sm font-medium text-neutral-700 mb-1">
                    Lien parent (Tier 1) <span class="text-danger-600">*</span>
                </label>

                {{-- Champ de recherche --}}
                <input
                    type="text"
                    x-model="search"
                    placeholder="Filtrer par site ou URL..."
                    class="block w-full px-4 py-2 mb-2 border border-neutral-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500 text-sm"
                />

                {{-- Select avec filtres --}}
                <select
                    id="parent_backlink_id"
                    name="parent_backlink_id"
                    class="block w-full px-4 py-2 border border-neutral-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500"
                    size="8"
                >
                    <option value="">-- Sélectionner un lien parent --</option>
                    @foreach($tier1Backlinks as $tier1)
                        <option
                            value="{{ $tier1->id }}"
                            {{ old('parent_backlink_id', $backlink?->parent_backlink_id) == $tier1->id ? 'selected' : '' }}
                            x-show="search === '' || '{{ strtolower($tier1->source_url) }} {{ strtolower($tier1->project->name ?? '') }}'.includes(search.toLowerCase())"
                        >
                            {{ $tier1->project->name ?? 'Sans site' }} - {{ Str::limit($tier1->source_url, 80) }}
                        </option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-neutral-500">Utilisez le champ de recherche pour filtrer par site ou URL</p>
                @error('parent_backlink_id')
                    <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- URL Source --}}
            <div>
                <x-form-input
                    label="URL Source"
                    name="source_url"
                    type="url"
                    :value="old('source_url', $backlink?->source_url)"
                    required
                    helper="URL de la page contenant le backlink"
                    :error="$errors->first('source_url')"
                />
            </div>

            {{-- URL Cible --}}
            <div>
                <x-form-input
                    label="URL Cible"
                    name="target_url"
                    type="url"
                    :value="old('target_url', $backlink?->target_url)"
                    required
                    helper="URL vers laquelle pointe le backlink"
                    :error="$errors->first('target_url')"
                />
            </div>

            {{-- Texte d'ancre --}}
            <div>
                <x-form-input
                    label="Texte d'ancre"
                    name="anchor_text"
                    type="text"
                    :value="old('anchor_text', $backlink?->anchor_text)"
                    helper="Le texte cliquable du lien. Si non renseigné, il sera récupéré automatiquement lors de l'export de la page."
                    :error="$errors->first('anchor_text')"
                />
            </div>

            {{-- Statut (uniquement en édition) --}}
            @if($isEdit)
                <div>
                    <label for="status" class="block text-sm font-medium text-neutral-700 mb-1">
                        Statut <span class="text-danger-600">*</span>
                    </label>
                    <select
                        id="status"
                        name="status"
                        class="block w-full px-4 py-2 border border-neutral-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500"
                    >
                        <option value="active" {{ old('status', $backlink->status) === 'active' ? 'selected' : '' }}>Actif</option>
                        <option value="lost" {{ old('status', $backlink->status) === 'lost' ? 'selected' : '' }}>Perdu</option>
                        <option value="changed" {{ old('status', $backlink->status) === 'changed' ? 'selected' : '' }}>Modifié</option>
                    </select>
                    @error('status')
                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                    @enderror
                </div>
            @endif
        </div>
    </div>

    {{-- Section 2: Dates de publication --}}
    <div class="mb-8">
        <h3 class="text-lg font-semibold text-neutral-900 mb-4 pb-2 border-b border-neutral-200">
            Dates de publication
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Date de publication --}}
            <div>
                <x-form-input
                    label="Date de publication"
                    name="published_at"
                    type="date"
                    :value="old('published_at', $backlink?->published_at?->format('Y-m-d'))"
                    helper="Date de mise en ligne du backlink"
                    :error="$errors->first('published_at')"
                />
            </div>

            {{-- Date d'expiration --}}
            <div>
                <x-form-input
                    label="Date d'expiration"
                    name="expires_at"
                    type="date"
                    :value="old('expires_at', $backlink?->expires_at?->format('Y-m-d'))"
                    helper="Date de fin de validité (si applicable)"
                    :error="$errors->first('expires_at')"
                />
            </div>
        </div>
    </div>

    {{-- Section 3: Informations financières (visible seulement si Externe) --}}
    <div class="mb-8" x-show="spotType === 'external'" x-transition x-cloak>
        <h3 class="text-lg font-semibold text-neutral-900 mb-4 pb-2 border-b border-neutral-200">
            Informations financières
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            {{-- Prix --}}
            <div>
                <x-form-input
                    label="Prix"
                    name="price"
                    type="number"
                    step="0.01"
                    min="0"
                    :value="old('price', $backlink?->price)"
                    helper="Coût du backlink"
                    :error="$errors->first('price')"
                />
            </div>

            {{-- Devise --}}
            <div>
                <label for="currency" class="block text-sm font-medium text-neutral-700 mb-1">
                    Devise
                </label>
                <select
                    id="currency"
                    name="currency"
                    class="block w-full px-4 py-2 border border-neutral-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500"
                >
                    <option value="">Sélectionner</option>
                    <option value="EUR" {{ old('currency', $backlink?->currency) === 'EUR' ? 'selected' : '' }}>EUR (€)</option>
                    <option value="USD" {{ old('currency', $backlink?->currency) === 'USD' ? 'selected' : '' }}>USD ($)</option>
                    <option value="GBP" {{ old('currency', $backlink?->currency) === 'GBP' ? 'selected' : '' }}>GBP (£)</option>
                    <option value="CAD" {{ old('currency', $backlink?->currency) === 'CAD' ? 'selected' : '' }}>CAD ($)</option>
                    <option value="BRL" {{ old('currency', $backlink?->currency) === 'BRL' ? 'selected' : '' }}>BRL (R$)</option>
                    <option value="MXN" {{ old('currency', $backlink?->currency) === 'MXN' ? 'selected' : '' }}>MXN ($)</option>
                    <option value="ARS" {{ old('currency', $backlink?->currency) === 'ARS' ? 'selected' : '' }}>ARS ($)</option>
                    <option value="COP" {{ old('currency', $backlink?->currency) === 'COP' ? 'selected' : '' }}>COP ($)</option>
                    <option value="CLP" {{ old('currency', $backlink?->currency) === 'CLP' ? 'selected' : '' }}>CLP ($)</option>
                    <option value="PEN" {{ old('currency', $backlink?->currency) === 'PEN' ? 'selected' : '' }}>PEN (S/)</option>
                </select>
                @error('currency')
                    <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Facture payée (Toggle) --}}
            <div class="pt-7">
                <x-toggle-switch
                    name="invoice_paid"
                    label="Facture payée"
                    :checked="old('invoice_paid', $backlink?->invoice_paid ?? false)"
                />
            </div>
        </div>
    </div>

    {{-- Section 4: Plateforme et contact (visible seulement si Externe) --}}
    <div class="mb-8" x-show="spotType === 'external'" x-transition x-cloak>
        <h3 class="text-lg font-semibold text-neutral-900 mb-4 pb-2 border-b border-neutral-200">
            Plateforme et contact
        </h3>
        <div class="grid grid-cols-1 gap-6">
            {{-- Plateforme --}}
            <div>
                <label for="platform_id" class="block text-sm font-medium text-neutral-700 mb-1">
                    Plateforme d'achat
                </label>
                <select
                    id="platform_id"
                    name="platform_id"
                    x-model="platformId"
                    class="block w-full px-4 py-2 border border-neutral-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500"
                >
                    <option value="">Aucune plateforme (contact direct)</option>
                    @foreach($platforms as $platform)
                        <option value="{{ $platform->id }}" {{ old('platform_id', $backlink?->platform_id) == $platform->id ? 'selected' : '' }}>
                            {{ $platform->name }} ({{ ucfirst($platform->type) }})
                        </option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-neutral-500">
                    <a href="{{ route('platforms.create') }}" class="text-brand-500 hover:text-brand-600" target="_blank">
                        + Créer une nouvelle plateforme
                    </a>
                </p>
                @error('platform_id')
                    <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Champs contact conditionnels --}}
            <div x-show="platformId === ''" x-transition x-cloak>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 p-4 bg-neutral-50 rounded-lg border border-neutral-200">
                    <div>
                        <label for="contact_name" class="block text-sm font-medium text-neutral-700 mb-1">
                            Nom du contact <span class="text-danger-600">*</span>
                        </label>
                        <input
                            type="text"
                            id="contact_name"
                            name="contact_name"
                            value="{{ old('contact_name', $backlink?->contact_name) }}"
                            class="block w-full px-4 py-2 border border-neutral-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500 @error('contact_name') border-danger-500 @enderror"
                            placeholder="Jean Dupont"
                        />
                        <p class="mt-1 text-xs text-neutral-500">Nom de la personne à contacter</p>
                        @error('contact_name')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="contact_email" class="block text-sm font-medium text-neutral-700 mb-1">
                            Email du contact <span class="text-danger-600">*</span>
                        </label>
                        <input
                            type="email"
                            id="contact_email"
                            name="contact_email"
                            value="{{ old('contact_email', $backlink?->contact_email) }}"
                            class="block w-full px-4 py-2 border border-neutral-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500 @error('contact_email') border-danger-500 @enderror"
                            placeholder="contact@example.com"
                        />
                        <p class="mt-1 text-xs text-neutral-500">Email pour générer des messages automatiques</p>
                        @error('contact_email')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <div x-show="platformId !== ''" x-transition x-cloak>
                <label for="contact_info" class="block text-sm font-medium text-neutral-700 mb-1">
                    Informations de contact (optionnel)
                </label>
                <textarea
                    id="contact_info"
                    name="contact_info"
                    rows="3"
                    class="block w-full px-4 py-2 border border-neutral-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500"
                    placeholder="Nom du contact, email, téléphone, notes..."
                >{{ old('contact_info', $backlink?->contact_info) }}</textarea>
                <p class="mt-1 text-xs text-neutral-500">Informations supplémentaires sur le contact de la plateforme</p>
                @error('contact_info')
                    <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    {{-- Boutons d'action --}}
    <div class="flex items-center justify-end space-x-3 pt-6 border-t border-neutral-200">
        <x-button variant="secondary" href="{{ $cancelRoute ?? route('backlinks.index') }}">Annuler</x-button>
        <x-button variant="primary" type="submit">{{ $submitText }}</x-button>
    </div>
</form>

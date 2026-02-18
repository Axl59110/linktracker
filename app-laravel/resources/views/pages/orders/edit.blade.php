@extends('layouts.app')

@section('title', 'Modifier commande #{{ $order->id }} - Link Tracker')

@section('breadcrumb')
    <a href="{{ route('orders.index') }}" class="text-brand-500 hover:text-brand-600">Commandes</a>
    <span class="mx-2 text-neutral-400">/</span>
    <a href="{{ route('orders.show', $order) }}" class="text-brand-500 hover:text-brand-600">Commande #{{ $order->id }}</a>
    <span class="mx-2 text-neutral-400">/</span>
    <span class="text-neutral-900 font-medium">Modifier</span>
@endsection

@section('content')
    <x-page-header title="Modifier commande #{{ $order->id }}" subtitle="{{ $order->project->name }}" />

    <div class="max-w-2xl">
        <div class="bg-white rounded-lg border border-neutral-200 p-6">
            @if($errors->any())
                <x-alert variant="danger" class="mb-6">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </x-alert>
            @endif

            <form action="{{ route('orders.update', $order) }}" method="POST" class="space-y-5">
                @csrf
                @method('PUT')

                <div>
                    <label for="project_id" class="block text-sm font-medium text-neutral-700 mb-1">
                        Site <span class="text-red-500">*</span>
                    </label>
                    <select id="project_id" name="project_id" required
                        class="block w-full px-4 py-2 border border-neutral-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500">
                        @foreach($projects as $project)
                            <option value="{{ $project->id }}" {{ old('project_id', $order->project_id) == $project->id ? 'selected' : '' }}>
                                {{ $project->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="target_url" class="block text-sm font-medium text-neutral-700 mb-1">
                        URL cible <span class="text-red-500">*</span>
                    </label>
                    <input type="url" id="target_url" name="target_url"
                        value="{{ old('target_url', $order->target_url) }}" required
                        class="block w-full px-4 py-2 border border-neutral-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500" />
                </div>

                <div>
                    <label for="source_url" class="block text-sm font-medium text-neutral-700 mb-1">URL source</label>
                    <input type="url" id="source_url" name="source_url"
                        value="{{ old('source_url', $order->source_url) }}"
                        class="block w-full px-4 py-2 border border-neutral-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500" />
                </div>

                <div>
                    <label for="anchor_text" class="block text-sm font-medium text-neutral-700 mb-1">Texte d'ancre</label>
                    <input type="text" id="anchor_text" name="anchor_text"
                        value="{{ old('anchor_text', $order->anchor_text) }}"
                        class="block w-full px-4 py-2 border border-neutral-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="tier_level" class="block text-sm font-medium text-neutral-700 mb-1">Niveau</label>
                        <select id="tier_level" name="tier_level" required
                            class="block w-full px-4 py-2 border border-neutral-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500">
                            <option value="tier1" {{ old('tier_level', $order->tier_level) === 'tier1' ? 'selected' : '' }}>Tier 1</option>
                            <option value="tier2" {{ old('tier_level', $order->tier_level) === 'tier2' ? 'selected' : '' }}>Tier 2</option>
                        </select>
                    </div>
                    <div>
                        <label for="spot_type" class="block text-sm font-medium text-neutral-700 mb-1">Type</label>
                        <select id="spot_type" name="spot_type" required
                            class="block w-full px-4 py-2 border border-neutral-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500">
                            <option value="external" {{ old('spot_type', $order->spot_type) === 'external' ? 'selected' : '' }}>Externe</option>
                            <option value="internal" {{ old('spot_type', $order->spot_type) === 'internal' ? 'selected' : '' }}>Interne (PBN)</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label for="platform_id" class="block text-sm font-medium text-neutral-700 mb-1">Plateforme</label>
                    <select id="platform_id" name="platform_id"
                        class="block w-full px-4 py-2 border border-neutral-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500">
                        <option value="">Aucune</option>
                        @foreach($platforms as $platform)
                            <option value="{{ $platform->id }}" {{ old('platform_id', $order->platform_id) == $platform->id ? 'selected' : '' }}>
                                {{ $platform->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="price" class="block text-sm font-medium text-neutral-700 mb-1">Prix</label>
                        <input type="number" id="price" name="price"
                            value="{{ old('price', $order->price) }}" step="0.01" min="0"
                            class="block w-full px-4 py-2 border border-neutral-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500" />
                    </div>
                    <div>
                        <label for="currency" class="block text-sm font-medium text-neutral-700 mb-1">Devise</label>
                        <select id="currency" name="currency"
                            class="block w-full px-4 py-2 border border-neutral-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500">
                            <option value="EUR" {{ old('currency', $order->currency) === 'EUR' ? 'selected' : '' }}>EUR</option>
                            <option value="USD" {{ old('currency', $order->currency) === 'USD' ? 'selected' : '' }}>USD</option>
                            <option value="GBP" {{ old('currency', $order->currency) === 'GBP' ? 'selected' : '' }}>GBP</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label for="ordered_at" class="block text-sm font-medium text-neutral-700 mb-1">Commandé le</label>
                        <input type="date" id="ordered_at" name="ordered_at"
                            value="{{ old('ordered_at', $order->ordered_at?->format('Y-m-d')) }}"
                            class="block w-full px-4 py-2 border border-neutral-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500" />
                    </div>
                    <div>
                        <label for="expected_at" class="block text-sm font-medium text-neutral-700 mb-1">Prévu le</label>
                        <input type="date" id="expected_at" name="expected_at"
                            value="{{ old('expected_at', $order->expected_at?->format('Y-m-d')) }}"
                            class="block w-full px-4 py-2 border border-neutral-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500" />
                    </div>
                    <div>
                        <label for="published_at" class="block text-sm font-medium text-neutral-700 mb-1">Publié le</label>
                        <input type="date" id="published_at" name="published_at"
                            value="{{ old('published_at', $order->published_at?->format('Y-m-d')) }}"
                            class="block w-full px-4 py-2 border border-neutral-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500" />
                    </div>
                </div>

                <div>
                    <label for="notes" class="block text-sm font-medium text-neutral-700 mb-1">Notes</label>
                    <textarea id="notes" name="notes" rows="3"
                        class="block w-full px-4 py-2 border border-neutral-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500">{{ old('notes', $order->notes) }}</textarea>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <x-button variant="primary" type="submit">Enregistrer</x-button>
                    <x-button variant="secondary" href="{{ route('orders.show', $order) }}">Annuler</x-button>
                </div>
            </form>
        </div>
    </div>
@endsection

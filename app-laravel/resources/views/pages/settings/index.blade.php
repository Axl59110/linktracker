@extends('layouts.app')

@section('title', 'Paramètres - Link Tracker')

@section('breadcrumb')
    <span class="text-neutral-900 font-medium">Paramètres</span>
@endsection

@section('content')
    @if(session('success'))
        <x-alert variant="success" class="mb-6">{{ session('success') }}</x-alert>
    @endif

    @if(session('error'))
        <x-alert variant="danger" class="mb-6">{{ session('error') }}</x-alert>
    @endif

    <x-page-header title="Paramètres" subtitle="Configurez tous les aspects de votre instance LinkTracker" />

    {{-- Navigation par onglets --}}
    <div x-data="{ activeTab: '{{ request('tab', 'monitoring') }}' }" class="space-y-6">

        <div class="border-b border-neutral-200">
            <nav class="-mb-px flex space-x-8" aria-label="Onglets">
                <button @click="activeTab = 'monitoring'"
                    :class="activeTab === 'monitoring' ? 'border-brand-500 text-brand-600' : 'border-transparent text-neutral-500 hover:text-neutral-700 hover:border-neutral-300'"
                    class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm transition-colors">
                    Monitoring
                </button>
                <button @click="activeTab = 'seo'"
                    :class="activeTab === 'seo' ? 'border-brand-500 text-brand-600' : 'border-transparent text-neutral-500 hover:text-neutral-700 hover:border-neutral-300'"
                    class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm transition-colors">
                    APIs SEO
                </button>
                <a href="{{ route('settings.webhook') }}"
                    class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm border-transparent text-neutral-500 hover:text-neutral-700 hover:border-neutral-300 transition-colors">
                    Webhook
                </a>
                <button @click="activeTab = 'account'"
                    :class="activeTab === 'account' ? 'border-brand-500 text-brand-600' : 'border-transparent text-neutral-500 hover:text-neutral-700 hover:border-neutral-300'"
                    class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm transition-colors">
                    Compte
                </button>
            </nav>
        </div>

        {{-- Onglet Monitoring --}}
        <div x-show="activeTab === 'monitoring'" x-cloak>
            <div class="bg-white rounded-lg border border-neutral-200 p-6 max-w-2xl">
                <h2 class="text-base font-semibold text-neutral-900 mb-6">Paramètres de monitoring</h2>

                <form method="POST" action="{{ route('settings.monitoring') }}">
                    @csrf
                    @method('PATCH')

                    <div class="space-y-5">
                        {{-- Fréquence de vérification --}}
                        <div>
                            <label class="block text-sm font-medium text-neutral-700 mb-2">
                                Fréquence de vérification
                            </label>
                            <div class="space-y-2">
                                @foreach(['hourly' => 'Toutes les heures', 'daily' => 'Quotidienne (recommandé)', 'weekly' => 'Hebdomadaire'] as $value => $label)
                                    <label class="flex items-center gap-3 cursor-pointer">
                                        <input type="radio" name="check_frequency" value="{{ $value }}"
                                            {{ $user->check_frequency === $value ? 'checked' : '' }}
                                            class="text-brand-600 focus:ring-brand-500">
                                        <span class="text-sm text-neutral-700">{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        {{-- Timeout HTTP --}}
                        <div>
                            <label for="http_timeout" class="block text-sm font-medium text-neutral-700 mb-1">
                                Timeout HTTP (secondes)
                            </label>
                            <input type="number" id="http_timeout" name="http_timeout"
                                value="{{ old('http_timeout', $user->http_timeout ?? 30) }}"
                                min="5" max="120"
                                class="block w-32 px-3 py-2 border border-neutral-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500 text-sm">
                            <p class="mt-1 text-xs text-neutral-500">Entre 5 et 120 secondes. Défaut : 30s.</p>
                        </div>

                        {{-- Notifications email --}}
                        <div class="flex items-center gap-3">
                            <input type="hidden" name="email_alerts_enabled" value="0">
                            <input type="checkbox" id="email_alerts_enabled" name="email_alerts_enabled" value="1"
                                {{ ($user->email_alerts_enabled ?? true) ? 'checked' : '' }}
                                class="rounded text-brand-600 focus:ring-brand-500">
                            <label for="email_alerts_enabled" class="text-sm text-neutral-700">
                                Recevoir les emails pour les alertes critiques
                            </label>
                        </div>
                    </div>

                    <div class="mt-6">
                        <x-button type="submit" variant="primary">
                            Sauvegarder
                        </x-button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Onglet APIs SEO --}}
        <div x-show="activeTab === 'seo'" x-cloak>
            <div class="bg-white rounded-lg border border-neutral-200 p-6 max-w-2xl" x-data="seoSettingsForm()">
                <h2 class="text-base font-semibold text-neutral-900 mb-2">Configuration des APIs SEO</h2>
                <p class="text-sm text-neutral-500 mb-6">
                    Connectez une API SEO pour enrichir vos backlinks avec des métriques de qualité (DA, Spam Score…).
                </p>

                <form method="POST" action="{{ route('settings.seo') }}">
                    @csrf
                    @method('PATCH')

                    <div class="space-y-5">
                        {{-- Sélection du provider --}}
                        <div>
                            <label class="block text-sm font-medium text-neutral-700 mb-2">Provider SEO</label>
                            <div class="space-y-2">
                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input type="radio" name="seo_provider" value="custom"
                                        x-model="provider"
                                        {{ ($user->seo_provider ?? 'custom') === 'custom' ? 'checked' : '' }}
                                        class="text-brand-600 focus:ring-brand-500">
                                    <div>
                                        <span class="text-sm font-medium text-neutral-700">Aucun (mode gratuit)</span>
                                        <p class="text-xs text-neutral-500">Les métriques ne seront pas disponibles.</p>
                                    </div>
                                </label>
                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input type="radio" name="seo_provider" value="moz"
                                        x-model="provider"
                                        {{ ($user->seo_provider ?? 'custom') === 'moz' ? 'checked' : '' }}
                                        class="text-brand-600 focus:ring-brand-500">
                                    <div>
                                        <span class="text-sm font-medium text-neutral-700">Moz API v2</span>
                                        <p class="text-xs text-neutral-500">Domain Authority, Spam Score.</p>
                                    </div>
                                </label>
                            </div>
                        </div>

                        {{-- Clé API (visible seulement si Moz sélectionné) --}}
                        <div x-show="provider === 'moz'" x-transition>
                            <label for="seo_api_key" class="block text-sm font-medium text-neutral-700 mb-1">
                                Clé API Moz (format: accessId:secretKey)
                            </label>
                            <div class="flex gap-2">
                                <input :type="showKey ? 'text' : 'password'"
                                    id="seo_api_key" name="seo_api_key"
                                    placeholder="{{ $user->seo_api_key_encrypted ? '••••••••••••••••' : 'mozscape-XXXX:XXXX' }}"
                                    class="flex-1 px-3 py-2 border border-neutral-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500 text-sm font-mono">
                                <button type="button" @click="showKey = !showKey"
                                    class="px-3 py-2 border border-neutral-300 rounded-lg text-neutral-500 hover:text-neutral-700 hover:bg-neutral-50 text-sm">
                                    <span x-text="showKey ? 'Masquer' : 'Afficher'"></span>
                                </button>
                            </div>
                            <p class="mt-1 text-xs text-neutral-500">
                                Trouvez vos clés sur
                                <a href="https://moz.com/api" target="_blank" class="text-brand-600 hover:underline">moz.com/api</a>.
                                Laissez vide pour conserver la clé actuelle.
                            </p>
                        </div>
                    </div>

                    <div class="mt-6 flex items-center gap-3">
                        <x-button type="submit" variant="primary">Sauvegarder</x-button>

                        <button type="button" @click="testConnection()"
                            x-show="provider === 'moz'"
                            class="px-4 py-2 border border-neutral-300 rounded-lg text-sm text-neutral-700 hover:bg-neutral-50 transition-colors">
                            Tester la connexion
                        </button>
                    </div>

                    {{-- Résultat du test --}}
                    <div x-show="testResult" x-transition class="mt-4 p-3 rounded-lg text-sm"
                        :class="testSuccess ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200'">
                        <span x-text="testResult"></span>
                    </div>
                </form>
            </div>
        </div>

        {{-- Onglet Compte --}}
        <div x-show="activeTab === 'account'" x-cloak>
            <div class="bg-white rounded-lg border border-neutral-200 p-6 max-w-2xl">
                <h2 class="text-base font-semibold text-neutral-900 mb-6">Informations du compte</h2>

                <div class="space-y-4 text-sm">
                    <div class="flex items-center gap-3">
                        <span class="text-neutral-500 w-24">Nom</span>
                        <span class="font-medium text-neutral-900">{{ $user->name }}</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-neutral-500 w-24">Email</span>
                        <span class="font-medium text-neutral-900">{{ $user->email }}</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-neutral-500 w-24">Membre depuis</span>
                        <span class="font-medium text-neutral-900">{{ $user->created_at->format('d/m/Y') }}</span>
                    </div>
                </div>

                <div class="mt-6 pt-6 border-t border-neutral-200">
                    <form method="POST" action="/api/v1/auth/logout">
                        @csrf
                        <x-button type="submit" variant="secondary">Se déconnecter</x-button>
                    </form>
                </div>
            </div>
        </div>

    </div>

@endsection

@push('scripts')
<script>
function seoSettingsForm() {
    return {
        provider: '{{ $user->seo_provider ?? 'custom' }}',
        showKey: false,
        testResult: null,
        testSuccess: false,
        async testConnection() {
            this.testResult = 'Test en cours…';
            this.testSuccess = false;
            try {
                const res = await fetch('{{ route('settings.seo.test') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                    },
                });
                const data = await res.json();
                this.testResult = data.message;
                this.testSuccess = data.success;
            } catch (e) {
                this.testResult = 'Erreur de connexion.';
            }
        },
    };
}
</script>
@endpush

@extends('layouts.app')

@section('title', 'Webhook - Paramètres - Link Tracker')

@section('breadcrumb')
    <span class="text-neutral-500">Paramètres</span>
    <span class="text-neutral-400 mx-2">/</span>
    <span class="text-neutral-900 font-medium">Webhook</span>
@endsection

@section('content')
    <x-page-header title="Configuration Webhook" subtitle="Recevez des notifications en temps réel dans Slack ou tout autre service externe" />

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Formulaire principal --}}
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg border border-neutral-200 p-6">
                <h2 class="text-base font-semibold text-neutral-900 mb-6">Paramètres du webhook</h2>

                <form method="POST" action="{{ route('settings.webhook.update') }}" x-data="webhookForm()">
                    @csrf
                    @method('PUT')

                    {{-- URL du webhook --}}
                    <div class="mb-5">
                        <label for="webhook_url" class="block text-sm font-medium text-neutral-700 mb-1">
                            URL du webhook
                        </label>
                        <input
                            type="url"
                            id="webhook_url"
                            name="webhook_url"
                            value="{{ old('webhook_url', $user->webhook_url) }}"
                            placeholder="https://hooks.slack.com/services/..."
                            class="block w-full px-4 py-2 border border-neutral-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500 @error('webhook_url') border-red-500 @enderror"
                        />
                        @error('webhook_url')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-neutral-500">URL vers laquelle LinkTracker enverra les notifications HTTP POST.</p>
                    </div>

                    {{-- Secret HMAC --}}
                    <div class="mb-5">
                        <label for="webhook_secret" class="block text-sm font-medium text-neutral-700 mb-1">
                            Secret HMAC (optionnel)
                        </label>
                        <div class="flex gap-2">
                            <input
                                type="text"
                                id="webhook_secret"
                                name="webhook_secret"
                                value="{{ old('webhook_secret', $user->webhook_secret) }}"
                                placeholder="Votre secret partagé"
                                class="block flex-1 px-4 py-2 border border-neutral-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500 font-mono text-sm"
                            />
                            <button
                                type="button"
                                @click="generateSecret"
                                class="px-4 py-2 text-sm font-medium text-neutral-700 bg-neutral-100 hover:bg-neutral-200 rounded-lg transition-colors whitespace-nowrap"
                            >
                                Générer
                            </button>
                        </div>
                        <p class="mt-1 text-xs text-neutral-500">
                            Utilisé pour signer les payloads via HMAC-SHA256. Vérifiable via l'header <code class="bg-neutral-100 px-1 rounded">X-Webhook-Signature</code>.
                        </p>
                    </div>

                    {{-- Événements à notifier --}}
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-neutral-700 mb-3">
                            Événements à notifier
                        </label>
                        <div class="space-y-2">
                            @foreach($availableEvents as $eventKey => $eventLabel)
                                <label class="flex items-center gap-3 p-3 border border-neutral-200 rounded-lg hover:bg-neutral-50 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        name="webhook_events[]"
                                        value="{{ $eventKey }}"
                                        {{ in_array($eventKey, $user->webhook_events ?? []) ? 'checked' : '' }}
                                        class="w-4 h-4 text-brand-500 rounded border-neutral-300 focus:ring-brand-500"
                                    />
                                    <span class="text-sm font-medium text-neutral-900">{{ $eventLabel }}</span>
                                    @if($eventKey === 'backlink_lost')
                                        <x-badge variant="danger" class="ml-auto">Critique</x-badge>
                                    @elseif($eventKey === 'backlink_changed')
                                        <x-badge variant="warning" class="ml-auto">Modification</x-badge>
                                    @else
                                        <x-badge variant="success" class="ml-auto">Récupération</x-badge>
                                    @endif
                                </label>
                            @endforeach
                        </div>
                        <p class="mt-2 text-xs text-neutral-500">Si aucun événement n'est coché, tous les types seront envoyés.</p>
                    </div>

                    <div class="flex items-center gap-3">
                        <x-button variant="primary" type="submit">
                            Sauvegarder
                        </x-button>

                        @if($user->webhook_url)
                            <form method="POST" action="{{ route('settings.webhook.test') }}" class="inline">
                                @csrf
                                <button
                                    type="submit"
                                    class="px-4 py-2 text-sm font-medium text-neutral-700 bg-neutral-100 hover:bg-neutral-200 rounded-lg transition-colors"
                                >
                                    Tester le webhook
                                </button>
                            </form>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        {{-- Documentation --}}
        <div class="space-y-4">
            {{-- Statut --}}
            <div class="bg-white rounded-lg border border-neutral-200 p-5">
                <h3 class="text-sm font-semibold text-neutral-900 mb-3">Statut</h3>
                @if($user->webhook_url)
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                        <span class="text-sm text-neutral-700">Webhook configuré</span>
                    </div>
                    <p class="text-xs text-neutral-500 mt-2 truncate">{{ $user->webhook_url }}</p>
                @else
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 bg-neutral-300 rounded-full"></span>
                        <span class="text-sm text-neutral-500">Aucun webhook configuré</span>
                    </div>
                @endif
            </div>

            {{-- Format du payload --}}
            <div class="bg-white rounded-lg border border-neutral-200 p-5">
                <h3 class="text-sm font-semibold text-neutral-900 mb-3">Format du payload</h3>
                <pre class="text-xs bg-neutral-900 text-green-400 p-3 rounded-lg overflow-x-auto leading-relaxed">{
  "event": "backlink_lost",
  "timestamp": "2026-02-17T...",
  "alert": {
    "type": "backlink_lost",
    "severity": "critical",
    "title": "Backlink perdu",
    "message": "..."
  },
  "backlink": {
    "source_url": "https://...",
    "target_url": "https://...",
    "anchor_text": "mon ancre",
    "status": "lost"
  }
}</pre>
            </div>

            {{-- Vérification de signature --}}
            <div class="bg-white rounded-lg border border-neutral-200 p-5">
                <h3 class="text-sm font-semibold text-neutral-900 mb-3">Vérifier la signature</h3>
                <p class="text-xs text-neutral-600 mb-2">Comparez l'header <code class="bg-neutral-100 px-1 rounded">X-Webhook-Signature</code> :</p>
                <pre class="text-xs bg-neutral-900 text-green-400 p-3 rounded-lg overflow-x-auto"># PHP
$expected = 'sha256=' . hash_hmac(
  'sha256',
  $rawBody,
  $secret
);</pre>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
function webhookForm() {
    return {
        generateSecret() {
            fetch('{{ route('settings.webhook.generate-secret') }}')
                .then(r => r.json())
                .then(data => {
                    document.getElementById('webhook_secret').value = data.secret;
                });
        }
    }
}
</script>
@endpush

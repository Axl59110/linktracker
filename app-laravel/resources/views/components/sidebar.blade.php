{{--
    Sidebar Navigation Component

    Sidebar fixe (256px) avec navigation principale.
    Sur mobile (<1024px), devient un drawer off-canvas.

    TODO: Ajouter toggle mobile drawer avec AlpineJS ou Vue.js
    TODO: Récupérer count des alertes non lues depuis database
--}}

<aside class="fixed inset-y-0 left-0 w-64 bg-white border-r border-neutral-200 hidden lg:block z-40">
    {{-- Logo Section --}}
    <div class="h-16 flex items-center px-6 border-b border-neutral-200">
        <a href="{{ url('/dashboard') }}" class="flex items-center">
            <h1 class="text-xl font-semibold text-neutral-900">Link Tracker</h1>
        </a>
    </div>

    {{-- Navigation Links --}}
    <nav class="p-4 space-y-1 flex-1 overflow-y-auto">
        {{-- Dashboard --}}
        <a
            href="{{ url('/dashboard') }}"
            class="flex items-center px-4 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->is('dashboard') ? 'bg-neutral-100 text-neutral-900' : 'text-neutral-600 hover:bg-neutral-100' }}"
        >
            <span class="mr-3 text-lg">📊</span>
            <span>Dashboard</span>
        </a>

        {{-- Projets --}}
        <a
            href="{{ url('/projects') }}"
            class="flex items-center px-4 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->is('projects*') ? 'bg-neutral-100 text-neutral-900' : 'text-neutral-600 hover:bg-neutral-100' }}"
        >
            <span class="mr-3 text-lg">📁</span>
            <span>Projets</span>
        </a>

        {{-- Backlinks --}}
        {{-- TODO: Cette route n'existe pas encore, créer /backlinks global pour voir tous les backlinks --}}
        <a
            href="{{ url('/backlinks') }}"
            class="flex items-center px-4 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->is('backlinks*') ? 'bg-neutral-100 text-neutral-900' : 'text-neutral-600 hover:bg-neutral-100' }}"
        >
            <span class="mr-3 text-lg">🔗</span>
            <span>Backlinks</span>
        </a>

        {{-- Alertes --}}
        {{-- TODO: Cette route n'existe pas encore, sera créée dans EPIC-004 (Alertes) --}}
        <a
            href="{{ url('/alerts') }}"
            class="flex items-center px-4 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->is('alerts*') ? 'bg-neutral-100 text-neutral-900' : 'text-neutral-600 hover:bg-neutral-100' }}"
        >
            <span class="mr-3 text-lg">🔔</span>
            <span>Alertes</span>

            {{-- TODO: Remplacer par count réel depuis database (Alert::where('is_read', false)->count()) --}}
            @php
                $unreadAlertsCount = 0; // PLACEHOLDER: Sera remplacé par Alert::unread()->count()
            @endphp

            @if($unreadAlertsCount > 0)
                <span class="ml-auto inline-flex items-center justify-center px-2 py-0.5 text-xs font-medium rounded-full bg-danger-50 text-danger-600 border border-danger-200">
                    {{ $unreadAlertsCount }}
                </span>
            @endif
        </a>

        {{-- Commandes --}}
        {{-- TODO: Cette route n'existe pas encore, sera créée dans EPIC-006 (Marketplace) --}}
        <a
            href="{{ url('/orders') }}"
            class="flex items-center px-4 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->is('orders*') ? 'bg-neutral-100 text-neutral-900' : 'text-neutral-600 hover:bg-neutral-100' }}"
        >
            <span class="mr-3 text-lg">🛒</span>
            <span>Commandes</span>
        </a>

        {{-- Divider --}}
        <div class="pt-4 pb-2">
            <div class="border-t border-neutral-200"></div>
        </div>

        {{-- Métriques SEO (optionnel) --}}
        {{-- TODO: Cette section sera ajoutée dans EPIC-005 (Métriques SEO) --}}
        {{--
        <a
            href="{{ url('/seo-metrics') }}"
            class="flex items-center px-4 py-2.5 rounded-lg text-sm font-medium transition-colors text-neutral-600 hover:bg-neutral-100"
        >
            <span class="mr-3 text-lg">📈</span>
            <span>Métriques SEO</span>
        </a>
        --}}
    </nav>

    {{-- Bottom Section - Settings --}}
    <div class="p-4 border-t border-neutral-200">
        {{-- Paramètres --}}
        {{-- TODO: Cette route n'existe pas encore, sera créée dans EPIC-008 (Configuration) --}}
        <a
            href="{{ url('/settings') }}"
            class="flex items-center px-4 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->is('settings*') ? 'bg-neutral-100 text-neutral-900' : 'text-neutral-600 hover:bg-neutral-100' }}"
        >
            <span class="mr-3 text-lg">⚙️</span>
            <span>Paramètres</span>
        </a>

        {{-- User Info (optionnel) --}}
        {{-- TODO: Afficher nom utilisateur si auth()->check() --}}
        @auth
            <div class="mt-4 px-4 py-3 bg-neutral-100 rounded-lg">
                <p class="text-xs text-neutral-500">Connecté en tant que</p>
                <p class="text-sm font-medium text-neutral-900 truncate">
                    {{ auth()->user()->name ?? 'Utilisateur' }}
                </p>
            </div>
        @endauth
    </div>
</aside>

{{-- Mobile Sidebar Overlay (TODO: Implémenter dans STORY-027) --}}
{{--
<div
    x-show="mobileMenuOpen"
    x-cloak
    @click="mobileMenuOpen = false"
    class="fixed inset-0 bg-neutral-900 bg-opacity-50 z-30 lg:hidden"
></div>
--}}

{{-- Mobile Sidebar Drawer (TODO: Implémenter dans STORY-027) --}}
{{--
<aside
    x-show="mobileMenuOpen"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="transform -translate-x-full"
    x-transition:enter-end="transform translate-x-0"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="transform translate-x-0"
    x-transition:leave-end="transform -translate-x-full"
    class="fixed inset-y-0 left-0 w-64 bg-white border-r border-neutral-200 z-40 lg:hidden"
>
    <!-- Même contenu que sidebar desktop -->
</aside>
--}}

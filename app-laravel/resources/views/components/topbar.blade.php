{{--
    Topbar Component

    Contient:
    - Breadcrumb navigation (slot 'breadcrumb')
    - Quick stats (optionnel)
    - User menu (dropdown)

    TODO: Implémenter dropdown user menu avec AlpineJS
    TODO: Récupérer stats réelles depuis database
--}}

<div class="h-16 bg-white border-b border-neutral-200 flex items-center justify-between px-6 sticky top-0 z-30">
    {{-- Left: Breadcrumb Navigation --}}
    <nav class="flex items-center space-x-2 text-sm">
        @hasSection('breadcrumb')
            @yield('breadcrumb')
        @else
            {{-- Default breadcrumb si aucun fourni --}}
            <span class="text-neutral-900 font-medium">{{ $title ?? 'Page' }}</span>
        @endhasSection
    </nav>

    {{-- Right: Stats + User Menu --}}
    <div class="flex items-center space-x-6">
        {{-- Quick Stats (Optional) --}}
        {{-- TODO: Remplacer par données réelles depuis database --}}
        @php
            // PLACEHOLDERS: Seront remplacés par requêtes réelles
            $activeBacklinksCount = 0; // Backlink::where('status', 'active')->count()
            $projectsCount = 0;        // Project::count()
        @endphp

        <div class="hidden md:flex items-center space-x-4 text-sm text-neutral-500">
            <span>{{ $activeBacklinksCount }} actifs</span>
            <span class="w-1 h-1 bg-neutral-300 rounded-full"></span>
            <span>{{ $projectsCount }} projets</span>
        </div>

        {{-- User Menu --}}
        <div class="relative">
            {{-- TODO: Implémenter dropdown avec AlpineJS ou Livewire --}}
            {{-- Pour l'instant, simple bouton sans dropdown --}}
            <button
                type="button"
                class="flex items-center space-x-2 hover:opacity-80 transition-opacity"
                {{-- TODO: Ajouter @click="userMenuOpen = !userMenuOpen" avec AlpineJS --}}
            >
                {{-- User Avatar --}}
                <div class="w-8 h-8 bg-brand-500 text-white rounded-full flex items-center justify-center text-sm font-medium">
                    @auth
                        {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                    @else
                        U
                    @endauth
                </div>

                {{-- User Name (hidden on mobile) --}}
                <span class="text-sm font-medium text-neutral-700 hidden md:block">
                    @auth
                        {{ auth()->user()->name ?? 'Utilisateur' }}
                    @else
                        Utilisateur
                    @endauth
                </span>

                {{-- Dropdown Arrow --}}
                <svg class="w-4 h-4 text-neutral-400 hidden md:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            {{-- Dropdown Menu (TODO: Implémenter dans phase ultérieure) --}}
            {{--
            <div
                x-show="userMenuOpen"
                @click.away="userMenuOpen = false"
                x-cloak
                class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-neutral-200 py-1 z-50"
            >
                <a href="{{ url('/profile') }}" class="block px-4 py-2 text-sm text-neutral-700 hover:bg-neutral-100">
                    Mon profil
                </a>
                <a href="{{ url('/settings') }}" class="block px-4 py-2 text-sm text-neutral-700 hover:bg-neutral-100">
                    Paramètres
                </a>
                <div class="border-t border-neutral-200 my-1"></div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-danger-600 hover:bg-neutral-100">
                        Déconnexion
                    </button>
                </form>
            </div>
            --}}
        </div>
    </div>
</div>

{{-- Mobile Menu Button (TODO: Implémenter dans STORY-027) --}}
{{--
<button
    @click="mobileMenuOpen = !mobileMenuOpen"
    class="lg:hidden fixed top-4 left-4 z-50 p-2 rounded-lg bg-white border border-neutral-200 hover:bg-neutral-100"
>
    <svg class="w-6 h-6 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
    </svg>
</button>
--}}

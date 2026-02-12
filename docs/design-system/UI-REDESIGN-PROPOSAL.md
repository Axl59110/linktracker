# Link Tracker - SaaS UI/UX Redesign Proposal

**Date:** 2026-02-12
**Version:** 1.0
**Status:** Proposition

---

## 🎯 Objectifs du Redesign

### Problèmes Actuels
1. ❌ **Navigation fragmentée** - Pas de sidebar, navigation par boutons éparpillés
2. ❌ **Manque de contexte** - L'utilisateur ne sait pas où il se trouve (pas de breadcrumb)
3. ❌ **Palette surchargée** - 5+ couleurs diluent l'attention
4. ❌ **Composants dupliqués** - Code Vue.js répétitif, pas de Blade réutilisables
5. ❌ **Layout centré** - Pas adapté à une app SaaS (gaspille l'espace horizontal)

### Solutions Proposées
1. ✅ **Sidebar persistante** - Navigation toujours visible (desktop)
2. ✅ **Breadcrumb contextuel** - L'utilisateur sait où il est
3. ✅ **Palette minimale** - 4 couleurs avec rôles clairs
4. ✅ **Composants Blade** - Headers, tables, cards, forms réutilisables
5. ✅ **Layout SaaS** - Sidebar + Content area

---

## 🎨 Design System

### Palette de Couleurs

#### Règle d'Or : "Une couleur = Une fonction"

```
NEUTRAL (Gris) → Tout par défaut
BRAND (Bleu)   → Actions principales uniquement
SUCCESS (Vert) → Statut "actif" uniquement
DANGER (Rouge) → Erreurs et alertes critiques
```

#### Palette Détaillée

**Neutral (95% de l'interface)**
```css
--neutral-50: #fafafa;   /* Background page */
--neutral-100: #f5f5f5;  /* Background card, input */
--neutral-200: #e5e5e5;  /* Borders */
--neutral-300: #d4d4d4;  /* Borders hover */
--neutral-400: #a3a3a3;  /* Text secondary, placeholders */
--neutral-500: #737373;  /* Text tertiary */
--neutral-600: #525252;  /* Text primary */
--neutral-700: #404040;  /* Text emphasis */
--neutral-900: #171717;  /* Headings */
```

**Brand (Actions uniquement)**
```css
--brand-50: #eff6ff;     /* Button hover background subtle */
--brand-500: #3b82f6;    /* Primary button, links */
--brand-600: #2563eb;    /* Primary button hover */
--brand-700: #1d4ed8;    /* Active state */
```

**Success (Statut actif)**
```css
--success-50: #f0fdf4;   /* Badge background */
--success-600: #16a34a;  /* Badge text, icon */
```

**Danger (Alertes)**
```css
--danger-50: #fef2f2;    /* Alert background */
--danger-600: #dc2626;   /* Alert text, delete button */
```

#### Exemples d'Usage

**✅ CORRECT**
- Bouton "Créer un projet" → `bg-brand-500`
- Badge "Actif" → `bg-success-50 text-success-600`
- Badge "Perdu" → `bg-danger-50 text-danger-600`
- Badge "Modifié" → `bg-neutral-100 text-neutral-600` (pas de jaune !)
- Texte principal → `text-neutral-600`
- Heading → `text-neutral-900`

**❌ INCORRECT**
- Bouton secondaire → `bg-blue-100` (utiliser `bg-neutral-100`)
- Multiples couleurs dans un badge → Non, 1 couleur = 1 sens

---

## 🏗️ Architecture de Layout

### Structure SaaS avec Sidebar

```
┌─────────────────────────────────────────────────────┐
│ Topbar (breadcrumb + user menu)                     │
├─────────┬───────────────────────────────────────────┤
│         │                                           │
│ Sidebar │  Content Area                            │
│         │  ┌─────────────────────────────────────┐ │
│ - Home  │  │ Page Header                         │ │
│ - Proj  │  │ Title + Actions                     │ │
│ - Alerts│  ├─────────────────────────────────────┤ │
│ - Sett  │  │                                     │ │
│         │  │ Main Content                        │ │
│         │  │ (Cards, Tables, Forms)              │ │
│         │  │                                     │ │
│         │  │                                     │ │
│         │  └─────────────────────────────────────┘ │
└─────────┴───────────────────────────────────────────┘
```

### Dimensions

**Desktop (lg+):**
- Sidebar width: `256px` (fixe)
- Content area: `calc(100vw - 256px)`
- Max content width: `1400px` (centré avec padding)

**Tablet/Mobile (<1024px):**
- Sidebar devient un drawer off-canvas
- Hamburger menu en haut à gauche
- Content area: `100vw`

---

## 🧩 Composants Blade Réutilisables

### 1. Layout Principal

**File:** `resources/views/layouts/app.blade.php`

```blade
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Link Tracker')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-neutral-50 text-neutral-600 antialiased">
    <div id="app" class="min-h-screen">
        @include('components.sidebar')

        <div class="lg:pl-64">
            @include('components.topbar')

            <main class="p-6 max-w-7xl mx-auto">
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
```

### 2. Sidebar Component

**File:** `resources/views/components/sidebar.blade.php`

```blade
<aside class="fixed inset-y-0 left-0 w-64 bg-white border-r border-neutral-200 hidden lg:block">
    <!-- Logo -->
    <div class="h-16 flex items-center px-6 border-b border-neutral-200">
        <h1 class="text-xl font-semibold text-neutral-900">Link Tracker</h1>
    </div>

    <!-- Navigation -->
    <nav class="p-4 space-y-1">
        <a href="/dashboard" class="block px-4 py-2.5 rounded-lg text-sm font-medium hover:bg-neutral-100 {{ request()->is('dashboard') ? 'bg-neutral-100 text-neutral-900' : 'text-neutral-600' }}">
            <span class="mr-3">📊</span> Dashboard
        </a>

        <a href="/projects" class="block px-4 py-2.5 rounded-lg text-sm font-medium hover:bg-neutral-100 {{ request()->is('projects*') ? 'bg-neutral-100 text-neutral-900' : 'text-neutral-600' }}">
            <span class="mr-3">📁</span> Projets
        </a>

        <a href="/backlinks" class="block px-4 py-2.5 rounded-lg text-sm font-medium hover:bg-neutral-100 {{ request()->is('backlinks*') ? 'bg-neutral-100 text-neutral-900' : 'text-neutral-600' }}">
            <span class="mr-3">🔗</span> Backlinks
        </a>

        <a href="/alerts" class="block px-4 py-2.5 rounded-lg text-sm font-medium hover:bg-neutral-100 {{ request()->is('alerts*') ? 'bg-neutral-100 text-neutral-900' : 'text-neutral-600' }}">
            <span class="mr-3">🔔</span> Alertes
            @if($unreadAlertsCount ?? 0 > 0)
                <span class="ml-auto inline-flex items-center justify-center px-2 py-0.5 text-xs font-medium rounded-full bg-danger-50 text-danger-600">
                    {{ $unreadAlertsCount }}
                </span>
            @endif
        </a>

        <a href="/orders" class="block px-4 py-2.5 rounded-lg text-sm font-medium hover:bg-neutral-100 {{ request()->is('orders*') ? 'bg-neutral-100 text-neutral-900' : 'text-neutral-600' }}">
            <span class="mr-3">🛒</span> Commandes
        </a>
    </nav>

    <!-- Bottom Section -->
    <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-neutral-200">
        <a href="/settings" class="block px-4 py-2.5 rounded-lg text-sm font-medium hover:bg-neutral-100 {{ request()->is('settings*') ? 'bg-neutral-100 text-neutral-900' : 'text-neutral-600' }}">
            <span class="mr-3">⚙️</span> Paramètres
        </a>
    </div>
</aside>
```

### 3. Topbar (Breadcrumb + User Menu)

**File:** `resources/views/components/topbar.blade.php`

```blade
<div class="h-16 bg-white border-b border-neutral-200 flex items-center justify-between px-6">
    <!-- Breadcrumb -->
    <nav class="flex items-center space-x-2 text-sm">
        @yield('breadcrumb')
    </nav>

    <!-- User Menu -->
    <div class="flex items-center space-x-4">
        <!-- Quick Stats (optional) -->
        <div class="hidden md:flex items-center space-x-4 text-sm text-neutral-500">
            <span>{{ $activeBacklinksCount ?? 0 }} actifs</span>
            <span class="w-1 h-1 bg-neutral-300 rounded-full"></span>
            <span>{{ $projectsCount ?? 0 }} projets</span>
        </div>

        <!-- User Dropdown -->
        <div class="relative">
            <button class="flex items-center space-x-2 hover:opacity-80">
                <div class="w-8 h-8 bg-brand-500 text-white rounded-full flex items-center justify-center text-sm font-medium">
                    {{ substr(auth()->user()->name ?? 'U', 0, 1) }}
                </div>
                <span class="text-sm font-medium text-neutral-700 hidden md:block">{{ auth()->user()->name ?? 'User' }}</span>
            </button>
        </div>
    </div>
</div>
```

### 4. Page Header Component

**File:** `resources/views/components/page-header.blade.php`

```blade
<div class="mb-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-neutral-900">{{ $title }}</h1>
            @if(isset($subtitle))
                <p class="mt-1 text-sm text-neutral-500">{{ $subtitle }}</p>
            @endif
        </div>

        @if(isset($actions))
            <div class="flex items-center space-x-3">
                {{ $actions }}
            </div>
        @endif
    </div>
</div>
```

### 5. Stats Card Component

**File:** `resources/views/components/stats-card.blade.php`

```blade
<div class="bg-white border border-neutral-200 rounded-lg p-5">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-sm text-neutral-500">{{ $label }}</p>
            <p class="text-2xl font-semibold text-neutral-900 mt-1">{{ $value }}</p>
            @if(isset($change))
                <p class="text-xs text-neutral-400 mt-1">{{ $change }}</p>
            @endif
        </div>

        @if(isset($icon))
            <div class="text-3xl">{{ $icon }}</div>
        @endif
    </div>
</div>
```

### 6. Table Component

**File:** `resources/views/components/table.blade.php`

```blade
<div class="bg-white border border-neutral-200 rounded-lg overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-neutral-200">
            <thead>
                <tr class="bg-neutral-50">
                    {{ $header }}
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-200 bg-white">
                {{ $body }}
            </tbody>
        </table>
    </div>
</div>
```

### 7. Badge Component

**File:** `resources/views/components/badge.blade.php`

```blade
@php
    $variants = [
        'success' => 'bg-success-50 text-success-600 border-success-200',
        'danger' => 'bg-danger-50 text-danger-600 border-danger-200',
        'neutral' => 'bg-neutral-100 text-neutral-600 border-neutral-200',
    ];

    $classes = $variants[$variant ?? 'neutral'] ?? $variants['neutral'];
@endphp

<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border {{ $classes }}">
    {{ $slot }}
</span>
```

### 8. Button Component

**File:** `resources/views/components/button.blade.php`

```blade
@php
    $variants = [
        'primary' => 'bg-brand-500 hover:bg-brand-600 text-white',
        'secondary' => 'bg-neutral-100 hover:bg-neutral-200 text-neutral-700 border border-neutral-200',
        'danger' => 'bg-danger-600 hover:bg-danger-700 text-white',
    ];

    $sizes = [
        'sm' => 'px-3 py-1.5 text-sm',
        'md' => 'px-4 py-2 text-sm',
        'lg' => 'px-5 py-2.5 text-base',
    ];

    $variantClass = $variants[$variant ?? 'primary'] ?? $variants['primary'];
    $sizeClass = $sizes[$size ?? 'md'] ?? $sizes['md'];
@endphp

<button
    type="{{ $type ?? 'button' }}"
    class="inline-flex items-center justify-center font-medium rounded-lg transition-colors {{ $variantClass }} {{ $sizeClass }} {{ $class ?? '' }}"
    {{ $attributes }}
>
    {{ $slot }}
</button>
```

---

## 📐 Layout Examples

### Dashboard Page

```blade
@extends('layouts.app')

@section('title', 'Dashboard - Link Tracker')

@section('breadcrumb')
    <span class="text-neutral-900 font-medium">Dashboard</span>
@endsection

@section('content')
    <x-page-header
        title="Dashboard"
        subtitle="Vue d'ensemble de vos backlinks et projets">
        <x-slot:actions>
            <x-button variant="primary" href="/projects/create">
                + Nouveau projet
            </x-button>
        </x-slot:actions>
    </x-page-header>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <x-stats-card
            label="Backlinks actifs"
            value="127"
            change="+12 ce mois"
            icon="✅" />

        <x-stats-card
            label="Backlinks perdus"
            value="3"
            change="-2 vs mois dernier"
            icon="❌" />

        <x-stats-card
            label="Projets"
            value="8"
            icon="📁" />
    </div>

    <!-- Recent Alerts -->
    <div class="bg-white border border-neutral-200 rounded-lg p-6">
        <h2 class="text-lg font-semibold text-neutral-900 mb-4">Alertes récentes</h2>
        <!-- Content -->
    </div>
@endsection
```

### Projects List Page

```blade
@extends('layouts.app')

@section('title', 'Projets - Link Tracker')

@section('breadcrumb')
    <a href="/dashboard" class="text-neutral-500 hover:text-neutral-700">Dashboard</a>
    <span class="text-neutral-400 mx-2">/</span>
    <span class="text-neutral-900 font-medium">Projets</span>
@endsection

@section('content')
    <x-page-header
        title="Projets"
        subtitle="{{ count($projects) }} projet(s) configuré(s)">
        <x-slot:actions>
            <x-button variant="primary" href="/projects/create">
                + Créer un projet
            </x-button>
        </x-slot:actions>
    </x-page-header>

    <!-- Projects Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($projects as $project)
            <div class="bg-white border border-neutral-200 rounded-lg p-6 hover:border-neutral-300 transition-colors">
                <div class="flex items-start justify-between mb-3">
                    <h3 class="text-lg font-semibold text-neutral-900">{{ $project->name }}</h3>
                    <x-badge variant="{{ $project->status === 'active' ? 'success' : 'neutral' }}">
                        {{ ucfirst($project->status) }}
                    </x-badge>
                </div>

                <p class="text-sm text-neutral-500 mb-4 truncate">{{ $project->url }}</p>

                <div class="flex items-center space-x-3">
                    <x-button variant="secondary" size="sm" href="/projects/{{ $project->id }}">
                        Voir
                    </x-button>
                    <x-button variant="secondary" size="sm" href="/projects/{{ $project->id }}/backlinks">
                        Backlinks
                    </x-button>
                </div>
            </div>
        @endforeach
    </div>
@endsection
```

---

## 🔤 Typographie

### Font Stack (System Fonts)

```css
font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
```

**Pourquoi system fonts ?**
- Chargement instantané (0ms)
- Familier pour l'utilisateur
- Professionnel et neutre
- Pas de Google Fonts = respect RGPD

### Échelle Typographique

```css
/* Headings */
--text-2xl: 1.5rem;    /* 24px - Page title */
--text-xl: 1.25rem;    /* 20px - Section title */
--text-lg: 1.125rem;   /* 18px - Card title */

/* Body */
--text-base: 1rem;     /* 16px - Default */
--text-sm: 0.875rem;   /* 14px - Secondary text */
--text-xs: 0.75rem;    /* 12px - Labels, badges */

/* Weights */
--font-normal: 400;
--font-medium: 500;
--font-semibold: 600;
```

---

## 🎯 Migration Plan

### Phase 1 : Infrastructure (Sprint actuel)
1. Créer les composants Blade de base
2. Créer le layout avec sidebar
3. Migrer CSS vers nouvelles variables

### Phase 2 : Migration Pages (Sprint suivant)
1. Migrer Dashboard
2. Migrer Projects
3. Migrer Backlinks

### Phase 3 : Composants Avancés
1. Dropdowns, modals
2. Tables avancées avec tri/filtres
3. Forms avec validation visuelle

---

## ✅ Checklist d'Implémentation

- [ ] Créer `resources/views/layouts/app.blade.php`
- [ ] Créer `resources/views/components/sidebar.blade.php`
- [ ] Créer `resources/views/components/topbar.blade.php`
- [ ] Créer `resources/views/components/page-header.blade.php`
- [ ] Créer `resources/views/components/stats-card.blade.php`
- [ ] Créer `resources/views/components/table.blade.php`
- [ ] Créer `resources/views/components/badge.blade.php`
- [ ] Créer `resources/views/components/button.blade.php`
- [ ] Mettre à jour `resources/css/app.css` avec nouvelles variables
- [ ] Créer page Dashboard avec nouveau layout
- [ ] Migrer Projects Index vers Blade
- [ ] Migrer Backlinks Index vers Blade

---

## 📊 Mesures de Succès

**UX :**
- ✅ Navigation accessible en 0 clics (sidebar toujours visible)
- ✅ Contexte clair avec breadcrumb
- ✅ Moins de couleurs = meilleure hiérarchie

**DX (Developer Experience) :**
- ✅ Composants réutilisables = moins de code
- ✅ Blade natif = pas de build Vue pour layout
- ✅ Variables CSS = changements globaux faciles

**Performance :**
- ✅ Sidebar server-side = pas de JS pour navigation
- ✅ System fonts = 0ms de chargement
- ✅ CSS minimal = bundle plus léger

---

**Créé le :** 2026-02-12
**Auteur :** Claude Code (via frontend-design skill)

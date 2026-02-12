# Link Tracker - Component Library Documentation

**Date:** 2026-02-12
**Version:** 1.0
**Status:** Phase 1 Complétée

---

## 📚 Vue d'Ensemble

Cette documentation présente tous les composants Blade réutilisables créés pour Link Tracker. Tous les composants suivent le design system minimal (4 couleurs) et sont optimisés pour la cohérence et la maintenabilité.

---

## 🎨 Design Tokens

### Couleurs

```css
/* NEUTRAL - 95% de l'UI */
--color-neutral-50: #fafafa;
--color-neutral-100: #f5f5f5;
--color-neutral-200: #e5e5e5;
--color-neutral-600: #525252;
--color-neutral-900: #171717;

/* BRAND - Actions primaires */
--color-brand-500: #3b82f6;
--color-brand-600: #2563eb;

/* SUCCESS - Statut actif */
--color-success-50: #f0fdf4;
--color-success-600: #16a34a;

/* DANGER - Alertes */
--color-danger-50: #fef2f2;
--color-danger-600: #dc2626;
```

---

## 📦 Composants Disponibles

### 1. Layout Principal

**Fichier:** `resources/views/layouts/app.blade.php`

Layout SaaS avec sidebar fixe et topbar.

**Usage:**
```blade
@extends('layouts.app')

@section('title', 'Ma Page')

@section('breadcrumb')
    <a href="/dashboard">Dashboard</a>
    <span class="mx-2 text-neutral-400">/</span>
    <span class="text-neutral-900 font-medium">Ma Page</span>
@endsection

@section('content')
    <!-- Votre contenu ici -->
@endsection
```

---

### 2. Page Header

**Fichier:** `resources/views/components/page-header.blade.php`

En-tête de page avec titre, sous-titre et actions.

**Props:**
- `title` (string, required) - Titre de la page
- `subtitle` (string, optional) - Sous-titre descriptif

**Slots:**
- `actions` (optional) - Boutons d'action

**Usage:**
```blade
<x-page-header
    title="Mes Projets"
    subtitle="8 projets configurés">
    <x-slot:actions>
        <x-button variant="primary" href="/projects/create">
            + Créer un projet
        </x-button>
    </x-slot:actions>
</x-page-header>
```

**Rendu:**
```
┌─────────────────────────────────────────────────┐
│ Mes Projets                   [+ Créer un projet]│
│ 8 projets configurés                             │
└─────────────────────────────────────────────────┘
```

---

### 3. Stats Card

**Fichier:** `resources/views/components/stats-card.blade.php`

Card pour afficher une statistique avec icône.

**Props:**
- `label` (string, required) - Label de la stat
- `value` (string|int, required) - Valeur à afficher
- `change` (string, optional) - Changement (ex: "+12 ce mois")
- `icon` (string, optional) - Emoji ou caractère

**Usage:**
```blade
<x-stats-card
    label="Backlinks actifs"
    value="127"
    change="+12 ce mois"
    icon="✅"
/>
```

**Rendu:**
```
┌─────────────────────────┐
│ Backlinks actifs    ✅  │
│ 127                     │
│ +12 ce mois             │
└─────────────────────────┘
```

**Grid Example:**
```blade
<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <x-stats-card label="Actifs" value="127" icon="✅" />
    <x-stats-card label="Perdus" value="3" icon="❌" />
    <x-stats-card label="Projets" value="8" icon="📁" />
</div>
```

---

### 4. Badge

**Fichier:** `resources/views/components/badge.blade.php`

Badge coloré pour statuts.

**Props:**
- `variant` (string, optional) - Couleur du badge
  - `success` - Vert (pour "Actif")
  - `danger` - Rouge (pour "Perdu", erreurs)
  - `neutral` - Gris (défaut, pour "Modifié", "Archivé")

**Usage:**
```blade
<x-badge variant="success">Actif</x-badge>
<x-badge variant="danger">Perdu</x-badge>
<x-badge variant="neutral">Modifié</x-badge>
```

**Guide d'Usage:**

| Statut | Variant | Justification |
|--------|---------|---------------|
| Actif | `success` | Positif, backlink présent |
| Perdu | `danger` | Critique, backlink disparu |
| Modifié | `neutral` | Information, pas critique |
| Archivé | `neutral` | Information, pas critique |
| En attente | `neutral` | Information neutre |

---

### 5. Button

**Fichier:** `resources/views/components/button.blade.php`

Bouton réutilisable avec variants et sizes.

**Props:**
- `variant` (string, optional, default: 'primary')
  - `primary` - Bleu (actions principales)
  - `secondary` - Gris (actions secondaires)
  - `danger` - Rouge (suppressions)
- `size` (string, optional, default: 'md')
  - `sm` - Petit
  - `md` - Moyen
  - `lg` - Grand
- `type` (string, optional, default: 'button')
- `href` (string, optional) - Si fourni, rend un lien `<a>`

**Usage:**
```blade
<!-- Bouton primary (action principale) -->
<x-button variant="primary" href="/projects/create">
    + Créer un projet
</x-button>

<!-- Bouton secondary (action secondaire) -->
<x-button variant="secondary" type="button">
    Annuler
</x-button>

<!-- Bouton danger (suppression) -->
<x-button variant="danger" type="submit">
    Supprimer
</x-button>

<!-- Sizes -->
<x-button variant="primary" size="sm">Petit</x-button>
<x-button variant="primary" size="md">Moyen</x-button>
<x-button variant="primary" size="lg">Grand</x-button>
```

**Guide d'Usage:**

| Action | Variant | Exemple |
|--------|---------|---------|
| Créer, Ajouter | `primary` | "Créer un projet" |
| Voir, Modifier | `secondary` | "Voir détails" |
| Annuler | `secondary` | "Annuler" |
| Supprimer | `danger` | "Supprimer le projet" |

---

### 6. Table

**Fichier:** `resources/views/components/table.blade.php`

Table responsive avec header et body slots.

**Slots:**
- `header` (required) - Colonnes du header
- `body` (required) - Lignes du body

**Usage:**
```blade
<x-table>
    <x-slot:header>
        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">
            Nom
        </th>
        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">
            Status
        </th>
        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">
            Actions
        </th>
    </x-slot:header>

    <x-slot:body>
        @foreach($projects as $project)
            <tr class="hover:bg-neutral-50 transition-colors">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-neutral-900">
                    {{ $project->name }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                    <x-badge variant="{{ $project->status === 'active' ? 'success' : 'neutral' }}">
                        {{ ucfirst($project->status) }}
                    </x-badge>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                    <x-button variant="secondary" size="sm" href="/projects/{{ $project->id }}">
                        Voir
                    </x-button>
                </td>
            </tr>
        @endforeach
    </x-slot:body>
</x-table>
```

**Empty State:**
```blade
<x-table>
    <x-slot:header>
        <!-- headers -->
    </x-slot:header>

    <x-slot:body>
        @forelse($items as $item)
            <!-- rows -->
        @empty
            <tr>
                <td colspan="3" class="px-6 py-12 text-center text-sm text-neutral-500">
                    Aucun élément trouvé
                </td>
            </tr>
        @endforelse
    </x-slot:body>
</x-table>
```

---

### 7. Alert

**Fichier:** `resources/views/components/alert.blade.php`

Notification ou message d'alerte.

**Props:**
- `variant` (string, optional, default: 'info')
  - `success` - Vert
  - `danger` - Rouge
  - `info` - Bleu

**Usage:**
```blade
<x-alert variant="success">
    Le projet a été créé avec succès.
</x-alert>

<x-alert variant="danger">
    Une erreur est survenue lors de la suppression.
</x-alert>

<x-alert variant="info">
    Vos modifications seront enregistrées automatiquement.
</x-alert>
```

**Avec Icônes:**
Les icônes sont automatiques selon le variant (✓, ✗, ℹ).

---

### 8. Form Input

**Fichier:** `resources/views/components/form-input.blade.php`

Input de formulaire avec label, helper et error.

**Props:**
- `name` (string, required) - Nom du champ
- `label` (string, optional) - Label du champ
- `type` (string, optional, default: 'text') - Type d'input
- `placeholder` (string, optional) - Placeholder
- `helper` (string, optional) - Texte d'aide
- `value` (string, optional) - Valeur pré-remplie
- `error` (string, optional) - Message d'erreur
- `required` (boolean, optional) - Champ requis

**Usage:**
```blade
<x-form-input
    name="name"
    label="Nom du projet"
    type="text"
    placeholder="Mon site web"
    helper="Le nom affiché dans la liste des projets"
    :value="old('name', $project->name ?? '')"
    :error="$errors->first('name')"
    required
/>
```

**Avec Validation Laravel:**
```blade
<form method="POST" action="/projects">
    @csrf

    <x-form-input
        name="name"
        label="Nom"
        :value="old('name')"
        :error="$errors->first('name')"
        required
    />

    <x-form-input
        name="url"
        label="URL"
        type="url"
        :value="old('url')"
        :error="$errors->first('url')"
        required
    />

    <x-button variant="primary" type="submit">
        Créer
    </x-button>
</form>
```

---

## 🏗️ Patterns Communs

### Dashboard Layout

```blade
@extends('layouts.app')

@section('title', 'Dashboard')

@section('breadcrumb')
    <span class="text-neutral-900 font-medium">Dashboard</span>
@endsection

@section('content')
    <x-page-header title="Dashboard" subtitle="Vue d'ensemble" />

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <x-stats-card label="Stat 1" value="100" icon="📊" />
        <x-stats-card label="Stat 2" value="50" icon="📈" />
        <x-stats-card label="Stat 3" value="25" icon="📉" />
    </div>

    <!-- Content Cards -->
    <div class="bg-white border border-neutral-200 rounded-lg p-6">
        <h2 class="text-lg font-semibold text-neutral-900 mb-4">Section</h2>
        <!-- Content -->
    </div>
@endsection
```

### List Page with Table

```blade
@extends('layouts.app')

@section('breadcrumb')
    <a href="/dashboard" class="text-neutral-500 hover:text-neutral-700">Dashboard</a>
    <span class="mx-2 text-neutral-400">/</span>
    <span class="text-neutral-900 font-medium">Projets</span>
@endsection

@section('content')
    <x-page-header
        title="Projets"
        subtitle="{{ count($projects) }} projet(s)">
        <x-slot:actions>
            <x-button variant="primary" href="/projects/create">
                + Créer
            </x-button>
        </x-slot:actions>
    </x-page-header>

    <x-table>
        <x-slot:header>
            <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase">Nom</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase">Status</th>
        </x-slot:header>

        <x-slot:body>
            @forelse($projects as $project)
                <tr class="hover:bg-neutral-50">
                    <td class="px-6 py-4 text-sm text-neutral-900">{{ $project->name }}</td>
                    <td class="px-6 py-4 text-sm">
                        <x-badge variant="success">Actif</x-badge>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="2" class="px-6 py-12 text-center text-neutral-500">
                        Aucun projet
                    </td>
                </tr>
            @endforelse
        </x-slot:body>
    </x-table>
@endsection
```

### Form Page

```blade
@extends('layouts.app')

@section('breadcrumb')
    <a href="/dashboard" class="text-neutral-500 hover:text-neutral-700">Dashboard</a>
    <span class="mx-2 text-neutral-400">/</span>
    <a href="/projects" class="text-neutral-500 hover:text-neutral-700">Projets</a>
    <span class="mx-2 text-neutral-400">/</span>
    <span class="text-neutral-900 font-medium">Créer</span>
@endsection

@section('content')
    <x-page-header title="Créer un projet" />

    <div class="max-w-2xl">
        <div class="bg-white border border-neutral-200 rounded-lg p-6">
            <form method="POST" action="/projects">
                @csrf

                <x-form-input
                    name="name"
                    label="Nom du projet"
                    :value="old('name')"
                    :error="$errors->first('name')"
                    required
                />

                <x-form-input
                    name="url"
                    label="URL"
                    type="url"
                    :value="old('url')"
                    :error="$errors->first('url')"
                    required
                />

                <div class="flex items-center space-x-3 mt-6">
                    <x-button variant="primary" type="submit">
                        Créer le projet
                    </x-button>
                    <x-button variant="secondary" href="/projects">
                        Annuler
                    </x-button>
                </div>
            </form>
        </div>
    </div>
@endsection
```

---

## ✅ TODO: Composants Futurs

Ces composants seront créés dans les phases ultérieures :

- [ ] `form-select.blade.php` - Select avec options
- [ ] `form-textarea.blade.php` - Textarea
- [ ] `form-checkbox.blade.php` - Checkbox
- [ ] `modal.blade.php` - Modal dialog
- [ ] `dropdown.blade.php` - Dropdown menu
- [ ] `pagination.blade.php` - Pagination
- [ ] `empty-state.blade.php` - Empty state réutilisable
- [ ] `loading-spinner.blade.php` - Loading state

---

## 🎯 Best Practices

### 1. Toujours Utiliser les Composants

❌ **Mauvais:**
```blade
<button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
    Créer
</button>
```

✅ **Bon:**
```blade
<x-button variant="primary">
    Créer
</x-button>
```

### 2. Respecter les Variants

❌ **Mauvais:**
```blade
<x-badge variant="warning">Modifié</x-badge>  <!-- warning n'existe pas -->
```

✅ **Bon:**
```blade
<x-badge variant="neutral">Modifié</x-badge>
```

### 3. Ne Pas Dupliquer le Markup

❌ **Mauvais:**
```blade
<!-- Page 1 -->
<div class="mb-8">
    <h1 class="text-2xl font-semibold text-neutral-900">Titre</h1>
</div>

<!-- Page 2 -->
<div class="mb-8">
    <h1 class="text-2xl font-semibold text-neutral-900">Autre Titre</h1>
</div>
```

✅ **Bon:**
```blade
<!-- Page 1 -->
<x-page-header title="Titre" />

<!-- Page 2 -->
<x-page-header title="Autre Titre" />
```

---

## 📚 Références

- [Laravel Blade Components](https://laravel.com/docs/10.x/blade#components)
- [Tailwind CSS](https://tailwindcss.com/)
- [Design System Variables](./UI-REDESIGN-PROPOSAL.md)

---

**Créé le:** 2026-02-12
**Mis à jour:** 2026-02-12
**Version:** 1.0 (Phase 1 Foundation)

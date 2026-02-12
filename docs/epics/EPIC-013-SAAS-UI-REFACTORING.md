# EPIC-013 : SaaS UI/UX Refactoring

**Status:** Planifié
**Priority:** High
**Epic Type:** Infrastructure / UX
**Estimated Points:** 21-34 points
**Estimated Stories:** 7-11 stories
**Dependencies:** EPIC-011 (partiellement complété)

---

## Vue d'Ensemble

Refactoriser l'interface utilisateur de Link Tracker pour adopter une architecture SaaS moderne avec sidebar navigation, breadcrumbs, composants Blade réutilisables, et un système de design minimaliste cohérent.

**Problème :**
L'interface actuelle utilise une approche centrée (landing page style) avec navigation fragmentée, palette de couleurs surchargée, et composants Vue.js dupliqués. Cela ne correspond pas aux attentes d'une application SaaS professionnelle où l'utilisateur passe plusieurs heures par semaine.

**Solution :**
Adopter un layout SaaS avec sidebar persistante, breadcrumb contextuel, palette minimale (4 couleurs), et composants Blade réutilisables pour alléger le code Vue.js.

---

## Objectifs Business

1. **Améliorer la productivité utilisateur**
   - Navigation instantanée via sidebar (0 clics)
   - Contexte clair avec breadcrumb
   - Réduction de la charge cognitive (moins de couleurs)

2. **Professionnaliser l'image**
   - Layout SaaS moderne (comme Linear, Stripe Dashboard)
   - Design cohérent et épuré
   - Crédibilité augmentée pour les utilisateurs professionnels (SEO consultants)

3. **Faciliter la maintenance**
   - Composants Blade réutilisables → moins de duplication
   - Variables CSS centralisées → changements globaux faciles
   - Architecture claire → onboarding développeurs rapide

---

## Functional Requirements Couverts

- **NFR-001 :** Performance - Temps de réponse des pages
  - Sidebar server-side (Blade) = pas de JS = chargement instantané
  - System fonts = 0ms de chargement vs Google Fonts

- **NFR-011 :** Compatibilité - Navigateurs et devices
  - Sidebar collapse sur mobile (drawer off-canvas)
  - Responsive breakpoints conservés

- **FR-028 :** Afficher le tableau de bord global
  - Nouveau layout Dashboard avec stats cards cohérentes

---

## Scope Détaillé

### 1. Design System Foundation

**Composants :**
- Palette de couleurs minimale (Neutral, Brand, Success, Danger)
- Variables CSS pour tous les tokens (couleurs, espacements, typographie)
- Documentation design system

**Livrables :**
- `docs/design-system/UI-REDESIGN-PROPOSAL.md` ✅ (créé)
- `resources/css/variables.css` (nouveau)
- `docs/design-system/COMPONENT-LIBRARY.md`

---

### 2. Layout & Navigation

**Composants :**
- Sidebar navigation (desktop)
- Mobile drawer (off-canvas)
- Topbar avec breadcrumb + user menu
- Responsive breakpoints (mobile, tablet, desktop)

**Livrables :**
- `resources/views/layouts/app.blade.php`
- `resources/views/components/sidebar.blade.php`
- `resources/views/components/topbar.blade.php`
- `resources/views/components/breadcrumb.blade.php`

---

### 3. Composants Blade Réutilisables

**Composants de Base :**
- Page Header (titre + actions)
- Stats Card
- Table (responsive)
- Badge (success, danger, neutral)
- Button (primary, secondary, danger)
- Form Input
- Alert / Notification

**Livrables :**
- `resources/views/components/page-header.blade.php`
- `resources/views/components/stats-card.blade.php`
- `resources/views/components/table.blade.php`
- `resources/views/components/badge.blade.php`
- `resources/views/components/button.blade.php`
- `resources/views/components/form-input.blade.php`
- `resources/views/components/alert.blade.php`

---

### 4. Migration Pages Existantes

**Pages à Migrer vers Nouveau Layout :**
- Dashboard (nouvelle page)
- Projects Index
- Projects Show
- Backlinks Index
- Backlinks Show
- Alerts Index

**Approche :**
- Conserver Vue.js pour la logique métier
- Remplacer HTML répétitif par composants Blade
- Intégrer breadcrumb sur chaque page

---

### 5. Color System Implementation

**Actions :**
- Remplacer toutes les couleurs par variables CSS
- Supprimer palette jaune (remplacer par neutral)
- Uniformiser les badges de statut
- Valider contraste WCAG AA

**Livrables :**
- `resources/css/app.css` mis à jour
- Audit couleurs (avant/après)

---

### 6. Responsive Navigation

**Mobile (<1024px) :**
- Hamburger menu (top-left)
- Sidebar devient drawer off-canvas
- Breadcrumb collapse sur très petit écran

**Desktop (≥1024px) :**
- Sidebar fixe 256px
- Content area calculé dynamiquement
- Max-width 1400px centré

---

### 7. Documentation & Guidelines

**Docs à Créer :**
- Guide d'utilisation des composants Blade
- Exemples de layouts
- Règles du design system (couleurs, typographie, espacements)

---

## User Stories (Détaillées)

### STORY-019 : Créer le Layout SaaS avec Sidebar (5 points)

**En tant qu'** utilisateur de Link Tracker
**Je veux** avoir une sidebar de navigation persistante
**Afin de** naviguer rapidement entre les sections sans chercher des boutons

**Acceptance Criteria :**
- [ ] Layout `app.blade.php` avec sidebar + content area
- [ ] Sidebar fixe 256px sur desktop
- [ ] Sidebar collapse en drawer sur mobile (<1024px)
- [ ] Navigation : Dashboard, Projets, Backlinks, Alertes, Commandes, Paramètres
- [ ] Active state visuellement distinct
- [ ] Responsive breakpoints testés

**Technical Notes :**
- Utiliser Tailwind `lg:` prefix pour breakpoint 1024px
- Sidebar en `position: fixed` sur desktop
- Content area avec `lg:pl-64` (256px sidebar)

---

### STORY-020 : Implémenter Breadcrumb Navigation (2 points)

**En tant qu'** utilisateur de Link Tracker
**Je veux** voir un fil d'Ariane (breadcrumb)
**Afin de** savoir où je me trouve et remonter facilement dans l'arborescence

**Acceptance Criteria :**
- [ ] Composant Blade `breadcrumb.blade.php`
- [ ] Topbar avec breadcrumb + user menu
- [ ] Breadcrumb sur toutes les pages internes
- [ ] Format : `Dashboard / Projets / Mon Site` avec liens cliquables
- [ ] Collapse texte sur mobile si trop long

**Examples :**
- Dashboard : `Dashboard`
- Projects Index : `Dashboard / Projets`
- Project Show : `Dashboard / Projets / Mon Site Web`
- Backlinks Index : `Dashboard / Projets / Mon Site Web / Backlinks`

---

### STORY-021 : Créer les Composants Blade de Base (3 points)

**En tant que** développeur
**Je veux** avoir des composants Blade réutilisables
**Afin de** réduire la duplication de code et uniformiser l'UI

**Acceptance Criteria :**
- [ ] Composant `page-header.blade.php` (titre + actions)
- [ ] Composant `stats-card.blade.php` (label + valeur + icône)
- [ ] Composant `badge.blade.php` (success, danger, neutral variants)
- [ ] Composant `button.blade.php` (primary, secondary, danger variants)
- [ ] Composant `table.blade.php` (header + body slots)
- [ ] Documentation d'usage pour chaque composant

**Technical Notes :**
- Utiliser `@props` pour typage
- Variants via array PHP
- Slots pour contenu flexible

---

### STORY-022 : Implémenter le Nouveau Color System (3 points)

**En tant que** designer/développeur
**Je veux** une palette minimale avec rôles clairs
**Afin de** améliorer la hiérarchie visuelle et réduire la charge cognitive

**Acceptance Criteria :**
- [ ] Variables CSS pour Neutral (9 nuances)
- [ ] Variables CSS pour Brand (bleu, 4 nuances)
- [ ] Variables CSS pour Success (vert, 2 nuances)
- [ ] Variables CSS pour Danger (rouge, 2 nuances)
- [ ] Suppression palette jaune (remplacer par neutral)
- [ ] Audit de toutes les pages : remplacement des anciennes couleurs
- [ ] Contraste WCAG AA vérifié

**Migration Guide :**
```
AVANT → APRÈS
bg-blue-100 → bg-neutral-100 (si secondaire)
bg-blue-600 → bg-brand-500 (si action primaire)
bg-yellow-100 → bg-neutral-100 (badge "modifié")
text-gray-600 → text-neutral-600
```

---

### STORY-023 : Migrer Dashboard vers Nouveau Layout (3 points)

**En tant qu'** utilisateur
**Je veux** un dashboard clair avec stats cards
**Afin de** voir l'état de mes backlinks en un coup d'œil

**Acceptance Criteria :**
- [ ] Dashboard utilise `layouts/app.blade.php`
- [ ] Breadcrumb : "Dashboard"
- [ ] Stats cards : Backlinks actifs, Perdus, Projets
- [ ] Section "Alertes récentes" (5 dernières)
- [ ] Section "Dernières vérifications" (optionnel)
- [ ] Design cohérent avec nouveau color system

---

### STORY-024 : Migrer Projects Index vers Nouveau Layout (2 points)

**En tant qu'** utilisateur
**Je veux** voir mes projets dans le nouveau layout
**Afin de** bénéficier de la nouvelle navigation

**Acceptance Criteria :**
- [ ] Projects Index utilise `layouts/app.blade.php`
- [ ] Breadcrumb : "Dashboard / Projets"
- [ ] Page header avec bouton "Créer un projet"
- [ ] Cards projets avec badges cohérents (success/neutral)
- [ ] Suppression des anciennes couleurs (jaune → neutral)

---

### STORY-025 : Migrer Backlinks Index vers Nouveau Layout (3 points)

**En tant qu'** utilisateur
**Je veux** voir mes backlinks dans le nouveau layout
**Afin de** naviguer facilement entre projets et backlinks

**Acceptance Criteria :**
- [ ] Backlinks Index utilise `layouts/app.blade.php`
- [ ] Breadcrumb : "Dashboard / Projets / {Nom Projet} / Backlinks"
- [ ] Stats cards : Actifs, Perdus, Modifiés
- [ ] Table avec composant Blade réutilisable
- [ ] Badges cohérents (success, danger, neutral)

---

### STORY-026 : Créer Composants Form Blade (3 points)

**En tant que** développeur
**Je veux** des composants form réutilisables
**Afin de** uniformiser les formulaires (Projects, Backlinks, Settings)

**Acceptance Criteria :**
- [ ] Composant `form-input.blade.php` (label + input + error)
- [ ] Composant `form-select.blade.php`
- [ ] Composant `form-textarea.blade.php`
- [ ] Composant `form-checkbox.blade.php`
- [ ] Validation errors affichés avec design cohérent
- [ ] Documentation d'usage

---

### STORY-027 : Responsive Mobile Navigation (3 points)

**En tant qu'** utilisateur mobile
**Je veux** accéder à la navigation via un menu drawer
**Afin de** utiliser l'app sur tablette/smartphone

**Acceptance Criteria :**
- [ ] Hamburger button visible sur mobile (<1024px)
- [ ] Sidebar se transforme en drawer off-canvas
- [ ] Overlay semi-transparent quand drawer ouvert
- [ ] Fermeture au clic extérieur
- [ ] Animation slide-in/slide-out fluide
- [ ] Breadcrumb collapse sur très petit écran (<640px)

**Technical Notes :**
- AlpineJS ou Vue.js pour toggle drawer
- `transform translateX(-100%)` → `translateX(0)` animation

---

### STORY-028 : Documentation Design System (2 points)

**En tant que** développeur
**Je veux** une documentation complète du design system
**Afin de** créer de nouvelles pages cohérentes

**Acceptance Criteria :**
- [ ] Doc : Palette de couleurs avec exemples
- [ ] Doc : Composants Blade avec code exemples
- [ ] Doc : Layouts disponibles
- [ ] Doc : Typographie (font stack, échelle, weights)
- [ ] Doc : Espacements (padding, margin standards)
- [ ] Doc : Migration guide (ancien → nouveau)

**Livrables :**
- `docs/design-system/COMPONENT-LIBRARY.md`
- `docs/design-system/MIGRATION-GUIDE.md`

---

### STORY-029 : Audit & Cleanup UI (3 points)

**En tant que** développeur
**Je veux** nettoyer l'ancien code UI
**Afin de** supprimer les classes Tailwind obsolètes

**Acceptance Criteria :**
- [ ] Recherche globale : `bg-blue-100`, `bg-yellow-`, etc.
- [ ] Remplacement par nouvelles variables
- [ ] Suppression des composants Vue.js dupliqués (remplacés par Blade)
- [ ] Test manuel de toutes les pages migrées
- [ ] Vérification responsive (mobile, tablet, desktop)

---

## Technical Architecture

### Blade Components Structure

```
resources/views/
├── layouts/
│   └── app.blade.php              # Layout principal
├── components/
│   ├── sidebar.blade.php          # Navigation sidebar
│   ├── topbar.blade.php           # Breadcrumb + user menu
│   ├── page-header.blade.php      # Titre + actions
│   ├── stats-card.blade.php       # Cards statistiques
│   ├── table.blade.php            # Table responsive
│   ├── badge.blade.php            # Badges statut
│   ├── button.blade.php           # Boutons (variants)
│   ├── alert.blade.php            # Notifications
│   └── forms/
│       ├── input.blade.php
│       ├── select.blade.php
│       ├── textarea.blade.php
│       └── checkbox.blade.php
└── pages/
    ├── dashboard.blade.php        # Dashboard (nouvelle page)
    ├── projects/
    │   ├── index.blade.php
    │   ├── show.blade.php
    │   └── create.blade.php
    └── backlinks/
        ├── index.blade.php
        └── show.blade.php
```

### CSS Variables Structure

```css
/* resources/css/variables.css */
:root {
    /* Neutral (95% de l'UI) */
    --color-neutral-50: #fafafa;
    --color-neutral-100: #f5f5f5;
    --color-neutral-200: #e5e5e5;
    --color-neutral-400: #a3a3a3;
    --color-neutral-600: #525252;
    --color-neutral-900: #171717;

    /* Brand (Actions) */
    --color-brand-500: #3b82f6;
    --color-brand-600: #2563eb;

    /* Success */
    --color-success-50: #f0fdf4;
    --color-success-600: #16a34a;

    /* Danger */
    --color-danger-50: #fef2f2;
    --color-danger-600: #dc2626;

    /* Spacing */
    --spacing-4: 1rem;
    --spacing-6: 1.5rem;
    --spacing-8: 2rem;

    /* Layout */
    --sidebar-width: 256px;
    --topbar-height: 64px;
}
```

### Tailwind Config Extension

```javascript
// tailwind.config.js
module.exports = {
    theme: {
        extend: {
            colors: {
                neutral: {
                    50: 'var(--color-neutral-50)',
                    100: 'var(--color-neutral-100)',
                    // ... etc
                },
                brand: {
                    500: 'var(--color-brand-500)',
                    600: 'var(--color-brand-600)',
                },
                // ...
            }
        }
    }
}
```

---

## Migration Strategy

### Phase 1 : Foundation (Sprint 3)
- [ ] STORY-021 : Composants Blade de base
- [ ] STORY-022 : Nouveau color system
- [ ] STORY-028 : Documentation design system

**Objectif :** Infrastructure prête, 0 pages migrées (pas de breaking change)

---

### Phase 2 : Layout & Navigation (Sprint 4)
- [ ] STORY-019 : Layout SaaS avec sidebar
- [ ] STORY-020 : Breadcrumb navigation
- [ ] STORY-027 : Responsive mobile navigation

**Objectif :** Layout fonctionnel, prêt pour migration pages

---

### Phase 3 : Pages Migration (Sprint 5)
- [ ] STORY-023 : Dashboard
- [ ] STORY-024 : Projects Index
- [ ] STORY-025 : Backlinks Index

**Objectif :** Pages principales migrées, app utilisable en production

---

### Phase 4 : Forms & Cleanup (Sprint 6)
- [ ] STORY-026 : Composants Form Blade
- [ ] STORY-029 : Audit & cleanup UI

**Objectif :** App 100% migrée, ancien code supprimé

---

## Success Metrics

### UX Metrics
- **Navigation speed** : 0 clics pour accéder à n'importe quelle section (via sidebar)
- **Context awareness** : 100% des pages ont breadcrumb
- **Color simplicity** : Réduction de 5+ couleurs → 4 couleurs

### DX Metrics
- **Code reuse** : 8 composants Blade réutilisés sur toutes les pages
- **Lines of code** : Réduction estimée de 30% (grâce aux composants)
- **Onboarding time** : Nouveau dev comprend le design system en < 1h (grâce à docs)

### Performance Metrics
- **Bundle size** : Réduction CSS (-15% estimé grâce à variables)
- **TTI (Time to Interactive)** : Pas de dégradation (sidebar server-side)

---

## Risks & Mitigations

### Risk 1 : Transition Progressive Vue → Blade
**Impact :** Confusion entre ancien/nouveau layout pendant migration

**Mitigation :**
- Migration page par page (pas de big bang)
- Feature flag si besoin (`config('ui.new_layout')`)
- Documentation claire des pages migrées

---

### Risk 2 : Responsive Mobile Sidebar
**Impact :** Complexité d'implémentation drawer off-canvas

**Mitigation :**
- Utiliser AlpineJS (déjà dans Laravel Breeze) ou Vue.js
- Tester sur vrais devices (pas seulement Chrome DevTools)
- Fallback : simple collapse sans animation si problème

---

### Risk 3 : Adoption Développeurs
**Impact :** Développeurs continuent d'utiliser ancien pattern

**Mitigation :**
- Documentation complète avec exemples
- Code review : bloquer PR avec ancien pattern
- Supprimer ancien code après migration

---

## Dependencies

### Bloque :
- Tous les futurs développements UI (doivent utiliser nouveau layout)

### Bloqué par :
- EPIC-011 (partiellement) : Certaines stories EPIC-011 peuvent être remplacées par EPIC-013

### Synergies :
- EPIC-004 (Alertes) : Notification component réutilisable
- EPIC-007 (Dashboard) : Utilise nouveau layout

---

## Out of Scope

**Explicitement exclu de cet epic :**
- ❌ Dark mode (user requirement : jamais de dark mode)
- ❌ Animations complexes (focus sur fonction > forme)
- ❌ Illustrations custom / iconographie
- ❌ Refactoring Vue.js composants (garde logique actuelle)
- ❌ Multi-langue i18n (pas prévu v1)

---

## Acceptance Criteria (Epic Level)

**L'EPIC-013 est considéré terminé quand :**
- [ ] Toutes les pages utilisent `layouts/app.blade.php`
- [ ] Sidebar visible sur toutes les pages (desktop)
- [ ] Breadcrumb présent et fonctionnel sur toutes les pages
- [ ] 8+ composants Blade créés et documentés
- [ ] Palette réduite à 4 couleurs (Neutral, Brand, Success, Danger)
- [ ] Responsive mobile testé et fonctionnel
- [ ] Documentation design system complète
- [ ] Ancien code UI supprimé (pas de duplication)
- [ ] Tests manuels passés sur toutes les pages
- [ ] User feedback positif (clarity, navigation speed)

---

## References

- [Design Proposal](../design-system/UI-REDESIGN-PROPOSAL.md)
- [PRD - EPIC-011](../prd-link-tracker-2026-02-09.md#epic-011--interface-utilisateur-moderne-et-responsive)
- [Laravel Blade Components](https://laravel.com/docs/10.x/blade#components)
- [Tailwind CSS Variables](https://tailwindcss.com/docs/customizing-colors#using-css-variables)

---

**Créé le :** 2026-02-12
**Auteur :** Claude Code
**Status :** Ready for Sprint Planning

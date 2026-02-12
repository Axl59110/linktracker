# STORY-011: Backlinks List Vue Component

**Epic:** EPIC-002 - Backlinks Management
**Points:** 3
**Status:** ✅ Completed
**Branch:** `feature/STORY-011-backlinks-list-component`

## Objectif

Créer le composant Vue.js pour afficher la liste des backlinks d'un projet avec statistiques, filtrage par statut, et actions (voir, modifier).

## Description

Cette story implémente l'interface utilisateur pour afficher tous les backlinks d'un projet sous forme de tableau avec :
- Statistiques en temps réel (actifs, perdus, modifiés)
- Tableau avec colonnes : source URL, anchor text, statut, dernier check, présence
- Actions rapides : voir détails, modifier
- États loading/error/empty
- Design cohérent avec le reste de l'application

## Implémentation

### 1. Backlinks Store (Pinia)

**Fichier:** `resources/js/stores/backlinks.js`

Store Pinia pour gérer l'état des backlinks :

```javascript
export const useBacklinksStore = defineStore('backlinks', () => {
    const backlinks = ref([]);
    const loading = ref(false);
    const error = ref(null);
    const hasBacklinks = computed(() => backlinks.value.length > 0);

    // Methods
    async function fetchBacklinks(projectId)
    async function getBacklink(projectId, backlinkId)
    async function createBacklink(projectId, backlinkData)
    async function updateBacklink(projectId, backlinkId, backlinkData)
    async function deleteBacklink(projectId, backlinkId)
    function clearError()
});
```

**Pattern:** Composition API avec composables

### 2. BacklinksList Component

**Fichier:** `resources/js/components/Backlinks/BacklinksList.vue`

Composant principal affichant la liste des backlinks.

**Props:**
- `projectId` (Number|String, required): ID du projet

**Features:**

#### Stats Cards
Trois cartes affichant :
- **Actifs** (vert) : Nombre de backlinks avec status='active'
- **Perdus** (rouge) : Nombre de backlinks avec status='lost'
- **Modifiés** (jaune) : Nombre de backlinks avec status='changed'

```vue
<div class="grid gap-4 md:grid-cols-3">
    <div class="bg-white border border-gray-200 rounded-lg p-4">
        <p class="text-2xl font-bold text-green-600">{{ activeBacklinks.length }}</p>
    </div>
</div>
```

#### Tableau Responsive
Colonnes :
1. **Source URL** - Lien cliquable vers la page source
2. **Texte d'ancre** - Anchor text ou 'N/A'
3. **Statut** - Badge coloré (Actif/Perdu/Modifié)
4. **Dernier check** - Date et heure du last_checked_at
5. **Présence** - Icône ✓ (présent), ✗ (non trouvé), ⏳ (jamais vérifié)
6. **Actions** - Boutons Voir/Modifier

**Computed Properties:**
```javascript
const activeBacklinks = computed(() => store.backlinks.filter(b => b.status === 'active'));
const lostBacklinks = computed(() => store.backlinks.filter(b => b.status === 'lost'));
const changedBacklinks = computed(() => store.backlinks.filter(b => b.status === 'changed'));
```

**Helpers:**
```javascript
getStatusColor(status) // Retourne classes Tailwind pour badges
getStatusLabel(status) // Retourne label en français
getCheckStatusIcon(backlink) // Retourne {icon, color, label} selon latest_check
formatDate(date) // Format: DD/MM/YYYY
formatDateTime(date) // Format: DD/MM/YYYY HH:MM
```

#### États du Composant

**Loading:**
```vue
<div v-if="store.loading">
    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
    <p>Chargement des backlinks...</p>
</div>
```

**Error:**
```vue
<div v-else-if="store.error" class="bg-red-50 border border-red-200">
    {{ store.error }}
</div>
```

**Empty State:**
```vue
<div v-else-if="!store.hasBacklinks" class="text-center py-12">
    <svg>...</svg>
    <h3>Aucun backlink</h3>
    <p>Commencez par ajouter votre premier backlink à surveiller</p>
    <button>Ajouter un backlink</button>
</div>
```

**Success (Data):**
- Stats cards avec nombres réels
- Tableau avec tous les backlinks
- Boutons actions fonctionnels

### 3. Backlinks Index Page

**Fichier:** `resources/js/pages/Backlinks/Index.vue`

Page wrapper qui :
- Récupère `projectId` depuis `route.params`
- Affiche `<BacklinksList :project-id="projectId" />`
- Ajoute padding et container

### 4. Router Configuration

**Fichier:** `resources/js/router/index.js`

Ajout de la route :
```javascript
{
    path: '/projects/:projectId/backlinks',
    name: 'backlinks.index',
    component: BacklinksIndex,
    meta: { title: 'Backlinks du projet', requiresAuth: true }
}
```

### 5. Projects Show Update

**Fichier:** `resources/js/pages/Projects/Show.vue`

Mise à jour de la section backlinks pour ajouter le bouton d'accès :
```vue
<div class="bg-white rounded-lg border p-6">
    <h2>Backlinks</h2>
    <button @click="router.push(`/projects/${project.id}/backlinks`)">
        Voir tous les backlinks
    </button>
    <p>Gérez et surveillez les backlinks pointant vers ce projet.</p>
</div>
```

## Design

### Palette de Couleurs

**Status Badges:**
- Actif : `bg-green-100 text-green-800 border-green-200`
- Perdu : `bg-red-100 text-red-800 border-red-200`
- Modifié : `bg-yellow-100 text-yellow-800 border-yellow-200`

**Check Status Icons:**
- ✓ Présent : `text-green-600`
- ✗ Non trouvé : `text-red-600`
- ⏳ Jamais vérifié : `text-gray-400`

**Buttons:**
- Primary : `bg-blue-600 hover:bg-blue-700 text-white`
- Secondary : `text-gray-600 hover:text-gray-900`

### Responsive Design

- **Desktop (lg+)** : Stats cards en 3 colonnes
- **Tablet (md)** : Stats cards en 3 colonnes, tableau scroll horizontal si nécessaire
- **Mobile** : Stats cards en 1 colonne, tableau scroll horizontal

## Navigation

**Flow utilisateur:**
1. `/projects` → Voir la liste des projets
2. `/projects/:id` → Cliquer "Voir tous les backlinks"
3. `/projects/:projectId/backlinks` → **Voir la liste des backlinks** ✓
4. Cliquer "Voir" sur un backlink → `/projects/:projectId/backlinks/:id` (Story-012)
5. Cliquer "Modifier" → `/projects/:projectId/backlinks/:id/edit` (Story-012)
6. Cliquer "+ Ajouter un backlink" → `/projects/:projectId/backlinks/create` (Story-012)

## Fichiers Créés/Modifiés

**Créés:**
- `resources/js/stores/backlinks.js` - Store Pinia
- `resources/js/components/Backlinks/BacklinksList.vue` - Composant liste
- `resources/js/pages/Backlinks/Index.vue` - Page wrapper
- `docs/stories/STORY-011.md` - Documentation

**Modifiés:**
- `resources/js/router/index.js` - Ajout route backlinks.index
- `resources/js/pages/Projects/Show.vue` - Ajout bouton "Voir tous les backlinks"

**Build:**
- `npm run build` - Assets rebuildés avec succès

## Tests Manuels

### Scénarios de Test

1. **Empty State**
   - Créer un nouveau projet sans backlinks
   - Naviguer vers `/projects/:id/backlinks`
   - Vérifier : Message "Aucun backlink", bouton "Ajouter un backlink"

2. **Loading State**
   - Rafraîchir la page `/projects/:id/backlinks`
   - Vérifier : Spinner visible pendant chargement

3. **Data Display**
   - Créer plusieurs backlinks (via API/Tinker)
   - Vérifier : Stats cards affichent les bons nombres
   - Vérifier : Tableau affiche toutes les colonnes
   - Vérifier : Les URLs sont cliquables (nouvel onglet)
   - Vérifier : Les badges de statut ont les bonnes couleurs

4. **Actions**
   - Cliquer sur icône "Voir" → Devrait naviguer vers `/projects/:projectId/backlinks/:id`
   - Cliquer sur icône "Modifier" → Devrait naviguer vers `/projects/:projectId/backlinks/:id/edit`
   - Cliquer "+ Ajouter un backlink" → Devrait naviguer vers `/projects/:projectId/backlinks/create`

5. **Responsive**
   - Tester sur mobile (DevTools responsive mode)
   - Vérifier : Stats cards empilées en colonne
   - Vérifier : Tableau scroll horizontal

## Dépendances

- ✅ STORY-010: Backlinks CRUD API (endpoint `/api/v1/projects/{project}/backlinks`)
- ✅ STORY-009: Backlink Model (statut, accessors, helpers)
- ✅ Vue Router configuré
- ✅ Pinia installé
- ✅ Tailwind CSS configuré

## Points d'Attention

### Eager Loading
L'API charge automatiquement `latestCheck` pour chaque backlink via :
```php
$backlinks = $project->backlinks()->with('latestCheck')->latest()->get();
```

Cela évite le problème N+1 queries et permet d'afficher l'icône de statut du check.

### Date Formatting
Utilisation de `toLocaleDateString('fr-FR')` pour affichage en français :
- Format date : `12/02/2026`
- Format datetime : `12/02/2026 15:30`

### Truncation
Les URLs et anchor text sont tronqués avec `truncate` et `max-w-xs` pour éviter le débordement du tableau.

### Link Target
Les liens vers source_url ouvrent dans un nouvel onglet avec `target="_blank"` et `rel="noopener noreferrer"` pour la sécurité.

## Exemples d'Utilisation

### Via Projects Show
```
1. Se connecter
2. Aller sur /projects
3. Cliquer "Voir" sur un projet
4. Cliquer "Voir tous les backlinks"
5. → Arrive sur /projects/1/backlinks
```

### Navigation Directe
```
http://app-laravel.test/projects/1/backlinks
```

### Avec Données de Test
```php
// Dans Tinker
$project = Project::find(1);
$project->backlinks()->create([
    'source_url' => 'https://example.com/article',
    'target_url' => 'https://mysite.com',
    'anchor_text' => 'Mon super site',
    'status' => 'active',
    'first_seen_at' => now(),
]);
```

Puis rafraîchir `/projects/1/backlinks` pour voir le backlink.

## Prochaines Étapes

- ✅ STORY-012: Backlink Create/Edit Form (formulaires création/modification)
- ⏳ STORY-013: HTTP Service for Checking Backlinks (vérification présence)
- ⏳ STORY-014: Check Backlink Job (job asynchrone de vérification)
- ⏳ STORY-017: Schedule Backlink Checks (planification automatique)

## Commits

```bash
git add .
git commit -m "feat(backlinks): implement Backlinks List Vue Component (STORY-011)

- BacklinksStore (Pinia) for state management
- BacklinksList component with stats cards and responsive table
- Stats cards: active/lost/changed backlinks count
- Table columns: source URL, anchor text, status, last check, presence, actions
- Empty/loading/error states
- Date formatting and status badges
- Navigation to view/edit backlinks
- Updated Projects Show with 'View all backlinks' button
- Route /projects/:projectId/backlinks
- Assets rebuilt with npm run build

Co-Authored-By: Claude Sonnet 4.5 <noreply@anthropic.com>"
```

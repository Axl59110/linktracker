# STORY-012: Backlink Create/Edit Form

**Epic:** EPIC-002 - Backlinks Management
**Points:** 3
**Status:** ✅ Completed
**Branch:** `feature/STORY-012-backlink-create-edit-form`

## Objectif

Créer les composants Vue.js pour permettre la création et la modification de backlinks, ainsi que la page de détails d'un backlink avec historique des vérifications.

## Description

Cette story implémente l'interface utilisateur complète pour gérer les backlinks :
- Formulaire réutilisable pour création et modification
- Validation côté client (URL format, champs requis)
- Gestion des erreurs backend
- Page de détails avec historique des vérifications
- Actions de suppression avec confirmation
- Navigation cohérente

## Implémentation

### 1. BacklinkForm Component

**Fichier:** `resources/js/components/Backlinks/BacklinkForm.vue`

Composant réutilisable pour create/edit avec validation côté client.

**Props:**
- `projectId` (Number|String, required): ID du projet parent
- `backlinkId` (Number|String, default: null): ID du backlink (null = mode création)

**Features:**

#### Mode Détection
```javascript
const isEditMode = computed(() => !!props.backlinkId);
```
Le composant détecte automatiquement s'il est en mode création ou édition selon la présence de `backlinkId`.

#### Champs du Formulaire

**Mode Création:**
- Source URL * (required, URL validation)
- Target URL * (required, URL validation)
- Anchor Text (optional)
- Status = 'active' (automatique)

**Mode Édition:**
- Source URL * (pré-rempli)
- Target URL * (pré-rempli)
- Anchor Text (pré-rempli)
- Status (select: active/lost/changed)

#### Validation Client

```javascript
const validateForm = () => {
    errors.value = {};
    let isValid = true;

    if (!formData.value.source_url) {
        errors.value.source_url = 'L\'URL source est requise';
        isValid = false;
    } else if (!isValidUrl(formData.value.source_url)) {
        errors.value.source_url = 'L\'URL source n\'est pas valide';
        isValid = false;
    }

    // Idem pour target_url
    return isValid;
};

const isValidUrl = (url) => {
    try {
        new URL(url);
        return true;
    } catch {
        return false;
    }
};
```

#### Gestion des Erreurs Backend

```javascript
try {
    if (isEditMode.value) {
        await store.updateBacklink(props.projectId, props.backlinkId, formData.value);
    } else {
        await store.createBacklink(props.projectId, formData.value);
    }
    router.push(`/projects/${props.projectId}/backlinks`);
} catch (err) {
    if (err.response?.data?.errors) {
        errors.value = err.response.data.errors; // Erreurs de validation Laravel
    } else {
        errors.value.general = err.response?.data?.message || 'Une erreur est survenue';
    }
}
```

#### États du Composant

**Loading (Edit Mode):**
- Spinner affiché pendant le chargement des données
- Champs désactivés pendant la soumission

**Form Display:**
- Tous les champs avec labels clairs
- Textes d'aide sous chaque champ
- Bordures rouges sur erreurs
- Messages d'erreur en rouge

### 2. Backlinks Create Page

**Fichier:** `resources/js/pages/Backlinks/Create.vue`

Page wrapper pour la création.

```vue
<script setup>
import { useRoute } from 'vue-router';
import BacklinkForm from '@/components/Backlinks/BacklinkForm.vue';

const route = useRoute();
const projectId = route.params.projectId;
</script>

<template>
    <div class="container mx-auto px-4 py-8">
        <BacklinkForm :project-id="projectId" />
    </div>
</template>
```

### 3. Backlinks Edit Page

**Fichier:** `resources/js/pages/Backlinks/Edit.vue`

Page wrapper pour l'édition.

```vue
<script setup>
import { useRoute } from 'vue-router';
import BacklinkForm from '@/components/Backlinks/BacklinkForm.vue';

const route = useRoute();
const projectId = route.params.projectId;
const backlinkId = route.params.id;
</script>

<template>
    <div class="container mx-auto px-4 py-8">
        <BacklinkForm
            :project-id="projectId"
            :backlink-id="backlinkId"
        />
    </div>
</template>
```

### 4. Backlinks Show Page

**Fichier:** `resources/js/pages/Backlinks/Show.vue`

Page de détails d'un backlink avec historique des vérifications.

**Features:**

#### Section Principale

Affiche les informations du backlink :
- Statut (badge coloré)
- Source URL (lien cliquable)
- Target URL (lien cliquable)
- Anchor Text
- État de vérification (icône + label)
- Dates : première détection, dernière vérification

#### Actions

- **Modifier** : Navigue vers `/projects/:projectId/backlinks/:id/edit`
- **Supprimer** : Demande confirmation puis supprime et redirige vers la liste
- **Retour** : Retourne à la liste des backlinks

#### Historique des Vérifications

Si `backlink.checks` existe et contient des données :
```vue
<div v-for="check in backlink.checks" class="border-l-4 pl-4 py-2"
     :class="check.is_present ? 'border-green-500' : 'border-red-500'">
    <p>{{ check.is_present ? '✓ Backlink trouvé' : '✗ Backlink non trouvé' }}</p>
    <p>{{ formatDateTime(check.checked_at) }}</p>
    <p>Code HTTP: {{ check.http_status }}</p>
</div>
```

Sinon, affiche un message vide :
```
"Aucune vérification n'a encore été effectuée pour ce backlink."
```

### 5. Router Configuration

**Fichier:** `resources/js/router/index.js`

Ajout de 3 nouvelles routes :

```javascript
import BacklinksCreate from '../pages/Backlinks/Create.vue';
import BacklinksEdit from '../pages/Backlinks/Edit.vue';
import BacklinksShow from '../pages/Backlinks/Show.vue';

// Routes
{
    path: '/projects/:projectId/backlinks/create',
    name: 'backlinks.create',
    component: BacklinksCreate,
    meta: { title: 'Ajouter un backlink', requiresAuth: true }
},
{
    path: '/projects/:projectId/backlinks/:id',
    name: 'backlinks.show',
    component: BacklinksShow,
    meta: { title: 'Détails du backlink', requiresAuth: true }
},
{
    path: '/projects/:projectId/backlinks/:id/edit',
    name: 'backlinks.edit',
    component: BacklinksEdit,
    meta: { title: 'Modifier le backlink', requiresAuth: true }
}
```

**Ordre Important:** La route `/create` doit être **avant** `/:id` pour éviter que "create" soit interprété comme un ID.

## Design

### Palette de Couleurs

**Formulaire:**
- Primary button : `bg-blue-600 hover:bg-blue-700 text-white`
- Cancel button : `border border-gray-300 text-gray-700 hover:bg-gray-50`
- Error state : `border-red-500`, `text-red-600`
- Disabled state : `opacity-50 cursor-not-allowed`

**Page Show:**
- Status badges : Identiques à BacklinksList
- Delete button : `bg-red-600 hover:bg-red-700 text-white`
- History borders : `border-green-500` (found) / `border-red-500` (lost)

### Responsive Design

- **Desktop (lg+)** : Formulaire max-width-2xl centré
- **Tablet/Mobile** : Formulaire pleine largeur avec padding
- **URLs** : `break-all` pour éviter le débordement

## Navigation

**Flow complet utilisateur:**

1. `/projects/:projectId/backlinks` → Liste des backlinks
2. Cliquer **"+ Ajouter un backlink"** → `/projects/:projectId/backlinks/create`
3. Remplir formulaire → Créer → Retour liste ✓
4. Cliquer **icône "Voir"** sur un backlink → `/projects/:projectId/backlinks/:id`
5. Cliquer **"Modifier"** → `/projects/:projectId/backlinks/:id/edit`
6. Modifier données → Mettre à jour → Retour liste ✓
7. Cliquer **"Supprimer"** → Confirmation → Suppression → Retour liste ✓

## Fichiers Créés/Modifiés

**Créés:**
- `resources/js/components/Backlinks/BacklinkForm.vue` - Composant formulaire
- `resources/js/pages/Backlinks/Create.vue` - Page création
- `resources/js/pages/Backlinks/Edit.vue` - Page édition
- `resources/js/pages/Backlinks/Show.vue` - Page détails
- `docs/stories/STORY-012.md` - Documentation

**Modifiés:**
- `resources/js/router/index.js` - Ajout des 3 routes (create, show, edit)

**Build:**
- `npm run build` - Assets rebuildés avec succès (101 modules, 6.16s)

## Tests Manuels

### Scénarios de Test

1. **Créer un Backlink (Succès)**
   - Aller sur `/projects/1/backlinks`
   - Cliquer "+ Ajouter un backlink"
   - Remplir : source_url, target_url, anchor_text
   - Soumettre
   - Vérifier : Redirection vers liste, backlink apparaît

2. **Validation Client**
   - Soumettre formulaire vide
   - Vérifier : Messages d'erreur "requis"
   - Entrer URL invalide (ex: "not-a-url")
   - Vérifier : Message "URL n'est pas valide"

3. **Validation Backend (SSRF)**
   - Créer backlink avec source_url="http://localhost"
   - Vérifier : Erreur backend SSRF s'affiche

4. **Modifier un Backlink**
   - Cliquer "Modifier" sur un backlink existant
   - Vérifier : Formulaire pré-rempli
   - Modifier anchor_text et status
   - Soumettre
   - Vérifier : Modifications sauvegardées

5. **Voir Détails**
   - Cliquer icône "Voir" sur un backlink
   - Vérifier : Toutes les infos affichées
   - Vérifier : Historique des checks (vide si jamais vérifié)

6. **Supprimer un Backlink**
   - Sur page Show, cliquer "Supprimer"
   - Vérifier : Popup de confirmation
   - Confirmer
   - Vérifier : Supprimé, redirection vers liste

7. **Annuler Création/Édition**
   - Commencer à remplir formulaire
   - Cliquer "Annuler"
   - Vérifier : Retour à la liste sans sauvegarder

## Dépendances

- ✅ STORY-011: Backlinks List Component (liste + store Pinia)
- ✅ STORY-010: Backlinks CRUD API (endpoints backend)
- ✅ Vue Router configuré
- ✅ Pinia store avec méthodes createBacklink, updateBacklink, deleteBacklink, getBacklink

## Points d'Attention

### Mode Détection Automatique

Le composant BacklinkForm détecte automatiquement le mode (create/edit) selon la prop `backlinkId`. Pas besoin de prop `mode` séparée.

### Validation Double Couche

- **Client** : Validation immédiate avec feedback visuel (URL format, required)
- **Backend** : Validation Laravel (SSRF protection, max length, etc.)

Les erreurs backend sont capturées et affichées dans le formulaire.

### Eager Loading dans Show

La méthode `store.getBacklink()` charge automatiquement :
```javascript
return response()->json($backlink->load(['latestCheck', 'checks']));
```

Cela permet d'afficher l'historique complet des vérifications.

### Suppression avec Confirmation

La suppression nécessite une double confirmation :
1. Bouton "Supprimer" sur la page Show
2. Popup `confirm()` natif du navigateur

### Loading States

- Formulaire en mode édition : Loading pendant fetch initial
- Soumission : Bouton disabled + spinner
- Page Show : Loading pendant fetch

## Exemples d'Utilisation

### Créer un Backlink

```
1. Aller sur /projects/1/backlinks
2. Cliquer "+ Ajouter un backlink"
3. Remplir :
   - Source URL: https://example.com/blog/article
   - Target URL: https://mysite.com
   - Anchor Text: Découvrir mon site
4. Cliquer "Créer le backlink"
5. → Arrive sur /projects/1/backlinks avec le nouveau backlink
```

### Modifier un Backlink

```
1. Depuis /projects/1/backlinks
2. Cliquer icône crayon sur un backlink
3. Modifier le statut : active → lost
4. Cliquer "Mettre à jour"
5. → Retour à la liste avec modification sauvegardée
```

### Voir Détails et Historique

```
1. Depuis /projects/1/backlinks
2. Cliquer icône œil sur un backlink
3. → Page Show avec toutes les infos
4. Si vérifications existent : Historique affiché avec codes HTTP
5. Sinon : Message "Aucune vérification"
```

## Prochaines Étapes

- ⏳ STORY-013: HTTP Service for Checking Backlinks (vérification présence du lien)
- ⏳ STORY-014: Check Backlink Job (job asynchrone de vérification)
- ⏳ STORY-017: Schedule Backlink Checks (planification automatique)

Les jobs de vérification rempliront l'historique `backlink.checks` visible sur la page Show.

## Commits

```bash
git add .
git commit -m "feat(backlinks): implement Backlink Create/Edit Form (STORY-012)" -m "- BacklinkForm component with create/edit modes" -m "- Client-side validation (URL format, required fields)" -m "- Backend error handling and display" -m "- Create, Edit, Show pages with proper routing" -m "- Show page with check history display" -m "- Delete action with confirmation" -m "- Navigation flow complete" -m "- Assets rebuilt with npm run build" -m "" -m "Co-Authored-By: Claude Sonnet 4.5 <noreply@anthropic.com>"
```

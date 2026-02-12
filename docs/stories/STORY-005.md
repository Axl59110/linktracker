# STORY-005: Build Project Create/Edit Form

**Status:** ✅ Completed
**Points:** 2
**Priority:** Must Have
**Epic:** Projects
**Branch:** feature/STORY-005-project-form
**Commit:** 133f032

## User Story

En tant qu'utilisateur authentifié
Je veux pouvoir créer et modifier mes projets via un formulaire
Afin de gérer facilement mes projets

## Dependencies

- ✅ STORY-003: Projects CRUD API
- ✅ STORY-004: Projects List Vue

## Implementation

### 1. Store Pinia - `resources/js/stores/projects.js`

Ajout des actions suivantes au store projects existant:

- `getProject(id)`: Récupère un projet spécifique pour l'édition
- `createProject(projectData)`: Crée un nouveau projet
- `updateProject(id, projectData)`: Met à jour un projet existant

Toutes les actions gèrent:
- Loading state
- Error handling
- Mise à jour du state local
- Propagation des erreurs backend

### 2. Composant ProjectForm - `resources/js/components/Projects/ProjectForm.vue`

Composant réutilisable pour la création et modification de projets.

**Props:**
- `projectId` (optionnel): ID du projet en mode édition

**Features:**
- Détection automatique du mode (create/edit) basée sur la présence de projectId
- Chargement automatique des données du projet en mode édition
- Formulaire avec 3 champs:
  - `name` (requis)
  - `url` (requis, validation format URL)
  - `status` (select: active, paused, archived)
- Validation frontend avant soumission
- Affichage des erreurs de validation backend
- Loading state avec bouton disabled
- Boutons "Créer/Modifier" et "Annuler"
- Redirection vers `/projects` après succès
- Message de succès via alert

**Validation:**
- Nom requis
- URL requise et format valide (via `new URL()`)
- Erreurs affichées sous chaque champ
- Support des erreurs backend (tableau ou string)

### 3. Pages

#### Create - `resources/js/pages/Projects/Create.vue`
Page simple qui utilise `<ProjectForm />` sans projectId.

#### Edit - `resources/js/pages/Projects/Edit.vue`
Page qui récupère l'ID depuis `route.params.id` et le passe à `<ProjectForm />`.

#### Index - `resources/js/pages/Projects/Index.vue`
Page qui affiche le composant `<ProjectsList />`.

### 4. Routes - `resources/js/router/index.js`

Ajout de 3 nouvelles routes protégées:

```javascript
{
    path: '/projects',
    name: 'projects.index',
    component: ProjectsIndex,
    meta: { title: 'Mes Projets', requiresAuth: true }
},
{
    path: '/projects/create',
    name: 'projects.create',
    component: ProjectsCreate,
    meta: { title: 'Créer un projet', requiresAuth: true }
},
{
    path: '/projects/:id/edit',
    name: 'projects.edit',
    component: ProjectsEdit,
    meta: { title: 'Modifier le projet', requiresAuth: true }
}
```

### 5. ProjectsList Enhancement

Ajout d'un bouton "Modifier" sur chaque carte projet:
- Bouton "Voir" → `/projects/{id}`
- Bouton "Modifier" → `/projects/{id}/edit`
- Layout flex avec 2 boutons côte à côte

## Acceptance Criteria

✅ **AC1:** Composant ProjectForm.vue créé (réutilisable pour create/edit)
✅ **AC2:** Page `/projects/create` (route protégée)
✅ **AC3:** Page `/projects/{id}/edit` (route protégée)
✅ **AC4:** Formulaire avec champs: name, url, status
✅ **AC5:** Validation frontend (required, url format)
✅ **AC6:** Gestion du loading state (bouton disabled)
✅ **AC7:** Affichage des erreurs de validation backend
✅ **AC8:** Redirection vers `/projects` après succès
✅ **AC9:** Toast/message de succès après création/modification
✅ **AC10:** Bouton "Annuler" → retour à `/projects`

## Files Modified/Created

### Created
- `app-laravel/resources/js/stores/projects.js` (nouveau)
- `app-laravel/resources/js/components/Projects/ProjectForm.vue` (nouveau)
- `app-laravel/resources/js/components/Projects/ProjectsList.vue` (nouveau)
- `app-laravel/resources/js/pages/Projects/Index.vue` (nouveau)
- `app-laravel/resources/js/pages/Projects/Create.vue` (nouveau)
- `app-laravel/resources/js/pages/Projects/Edit.vue` (nouveau)

### Modified
- `app-laravel/resources/js/router/index.js` (ajout des routes projects)

## Testing Checklist

### Création de projet
- [ ] Accès à `/projects/create` (authentifié)
- [ ] Validation nom vide
- [ ] Validation URL vide
- [ ] Validation URL invalide (ex: "not-a-url")
- [ ] Création réussie avec données valides
- [ ] Loading state pendant création
- [ ] Message de succès affiché
- [ ] Redirection vers `/projects`
- [ ] Nouveau projet visible dans la liste

### Modification de projet
- [ ] Accès à `/projects/{id}/edit` (authentifié)
- [ ] Chargement automatique des données du projet
- [ ] Loading state pendant chargement
- [ ] Erreur si projet inexistant
- [ ] Modification des données
- [ ] Validation identique à création
- [ ] Sauvegarde réussie
- [ ] Message de succès
- [ ] Redirection vers `/projects`
- [ ] Modifications visibles dans la liste

### Bouton Annuler
- [ ] Retour à `/projects` sans sauvegarder
- [ ] Fonctionne en mode création
- [ ] Fonctionne en mode édition

### Erreurs backend
- [ ] Affichage erreur nom (ex: trop long)
- [ ] Affichage erreur URL (format invalide côté backend)
- [ ] Affichage erreur status invalide
- [ ] Support erreurs multiples simultanées

### Routes protégées
- [ ] Redirection vers `/login` si non authentifié
- [ ] Accès autorisé si authentifié

## API Endpoints Used

- `GET /api/v1/projects/{id}` - Récupération projet pour édition
- `POST /api/v1/projects` - Création projet
- `PUT /api/v1/projects/{id}` - Mise à jour projet

## Technical Notes

### URL Validation
La validation frontend utilise `new URL()` qui lance une exception si l'URL n'est pas valide.
Format accepté: `https://example.com`, `http://localhost:3000`, etc.

### Error Handling
Les erreurs backend peuvent être:
- Un objet `{ name: ['error'], url: ['error'] }` (validation Laravel)
- Un string simple `"Error message"`
- Géré par le composant avec `Array.isArray()` check

### Loading State
Le loading est géré à deux niveaux:
- Store level: `store.loading` (pour les actions async)
- Component level: `loading` local (pour l'UI du formulaire)

### Reactive Form
Utilisation de `reactive()` pour le formulaire pour faciliter la soumission:
```javascript
const form = reactive({
    name: '',
    url: '',
    status: 'active'
});
```

## Future Improvements

1. **Toast notifications** au lieu de `alert()`
   - Installer une lib comme vue-toastification
   - Meilleure UX

2. **Confirmation avant annulation**
   - Si formulaire modifié, demander confirmation
   - Éviter perte de données accidentelle

3. **Validation temps réel**
   - Valider pendant la saisie
   - Feedback immédiat

4. **Champs supplémentaires**
   - Description du projet
   - Tags/catégories
   - Image/logo

5. **Breadcrumb navigation**
   - Home > Projects > Create
   - Meilleure navigation

## Related Stories

- STORY-003: Projects CRUD API (dépendance)
- STORY-004: Projects List Vue (dépendance)
- STORY-006: Project Detail View (next)

## Timeline

- **Started:** 2026-02-12
- **Completed:** 2026-02-12
- **Duration:** ~1 hour

## Notes

Implémentation conforme aux spécifications BMAD.
Tous les critères d'acceptation sont remplis.
Le composant est réutilisable et maintenable.

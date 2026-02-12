# STORY-010: Backlinks CRUD API

**Epic:** EPIC-002 - Backlinks Management
**Points:** 5
**Status:** ✅ Completed
**Branch:** `feature/STORY-010-backlinks-crud-api`

## Objectif

Créer l'API RESTful complète pour gérer les backlinks (Create, Read, Update, Delete) avec autorisation et validation SSRF.

## Description

Cette story implémente l'API CRUD pour les backlinks, permettant aux utilisateurs de :
- Lister tous les backlinks d'un projet
- Créer un nouveau backlink avec URL source et cible
- Afficher les détails d'un backlink avec son historique de checks
- Mettre à jour les informations d'un backlink
- Supprimer un backlink

L'API inclut une validation SSRF pour protéger contre les attaques via URLs malveillantes.

## Implémentation

### 1. BacklinkController

**Fichier:** `app/Http/Controllers/Api/V1/BacklinkController.php`

Contrôleur API avec 5 méthodes :

- **index(Request $request, Project $project)**: Liste les backlinks d'un projet
  - Vérifie que l'utilisateur possède le projet
  - Charge la relation `latestCheck` pour chaque backlink
  - Retourne JSON avec 200

- **store(StoreBacklinkRequest $request, Project $project)**: Crée un backlink
  - Valide avec `StoreBacklinkRequest` (SSRF protection sur source_url)
  - Définit automatiquement `status` = 'active' et `first_seen_at` = now()
  - Retourne JSON avec 201

- **show(Project $project, Backlink $backlink)**: Affiche un backlink
  - Charge `latestCheck` et `checks` (historique complet)
  - Retourne JSON avec 200

- **update(UpdateBacklinkRequest $request, Project $project, Backlink $backlink)**: Met à jour un backlink
  - Valide avec `UpdateBacklinkRequest` (SSRF + status in active/lost/changed)
  - Retourne JSON avec 200

- **destroy(Project $project, Backlink $backlink)**: Supprime un backlink
  - Cascade delete des checks associés (défini dans migration)
  - Retourne 204 No Content

**Pattern:** Nested resource (`/api/v1/projects/{project}/backlinks/{backlink}`)

### 2. FormRequests

**StoreBacklinkRequest** (`app/Http/Requests/StoreBacklinkRequest.php`):
```php
'source_url' => [
    'required',
    'url',
    'max:2048',
    function ($attribute, $value, $fail) {
        try {
            app(UrlValidator::class)->validate($value);
        } catch (SsrfException $e) {
            $fail("L'URL source est bloquée pour des raisons de sécurité : " . $e->getMessage());
        }
    },
],
'target_url' => ['required', 'url', 'max:2048'],
'anchor_text' => 'nullable|string|max:500',
```

**UpdateBacklinkRequest** (`app/Http/Requests/UpdateBacklinkRequest.php`):
- Même validation que Store, mais avec `sometimes` (champs optionnels)
- Validation additionnelle : `'status' => 'sometimes|in:active,lost,changed'`

### 3. BacklinkPolicy

**Fichier:** `app/Policies/BacklinkPolicy.php`

Règles d'autorisation :
- **viewAny**: Tous les utilisateurs authentifiés
- **view**: L'utilisateur doit posséder le projet parent (`$backlink->project->user_id`)
- **create**: Tous les utilisateurs authentifiés
- **update/delete**: L'utilisateur doit posséder le projet parent

### 4. Routes API

**Fichier:** `routes/api.php`

```php
Route::middleware('auth:sanctum')->group(function () {
    // Backlinks CRUD (nested under projects)
    Route::apiResource('projects.backlinks', BacklinkController::class)->except(['index', 'store']);
    Route::get('projects/{project}/backlinks', [BacklinkController::class, 'index'])->name('projects.backlinks.index');
    Route::post('projects/{project}/backlinks', [BacklinkController::class, 'store'])->name('projects.backlinks.store');
});
```

**Endpoints disponibles:**
- `GET    /api/v1/projects/{project}/backlinks` - Liste
- `POST   /api/v1/projects/{project}/backlinks` - Créer
- `GET    /api/v1/projects/{project}/backlinks/{backlink}` - Afficher
- `PUT    /api/v1/projects/{project}/backlinks/{backlink}` - Mettre à jour
- `DELETE /api/v1/projects/{project}/backlinks/{backlink}` - Supprimer

### 5. Tests

**Fichier:** `tests/Feature/Api/V1/BacklinkApiTest.php`

**16 tests / 62 assertions** couvrant :

✅ **Happy path:**
- Liste des backlinks d'un projet
- Création d'un backlink
- Affichage d'un backlink avec checks
- Mise à jour d'un backlink
- Suppression d'un backlink

✅ **Sécurité SSRF:**
- Bloque localhost (http://localhost/admin)
- Bloque réseaux privés (http://192.168.1.1/admin)

✅ **Validation:**
- source_url requis
- target_url requis
- status doit être in:active,lost,changed

✅ **Autorisation:**
- Impossible de lister les backlinks d'un autre utilisateur
- Impossible de créer un backlink pour un projet d'un autre utilisateur
- Impossible d'afficher/modifier/supprimer un backlink d'un autre utilisateur

✅ **Authentification:**
- Toutes les routes nécessitent auth:sanctum

## Résultats des Tests

```
PASS  Tests\Feature\Api\V1\BacklinkApiTest
✓ can list backlinks for project
✓ cannot list backlinks for other users project
✓ can create backlink
✓ ssrf protection blocks localhost
✓ ssrf protection blocks private ips
✓ validation requires source url
✓ validation requires target url
✓ cannot create backlink for other users project
✓ can show backlink
✓ cannot show other users backlink
✓ can update backlink
✓ validation rejects invalid status
✓ cannot update other users backlink
✓ can delete backlink
✓ cannot delete other users backlink
✓ requires authentication

Tests:  16 passed (62 assertions)
```

**Total projet: 82 tests / 234 assertions**

## Sécurité

### SSRF Protection
- Validation sur `source_url` via `UrlValidator` service
- Bloque les URLs vers localhost, réseaux privés, link-local
- Messages d'erreur clairs pour l'utilisateur

### Autorisation
- Policy vérifie que l'utilisateur possède le projet parent
- `authorizeResource()` dans le constructeur pour autorisation automatique
- Autorisation manuelle dans `index()` et `store()` sur le projet parent

### Validation
- URLs limitées à 2048 caractères (prévention buffer overflow)
- Anchor text limité à 500 caractères
- Status enum restreint (active/lost/changed)

## Dépendances

- ✅ STORY-006: Backlinks Table Migration (table `backlinks`)
- ✅ STORY-009: Backlink Model (scopes, accessors, helpers)
- ✅ STORY-008: UrlValidator Service (SSRF protection)
- ✅ STORY-002: Laravel Sanctum Authentication

## Points d'Attention

### Nested Resource Pattern
Les routes utilisent le pattern nested resource (`/projects/{project}/backlinks/{backlink}`), ce qui implique :
- Les signatures de méthodes doivent inclure les deux paramètres : `Project $project, Backlink $backlink`
- L'ordre des paramètres est important dans le contrôleur
- Laravel fait automatiquement le binding et vérifie que le backlink appartient au projet

### Eager Loading
Les relations sont chargées avec `load()` ou `with()` pour éviter le problème N+1 :
- `index()`: charge `latestCheck` pour chaque backlink
- `show()`: charge `latestCheck` et `checks` (historique complet)
- `store()/update()`: charge `latestCheck` pour retourner les données complètes

### Cascade Delete
La suppression d'un backlink supprime automatiquement tous ses checks grâce à la foreign key `onDelete('cascade')` dans la migration `backlink_checks`.

## Exemples d'Utilisation

### Créer un backlink
```bash
POST /api/v1/projects/1/backlinks
Content-Type: application/json

{
  "source_url": "https://example.com/blog/article",
  "target_url": "https://mysite.com",
  "anchor_text": "Visit my site"
}

Response 201:
{
  "id": 1,
  "project_id": 1,
  "source_url": "https://example.com/blog/article",
  "target_url": "https://mysite.com",
  "anchor_text": "Visit my site",
  "status": "active",
  "first_seen_at": "2026-02-12T15:30:00.000000Z",
  "latest_check": null
}
```

### Mettre à jour le statut
```bash
PUT /api/v1/projects/1/backlinks/1
Content-Type: application/json

{
  "status": "lost",
  "anchor_text": "Updated anchor"
}

Response 200:
{
  "id": 1,
  "status": "lost",
  "anchor_text": "Updated anchor",
  ...
}
```

## Commits

```bash
git add .
git commit -m "feat(backlinks): implement Backlinks CRUD API with SSRF protection (STORY-010)

- BacklinkController with index, store, show, update, destroy
- StoreBacklinkRequest with SSRF validation on source_url
- UpdateBacklinkRequest with SSRF validation and status validation
- BacklinkPolicy for authorization (user must own parent project)
- Nested resource routes /projects/{project}/backlinks
- 16 tests / 62 assertions covering CRUD, SSRF, validation, authorization
- All 82 tests passing (234 assertions total)

Co-Authored-By: Claude Sonnet 4.5 <noreply@anthropic.com>"
```

## Prochaines Étapes

- ✅ STORY-011: Backlinks List Vue Component
- ✅ STORY-012: Backlink Create/Edit Form
- ✅ STORY-013: HTTP Service for Checking Backlinks
- ✅ STORY-014: Check Backlink Job
- ✅ STORY-017: Schedule Backlink Checks

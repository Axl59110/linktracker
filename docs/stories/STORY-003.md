# STORY-003: Create Project CRUD API

**Status:** Completed
**Points:** 3
**Epic:** Projects
**Dependencies:** STORY-002 (Authentication) ✅

## User Story

En tant qu'utilisateur authentifié
Je veux gérer mes projets via une API REST
Afin de pouvoir créer, lire, mettre à jour et supprimer mes projets

## Implementation Summary

### 1. Database Migration

**File:** `app-laravel/database/migrations/2026_02_12_110829_create_projects_table.php`

Created `projects` table with the following schema:
- `id` - Primary key
- `user_id` - Foreign key to users table (cascade on delete)
- `name` - Project name (string, max 255)
- `url` - Project URL (string, max 2048)
- `status` - Enum: active, paused, archived (default: active)
- `timestamps` - created_at, updated_at
- `softDeletes` - deleted_at for soft deletion
- **Unique constraint:** (user_id, name) - user cannot have duplicate project names
- **Index:** status field for performance

### 2. Models

#### Project Model

**File:** `app-laravel/app/Models/Project.php`

Features:
- Uses `HasFactory` and `SoftDeletes` traits
- Mass assignable: name, url, status
- Casts: created_at, updated_at, deleted_at to datetime
- **Relationship:** `belongsTo(User::class)`

#### User Model Enhancement

**File:** `app-laravel/app/Models/User.php`

Added relationship:
- **Relationship:** `hasMany(Project::class)`

### 3. Form Requests

#### StoreProjectRequest

**File:** `app-laravel/app/Http/Requests/StoreProjectRequest.php`

Validation rules:
- `name`: required, string, max:255, unique per user (using Rule::unique with where clause)
- `url`: required, url, max:2048
- `status`: nullable, in:active,paused,archived

#### UpdateProjectRequest

**File:** `app-laravel/app/Http/Requests/UpdateProjectRequest.php`

Validation rules:
- `name`: sometimes, required, string, max:255, unique per user (ignores current project)
- `url`: sometimes, required, url, max:2048
- `status`: sometimes, in:active,paused,archived

### 4. Controller

**File:** `app-laravel/app/Http/Controllers/Api/V1/ProjectController.php`

API Resource Controller with authorization via `authorizeResource()`:

- **index()** - GET /api/v1/projects
  - Returns authenticated user's projects (latest first)
  - Response: JSON array of projects

- **store()** - POST /api/v1/projects
  - Creates new project for authenticated user
  - Validates via StoreProjectRequest
  - Response: 201 with created project

- **show()** - GET /api/v1/projects/{id}
  - Shows single project (ownership verified by policy)
  - Response: 200 with project data

- **update()** - PUT /api/v1/projects/{id}
  - Updates project (ownership verified by policy)
  - Validates via UpdateProjectRequest
  - Response: 200 with updated project

- **destroy()** - DELETE /api/v1/projects/{id}
  - Soft deletes project (ownership verified by policy)
  - Response: 204 No Content

### 5. Authorization Policy

**File:** `app-laravel/app/Policies/ProjectPolicy.php`

Authorization rules:
- `viewAny()` - Always true (user can list their own projects)
- `view()` - User can only view their own projects
- `create()` - Always true (authenticated users can create)
- `update()` - User can only update their own projects
- `delete()` - User can only delete their own projects
- `restore()` - User can only restore their own projects
- `forceDelete()` - User can only force delete their own projects

### 6. Routes

**File:** `app-laravel/routes/api.php`

Added to protected routes (auth:sanctum middleware):
```php
Route::apiResource('projects', ProjectController::class);
```

This generates:
- GET /api/v1/projects - index
- POST /api/v1/projects - store
- GET /api/v1/projects/{project} - show
- PUT/PATCH /api/v1/projects/{project} - update
- DELETE /api/v1/projects/{project} - destroy

### 7. Factory

**File:** `app-laravel/database/factories/ProjectFactory.php`

Factory for testing:
- `user_id` - Creates associated user
- `name` - Generates random 3-word name
- `url` - Generates random URL
- `status` - Random selection from active, paused, archived

### 8. Feature Tests

**File:** `app-laravel/tests/Feature/Api/V1/ProjectApiTest.php`

**Test Results: 10/10 PASSING (42 assertions)**

Tests coverage:
1. ✅ `test_authenticated_user_can_list_their_projects` - Lists only user's projects
2. ✅ `test_unauthenticated_user_cannot_list_projects` - 401 for unauthenticated
3. ✅ `test_authenticated_user_can_create_project` - Creates project with valid data
4. ✅ `test_user_cannot_create_project_with_duplicate_name` - 422 validation error
5. ✅ `test_authenticated_user_can_view_their_project` - Views own project
6. ✅ `test_user_cannot_view_other_users_project` - 403 forbidden
7. ✅ `test_authenticated_user_can_update_their_project` - Updates own project
8. ✅ `test_user_cannot_update_other_users_project` - 403 forbidden
9. ✅ `test_authenticated_user_can_delete_their_project` - Soft deletes project
10. ✅ `test_user_cannot_delete_other_users_project` - 403 forbidden

**Test Duration:** 86.27s

## Acceptance Criteria Status

✅ Migration `projects` table créée (id, user_id, name, url, status, timestamps, soft deletes)
✅ Model `Project` avec relations (belongsTo User)
✅ POST `/api/v1/projects` - Créer un projet (auth requis)
✅ GET `/api/v1/projects` - Lister mes projets (auth requis)
✅ GET `/api/v1/projects/{id}` - Voir un projet (auth requis)
✅ PUT `/api/v1/projects/{id}` - Modifier un projet (auth requis)
✅ DELETE `/api/v1/projects/{id}` - Supprimer un projet (auth requis, soft delete)
✅ Policy `ProjectPolicy` pour autorisation (user peut seulement gérer ses propres projets)
✅ Tests Feature `ProjectApiTest.php` (CRUD complet)

## API Examples

### Create Project
```http
POST /api/v1/projects
Content-Type: application/json
Authorization: Session (Sanctum)

{
  "name": "My Website",
  "url": "https://example.com",
  "status": "active"
}
```

Response (201):
```json
{
  "id": 1,
  "user_id": 1,
  "name": "My Website",
  "url": "https://example.com",
  "status": "active",
  "created_at": "2026-02-12T12:00:00.000000Z",
  "updated_at": "2026-02-12T12:00:00.000000Z"
}
```

### List Projects
```http
GET /api/v1/projects
Authorization: Session (Sanctum)
```

Response (200):
```json
[
  {
    "id": 1,
    "user_id": 1,
    "name": "My Website",
    "url": "https://example.com",
    "status": "active",
    "created_at": "2026-02-12T12:00:00.000000Z",
    "updated_at": "2026-02-12T12:00:00.000000Z"
  }
]
```

### Update Project
```http
PUT /api/v1/projects/1
Content-Type: application/json
Authorization: Session (Sanctum)

{
  "status": "paused"
}
```

Response (200):
```json
{
  "id": 1,
  "user_id": 1,
  "name": "My Website",
  "url": "https://example.com",
  "status": "paused",
  "created_at": "2026-02-12T12:00:00.000000Z",
  "updated_at": "2026-02-12T12:15:00.000000Z"
}
```

### Delete Project (Soft Delete)
```http
DELETE /api/v1/projects/1
Authorization: Session (Sanctum)
```

Response (204): No Content

## Security Features

1. **Authentication Required:** All endpoints require Sanctum authentication
2. **Authorization Policy:** Users can only access their own projects
3. **Unique Constraint:** Prevents duplicate project names per user
4. **URL Validation:** Ensures valid URL format
5. **Soft Deletes:** Projects are soft-deleted, preserving history
6. **Mass Assignment Protection:** Only name, url, status are fillable

## Technical Decisions

### Why Soft Deletes?
- Preserves historical data for audit trails
- Allows potential restoration if user deletes by mistake
- Maintains referential integrity with related data (backlinks, checks)

### Why Unique Constraint on (user_id, name)?
- Prevents user confusion with duplicate project names
- Improves UX by enforcing clear project naming
- Different users can still have projects with same name

### Why Enum for Status?
- Ensures data consistency (only valid states)
- Prevents typos and invalid status values
- Makes it easy to extend with new states if needed

## Files Created/Modified

**Created:**
- `app-laravel/app/Models/Project.php`
- `app-laravel/app/Http/Controllers/Api/V1/ProjectController.php`
- `app-laravel/app/Http/Requests/StoreProjectRequest.php`
- `app-laravel/app/Http/Requests/UpdateProjectRequest.php`
- `app-laravel/app/Policies/ProjectPolicy.php`
- `app-laravel/database/migrations/2026_02_12_110829_create_projects_table.php`
- `app-laravel/database/factories/ProjectFactory.php`
- `app-laravel/tests/Feature/Api/V1/ProjectApiTest.php`

**Modified:**
- `app-laravel/app/Models/User.php` - Added projects relationship
- `app-laravel/routes/api.php` - Added projects routes

## Testing Notes

All tests use `RefreshDatabase` trait to ensure clean state.

Tests verify:
- Authentication requirements (401 for unauthenticated)
- Authorization (403 for unauthorized actions)
- Validation (422 for invalid data)
- CRUD operations (201, 200, 204 status codes)
- Soft deletes (assertSoftDeleted)
- Data integrity (assertDatabaseHas)

## Next Steps

- STORY-004: Build Projects List Vue Component
- STORY-005: Build Project Create/Edit Form
- STORY-006: Create Backlinks Table Migration

## Commit

```
feat(projects): implement Project CRUD API (STORY-003)

- Add projects table migration with user relationship
- Create Project model with SoftDeletes
- Create ProjectPolicy for authorization
- Implement ProjectController with full CRUD
- Add Form Requests (StoreProjectRequest, UpdateProjectRequest)
- Write 10 Feature tests (all passing)
- Ensure users can only manage their own projects
```

**Branch:** feature/STORY-003-projects-crud-api
**Commit:** 127f9aa
**Date:** 2026-02-12

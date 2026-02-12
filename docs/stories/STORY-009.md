# STORY-009: Create Backlink Model + Factory

**Epic:** EPIC-002 (Backlinks Management)
**Points:** 3
**Status:** Completed ✅
**Date:** 2026-02-13
**Critical Path:** ⭐ **YES** - Bloque toutes les autres stories backlinks

## Objectif

Créer le modèle Eloquent Backlink avec toutes ses relations, scopes, accessors et factory pour manipuler les backlinks efficacement.

## User Story

En tant que développeur
Je veux créer le modèle Backlink avec relations et accessors
Afin de manipuler les backlinks en Eloquent ORM

## Implémentation

### 1. Modèle Backlink

**Fichier:** `app/Models/Backlink.php`

#### Attributs Fillable

```php
protected $fillable = [
    'source_url',      // URL de la page contenant le backlink
    'target_url',      // URL cible du backlink
    'anchor_text',     // Texte d'ancre
    'status',          // active, lost, changed
    'http_status',     // Code HTTP (200, 404, etc.)
    'rel_attributes',  // follow, nofollow
    'is_dofollow',     // Boolean
    'first_seen_at',   // Date de première détection
    'last_checked_at', // Date du dernier check
];
```

#### Casts

```php
protected $casts = [
    'is_dofollow' => 'boolean',
    'first_seen_at' => 'datetime',
    'last_checked_at' => 'datetime',
    'created_at' => 'datetime',
    'updated_at' => 'datetime',
];
```

#### Relations

```php
// Relation: Backlink belongsTo Project
public function project()
{
    return $this->belongsTo(Project::class);
}
```

#### Query Scopes

**3 scopes pour filtrer les backlinks par statut:**

```php
// Filtrer uniquement les backlinks actifs
public function scopeActive($query)
{
    return $query->where('status', 'active');
}

// Filtrer uniquement les backlinks perdus
public function scopeLost($query)
{
    return $query->where('status', 'lost');
}

// Filtrer uniquement les backlinks modifiés
public function scopeChanged($query)
{
    return $query->where('status', 'changed');
}
```

**Usage:**
```php
// Tous les backlinks actifs
$activeBacklinks = Backlink::active()->get();

// Backlinks actifs d'un projet
$projectActiveBacklinks = $project->backlinks()->active()->get();

// Backlinks perdus récemment
$recentLost = Backlink::lost()
    ->where('last_checked_at', '>=', now()->subDays(7))
    ->get();
```

#### Accessors pour l'UI

**Badge color (Tailwind CSS):**
```php
public function getStatusBadgeColorAttribute(): string
{
    return match($this->status) {
        'active' => 'bg-green-100 text-green-800 border-green-200',
        'lost' => 'bg-red-100 text-red-800 border-red-200',
        'changed' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
        default => 'bg-gray-100 text-gray-800 border-gray-200',
    };
}
```

**Status label (français):**
```php
public function getStatusLabelAttribute(): string
{
    return match($this->status) {
        'active' => 'Actif',
        'lost' => 'Perdu',
        'changed' => 'Modifié',
        default => ucfirst($this->status),
    };
}
```

**Usage dans Vue:**
```vue
<span :class="backlink.status_badge_color" class="px-2 py-1 rounded">
    {{ backlink.status_label }}
</span>
```

#### Helper Methods

```php
// Vérifier le statut facilement
public function isActive(): bool
{
    return $this->status === 'active';
}

public function isLost(): bool
{
    return $this->status === 'lost';
}

public function hasChanged(): bool
{
    return $this->status === 'changed';
}
```

### 2. BacklinkFactory

**Fichier:** `database/factories/BacklinkFactory.php`

#### État par défaut

```php
public function definition(): array
{
    return [
        'source_url' => fake()->url(),
        'target_url' => fake()->url(),
        'anchor_text' => fake()->words(3, true),
        'status' => 'active',
        'http_status' => 200,
        'rel_attributes' => 'follow',
        'is_dofollow' => true,
        'first_seen_at' => now(),
        'last_checked_at' => fake()->optional()->dateTimeBetween('-1 week', 'now'),
    ];
}
```

#### Factory States

**4 états disponibles:**

```php
// État actif (défaut)
Backlink::factory()->active()->create();

// État perdu (404)
Backlink::factory()->lost()->create();

// État modifié (passage nofollow)
Backlink::factory()->changed()->create();

// État nofollow spécifique
Backlink::factory()->nofollow()->create();
```

**Implémentation des états:**
```php
public function active(): static
{
    return $this->state(fn (array $attributes) => [
        'status' => 'active',
        'http_status' => 200,
        'is_dofollow' => true,
        'rel_attributes' => 'follow',
    ]);
}

public function lost(): static
{
    return $this->state(fn (array $attributes) => [
        'status' => 'lost',
        'http_status' => 404,
        'last_checked_at' => now(),
    ]);
}

public function changed(): static
{
    return $this->state(fn (array $attributes) => [
        'status' => 'changed',
        'http_status' => 200,
        'is_dofollow' => false,
        'rel_attributes' => 'nofollow',
        'last_checked_at' => now(),
    ]);
}

public function nofollow(): static
{
    return $this->state(fn (array $attributes) => [
        'is_dofollow' => false,
        'rel_attributes' => 'nofollow',
    ]);
}
```

**Usage avancé:**
```php
// Créer 10 backlinks actifs pour un projet
Backlink::factory()
    ->for($project)
    ->active()
    ->count(10)
    ->create();

// Créer 5 backlinks perdus
Backlink::factory()
    ->for($project)
    ->lost()
    ->count(5)
    ->create();

// Mélange de statuts
Backlink::factory()->for($project)->count(3)->active()->create();
Backlink::factory()->for($project)->count(2)->lost()->create();
Backlink::factory()->for($project)->count(1)->changed()->create();
```

### 3. Tests Unitaires

**Fichier:** `tests/Unit/Models/BacklinkModelTest.php`

**13 tests / 44 assertions ✅**

#### Tests implémentés

1. ✅ **test_can_create_backlink_with_factory** - Factory création
2. ✅ **test_backlink_belongs_to_project** - Relation belongsTo
3. ✅ **test_active_scope_returns_only_active_backlinks** - Scope active
4. ✅ **test_lost_scope_returns_only_lost_backlinks** - Scope lost
5. ✅ **test_changed_scope_returns_only_changed_backlinks** - Scope changed
6. ✅ **test_status_badge_color_accessor** - Accessor badge color
7. ✅ **test_status_label_accessor** - Accessor label
8. ✅ **test_is_active_method** - Helper isActive()
9. ✅ **test_is_lost_method** - Helper isLost()
10. ✅ **test_has_changed_method** - Helper hasChanged()
11. ✅ **test_factory_states** - Tous les états factory
12. ✅ **test_casts_work_properly** - Casts datetime et boolean
13. ✅ **test_fillable_attributes** - Attributs fillable

#### Exécution des tests

```bash
php artisan test tests/Unit/Models/BacklinkModelTest.php

# Résultat
PASS  Tests\Unit\Models\BacklinkModelTest
✓ can create backlink with factory
✓ backlink belongs to project
✓ active scope returns only active backlinks
✓ lost scope returns only lost backlinks
✓ changed scope returns only changed backlinks
✓ status badge color accessor
✓ status label accessor
✓ is active method
✓ is lost method
✓ has changed method
✓ factory states
✓ casts work properly
✓ fillable attributes

Tests:    13 passed (44 assertions)
Duration: 1.73s
```

## Acceptance Criteria

- [x] Model `Backlink` créé (app/Models/Backlink.php)
- [x] Fillable attributes définis (9 champs)
- [x] Casts configurés (boolean, datetime)
- [x] Relation belongsTo(Project) fonctionnelle
- [x] Scopes: scopeActive(), scopeLost(), scopeChanged()
- [x] Accessors: getStatusBadgeColorAttribute(), getStatusLabelAttribute()
- [x] Helper methods: isActive(), isLost(), hasChanged()
- [x] Factory BacklinkFactory avec états (active, lost, changed, nofollow)
- [x] Tests: BacklinkModelTest.php (13 tests / 44 assertions passent)

## Usage dans les Prochaines Stories

### STORY-010 (API CRUD)

```php
// Controller
public function index(Request $request, Project $project)
{
    $backlinks = $project->backlinks()
        ->when($request->status, fn($q, $status) =>
            $q->where('status', $status)
        )
        ->latest()
        ->paginate(50);

    return BacklinkResource::collection($backlinks);
}
```

### STORY-011 (List UI)

```vue
<div v-for="backlink in backlinks" :key="backlink.id">
    <span :class="backlink.status_badge_color">
        {{ backlink.status_label }}
    </span>
    <p>{{ backlink.source_url }}</p>
    <p v-if="backlink.is_dofollow" class="text-green-600">
        Follow
    </p>
</div>
```

### STORY-013 (BacklinkChecker)

```php
// Service
public function checkBacklink(Backlink $backlink)
{
    // Vérifier le backlink
    $result = $this->httpClient->get($backlink->source_url);

    // Mettre à jour
    $backlink->update([
        'http_status' => $result->status(),
        'last_checked_at' => now(),
    ]);

    // Détecter si perdu
    if ($result->status() === 404) {
        $backlink->update(['status' => 'lost']);
    }
}
```

## Points Complétés

**3 points** - Model Backlink complété avec toutes fonctionnalités et tests.

## Commit

```
feat(backlinks): create Backlink model with scopes and factory (STORY-009)

Modèle complet avec:
- Fillable attributes (9 champs)
- Casts (datetime, boolean)
- Relation belongsTo Project
- 3 Query scopes (active, lost, changed)
- 2 Accessors UI (badge_color, label)
- 3 Helper methods (isActive, isLost, hasChanged)

Factory avec 4 états:
- active (défaut, HTTP 200, dofollow)
- lost (HTTP 404)
- changed (nofollow)
- nofollow (custom)

Tests: 13/13 passés (44 assertions)

STORY-009 completed - 3 points

Co-Authored-By: Claude Sonnet 4.5 <noreply@anthropic.com>
```

## Bloque les Stories

Cette story est sur le **CRITICAL PATH** et bloque:
- ✋ STORY-010 (API CRUD) - Nécessite le modèle
- ✋ STORY-011 (List UI) - Utilise les accessors
- ✋ STORY-012 (Form) - Utilise le modèle
- ✋ STORY-013 (BacklinkChecker) - Manipule le modèle
- ✋ STORY-015 (BacklinkCheck) - Relation avec Backlink

## Notes Techniques

### Statuts des Backlinks

**3 statuts possibles:**

1. **active** - Backlink présent et actif
   - HTTP 200
   - Lien détecté dans la page
   - Dofollow (généralement)

2. **lost** - Backlink perdu/disparu
   - HTTP 404, 410, 500
   - Lien non détecté
   - Nécessite action utilisateur

3. **changed** - Backlink modifié
   - HTTP 200 mais changements détectés
   - Passage follow → nofollow
   - Anchor text modifié
   - Nécessite review

### Pourquoi ces Scopes ?

Les scopes permettent des requêtes expressives:

```php
// Sans scope (verbose)
Backlink::where('status', 'active')->get();

// Avec scope (clair)
Backlink::active()->get();

// Combinable
Backlink::active()
    ->where('is_dofollow', true)
    ->whereDate('last_checked_at', '>=', now()->subDays(7))
    ->get();
```

### Pourquoi ces Accessors ?

Les accessors simplifient l'UI:

```vue
<!-- Sans accessor -->
<span :class="getStatusColor(backlink.status)">
    {{ getStatusLabel(backlink.status) }}
</span>

<!-- Avec accessor -->
<span :class="backlink.status_badge_color">
    {{ backlink.status_label }}
</span>
```

Moins de logique frontend, plus maintenable.

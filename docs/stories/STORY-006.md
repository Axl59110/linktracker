# STORY-006: Backlinks Table Migration

**Epic:** Sprint 1 - Core Infrastructure
**Points:** 2
**Status:** Completed ✅
**Date:** 2026-02-12

## Objectif

Créer la table `backlinks` en base de données pour stocker les backlinks à monitorer.

## Raison d'Être

Cette table est fondamentale pour le Sprint 2 qui implémentera le CRUD des backlinks. Elle stocke toutes les informations nécessaires au suivi des backlinks :
- URLs source et cible
- Statut du lien (actif, perdu, modifié)
- Attributs SEO (dofollow/nofollow, rel attributes)
- Historique (première détection, dernier check)

## Implémentation

### 1. Migration

**Fichier:** `database/migrations/2026_02_12_114317_create_backlinks_table.php`

```php
Schema::create('backlinks', function (Blueprint $table) {
    $table->id();
    $table->foreignId('project_id')->constrained()->onDelete('cascade');
    $table->text('source_url');
    $table->text('target_url');
    $table->text('anchor_text')->nullable();
    $table->string('status', 50)->default('active'); // active, lost, changed
    $table->integer('http_status')->nullable();
    $table->string('rel_attributes', 100)->nullable(); // follow, nofollow
    $table->boolean('is_dofollow')->default(true);
    $table->timestamp('first_seen_at')->useCurrent();
    $table->timestamp('last_checked_at')->nullable();
    $table->timestamps();

    // Indexes pour performance
    $table->index(['project_id', 'status']);
    $table->index('status');
});
```

### 2. Modèle Backlink

**Fichier:** `app/Models/Backlink.php`

```php
class Backlink extends Model
{
    use HasFactory;

    protected $fillable = [
        'source_url',
        'target_url',
        'anchor_text',
        'status',
        'http_status',
        'rel_attributes',
        'is_dofollow',
        'first_seen_at',
        'last_checked_at',
    ];

    protected $casts = [
        'is_dofollow' => 'boolean',
        'first_seen_at' => 'datetime',
        'last_checked_at' => 'datetime',
    ];

    // Relations
    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
```

### 3. Factory

**Fichier:** `database/factories/BacklinkFactory.php`

Factory pour générer des données de test réalistes avec :
- URLs variées (source et target)
- Statuts aléatoires (active, lost, changed)
- Attributs SEO réalistes
- Timestamps cohérents

### 4. Relation dans Project

**Fichier:** `app/Models/Project.php`

```php
public function backlinks()
{
    return $this->hasMany(Backlink::class);
}
```

## Structure de la Table

| Colonne | Type | Description |
|---------|------|-------------|
| `id` | bigint | Primary key |
| `project_id` | bigint | FK vers projects (cascade delete) |
| `source_url` | text | URL de la page contenant le backlink |
| `target_url` | text | URL cible du backlink |
| `anchor_text` | text nullable | Texte d'ancre du lien |
| `status` | varchar(50) | active, lost, changed |
| `http_status` | int nullable | Code HTTP lors du dernier check |
| `rel_attributes` | varchar(100) nullable | follow, nofollow, etc. |
| `is_dofollow` | boolean | True si lien dofollow |
| `first_seen_at` | timestamp | Date de première détection |
| `last_checked_at` | timestamp nullable | Date du dernier contrôle |
| `created_at` | timestamp | Date de création |
| `updated_at` | timestamp | Date de modification |

## Indexes

1. **Composite:** `(project_id, status)` - Requêtes filtrées par projet et statut
2. **Simple:** `status` - Statistiques globales par statut

## Utilisation

### Créer un backlink
```php
$project = Project::find(1);

$backlink = $project->backlinks()->create([
    'source_url' => 'https://example.com/page',
    'target_url' => 'https://mysite.com',
    'anchor_text' => 'Mon Site',
    'status' => 'active',
    'http_status' => 200,
    'rel_attributes' => 'follow',
    'is_dofollow' => true,
]);
```

### Récupérer les backlinks d'un projet
```php
// Tous les backlinks
$backlinks = $project->backlinks;

// Backlinks actifs
$activeBacklinks = $project->backlinks()
    ->where('status', 'active')
    ->get();

// Backlinks perdus
$lostBacklinks = $project->backlinks()
    ->where('status', 'lost')
    ->get();
```

### Utiliser la Factory
```php
// Créer 10 backlinks pour un projet
Backlink::factory()
    ->for($project)
    ->count(10)
    ->create();

// Créer des backlinks avec statut spécifique
Backlink::factory()
    ->for($project)
    ->count(5)
    ->create(['status' => 'lost']);
```

## Tests de Validation

### Migration
```bash
# Exécuter la migration
php artisan migrate

# Vérifier la table
php artisan tinker
>>> Schema::hasTable('backlinks'); // true
>>> Schema::getColumnListing('backlinks');
```

### Relations
```bash
php artisan tinker
>>> $project = Project::first();
>>> $backlink = Backlink::factory()->for($project)->create();
>>> $backlink->project->name; // Affiche le nom du projet
>>> $project->backlinks->count(); // Compte les backlinks
```

### Cascade Delete
```bash
php artisan tinker
>>> $project = Project::with('backlinks')->first();
>>> $count = $project->backlinks->count();
>>> $project->delete();
>>> Backlink::count(); // Doit être 0 si c'était le seul projet
```

## Critères d'Acceptation

- [x] Migration créée et exécutée sans erreur
- [x] Table `backlinks` existe avec toutes les colonnes
- [x] Foreign key vers `projects` avec cascade delete
- [x] Indexes créés pour optimisation
- [x] Model Backlink avec relations
- [x] Factory BacklinkFactory fonctionnelle
- [x] Relation `hasMany` dans Project model
- [x] Tests manuels en Tinker validés

## Points Complétés

**2 points** - Story complétée avec succès.

## Commit

```
feat(backlinks): add backlinks table migration and model (STORY-006)

- Create backlinks table with full schema
- Add foreign key to projects with cascade delete
- Create Backlink model with relations
- Add BacklinkFactory for testing
- Add backlinks() relation to Project model

Tests: Manual validation in Tinker passed

STORY-006 completed - 2 points
```

## Prochaines Étapes

Cette table sera utilisée dans :
- **Sprint 2:** STORY-007 (Backlinks CRUD API)
- **Sprint 2:** Jobs de monitoring des backlinks
- **Sprint 3:** Dashboard de statistiques SEO

## Notes Techniques

### Choix de Design

1. **URLs en TEXT** : Permet URLs longues (>255 caractères)
2. **Cascade Delete** : Si projet supprimé, tous ses backlinks le sont aussi
3. **first_seen_at par défaut** : Timestamp automatique à la création
4. **Indexes composés** : Optimisent les requêtes fréquentes (projet + statut)

### Performance

Les indexes garantissent de bonnes performances même avec :
- Des milliers de backlinks par projet
- Requêtes fréquentes de filtrage par statut
- Agrégations (COUNT, GROUP BY)

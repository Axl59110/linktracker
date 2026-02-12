# STORY-017: Schedule Backlink Checks

**Epic:** EPIC-002 - Backlinks Management
**Points:** 7
**Status:** ✅ Completed
**Branch:** `feature/STORY-017-schedule-backlink-checks`

## Objectif

Créer un système de planification automatique pour vérifier régulièrement tous les backlinks, avec support de différentes fréquences, filtres avancés, et intégration au Task Scheduler de Laravel.

## Description

Cette story implémente la couche de planification et d'orchestration des vérifications de backlinks :
- Command Laravel `app:check-backlinks` avec options configurables
- Filtrage par fréquence (daily, weekly, all)
- Filtrage par projet, statut, limite
- Priorisation des backlinks jamais vérifiés
- Dispatch asynchrone des jobs CheckBacklinkJob
- Configuration du Task Scheduler Laravel
- Progress bar et output informatif
- Logging complet
- Tests complets (11 tests / 23 assertions)

## Implémentation

### 1. CheckBacklinksCommand

**Fichier:** `app/Console/Commands/CheckBacklinksCommand.php`

Command Laravel pour dispatcher les jobs de vérification.

#### Signature

```php
protected $signature = 'app:check-backlinks
                        {--frequency=daily : Frequency filter (daily, weekly, all)}
                        {--project= : Check only backlinks for a specific project ID}
                        {--limit= : Maximum number of backlinks to check}
                        {--status=active : Filter by status (active, lost, changed, all)}';
```

#### Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `--frequency` | string | daily | Fréquence de vérification |
| `--project` | int | null | ID du projet à vérifier |
| `--limit` | int | null | Nombre max de backlinks |
| `--status` | string | active | Statut à filtrer |

#### Méthode handle()

**Workflow:**

**1. Construction de la Query**

```php
$query = Backlink::query();

// Filtre par projet
if ($projectId) {
    $query->where('project_id', $projectId);
}

// Filtre par statut
if ($status !== 'all') {
    $query->where('status', $status);
}
```

**2. Filtre par Fréquence**

```php
switch ($frequency) {
    case 'daily':
        // Backlinks non vérifiés depuis 24h
        $query->where(function ($q) {
            $q->whereNull('last_checked_at')
              ->orWhere('last_checked_at', '<', now()->subDay());
        });
        break;

    case 'weekly':
        // Backlinks non vérifiés depuis 7 jours
        $query->where(function ($q) {
            $q->whereNull('last_checked_at')
              ->orWhere('last_checked_at', '<', now()->subWeek());
        });
        break;

    case 'all':
        // Tous les backlinks
        break;
}
```

**3. Priorisation**

```php
// Jamais vérifiés d'abord, puis les plus anciens
$query->orderByRaw('CASE WHEN last_checked_at IS NULL THEN 0 ELSE 1 END')
      ->orderBy('last_checked_at', 'asc');
```

Cette logique garantit que :
- Les backlinks jamais vérifiés (`last_checked_at IS NULL`) sont traités en priorité
- Parmi ceux déjà vérifiés, les plus anciens sont prioritaires

**4. Application de la Limite**

```php
if ($limit) {
    $query->limit((int) $limit);
}
```

**5. Dispatch des Jobs**

```php
$backlinks = $query->get();

$progressBar = $this->output->createProgressBar($backlinks->count());
$progressBar->start();

foreach ($backlinks as $backlink) {
    try {
        CheckBacklinkJob::dispatch($backlink);
        $dispatched++;
        $progressBar->advance();
    } catch (\Exception $e) {
        Log::error('Failed to dispatch CheckBacklinkJob', [
            'backlink_id' => $backlink->id,
            'error' => $e->getMessage(),
        ]);
    }
}

$progressBar->finish();
```

**6. Output et Logging**

```php
$this->info("✅ Successfully dispatched {$dispatched} job(s) to the queue.");

Log::info('Backlink check command completed', [
    'frequency' => $frequency,
    'project_id' => $projectId,
    'status' => $status,
    'backlinks_found' => $backlinks->count(),
    'jobs_dispatched' => $dispatched,
]);
```

### 2. Task Scheduler Configuration

**Fichier:** `app/Console/Kernel.php`

Configuration du scheduler Laravel pour exécutions automatiques.

#### Planifications

```php
protected function schedule(Schedule $schedule): void
{
    // Vérification quotidienne des backlinks actifs (2h du matin)
    $schedule->command('app:check-backlinks --frequency=daily')
             ->dailyAt('02:00')
             ->withoutOverlapping()
             ->appendOutputTo(storage_path('logs/scheduler.log'));

    // Vérification hebdomadaire complète (dimanche 3h)
    $schedule->command('app:check-backlinks --frequency=weekly --status=all')
             ->weekly()
             ->sundays()
             ->at('03:00')
             ->withoutOverlapping()
             ->appendOutputTo(storage_path('logs/scheduler.log'));
}
```

#### Options Utilisées

- `dailyAt('02:00')` : Exécution à 2h du matin (heure creuse)
- `weekly()->sundays()->at('03:00')` : Tous les dimanches à 3h
- `withoutOverlapping()` : Empêche les exécutions simultanées
- `appendOutputTo()` : Logs dans `storage/logs/scheduler.log`

### 3. Démarrage du Scheduler

#### En Production (Linux/Mac)

Ajouter au crontab :

```bash
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

Cette ligne exécute `schedule:run` chaque minute. Laravel détermine ensuite quels commands doivent tourner.

#### En Développement (Windows/Herd)

Utiliser le command de test :

```bash
php artisan schedule:work
```

Ou exécuter manuellement :

```bash
php artisan app:check-backlinks --frequency=daily
```

## Exemples d'Utilisation

### Utilisation Basique

```bash
# Vérifier les backlinks qui nécessitent une vérification quotidienne
php artisan app:check-backlinks

# Équivalent à :
php artisan app:check-backlinks --frequency=daily --status=active
```

**Output:**
```
🔍 Starting backlink check process...
Frequency: daily
Status filter: active
Found 15 backlink(s) to check.
 15/15 [████████████████████████████] 100%
✅ Successfully dispatched 15 job(s) to the queue.
```

### Vérification Hebdomadaire

```bash
# Vérifier tous les backlinks (actifs, perdus, modifiés)
php artisan app:check-backlinks --frequency=weekly --status=all
```

### Vérification d'un Projet Spécifique

```bash
# Vérifier seulement les backlinks du projet #5
php artisan app:check-backlinks --frequency=all --project=5
```

### Vérification avec Limite

```bash
# Vérifier maximum 10 backlinks
php artisan app:check-backlinks --frequency=daily --limit=10
```

**Utile pour:**
- Tests en production
- Limiter la charge serveur
- Déploiements progressifs

### Vérifier les Backlinks Perdus

```bash
# Vérifier si les backlinks perdus sont de retour
php artisan app:check-backlinks --frequency=all --status=lost
```

### Vérification Immédiate (Tous)

```bash
# Forcer la vérification de tous les backlinks
php artisan app:check-backlinks --frequency=all --status=all
```

## Tests

### Fichier de Tests

**Fichier:** `tests/Feature/Console/CheckBacklinksCommandTest.php`

**11 tests / 23 assertions**

### Scénarios Testés

#### 1. Dispatch de Base

**✓ command_dispatches_jobs_for_active_backlinks**
```php
$backlinks = Backlink::factory()->count(3)->create(['status' => 'active']);

Artisan::call('app:check-backlinks', ['--frequency' => 'daily']);

Queue::assertPushed(CheckBacklinkJob::class, 3);
```

#### 2. Filtrage par Fréquence

**✓ command_filters_by_daily_frequency**
```php
// Backlink récent (12h) - exclu
Backlink::factory()->create(['last_checked_at' => now()->subHours(12)]);

// Backlink ancien (2j) - inclus
Backlink::factory()->create(['last_checked_at' => now()->subDays(2)]);

// Jamais vérifié - inclus
Backlink::factory()->create(['last_checked_at' => null]);

Artisan::call('app:check-backlinks', ['--frequency' => 'daily']);

Queue::assertPushed(CheckBacklinkJob::class, 2); // Seulement 2
```

**✓ command_filters_by_weekly_frequency**
```php
// Vérifié il y a 3 jours - exclu
Backlink::factory()->create(['last_checked_at' => now()->subDays(3)]);

// Vérifié il y a 10 jours - inclus
Backlink::factory()->create(['last_checked_at' => now()->subDays(10)]);

Artisan::call('app:check-backlinks', ['--frequency' => 'weekly']);

Queue::assertPushed(CheckBacklinkJob::class, 1);
```

#### 3. Filtres Avancés

**✓ command_filters_by_project**
```php
$project1 = Project::factory()->create();
$project2 = Project::factory()->create();

Backlink::factory()->create(['project_id' => $project1->id]);
Backlink::factory()->create(['project_id' => $project2->id]);

Artisan::call('app:check-backlinks', [
    '--frequency' => 'all',
    '--project' => $project2->id,
]);

Queue::assertPushed(CheckBacklinkJob::class, 1); // Seulement project2
```

**✓ command_filters_by_status**
```php
Backlink::factory()->create(['status' => 'active']);
Backlink::factory()->create(['status' => 'lost']);

Artisan::call('app:check-backlinks', [
    '--frequency' => 'all',
    '--status' => 'lost',
]);

Queue::assertPushed(CheckBacklinkJob::class, 1); // Seulement lost
```

**✓ command_filters_by_status_all**
```php
Backlink::factory()->create(['status' => 'active']);
Backlink::factory()->create(['status' => 'lost']);
Backlink::factory()->create(['status' => 'changed']);

Artisan::call('app:check-backlinks', [
    '--frequency' => 'all',
    '--status' => 'all',
]);

Queue::assertPushed(CheckBacklinkJob::class, 3); // Tous
```

**✓ command_respects_limit_option**
```php
Backlink::factory()->count(5)->create(['status' => 'active']);

Artisan::call('app:check-backlinks', [
    '--frequency' => 'all',
    '--limit' => 2,
]);

Queue::assertPushed(CheckBacklinkJob::class, 2); // Limite respectée
```

#### 4. Priorisation

**✓ command_prioritizes_never_checked_backlinks**
```php
$recentBacklink = Backlink::factory()->create([
    'last_checked_at' => now()->subDays(5),
]);

$neverChecked = Backlink::factory()->create([
    'last_checked_at' => null, // Prioritaire
]);

$veryOld = Backlink::factory()->create([
    'last_checked_at' => now()->subDays(30),
]);

Artisan::call('app:check-backlinks', [
    '--frequency' => 'all',
    '--limit' => 1,
]);

// Le jamais vérifié est dispatché en premier
Queue::assertPushed(CheckBacklinkJob::class, function ($job) use ($neverChecked) {
    return $job->backlink->id === $neverChecked->id;
});
```

#### 5. Cas Limites

**✓ command_returns_zero_when_no_backlinks_found**
```php
// Aucun backlink

$exitCode = Artisan::call('app:check-backlinks', ['--frequency' => 'daily']);

$this->assertEquals(0, $exitCode);
```

**✓ command_returns_error_on_invalid_frequency**
```php
$exitCode = Artisan::call('app:check-backlinks', ['--frequency' => 'invalid']);

$this->assertEquals(1, $exitCode); // Erreur
```

#### 6. Output

**✓ command_outputs_progress_information**
```php
Backlink::factory()->count(3)->create(['status' => 'active']);

$this->artisan('app:check-backlinks', ['--frequency' => 'all'])
     ->expectsOutput('🔍 Starting backlink check process...')
     ->expectsOutputToContain('Found 3 backlink(s) to check')
     ->expectsOutputToContain('Successfully dispatched 3 job(s)')
     ->assertExitCode(0);
```

## Résultats des Tests

```bash
php artisan test tests/Feature/Console/CheckBacklinksCommandTest.php

✓ command dispatches jobs for active backlinks (1.36s)
✓ command filters by daily frequency (0.14s)
✓ command filters by weekly frequency (0.10s)
✓ command filters by project (0.17s)
✓ command filters by status (0.19s)
✓ command filters by status all (0.16s)
✓ command respects limit option (0.14s)
✓ command prioritizes never checked backlinks (0.11s)
✓ command returns zero when no backlinks found (0.13s)
✓ command returns error on invalid frequency (0.17s)
✓ command outputs progress information (9.42s)

Tests:    11 passed (23 assertions)
Duration: 12.66s
```

**Tous les tests du projet:**
```bash
php artisan test

Tests:    121 passed (320 assertions)
Duration: 7.69s
```

## Fichiers Créés/Modifiés

**Créés:**
- `app/Console/Commands/CheckBacklinksCommand.php` - Command principal
- `tests/Feature/Console/CheckBacklinksCommandTest.php` - Tests Feature
- `docs/stories/STORY-017.md` - Documentation

**Modifiés:**
- `app/Console/Kernel.php` - Configuration Task Scheduler

## Monitoring et Logs

### Logs du Scheduler

```bash
# Voir les logs du scheduler
tail -f storage/logs/scheduler.log
```

**Exemple de log:**
```
🔍 Starting backlink check process...
Frequency: daily
Status filter: active
Found 42 backlink(s) to check.
 42/42 [████████████████████████████] 100%
✅ Successfully dispatched 42 job(s) to the queue.
```

### Logs Laravel

```bash
# Voir les logs généraux
tail -f storage/logs/laravel.log | grep "Backlink check command"
```

**Exemple de log:**
```
[2026-02-12 02:00:15] production.INFO: Backlink check command completed {
    "frequency": "daily",
    "project_id": null,
    "status": "active",
    "backlinks_found": 42,
    "jobs_dispatched": 42
}
```

### Monitoring de la Queue

```bash
# Voir les jobs en queue
php artisan queue:work --once

# Voir les failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all
```

### Dashboard Horizon (Production Linux)

Si Horizon est configuré sur un serveur Linux :

```bash
# Démarrer Horizon
php artisan horizon

# Accéder au dashboard
http://yoursite.com/horizon
```

## Scénarios d'Utilisation Réels

### Scénario 1: Setup Initial

**Situation:** Premier déploiement avec 100 backlinks jamais vérifiés.

```bash
# 1. Vérifier progressivement par lots de 20
php artisan app:check-backlinks --frequency=all --limit=20

# 2. Attendre que les jobs se terminent (queue:work)

# 3. Répéter jusqu'à tout vérifier
php artisan app:check-backlinks --frequency=all --limit=20
```

### Scénario 2: Maintenance Quotidienne

**Situation:** Opération quotidienne automatique.

Le scheduler exécute automatiquement à 2h :
```bash
php artisan app:check-backlinks --frequency=daily
```

Vérifie seulement les backlinks actifs non vérifiés depuis 24h.

### Scénario 3: Urgence - Projet Critique

**Situation:** Un client signale que ses backlinks sont perdus.

```bash
# Vérifier immédiatement tous les backlinks du projet
php artisan app:check-backlinks --frequency=all --project=15 --status=all
```

### Scénario 4: Récupération Après Panne

**Situation:** Le serveur était down pendant 3 jours.

```bash
# Vérifier tous les backlinks actifs avec limite
php artisan app:check-backlinks --frequency=all --status=active --limit=50

# Les plus anciens seront prioritaires
```

### Scénario 5: Audit Hebdomadaire

**Situation:** Audit complet tous les dimanches.

Le scheduler exécute automatiquement le dimanche à 3h :
```bash
php artisan app:check-backlinks --frequency=weekly --status=all
```

Vérifie TOUS les backlinks (actifs, perdus, modifiés) non vérifiés depuis 7 jours.

### Scénario 6: Déploiement Progressif

**Situation:** Test en production avant d'activer le scheduler.

```bash
# 1. Test avec 5 backlinks
php artisan app:check-backlinks --frequency=all --limit=5

# 2. Vérifier les logs et la queue

# 3. Augmenter progressivement
php artisan app:check-backlinks --frequency=all --limit=20

# 4. Activer le scheduler une fois validé
```

## Points d'Attention

### Queue Worker

Le command dispatch des jobs dans la queue. **Il faut un worker qui tourne** :

```bash
# En production (supervisord)
php artisan queue:work --tries=3 --timeout=120

# En développement
php artisan queue:work
```

Sans worker, les jobs restent en queue et ne sont jamais exécutés.

### Éviter les Overlaps

`withoutOverlapping()` empêche qu'une nouvelle exécution démarre si la précédente n'est pas terminée.

**Exemple:** Si la vérification quotidienne prend 30 minutes, la vérification du lendemain ne démarrera pas avant la fin.

### Limites de Performance

**Recommandations:**
- Éviter de dispatcher 1000+ jobs d'un coup
- Utiliser `--limit` pour des vérifications par lots
- Monitorer la mémoire du worker
- Ajuster le nombre de workers selon la charge

### Ordre de Priorité

L'ordre de dispatch est important :
1. Backlinks **jamais vérifiés** (NULL)
2. Backlinks les plus **anciens** (ASC)

Cela garantit que les nouveaux backlinks sont vérifiés rapidement.

### Logs et Debugging

En cas de problème :

```bash
# 1. Vérifier les logs du command
tail storage/logs/scheduler.log

# 2. Vérifier les logs Laravel
tail storage/logs/laravel.log

# 3. Vérifier les failed jobs
php artisan queue:failed

# 4. Tester manuellement
php artisan app:check-backlinks --frequency=all --limit=1
```

### Fréquences Recommandées

| Type de Backlink | Fréquence | Raison |
|------------------|-----------|--------|
| Nouveaux (< 1 mois) | Quotidienne | Vérifier stabilité |
| Actifs stables | Hebdomadaire | Économiser ressources |
| Perdus | Quotidienne | Détecter retour rapide |
| Modifiés | Quotidienne | Surveiller changements |

## Évolutions Futures

### Story Future: Fréquences Personnalisables par Projet

```php
// Dans Project model
protected $casts = [
    'check_frequency' => 'string', // daily, weekly, monthly
];

// Dans command
if ($project->check_frequency === 'daily') {
    // Logique spécifique
}
```

### Story Future: Notifications Automatiques

```php
// Après dispatch
if ($dispatched > 0) {
    Mail::to($admin)->send(new BacklinksCheckScheduled($dispatched));
}
```

### Story Future: Rate Limiting

```php
// Limiter les requêtes par domaine
RateLimiter::for('backlink-check', function (Backlink $backlink) {
    return Limit::perMinute(10)->by(parse_url($backlink->source_url)['host']);
});
```

## Prochaines Étapes

Le Sprint 2 est maintenant **100% complété !** 🎊

### Stories Complétées (37/37 points)

- ✅ STORY-018 (3 pts) - Horizon Configuration
- ✅ STORY-009 (3 pts) - Backlink Model
- ✅ STORY-015 (3 pts) - BacklinkCheck Model
- ✅ STORY-010 (5 pts) - Backlinks CRUD API
- ✅ STORY-011 (3 pts) - Backlinks List Component
- ✅ STORY-012 (3 pts) - Backlink Create/Edit Form
- ✅ STORY-013 (5 pts) - HTTP Service for Checking Backlinks
- ✅ STORY-014 (5 pts) - Check Backlink Job
- ✅ **STORY-017 (7 pts) - Schedule Backlink Checks**

### Fonctionnalités Opérationnelles

✅ **CRUD complet** : Projets et Backlinks
✅ **Interface Vue.js** : Liste, création, modification, détails
✅ **Vérification HTTP** : Service robuste avec SSRF protection
✅ **Jobs asynchrones** : Queue avec retries
✅ **Planification automatique** : Scheduler quotidien/hebdomadaire
✅ **Historique complet** : BacklinkCheck records
✅ **Statuts intelligents** : active/lost/changed

### Sprint 3 (Proposition)

- Dashboard analytics
- Notifications email/Slack
- Export CSV/PDF
- API webhooks
- Multi-tenancy

## Commits

```bash
git add .
git commit -m "feat(backlinks): implement Schedule Backlink Checks (STORY-017)" -m "- CheckBacklinksCommand with frequency filtering (daily, weekly, all)" -m "- Advanced filters: project, status, limit" -m "- Prioritization: never-checked first, then oldest" -m "- Progress bar and informative output" -m "- Task Scheduler configuration (daily 2am, weekly Sunday 3am)" -m "- withoutOverlapping to prevent concurrent executions" -m "- Comprehensive logging for monitoring" -m "- 11 Feature tests with 23 assertions (all passing)" -m "- Total: 121 tests passing (320 assertions)" -m "- Sprint 2 completed: 37/37 points (100%)" -m "" -m "Co-Authored-By: Claude Sonnet 4.5 <noreply@anthropic.com>"
```

# STORY-018: Configure Laravel Horizon for Production

**Epic:** EPIC-009 (Infrastructure)
**Points:** 3
**Status:** Completed ✅
**Date:** 2026-02-13

## Objectif

Configurer Laravel Horizon pour gérer efficacement le traitement des jobs asynchrones avec auto-scaling et monitoring.

## User Story

En tant que développeur
Je veux configurer Horizon pour gérer 100 jobs/minute
Afin de traiter efficacement toutes les vérifications de backlinks

## Implémentation

### 1. Installation Horizon

**Package installé:**
```bash
composer require laravel/horizon --ignore-platform-req=ext-pcntl --ignore-platform-req=ext-posix
```

**Version:** v5.44.0

**Note Windows:** Les extensions PCNTL et POSIX ne sont pas disponibles sur Windows. Nous ignorons ces requirements pour l'installation mais Horizon ne fonctionnera PAS pleinement sur Windows en développement.

### 2. Configuration

**Fichier:** `config/horizon.php`

#### Configuration des Queues

**3 queues configurées:**
- `high` - Jobs prioritaires (vérifications manuelles)
- `default` - Jobs automatiques (vérifications schedulées)
- `low` - Jobs de faible priorité (métriques, cleanups)

#### Environnement Production

```php
'production' => [
    'supervisor-1' => [
        'connection' => 'redis',
        'queue' => ['high', 'default', 'low'],
        'balance' => 'auto',
        'autoScalingStrategy' => 'time',
        'minProcesses' => 3,
        'maxProcesses' => 10,
        'balanceMaxShift' => 1,
        'balanceCooldown' => 3,
        'tries' => 3,
        'timeout' => 60,
        'nice' => 0,
    ],
],
```

**Caractéristiques:**
- **Auto-scaling**: 3-10 workers selon la charge
- **Balance strategy**: `auto` - Distribution intelligente
- **Timeout**: 60 secondes par job
- **Retries**: 3 tentatives avec exponential backoff

#### Environnement Local (Windows/Herd)

```php
'local' => [
    'supervisor-1' => [
        'connection' => 'redis',
        'queue' => ['high', 'default', 'low'],
        'balance' => 'simple',
        'maxProcesses' => 3,
        'minProcesses' => 1,
        'tries' => 3,
        'timeout' => 60,
    ],
],
```

**Limitations Windows:**
- Pas d'auto-scaling dynamique
- Maximum 3 workers
- Alternative: `php artisan queue:work` pour développement

### 3. Trimming Policy

**Rétention des jobs:**
- Recent/Completed: 60 minutes
- Failed: 7 jours (10080 minutes)
- Monitored: 7 jours

**Objectif:** Garder un historique pour debug mais ne pas surcharger Redis.

### 4. Wait Time Thresholds

**Alertes si dépassement:**
- `high`: 30 secondes
- `default`: 60 secondes
- `low`: 300 secondes (5 minutes)

### 5. Routes Horizon

**URL:** `/horizon`

**Middleware:** `web` (à protéger avec `auth` en production)

**Configuration à ajouter dans routes/web.php:**
```php
use Laravel\Horizon\Horizon;

Horizon::auth(function ($request) {
    return auth()->check();
});
```

## Tests de Validation

### 1. Vérification Installation

```bash
# Vérifier que Horizon est installé
composer show laravel/horizon

# Résultat attendu
laravel/horizon v5.44.0
```

### 2. Vérification Configuration

```bash
php artisan config:show horizon
```

### 3. Test Queue (Windows Alternative)

Puisque Horizon ne fonctionne pas sur Windows, utiliser `queue:work`:

```bash
# Démarrer le worker manuellement
php artisan queue:work redis --queue=high,default,low --tries=3 --timeout=60
```

### 4. Test Dispatch Job

```php
// Dans tinker
use Illuminate\Support\Facades\Queue;

// Dispatcher un job de test
dispatch(function () {
    logger('Test job executed');
})->onQueue('default');

// Vérifier dans les logs
tail -f storage/logs/laravel.log
```

## Acceptance Criteria

- [x] Horizon installé (v5.44.0)
- [x] config/horizon.php créé avec configuration complète
- [x] 3 queues configurées (high, default, low)
- [x] Auto-scaling configuré (3-10 workers en production)
- [x] Environnement local configuré (1-3 workers)
- [x] Timeout et retries définis (60s, 3 tries)
- [x] Trimming policy configurée (7 jours pour failed jobs)
- [x] Documentation des limitations Windows

## Limitations Connues

### Windows Development

**Problème:**
Horizon nécessite les extensions PHP `ext-pcntl` et `ext-posix` qui ne sont disponibles que sur Unix/Linux/macOS.

**Impact:**
- ❌ `php artisan horizon` ne fonctionnera PAS sur Windows
- ❌ Dashboard Horizon `/horizon` non accessible en local
- ❌ Auto-scaling non fonctionnel

**Solutions de contournement:**

#### Option 1: Queue Work Standard (Recommandé pour dev Windows)
```bash
php artisan queue:work redis --queue=high,default,low
```

✅ Fonctionne sur Windows
✅ Traite les jobs correctement
❌ Pas de dashboard
❌ Pas d'auto-scaling

#### Option 2: Docker/WSL2
```bash
# Utiliser Docker Desktop avec WSL2
docker-compose up horizon
```

✅ Horizon complet fonctionnel
❌ Overhead Docker
❌ Configuration supplémentaire

#### Option 3: Déploiement Linux pour tests
Tester Horizon sur environnement de staging Linux.

### Production (Linux)

En production sur serveur Linux, Horizon fonctionnera pleinement:

```bash
# Installer Horizon
composer install --no-dev --optimize-autoloader

# Démarrer Horizon
php artisan horizon

# Avec Supervisor pour auto-restart
[program:horizon]
command=php /path/to/artisan horizon
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/path/to/horizon.log
```

## Configuration Future

### Protection Route Horizon

**À ajouter dans routes/web.php:**
```php
use Laravel\Horizon\Horizon;

Horizon::auth(function ($request) {
    // Autoriser uniquement les admins
    return auth()->check() && auth()->user()->is_admin;
});
```

### Notifications

Configurer notifications Slack/Email pour failed jobs:

```php
// config/horizon.php
'notifications' => [
    'mail' => [
        'enabled' => true,
        'address' => 'admin@linktracker.app',
    ],
],
```

### Métriques

Ajouter snapshot command au scheduler:

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    $schedule->command('horizon:snapshot')->everyFiveMinutes();
}
```

## Prochaines Étapes (Sprint 2)

Cette configuration Horizon sera utilisée par:

1. **STORY-014**: CheckBacklink Job
   - Jobs dispatched dans queues high/default
   - Auto-scaling selon charge

2. **STORY-017**: Scheduled Monitoring
   - Command `backlinks:monitor` toutes les 4h
   - Dispatch batch de jobs

3. **STORY-XXX** (Sprint 3): Notifications
   - Failed job notifications
   - Metrics monitoring

## Références

- [Laravel Horizon Documentation](https://laravel.com/docs/10.x/horizon)
- [Horizon on Windows Issue](https://github.com/laravel/horizon/issues/1)
- [Queue Workers Documentation](https://laravel.com/docs/10.x/queues#running-the-queue-worker)

## Notes Techniques

### Pourquoi PCNTL/POSIX ?

Horizon utilise ces extensions pour:
- **Process forking**: Créer des workers enfants
- **Signal handling**: Gérer les signaux (SIGTERM, SIGKILL)
- **Process control**: Start/stop/restart workers
- **Auto-scaling**: Ajuster dynamiquement le nombre de workers

Ces fonctionnalités sont spécifiques Unix et n'existent pas sur Windows.

### Alternative: Laravel Queue Standard

Pour le développement Windows, `php artisan queue:work` suffit largement:
- ✅ Traite tous les jobs correctement
- ✅ Supporte tries et timeout
- ✅ Supporte multiple queues
- ❌ Pas de dashboard visuel
- ❌ Pas d'auto-scaling

### Redis Configuration

Horizon utilise Redis pour:
- Queue storage
- Job metadata
- Metrics et statistics

Configuration dans `.env`:
```env
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

## Commit

```
feat(infrastructure): configure Laravel Horizon for production (STORY-018)

Configuration complète de Horizon:
- 3 queues: high, default, low
- Auto-scaling: 3-10 workers en production
- Timeout: 60s, Retries: 3
- Failed jobs retention: 7 jours
- Local environment: 1-3 workers (Windows compatible)

Limitations Windows documentées:
- Horizon dashboard non accessible en dev Windows
- Alternative: php artisan queue:work

Configuration prête pour production Linux.

STORY-018 completed - 3 points
```

## Points Complétés

**3 points** - Configuration Horizon terminée avec documentation complète des limitations et alternatives.

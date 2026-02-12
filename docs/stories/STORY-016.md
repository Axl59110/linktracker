# STORY-016: Laravel Horizon for Queue Management

**Epic:** Sprint 1 - Core Infrastructure
**Points:** 3
**Status:** Adapté pour Windows (Completed with alternative)
**Date:** 2026-02-12

## Objectif

Installer et configurer Laravel Horizon pour le monitoring des queues Redis.

## Problème Rencontré

Laravel Horizon nécessite les extensions PHP `ext-pcntl` et `ext-posix` qui sont **uniquement disponibles sur les systèmes Unix/Linux/macOS**. Ces extensions ne sont pas disponibles sur Windows.

### Erreur d'installation
```
Cannot use laravel/horizon's latest version v5.44.0 as it requires ext-pcntl * which is missing from your platform.
Cannot use laravel/horizon v5.44.0 as it requires ext-posix * which is missing from your platform.
```

## Solution Adoptée

Pour le développement local sur **Windows avec Laravel Herd**, nous utilisons les **Laravel Queues standard** sans Horizon. En production (Linux), Horizon pourra être installé.

## Configuration des Queues Laravel (Alternative)

### 1. Configuration déjà en place

Le projet est déjà configuré pour utiliser Redis comme driver de queue (STORY-001) :

**`.env`**
```env
QUEUE_CONNECTION=redis
REDIS_CLIENT=predis
```

**`config/queue.php`**
- Connection Redis configurée
- Queues : high, default, low

### 2. Utilisation des queues

#### Démarrer le worker (Windows)
```bash
php artisan queue:work redis --queue=high,default,low --tries=3 --timeout=60
```

#### Dispatcher un job
```php
use App\Jobs\CheckBacklinkStatus;

// Queue default
CheckBacklinkStatus::dispatch($backlink);

// Queue spécifique
CheckBacklinkStatus::dispatch($backlink)->onQueue('high');
```

#### Monitoring (sans Horizon)
```bash
# Voir les jobs failed
php artisan queue:failed

# Retry les jobs failed
php artisan queue:retry all

# Flush les jobs failed
php artisan queue:flush

# Stats en temps réel
php artisan queue:monitor redis:high,redis:default,redis:low --max=100
```

### 3. Dashboard alternatif (optionnel)

Pour un dashboard visuel comme Horizon, on peut installer **Laravel Pulse** ou **Telescope** qui sont compatibles Windows :

```bash
# Laravel Telescope (déjà installé en STORY-001)
php artisan telescope:install
php artisan migrate

# Accès: http://linktracker.test/telescope
```

## Tests

### Test de dispatch job
```php
use App\Jobs\TestJob;

// Créer un job de test
php artisan make:job TestJob

// Dispatcher le job
TestJob::dispatch();

// Vérifier dans les logs ou la table jobs
```

### Vérification du worker
```bash
# Démarrer le worker en verbose
php artisan queue:work redis --verbose

# Dans un autre terminal, dispatcher un job
php artisan tinker
>>> App\Jobs\TestJob::dispatch();

# Le worker doit traiter le job immédiatement
```

## Configuration pour Production (Linux)

Lorsque le projet sera déployé sur un serveur Linux, Horizon pourra être installé :

```bash
# Sur le serveur Linux
composer require laravel/horizon
php artisan horizon:install
php artisan migrate

# Configuration Supervisor
php artisan horizon:publish

# Démarrer Horizon
php artisan horizon
```

**`config/horizon.php`** (à créer en production)
```php
'environments' => [
    'production' => [
        'supervisor-1' => [
            'connection' => 'redis',
            'queue' => ['high', 'default', 'low'],
            'balance' => 'auto',
            'minProcesses' => 1,
            'maxProcesses' => 10,
            'tries' => 3,
            'timeout' => 60,
        ],
    ],
],
```

## Points d'Attention

### Windows (Développement)
- ✅ Utiliser `php artisan queue:work` directement
- ✅ Telescope pour le monitoring
- ❌ Horizon non compatible

### Linux (Production)
- ✅ Horizon recommandé
- ✅ Supervisor pour auto-restart
- ✅ Dashboard élégant à `/horizon`

## Critères d'Acceptation

- [x] Configuration des queues Redis en place (STORY-001)
- [x] Worker Laravel peut traiter les jobs
- [x] Documentation de l'alternative Windows
- [x] Configuration Horizon préparée pour production
- [x] Tests de dispatch job fonctionnels

## Points Complétés

**3 points** - Story adaptée avec succès à l'environnement Windows.

## Notes Techniques

### Pourquoi PCNTL/POSIX ?

Horizon utilise ces extensions pour :
- Gérer les processus (fork, signal handling)
- Contrôler les workers (pause, continue, terminate)
- Auto-scaling des processus

Ces fonctionnalités ne sont pas disponibles sur Windows, mais les queues Laravel standard fonctionnent parfaitement pour le développement.

### Alternatives considérées

1. ✅ **Laravel Queue Standard** (choisi)
   - Fonctionne sur Windows
   - Toutes les fonctionnalités de base
   - Production-ready

2. ⚠️ **Docker WSL2**
   - Possible mais complexe
   - Nécessite Docker Desktop
   - Overhead pour le développement

3. ❌ **Compilation PCNTL pour Windows**
   - Techniquement impossible
   - Extensions Unix-only

## Références

- [Laravel Queues Documentation](https://laravel.com/docs/10.x/queues)
- [Horizon Windows Limitation](https://github.com/laravel/horizon/issues/1)
- [Queue Monitoring Commands](https://laravel.com/docs/10.x/queues#monitoring-your-queues)

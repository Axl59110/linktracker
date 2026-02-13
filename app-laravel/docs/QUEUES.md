# Configuration des Queues Laravel

## Vue d'ensemble

Le système de queues permet d'exécuter des tâches en arrière-plan, notamment la vérification des backlinks qui peut prendre du temps.

## Configuration

### 1. Migration des tables

Créer les tables nécessaires pour le driver `database` :

```bash
php artisan migrate
```

Cela créera les tables :
- `jobs` : file d'attente des jobs en cours
- `failed_jobs` : jobs ayant échoué après toutes les tentatives

### 2. Configuration .env

Par défaut, le projet utilise `QUEUE_CONNECTION=sync` (synchrone, pour le développement).

Pour activer les queues asynchrones en production :

```env
# Utiliser le driver database (recommandé pour démarrer)
QUEUE_CONNECTION=database

# Ou utiliser Redis pour de meilleures performances
# QUEUE_CONNECTION=redis
# REDIS_HOST=127.0.0.1
# REDIS_PASSWORD=null
# REDIS_PORT=6379
```

### 3. Lancer le worker

En développement :
```bash
php artisan queue:work --verbose
```

Avec rechargement automatique du code (développement) :
```bash
php artisan queue:listen
```

En production avec options :
```bash
php artisan queue:work --tries=3 --timeout=120 --sleep=3
```

### 4. Supervision avec Supervisor (Production)

Créer `/etc/supervisor/conf.d/linktracker-worker.conf` :

```ini
[program:linktracker-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/app-laravel/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/app-laravel/storage/logs/worker.log
stopwaitsecs=3600
```

Commandes Supervisor :
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start linktracker-worker:*
sudo supervisorctl status
```

## Jobs disponibles

### CheckBacklinkJob

Vérifie un backlink spécifique :
- Fait une requête HTTP vers `source_url`
- Parse le HTML pour trouver le lien vers `target_url`
- Extrait l'ancre, les attributs rel, et détecte si c'est dofollow/nofollow
- Met à jour le statut du backlink (active, lost, changed)
- Crée un enregistrement `BacklinkCheck` dans l'historique

**Configuration :**
- Tentatives : 3
- Timeout : 120 secondes
- Queue : `default`

**Dispatch manuel :**
```php
use App\Jobs\CheckBacklinkJob;
use App\Models\Backlink;

$backlink = Backlink::find(1);
CheckBacklinkJob::dispatch($backlink);
```

## Commandes artisan

### app:check-backlinks

Lance la vérification de plusieurs backlinks en batch :

```bash
# Vérifier les backlinks non vérifiés depuis 24h
php artisan app:check-backlinks --frequency=daily

# Vérifier tous les backlinks non vérifiés depuis 7 jours
php artisan app:check-backlinks --frequency=weekly

# Vérifier tous les backlinks
php artisan app:check-backlinks --frequency=all

# Filtrer par projet
php artisan app:check-backlinks --project=1

# Filtrer par statut
php artisan app:check-backlinks --status=active
php artisan app:check-backlinks --status=lost
php artisan app:check-backlinks --status=all

# Limiter le nombre
php artisan app:check-backlinks --limit=50
```

## Scheduler (Cron)

Le scheduler Laravel est configuré dans `app/Console/Kernel.php` :

- **Vérification quotidienne** : tous les jours à 2h du matin
  - Backlinks non vérifiés depuis 24h
  - Statut : active uniquement

- **Vérification hebdomadaire** : tous les dimanches à 3h du matin
  - Tous les backlinks non vérifiés depuis 7 jours
  - Tous les statuts

Pour activer le scheduler, ajouter cette ligne au crontab :

```bash
* * * * * cd /path/to/app-laravel && php artisan schedule:run >> /dev/null 2>&1
```

En développement, simuler le cron :
```bash
php artisan schedule:work
```

## Monitoring

### Voir les jobs en attente

```bash
# Avec Tinker
php artisan tinker
DB::table('jobs')->count();
DB::table('jobs')->get();
```

### Voir les jobs échoués

```bash
php artisan queue:failed
```

### Réessayer un job échoué

```bash
# Réessayer un job spécifique
php artisan queue:retry {id}

# Réessayer tous les jobs échoués
php artisan queue:retry all
```

### Supprimer les jobs échoués

```bash
# Supprimer un job spécifique
php artisan queue:forget {id}

# Supprimer tous les jobs échoués
php artisan queue:flush
```

## Logs

Les logs sont écrits dans :
- `storage/logs/laravel.log` : logs généraux de Laravel
- `storage/logs/scheduler.log` : logs du scheduler (commandes cron)
- `storage/logs/worker.log` : logs du worker (si Supervisor configuré)

Filtrer les logs de CheckBacklinkJob :
```bash
tail -f storage/logs/laravel.log | grep CheckBacklinkJob
tail -f storage/logs/laravel.log | grep "Starting backlink check"
tail -f storage/logs/laravel.log | grep "Backlink check completed"
```

## Tests

Tester le système de queues en local :

```bash
# 1. Terminal 1 : lancer le worker
php artisan queue:work --verbose

# 2. Terminal 2 : dispatcher un job manuellement
php artisan tinker
>>> $backlink = App\Models\Backlink::first();
>>> App\Jobs\CheckBacklinkJob::dispatch($backlink);

# 3. Observer les logs dans Terminal 1
```

## Bonnes pratiques

1. **Développement** : utiliser `QUEUE_CONNECTION=sync` pour débugger facilement
2. **Staging/Production** : utiliser `database` ou `redis`
3. **Monitoring** : utiliser Laravel Horizon si Redis (interface web pour les queues)
4. **Erreurs** : toujours vérifier les `failed_jobs` et les logs
5. **Rate limiting** : ne pas surcharger les sites externes (respecter les robots.txt)

## Troubleshooting

### Les jobs ne s'exécutent pas

1. Vérifier que le worker tourne : `ps aux | grep "queue:work"`
2. Vérifier la configuration : `php artisan config:cache`
3. Vérifier les logs : `tail -f storage/logs/laravel.log`

### Jobs qui échouent en boucle

1. Vérifier le nombre de tentatives dans le job
2. Vérifier les timeouts (serveur, job, worker)
3. Ajouter des try-catch dans le code métier
4. Implémenter la méthode `failed()` dans le job

### Worker qui se coupe

1. Augmenter `--max-time` et `--max-jobs`
2. Vérifier la mémoire : `--memory=512`
3. Utiliser Supervisor pour auto-restart

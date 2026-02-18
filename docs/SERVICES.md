# Documentation des Services — LinkTracker

**Version :** Sprint 4 (18/02/2026)

---

## Table des matières

1. [BacklinkCheckerService](#backlinkcheckerservice)
2. [AlertService](#alertservice)
3. [SeoMetricService](#seometricservice)
4. [BacklinkCsvImportService](#backlinkcsvimportservice)
5. [UrlValidator (Sécurité SSRF)](#urlvalidator-sécurité-ssrf)
6. [Jobs de Queue](#jobs-de-queue)
7. [Observers](#observers)
8. [Cache et Performance](#cache-et-performance)

---

## BacklinkCheckerService

**Fichier :** `app/Services/BacklinkCheckerService.php`

Service responsable de vérifier la présence et les attributs d'un backlink sur une page web.

### Méthodes publiques

#### `check(Backlink $backlink): BacklinkCheckResult`

Effectue une vérification complète d'un backlink.

**Processus :**
1. Requête HTTP GET vers `source_url` (timeout configurable)
2. Parse le HTML avec `DOMDocument` + `DOMXPath`
3. Recherche le lien vers `target_url` (normalisation URL)
4. Extrait ancre, attributs `rel` (nofollow, ugc, sponsored)
5. Retourne un `BacklinkCheckResult` avec présence, ancre, attributs

**Configuration :**
```php
$service = new BacklinkCheckerService(
    timeout: 30,        // secondes
    userAgent: 'LinkTracker/1.0'
);
```

**Protection SSRF :** Le service délègue la validation URL à `UrlValidator` avant d'effectuer la requête. Les IPs privées (RFC 1918, loopback, link-local) sont bloquées.

**Normalisation URL :** Les URLs sont normalisées avant comparaison :
- HTTP vs HTTPS traités comme équivalents
- `www.` supprimé
- Slash final ignoré

### BacklinkCheckResult

Objet valeur retourné par `check()` :

```php
$result->isPresent     // bool : lien trouvé ?
$result->anchorText    // ?string : texte d'ancre
$result->isNofollow    // bool : attribut nofollow présent ?
$result->isUgc        // bool : attribut ugc présent ?
$result->httpStatus    // int : code HTTP de la page source
$result->errorMessage  // ?string : message d'erreur si échec
```

---

## AlertService

**Fichier :** `app/Services/AlertService.php`

Service de création et gestion des alertes pour les backlinks.

### Méthodes publiques

#### `createBacklinkLostAlert(Backlink $backlink): Alert`

Crée une alerte "backlink perdu" quand `is_present=false` après une vérification.

**Sévérité calculée :**
- `critical` : tier1 + spot_type=external + price > 50
- `high` : tier1 ou price > 20
- `medium` : tier2 ou price > 0
- `low` : tous les autres cas

#### `createBacklinkChangedAlert(Backlink $backlink, array $changes): Alert`

Crée une alerte "backlink modifié" (ancre changée, nofollow ajouté, etc.).

**Paramètre `$changes` :**
```php
[
    'anchor_text' => ['old' => 'Ancre avant', 'new' => 'Ancre après'],
    'is_nofollow' => ['old' => false, 'new' => true],
]
```

**Sévérité calculée :**
- `high` : ajout de nofollow sur tier1
- `medium` : changement d'ancre
- `low` : autres modifications

#### `createBacklinkRecoveredAlert(Backlink $backlink): Alert`

Crée une alerte "backlink récupéré" quand un backlink perdu est retrouvé.

**Sévérité :** toujours `low` (bonne nouvelle)

### Notifications email

Les alertes `critical` et `high` déclenchent automatiquement une notification email via `CriticalAlertNotification` (si `alerts_email_enabled=true` dans les settings).

---

## SeoMetricService

**Fichier :** `app/Services/Seo/SeoMetricService.php`

Service de récupération et stockage des métriques SEO (Domain Authority, Spam Score).

### Architecture

Utilise le pattern **Strategy** avec un provider configurable :
- `CustomSeoProvider` — Provider local (pas de clé API, retourne erreur)
- `MozSeoProvider` — Provider Moz API (nécessite `MOZ_ACCESS_ID` + `MOZ_SECRET_KEY`)

### Méthodes publiques

#### `fetchAndStore(string $domain): DomainMetric`

Récupère les métriques pour un domaine et les stocke en base.

**Processus :**
1. Sélectionne le provider selon la configuration Settings
2. Appelle `provider->fetch($domain)` → `SeoMetricDto`
3. Upsert dans `domain_metrics` (`DomainMetric::forDomain($domain)`)
4. Retourne le modèle mis à jour

#### `getAvailableProviders(): array`

Retourne la liste des providers disponibles avec leur statut.

### SeoMetricDto

```php
$dto->domainAuthority   // ?int : Domain Authority (0-100)
$dto->spamScore         // ?int : Spam Score (0-100)
$dto->hasData()         // bool : au moins une métrique disponible
$dto->hasError()        // bool : une erreur s'est produite
$dto->error             // ?string : message d'erreur
```

### Configuration provider

Configuré via la page Settings (`/settings`) → "Métriques SEO".
Stocké dans `settings` table clé `seo_provider`.

---

## BacklinkCsvImportService

**Fichier :** `app/Services/BacklinkCsvImportService.php`

Service d'import de backlinks depuis un fichier CSV.

### Méthodes publiques

#### `import(UploadedFile $file, int $projectId): ImportResult`

Traite un fichier CSV et crée les backlinks.

**Format CSV attendu :**
```csv
source_url,target_url,anchor_text,project_id,tier_level,spot_type
```

**Règles d'import :**
- Lignes avec URL invalide : ignorées (comptées dans `skipped`)
- Doublons (`source_url` + `target_url` existants) : ignorées
- Colonnes manquantes : erreur retournée sans import
- `project_id` en colonne CSV surcharge le paramètre `$projectId`
- Valeurs par défaut : `tier_level=tier1`, `spot_type=external`

### ImportResult

```php
$result->imported   // int : nombre de backlinks créés
$result->skipped    // int : nombre de lignes ignorées
$result->errors     // array : erreurs rencontrées
```

---

## UrlValidator (Sécurité SSRF)

**Fichier :** `app/Services/Security/UrlValidator.php`
**Exception :** `app/Exceptions/SsrfException.php`

Service de validation des URLs pour prévenir les attaques SSRF (Server-Side Request Forgery).

### Méthodes publiques

#### `validate(string $url): void`

Valide une URL et lève `SsrfException` si elle est dangereuse.

**Vérifications effectuées (dans l'ordre) :**
1. URL valide (scheme http/https, format correct)
2. Scheme autorisé (uniquement http et https)
3. Host extrait et validé
4. Si host est une IP directe : bloquer les plages privées
5. Si host est un domaine : tenter résolution DNS, bloquer si résout vers IP privée
6. Si DNS ne résout pas : laisser passer (erreur réseau côté serveur)

**Plages IP bloquées :**
```
127.0.0.0/8    — Loopback
10.0.0.0/8     — RFC 1918 privé
172.16.0.0/12  — RFC 1918 privé
192.168.0.0/16 — RFC 1918 privé
169.254.0.0/16 — Link-local (AWS metadata)
0.0.0.0/8      — Adresse nulle
224.0.0.0/4    — Multicast
```

**Utilisation dans les controllers :**
```php
try {
    app(UrlValidator::class)->validate($url);
} catch (SsrfException $e) {
    // URL bloquée
    $fail($e->getMessage());
}
```

---

## Jobs de Queue

### CheckBacklinkJob

**Fichier :** `app/Jobs/CheckBacklinkJob.php`

Job de vérification d'un backlink individuel.

**Configuration :**
- Queue : `default`
- Tentatives max : 3
- Timeout : 120 secondes
- Délai entre tentatives : exponentiel

**Processus :**
1. Appel `BacklinkCheckerService::check()`
2. Création d'un `BacklinkCheck` (historique)
3. Mise à jour `backlink.status` (active/lost/changed)
4. Création d'alertes via `AlertService` si changement détecté
5. Envoi notification email si alerte critique

**Dispatch :**
```php
CheckBacklinkJob::dispatch($backlink);
// Ou depuis la commande Artisan :
// php artisan app:check-backlinks --frequency=daily
```

### FetchSeoMetricsJob

**Fichier :** `app/Jobs/FetchSeoMetricsJob.php`

Job de récupération des métriques SEO pour un domaine.

**Dispatch :**
```php
FetchSeoMetricsJob::dispatch($domainMetric);
// Ou depuis la commande :
// php artisan app:refresh-seo-metrics --force
```

---

## Observers

### BacklinkObserver

**Fichier :** `app/Observers/BacklinkObserver.php`

Observer Eloquent déclenché sur les événements du modèle `Backlink`.

**Actions :**
- `created` → `Cache::forget('dashboard_stats')`
- `updated` → `Cache::forget('dashboard_stats')`
- `deleted` → `Cache::forget('dashboard_stats')`

**Enregistrement :** `AppServiceProvider::boot()` via `Backlink::observe(BacklinkObserver::class)`

---

## Cache et Performance

### Stats Dashboard

**Clé :** `dashboard_stats`
**TTL :** 300 secondes (5 minutes)
**Invalidation :** Automatique via `BacklinkObserver` sur tout changement de backlink

**Données cachées :**
```php
[
    'activeBacklinks'   => int,
    'lostBacklinks'     => int,
    'changedBacklinks'  => int,
    'totalBacklinks'    => int,
    'totalProjects'     => int,
    'totalChecks'       => int,  // 30 derniers jours
    'uptimeRate'        => float, // % sur 30 jours
]
```

**Non cachés** (toujours frais) :
- `recentProjects` — 5 derniers projets
- `recentBacklinks` — 5 derniers backlinks
- `recentAlerts` — 5 dernières alertes non lues

### Indexes base de données

Indexes ajoutés en Sprint 4 (STORY-040) pour les colonnes les plus filtrées :
- `backlinks.status`
- `backlinks.project_id`
- `backlinks.tier_level`
- `backlinks.spot_type`
- `backlink_checks.backlink_id`
- `backlink_checks.checked_at`
- `alerts.is_read`
- `domain_metrics.domain`
- `orders.status`
- `orders.ordered_at`

---

## Commandes Artisan

| Commande | Description | Options |
|----------|-------------|---------|
| `app:check-backlinks` | Vérifier les backlinks en lot | `--frequency=daily\|weekly`, `--project=ID`, `--limit=N` |
| `app:check-backlink {id}` | Vérifier un backlink spécifique | `--verbose` |
| `app:refresh-seo-metrics` | Rafraîchir les métriques SEO | `--force` |
| `app:queue-status` | Afficher le statut de la queue | `--failed`, `--reset-failed`, `--limit=N` |

### app:queue-status (STORY-042)

```bash
# Statut général de la queue
php artisan app:queue-status

# Voir les jobs en échec
php artisan app:queue-status --failed

# Remettre les jobs en échec dans la queue
php artisan app:queue-status --reset-failed

# Limiter l'affichage des jobs en échec
php artisan app:queue-status --failed --limit=5
```

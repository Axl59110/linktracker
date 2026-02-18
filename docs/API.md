# Documentation API — LinkTracker

**Version :** Sprint 4 (18/02/2026)
**Stack :** Laravel 10.x, Blade + AlpineJS, SQLite/PostgreSQL

---

## Table des matières

1. [Routes Web Principales](#routes-web-principales)
2. [Authentification](#authentification)
3. [Dashboard](#dashboard)
4. [Projets](#projets)
5. [Backlinks](#backlinks)
6. [Alertes](#alertes)
7. [Commandes (Orders)](#commandes-orders)
8. [Plateformes](#plateformes)
9. [Paramètres](#paramètres)
10. [Profil](#profil)
11. [API JSON endpoints](#api-json-endpoints)
12. [Rate Limiting](#rate-limiting)

---

## Routes Web Principales

Toutes les routes sont dans `routes/web.php`. L'application utilise des sessions Laravel (pas de JWT). Toutes les routes nécessitent une session active (redirection vers `/login` sinon).

---

## Authentification

### API Sanctum (JSON)

| Méthode | URL | Description |
|---------|-----|-------------|
| POST | `/api/v1/auth/login` | Connexion |
| POST | `/api/v1/auth/logout` | Déconnexion |
| GET | `/api/v1/auth/user` | Informations utilisateur connecté |
| GET | `/sanctum/csrf-cookie` | Obtenir le cookie CSRF |

**Exemple connexion :**
```http
POST /api/v1/auth/login
Content-Type: application/json

{
    "email": "user@example.com",
    "password": "secret123"
}
```

**Réponse succès (200) :**
```json
{
    "user": {
        "id": 1,
        "name": "Axel",
        "email": "user@example.com",
        "created_at": "2026-02-10T00:00:00Z"
    },
    "message": "Connexion réussie"
}
```

---

## Dashboard

| Méthode | URL | Route Name | Description |
|---------|-----|------------|-------------|
| GET | `/dashboard` | `dashboard` | Page principale avec stats |
| GET | `/api/dashboard/chart` | `dashboard.chart` | Données graphique JSON |

### GET /api/dashboard/chart

Retourne les données de vérification pour le graphique. Les stats sont mises en cache 5 minutes (`Cache::remember('dashboard_stats', 300)`).

**Paramètres query :**
| Paramètre | Type | Default | Description |
|-----------|------|---------|-------------|
| `days` | int | 30 | Période en jours (7, 30, 90) |
| `project_id` | int | null | Filtrer par projet |

**Exemple :**
```http
GET /api/dashboard/chart?days=30&project_id=1
```

**Réponse (200) :**
```json
{
    "labels": ["18/01", "19/01", ..., "18/02"],
    "checks": [5, 3, 8, ...],
    "active": [4, 3, 7, ...]
}
```

**Stats dashboard (cachées 5 min) :**
- `activeBacklinks` — count backlinks status=active
- `lostBacklinks` — count backlinks status=lost
- `changedBacklinks` — count backlinks status=changed
- `totalBacklinks` — count total backlinks
- `totalProjects` — count total projets
- `totalChecks` — count backlink_checks (30 derniers jours)
- `uptimeRate` — % is_present=true sur 30 jours

---

## Projets

| Méthode | URL | Route Name | Description |
|---------|-----|------------|-------------|
| GET | `/projects` | `projects.index` | Liste des projets |
| GET | `/projects/create` | `projects.create` | Formulaire création |
| POST | `/projects` | `projects.store` | Créer un projet |
| GET | `/projects/{project}` | `projects.show` | Détail projet |
| GET | `/projects/{project}/edit` | `projects.edit` | Formulaire modification |
| PUT | `/projects/{project}` | `projects.update` | Modifier un projet |
| DELETE | `/projects/{project}` | `projects.destroy` | Supprimer un projet |
| GET | `/projects/{project}/report` | `projects.report` | Rapport HTML imprimable |

### GET /projects/{project}/report (STORY-039)

Génère un rapport HTML standalone (sans layout principal) avec CSS d'impression. Retourne une vue HTML complète.

**Contenu du rapport :**
- Stats par statut (total, actifs, perdus, modifiés)
- Tableau des backlinks avec DA (Domain Authority)
- Bouton "Imprimer" (masqué à l'impression via `@media print`)
- CSS embarqué pour autonomie (ouverture `target="_blank"`)

---

## Backlinks

| Méthode | URL | Route Name | Description |
|---------|-----|------------|-------------|
| GET | `/backlinks` | `backlinks.index` | Liste avec filtres |
| GET | `/backlinks/create` | `backlinks.create` | Formulaire création |
| POST | `/backlinks` | `backlinks.store` | Créer un backlink |
| GET | `/backlinks/{backlink}` | `backlinks.show` | Détail backlink |
| GET | `/backlinks/{backlink}/edit` | `backlinks.edit` | Formulaire modification |
| PUT | `/backlinks/{backlink}` | `backlinks.update` | Modifier un backlink |
| DELETE | `/backlinks/{backlink}` | `backlinks.destroy` | Supprimer un backlink |
| POST | `/backlinks/{backlink}/check` | `backlinks.check` | Vérification manuelle |
| POST | `/backlinks/{backlink}/seo-metrics` | `backlinks.seo-metrics` | Refresh métriques SEO |
| GET | `/backlinks/import` | `backlinks.import` | Formulaire import CSV |
| POST | `/backlinks/import` | `backlinks.import.process` | Traiter import CSV |
| GET | `/backlinks/export` | `backlinks.export` | Exporter CSV |

### Filtres disponibles sur GET /backlinks

| Paramètre | Valeurs | Description |
|-----------|---------|-------------|
| `search` | string (max 200) | Recherche source_url / anchor_text |
| `status` | active, lost, changed | Filtrer par statut |
| `project_id` | int | Filtrer par projet |
| `tier_level` | tier1, tier2 | Filtrer par niveau |
| `spot_type` | internal, external | Filtrer par type |
| `sort` | source_url, status, tier_level, spot_type, last_checked_at, created_at | Tri |
| `direction` | asc, desc | Direction du tri |

### POST /backlinks/{backlink}/check

Déclenche une vérification manuelle du backlink (appel HTTP + parse HTML).

**Rate limit :** 10 requêtes/min par utilisateur (`throttle:backlink-check`)

**Réponse :** Redirect vers `backlinks.show` avec flash message.

### POST /backlinks/{backlink}/seo-metrics

Déclenche un refresh asynchrone des métriques SEO (Domain Authority, Spam Score).

**Rate limit :** 3 requêtes/min par utilisateur (`throttle:seo-refresh`)

**Réponse :** Redirect vers `backlinks.show` avec flash message.

### POST /backlinks/import (CSV)

**Rate limit :** 5 requêtes/min par utilisateur (`throttle:backlink-import`)

**Format CSV attendu :**
```csv
source_url,target_url,anchor_text,project_id,tier_level,spot_type
https://blog.example.com/article,https://mysite.com,Mon ancre,1,tier1,external
```

**Colonnes obligatoires :** `source_url`, `target_url`
**Colonnes optionnelles :** `anchor_text`, `project_id`, `tier_level` (défaut: tier1), `spot_type` (défaut: external)

---

## Alertes

| Méthode | URL | Route Name | Description |
|---------|-----|------------|-------------|
| GET | `/alerts` | `alerts.index` | Liste des alertes |
| PATCH | `/alerts/{alert}/mark-read` | `alerts.mark-read` | Marquer comme lue |
| PATCH | `/alerts/mark-all-read` | `alerts.mark-all-read` | Tout marquer comme lu |
| DELETE | `/alerts/{alert}` | `alerts.destroy` | Supprimer une alerte |
| DELETE | `/alerts/destroy-all-read` | `alerts.destroy-all-read` | Supprimer toutes les lues |

**Types d'alertes :**
- `backlink_lost` — Le backlink n'est plus trouvé sur la page source
- `backlink_changed` — L'ancre ou les attributs rel ont changé
- `backlink_recovered` — Un backlink perdu a été retrouvé

**Sévérités :** `critical`, `high`, `medium`, `low`

---

## Commandes (Orders)

| Méthode | URL | Route Name | Description |
|---------|-----|------------|-------------|
| GET | `/orders` | `orders.index` | Liste avec tri |
| GET | `/orders/create` | `orders.create` | Formulaire création |
| POST | `/orders` | `orders.store` | Créer une commande |
| GET | `/orders/{order}` | `orders.show` | Détail + timeline |
| GET | `/orders/{order}/edit` | `orders.edit` | Formulaire modification |
| PUT | `/orders/{order}` | `orders.update` | Modifier une commande |
| DELETE | `/orders/{order}` | `orders.destroy` | Supprimer une commande |
| PATCH | `/orders/{order}/status` | `orders.status` | Changer le statut |

### Tri sur GET /orders (STORY-047)

| Paramètre `sort` | Description |
|-----------------|-------------|
| `ordered_at` | Date de commande |
| `expected_at` | Date de publication prévue |
| `published_at` | Date de publication effective |
| `price` | Prix |
| `status` | Statut |
| `created_at` | Date de création (défaut) |

**Paramètre `direction` :** `asc` ou `desc` (défaut: `desc`)

### Statuts commande

| Statut | Label FR |
|--------|----------|
| `pending` | En attente |
| `in_progress` | En cours |
| `published` | Publié |
| `cancelled` | Annulé |
| `rejected` | Rejeté |

**Workflow automatique (STORY-036) :** Quand le statut passe à `published`, un backlink est automatiquement créé depuis `source_url` vers le projet cible.

### Timeline statut (STORY-037)

Chaque changement de statut est enregistré dans la table `order_status_logs`. Visible sur la page `orders.show`.

**Champs OrderStatusLog :** `order_id`, `old_status`, `new_status`, `notes`, `changed_at`

---

## Plateformes

| Méthode | URL | Route Name | Description |
|---------|-----|------------|-------------|
| GET | `/platforms` | `platforms.index` | Liste des plateformes |
| GET | `/platforms/create` | `platforms.create` | Formulaire création |
| POST | `/platforms` | `platforms.store` | Créer une plateforme |
| GET | `/platforms/{platform}/edit` | `platforms.edit` | Formulaire modification |
| PUT | `/platforms/{platform}` | `platforms.update` | Modifier |
| DELETE | `/platforms/{platform}` | `platforms.destroy` | Supprimer |

---

## Paramètres

| Méthode | URL | Route Name | Description |
|---------|-----|------------|-------------|
| GET | `/settings` | `settings.index` | Page paramètres |
| PATCH | `/settings/monitoring` | `settings.monitoring` | Monitoring (fréquence, timeout, email) |
| PATCH | `/settings/seo` | `settings.seo` | Provider SEO (custom/moz) |
| POST | `/settings/seo/test` | `settings.seo.test` | Tester connexion SEO |
| GET | `/settings/webhook` | `settings.webhook` | Page webhook |
| PUT | `/settings/webhook` | `settings.webhook.update` | Configurer webhook |
| POST | `/settings/webhook/test` | `settings.webhook.test` | Tester webhook |
| GET | `/settings/webhook/generate-secret` | `settings.webhook.generate-secret` | Générer secret HMAC |

---

## Profil

| Méthode | URL | Route Name | Description |
|---------|-----|------------|-------------|
| GET | `/profile` | `profile.show` | Page profil utilisateur |
| PATCH | `/profile/password` | `profile.password` | Changer mot de passe |

### PATCH /profile/password

**Body :**
```
current_password=ancien_mdp
password=nouveau_mdp
password_confirmation=nouveau_mdp
```

**Validation :**
- `current_password` : doit correspondre au hash actuel
- `password` : minimum 8 caractères
- `password_confirmation` : doit correspondre à `password`

---

## API JSON Endpoints

### GET /api/dashboard/chart

Voir section Dashboard ci-dessus.

### API REST v1 (Sanctum)

Préfixe : `/api/v1/`
Middleware : `auth:sanctum`

| Méthode | URL | Description |
|---------|-----|-------------|
| POST | `/api/v1/auth/login` | Connexion |
| POST | `/api/v1/auth/logout` | Déconnexion |
| GET | `/api/v1/auth/user` | Utilisateur courant |
| GET | `/api/v1/projects` | Lister projets |
| POST | `/api/v1/projects` | Créer projet |
| GET | `/api/v1/projects/{id}` | Détail projet |
| PUT | `/api/v1/projects/{id}` | Modifier projet |
| DELETE | `/api/v1/projects/{id}` | Supprimer projet |
| GET | `/api/v1/projects/{id}/backlinks` | Backlinks d'un projet |
| POST | `/api/v1/projects/{id}/backlinks` | Créer backlink |
| GET | `/api/v1/backlinks/{id}` | Détail backlink |
| PUT | `/api/v1/backlinks/{id}` | Modifier backlink |
| DELETE | `/api/v1/backlinks/{id}` | Supprimer backlink |

---

## Rate Limiting

Configuration dans `AppServiceProvider::configureRateLimiting()` (STORY-044).

| Route | Limite | Par | Named Limiter |
|-------|--------|-----|---------------|
| `backlinks.check` | 10 req/min | Utilisateur (ID ou IP) | `backlink-check` |
| `backlinks.import.process` | 5 req/min | Utilisateur (ID ou IP) | `backlink-import` |
| `backlinks.seo-metrics` | 3 req/min | Utilisateur (ID ou IP) | `seo-refresh` |
| `backlinks.*` (index, CRUD) | 60 req/min | IP | `backlinks-general` |

**Réponse 429 :**
```http
HTTP/1.1 429 Too Many Requests
Retry-After: 58
X-RateLimit-Limit: 10
X-RateLimit-Remaining: 0
```

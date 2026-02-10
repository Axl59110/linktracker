# System Architecture: Link Tracker

**Date:** 2026-02-09
**Architect:** axel
**Version:** 1.0
**Project Type:** web-app
**Project Level:** 4
**Status:** Draft

---

## Document Overview

This document defines the system architecture for Link Tracker. It provides the technical blueprint for implementation, addressing all functional and non-functional requirements from the PRD.

**Related Documents:**
- Product Requirements Document: docs/prd-link-tracker-2026-02-09.md
- Product Brief: N/A (skipped)
- Technical Plan: ~/.claude/plans/spicy-juggling-toucan.md

---

## Executive Summary

Link Tracker est une application web SEO pour monitorer et analyser les backlinks. L'architecture proposée est un **Monolithe Modulaire avec architecture Event-Driven**, construit avec Laravel 10+ (backend), Vue.js 3 (frontend), et PostgreSQL 15+ (base de données).

**Choix Architecturaux Clés :**
- **Pattern :** Monolithe modulaire avec séparation par domaines métier (Backlinks, Monitoring, Alerts, SEO Metrics, Orders)
- **Communication :** Event-driven interne via Laravel Events pour découplage des modules
- **Scalabilité :** Scalabilité verticale (PostgreSQL partitioning, Redis cache) et horizontale limitée (auto-scaling queue workers)
- **Queue Management :** Laravel Horizon avec Redis pour traitement asynchrone haute performance (100 jobs/min)

**Justification du Monolithe :**
Pour un usage personnel (single-user) avec couplage métier fort entre modules, un monolithe modulaire offre :
- ✅ Déploiement simplifié (1 conteneur vs architecture distribuée)
- ✅ Transactions ACID simples (même base de données)
- ✅ Latence zéro entre modules
- ✅ Complexité opérationnelle réduite (pas d'orchestration microservices)

**Trade-off accepté :** Scalabilité distribuée limitée, mais suffisante pour les volumes projetés (500+ backlinks, 100 checks/minute).

**Architecture Technique :**
```
┌─────────────────────────────────────────────────────────────┐
│                      CLIENT (Browser)                        │
│                  Vue.js 3 + Tailwind CSS 4                   │
└──────────────────────┬──────────────────────────────────────┘
                       │ HTTPS (API REST)
┌──────────────────────┴──────────────────────────────────────┐
│                  NGINX (Reverse Proxy + SSL)                 │
└──────────────────────┬──────────────────────────────────────┘
                       │
┌──────────────────────┴──────────────────────────────────────┐
│                    LARAVEL APPLICATION                       │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐        │
│  │  Projects   │  │  Backlinks  │  │  Monitoring │        │
│  │   Module    │  │   Module    │  │    Module   │        │
│  └─────────────┘  └─────────────┘  └─────────────┘        │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐        │
│  │   Alerts    │  │ SEO Metrics │  │   Orders    │        │
│  │   Module    │  │   Module    │  │   Module    │        │
│  └─────────────┘  └─────────────┘  └─────────────┘        │
└──────────────┬────────────────┬────────────────────────────┘
               │                │
       ┌───────┴────────┐  ┌────┴─────────┐
       │  PostgreSQL    │  │    Redis     │
       │   (Primary)    │  │ (Cache+Queue)│
       └────────────────┘  └──────────────┘

External APIs:
- Moz API (Domain Authority)
- Ahrefs API (Domain Rating)
- NextLevel.link API (Backlink Orders)
- SEOClerks API (Backlink Orders)
```

**Capacité et Performance :**
- **Backlinks :** Jusqu'à 5,000 backlinks monitorés simultanément
- **Checks :** 100 vérifications/minute (6,000/heure, 144,000/jour)
- **Latence API :** <500ms (95th percentile) grâce au cache Redis
- **Disponibilité :** 99.5% (objectif) avec PostgreSQL replication + Redis Sentinel

---

## Architectural Drivers

Ces 6 exigences non-fonctionnelles critiques influencent fortement les décisions architecturales :

### 1. NFR-002 : Performance du Système de Queue (100 jobs/minute)

**Impact Architectural :**
- ❌ **Bloque :** Database queue (latence disk I/O insuffisante)
- ✅ **Impose :** Redis in-memory queue + Laravel Horizon
- ✅ **Impose :** Auto-scaling workers (3-10 workers dynamiques)

**Décision :** Redis Queue avec Horizon pour atteindre 100 jobs/min.

### 2. NFR-004 : Protection SSRF (Server-Side Request Forgery)

**Impact Architectural :**
- ✅ **Impose :** Service UrlValidator dédié avant toute requête HTTP externe
- ✅ **Impose :** Blacklist IPs privées (10.0.0.0/8, 172.16.0.0/12, 192.168.0.0/16, 127.0.0.1)
- ✅ **Impose :** Whitelist protocoles (http/https uniquement)

**Décision :** Validation systématique via UrlValidator dans BacklinkChecker.

### 3. NFR-006 : Fiabilité du Queue System

**Impact Architectural :**
- ✅ **Impose :** Redis Sentinel pour haute disponibilité (failover automatique)
- ✅ **Impose :** Job retry strategy (3 tentatives avec backoff exponentiel)
- ✅ **Impose :** Dead letter queue pour jobs échoués définitivement

**Décision :** Redis Sentinel + Horizon retry policy 3x.

### 4. NFR-008 : Résilience APIs Externes (Moz, Ahrefs, etc.)

**Impact Architectural :**
- ✅ **Impose :** Circuit Breaker pattern (ouverture après 5 échecs consécutifs)
- ✅ **Impose :** Cache long terme (TTL 24h pour métriques SEO)
- ✅ **Impose :** Fallback provider (CustomProvider si APIs principales down)

**Décision :** CircuitBreaker service + Strategy pattern pour providers SEO.

### 5. NFR-001 : Performance API (<500ms au 95th percentile)

**Impact Architectural :**
- ✅ **Impose :** Cache Redis pour dashboard stats (TTL 5 minutes)
- ✅ **Impose :** Index PostgreSQL sur colonnes critiques (backlinks.project_id, backlinks.status)
- ✅ **Impose :** Query optimization (eager loading Eloquent, pagination)

**Décision :** Stratégie de cache agressive + optimisation requêtes SQL.

### 6. NFR-009 : Haute Disponibilité (99.5% uptime)

**Impact Architectural :**
- ✅ **Impose :** PostgreSQL replication (master-replica avec failover)
- ✅ **Impose :** Redis Sentinel (3 nodes minimum)
- ✅ **Impose :** Health checks /api/v1/health (monitoring actif)
- ✅ **Impose :** Blue-Green deployment (zero-downtime)

**Décision :** Infrastructure HA complète avec monitoring proactif.

---

## System Overview

### High-Level Architecture

**Pattern Architectural :** Monolithe Modulaire avec Event-Driven Architecture

**Modules Principaux :**

```
app/
├── Modules/
│   ├── Projects/           # Gestion des projets (sites web monitorés)
│   │   ├── Controllers/
│   │   ├── Models/
│   │   ├── Services/
│   │   └── Resources/
│   │
│   ├── Backlinks/          # CRUD backlinks + import/export
│   │   ├── Controllers/
│   │   ├── Models/
│   │   ├── Services/
│   │   └── Resources/
│   │
│   ├── Monitoring/         # Vérification automatique backlinks
│   │   ├── Services/
│   │   │   ├── BacklinkChecker.php      (HTTP check + parsing)
│   │   │   ├── BacklinkAnalyzer.php     (détection changements)
│   │   │   └── UrlValidator.php         (SSRF protection)
│   │   ├── Jobs/
│   │   │   ├── CheckBacklink.php
│   │   │   └── MonitorProjectBacklinks.php
│   │   └── Models/BacklinkCheck.php
│   │
│   ├── Alerts/             # Notifications changements
│   │   ├── Controllers/
│   │   ├── Services/AlertService.php
│   │   ├── Models/Alert.php
│   │   └── Notifications/
│   │
│   ├── SeoMetrics/         # Métriques SEO (DA, DR, etc.)
│   │   ├── Controllers/
│   │   ├── Services/
│   │   │   ├── SeoMetricsInterface.php
│   │   │   ├── MozProvider.php
│   │   │   ├── AhrefsProvider.php
│   │   │   └── CustomProvider.php      (fallback scraping)
│   │   ├── Jobs/FetchSeoMetrics.php
│   │   └── Models/SeoMetric.php
│   │
│   ├── Orders/             # Commande de backlinks via APIs
│   │   ├── Controllers/
│   │   ├── Services/
│   │   │   ├── PlatformInterface.php
│   │   │   ├── NextLevelProvider.php
│   │   │   └── SeoClercProvider.php
│   │   ├── Jobs/PlaceBacklinkOrder.php
│   │   └── Models/BacklinkOrder.php
│   │
│   └── Dashboard/          # Stats et visualisations
│       ├── Controllers/DashboardController.php
│       ├── Services/DashboardService.php
│       └── Resources/
```

**Communication Inter-Modules :**
Les modules communiquent via **Laravel Events** pour découplage :

```php
// Exemple : Module Monitoring dispatch event → Module Alerts écoute
// app/Modules/Monitoring/Events/BacklinkStatusChanged.php
Event::dispatch(new BacklinkStatusChanged($backlink, $oldStatus, $newStatus));

// app/Modules/Alerts/Listeners/CreateStatusChangeAlert.php
class CreateStatusChangeAlert {
    public function handle(BacklinkStatusChanged $event) {
        Alert::create([
            'backlink_id' => $event->backlink->id,
            'type' => 'status_change',
            'old_value' => $event->oldStatus,
            'new_value' => $event->newStatus,
        ]);
    }
}
```

**Flux de Données Principal :**

```
1. USER → Vue.js → POST /api/v1/backlinks (Ajouter backlink)
                ↓
2. BacklinkController → Validation + UrlValidator (SSRF check)
                ↓
3. Backlink::create() → Saved to PostgreSQL
                ↓
4. Event: BacklinkCreated → Listener: ScheduleFirstCheck
                ↓
5. Job dispatched → CheckBacklink::dispatch($backlink) → Redis Queue
                ↓
6. Horizon Worker → BacklinkChecker::checkBacklink()
                ↓
7. HTTP GET source_url → Parse HTML → Detect link presence
                ↓
8. BacklinkCheck::create() → Store result in PostgreSQL
                ↓
9. BacklinkAnalyzer::analyzeChanges() → Compare with previous check
                ↓
10. IF changed → Event: BacklinkStatusChanged → Alert::create()
                ↓
11. User fetches → GET /api/v1/alerts?is_read=false
```

### Architecture Diagram

```mermaid
graph TB
    subgraph Client
        A[Vue.js SPA]
    end

    subgraph "Load Balancer"
        B[Nginx]
    end

    subgraph "Application Layer"
        C[Laravel App]
        C1[Projects Module]
        C2[Backlinks Module]
        C3[Monitoring Module]
        C4[Alerts Module]
        C5[SEO Metrics Module]
        C6[Orders Module]
        C7[Dashboard Module]
    end

    subgraph "Queue Layer"
        D[Laravel Horizon]
        D1[Queue: high]
        D2[Queue: default]
        D3[Queue: low]
    end

    subgraph "Data Layer"
        E[PostgreSQL Primary]
        E1[PostgreSQL Replica]
        F[Redis Cache]
        G[Redis Queue]
    end

    subgraph "External Services"
        H[Moz API]
        I[Ahrefs API]
        J[NextLevel API]
        K[SEOClerks API]
    end

    A -->|HTTPS REST API| B
    B --> C
    C --> C1 & C2 & C3 & C4 & C5 & C6 & C7
    C --> E
    C --> F
    C -->|Dispatch Jobs| G
    D -->|Poll Jobs| G
    D -->|Execute| C3 & C5 & C6
    E -->|Replication| E1
    C5 -->|Circuit Breaker| H & I
    C6 --> J & K
```

### Architectural Pattern

**Pattern :** Monolithe Modulaire avec Event-Driven Architecture

**Rationale :**

**Pourquoi Monolithe (vs Microservices) :**
- ✅ **Couplage métier fort :** Backlinks ↔ BacklinkChecks ↔ Alerts sont fortement couplés (1 backlink = N checks = N alerts). Séparer en microservices créerait une complexité de transactions distribuées.
- ✅ **Usage single-user :** Pas besoin de scalabilité multi-tenant (pas de séparation par tenant).
- ✅ **Déploiement simplifié :** 1 application = 1 conteneur Docker vs 6+ microservices + orchestration.
- ✅ **Latence nulle :** Appels internes (function calls) vs appels HTTP inter-services.

**Pourquoi Modulaire (vs Monolithe chaotique) :**
- ✅ **Séparation des responsabilités :** Chaque module a un domaine métier clair (Projects, Monitoring, Alerts, etc.).
- ✅ **Testabilité :** Modules peuvent être testés indépendamment via interfaces.
- ✅ **Évolutivité future :** Si besoin de microservices, modules peuvent être extraits facilement.

**Pourquoi Event-Driven :**
- ✅ **Découplage :** Module Monitoring ne connaît pas Module Alerts directement (communication via events).
- ✅ **Extensibilité :** Ajouter un nouveau module (ex: Reporting) qui écoute BacklinkStatusChanged sans modifier Monitoring.
- ✅ **Async processing :** Events peuvent déclencher des jobs asynchrones (ex: SendEmailNotification).

**Trade-offs :**
- ❌ **Scalabilité horizontale limitée :** Impossible de scaler uniquement le module Monitoring (tout l'app scale ensemble). **Accepté** car volumes prévus supportables par scalabilité verticale.
- ❌ **Single point of failure :** Si l'app crash, tous les modules sont down. **Mitigé** par HA PostgreSQL/Redis + monitoring proactif.

---

## Technology Stack

### Frontend

**Framework Principal :** Vue.js 3.4+ (Composition API)

**Justification :**
- ✅ **Réactivité optimale :** Composition API pour state management propre (vs Options API)
- ✅ **Écosystème mature :** Vue Router 4, Pinia (store), VueUse (composables)
- ✅ **Performance :** Virtual DOM optimisé, tree-shaking automatique
- ✅ **Developer Experience :** Hot Module Replacement (HMR), Vue DevTools

**Styling :** Tailwind CSS 4.0+

**Justification :**
- ✅ **Utility-first :** Développement rapide sans écrire CSS custom
- ✅ **Purge automatique :** CSS final optimisé (seulement classes utilisées)
- ✅ **Responsive design :** Breakpoints intégrés (sm, md, lg, xl, 2xl)
- ✅ **Dark mode support :** (future enhancement possible)

**Visualisation Données :** ApexCharts 3.x

**Justification :**
- ✅ **Charts interactifs :** Line charts pour évolution backlinks, pie charts pour répartition statuts
- ✅ **Responsive :** Auto-resize selon viewport
- ✅ **Animations fluides :** Transitions CSS optimisées
- ✅ **Export :** PNG/SVG download pour rapports

**HTTP Client :** Axios 1.6+

**Justification :**
- ✅ **Interceptors :** Gestion centralisée auth tokens (Sanctum CSRF)
- ✅ **Error handling :** Retry logic, timeout configuration
- ✅ **Concurrent requests :** Promise.all() pour requêtes parallèles

**Stack Frontend Complet :**
```json
{
  "dependencies": {
    "vue": "^3.4.0",
    "vue-router": "^4.2.0",
    "pinia": "^2.1.0",
    "axios": "^1.6.0",
    "apexcharts": "^3.45.0",
    "vue3-apexcharts": "^1.5.0"
  },
  "devDependencies": {
    "vite": "^5.0.0",
    "tailwindcss": "^4.0.0",
    "@vitejs/plugin-vue": "^5.0.0",
    "autoprefixer": "^10.4.0",
    "postcss": "^8.4.0"
  }
}
```

### Backend

**Framework :** Laravel 10.48+ (LTS)

**Justification :**
- ✅ **Écosystème complet :** Eloquent ORM, Queue system, Scheduler, Events
- ✅ **Performance :** Opcache PHP 8.2+, query optimization Eloquent
- ✅ **Security :** CSRF protection, SQL injection prevention, bcrypt passwords
- ✅ **Developer Experience :** Artisan CLI, Tinker REPL, migrations

**PHP Version :** 8.2+ (minimum)

**Justification :**
- ✅ **Performance :** JIT compiler (15-20% faster vs PHP 8.1)
- ✅ **Syntax moderne :** Readonly classes, enums, null coalescing
- ✅ **Type safety :** Union types, intersection types

**Queue Management :** Laravel Horizon 5.x

**Justification :**
- ✅ **Dashboard intégré :** Monitoring real-time des jobs (throughput, failed jobs, retry)
- ✅ **Auto-scaling :** Workers 3-10 selon charge (config: minProcesses/maxProcesses)
- ✅ **Metrics :** Job latency, wait time, runtime tracking
- ✅ **Failed job management :** Retry automatique 3x avec backoff exponentiel

**HTTP Client :** Guzzle 7.x

**Justification :**
- ✅ **Async requests :** Promise-based pour vérifications parallèles
- ✅ **Middleware :** Retry logic, timeout configuration, circuit breaker integration
- ✅ **Standards :** PSR-7 HTTP messages, PSR-18 HTTP client

**Stack Backend Complet :**
```json
{
  "require": {
    "php": "^8.2",
    "laravel/framework": "^10.48",
    "laravel/sanctum": "^3.3",
    "laravel/horizon": "^5.21",
    "guzzlehttp/guzzle": "^7.8",
    "predis/predis": "^2.2"
  },
  "require-dev": {
    "laravel/telescope": "^4.17",
    "phpunit/phpunit": "^10.5",
    "mockery/mockery": "^1.6",
    "fakerphp/faker": "^1.23"
  }
}
```

### Database

**SGBD Principal :** PostgreSQL 15.5+

**Justification :**
- ✅ **JSONB natif :** Stockage performant pour `platform_response` (BacklinkOrders), `settings` (Projects)
- ✅ **Partitioning avancé :** Table `backlink_checks` partitionnée par mois (2.19M records/an)
- ✅ **Performance agrégations :** Window functions, CTEs pour dashboard stats complexes
- ✅ **PITR (Point-In-Time Recovery) :** WAL archiving pour restore précis
- ✅ **Full-text search :** Support natif pour recherche backlinks par anchor text

**Extensions PostgreSQL :**
```sql
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";   -- UUID generation
CREATE EXTENSION IF NOT EXISTS "pg_trgm";     -- Trigram similarity pour recherche floue
```

**ORM :** Laravel Eloquent

**Justification :**
- ✅ **Abstraction élégante :** Query builder fluent, relationships déclaratives
- ✅ **Eager loading :** Évite N+1 queries (`with(['project', 'checks'])`)
- ✅ **Mutators/Casts :** Automatic JSON casting, date handling

**Indexes Critiques :**
```sql
-- Performance NFR-001 : API <500ms
CREATE INDEX idx_backlinks_project_status ON backlinks(project_id, status);
CREATE INDEX idx_backlinks_status ON backlinks(status) WHERE status = 'active';
CREATE INDEX idx_backlink_checks_checked_at ON backlink_checks(checked_at DESC);
CREATE INDEX idx_alerts_is_read ON alerts(is_read, detected_at DESC);

-- Full-text search
CREATE INDEX idx_backlinks_anchor_text_gin ON backlinks USING gin(to_tsvector('english', anchor_text));
```

**Partitioning Strategy :**
```sql
-- Table partitionnée pour BacklinkChecks (high volume)
CREATE TABLE backlink_checks (
    id BIGSERIAL,
    backlink_id BIGINT NOT NULL,
    checked_at TIMESTAMP NOT NULL,
    -- ... autres colonnes
    PRIMARY KEY (id, checked_at)
) PARTITION BY RANGE (checked_at);

-- Partitions mensuelles (automatisation via cron)
CREATE TABLE backlink_checks_2026_02 PARTITION OF backlink_checks
    FOR VALUES FROM ('2026-02-01') TO ('2026-03-01');
```

### Infrastructure

**Serveur Web :** Nginx 1.25+

**Justification :**
- ✅ **Performance :** Gestion 10,000+ connexions simultanées (vs Apache 1,000)
- ✅ **Reverse proxy :** Load balancing vers PHP-FPM workers
- ✅ **SSL Termination :** TLS 1.3, HTTP/2, HSTS headers
- ✅ **Static files :** Serving direct assets Vue.js (CSS/JS) sans passer par PHP

**Configuration Nginx :**
```nginx
upstream php-fpm {
    server 127.0.0.1:9000;
    server 127.0.0.1:9001;
    server 127.0.0.1:9002;
}

server {
    listen 443 ssl http2;
    server_name linktracker.com;

    ssl_certificate /etc/ssl/certs/linktracker.crt;
    ssl_certificate_key /etc/ssl/private/linktracker.key;
    ssl_protocols TLSv1.3;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass php-fpm;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

**Cache & Queue :** Redis 7.2+

**Justification :**
- ✅ **In-memory performance :** Latency <1ms (vs PostgreSQL ~5-10ms)
- ✅ **Dual usage :** Cache (dashboard stats) + Queue (job storage)
- ✅ **Persistence :** AOF (Append-Only File) pour durabilité queue
- ✅ **HA Support :** Redis Sentinel pour failover automatique

**Configuration Redis :**
```conf
# redis.conf
maxmemory 2gb
maxmemory-policy allkeys-lru   # Éviction LRU pour cache
appendonly yes                  # Persistence pour queue
appendfsync everysec
```

**Containerization :** Docker 24+ avec Docker Compose

**Justification :**
- ✅ **Environnements consistants :** Dev = Staging = Prod (même images)
- ✅ **Isolation services :** Laravel app, PostgreSQL, Redis, Nginx dans conteneurs séparés
- ✅ **Orchestration future :** Migration facile vers Kubernetes si scaling nécessaire

**Docker Compose Stack :**
```yaml
version: '3.8'
services:
  app:
    image: php:8.2-fpm
    volumes:
      - ./:/var/www/html
    depends_on:
      - postgres
      - redis

  nginx:
    image: nginx:1.25
    ports:
      - "443:443"
    volumes:
      - ./nginx.conf:/etc/nginx/nginx.conf
    depends_on:
      - app

  postgres:
    image: postgres:15.5
    environment:
      POSTGRES_DB: linktracker
      POSTGRES_USER: linktracker_user
      POSTGRES_PASSWORD: ${DB_PASSWORD}
    volumes:
      - postgres_data:/var/lib/postgresql/data

  redis:
    image: redis:7.2
    command: redis-server --appendonly yes
    volumes:
      - redis_data:/data

  horizon:
    image: php:8.2-fpm
    command: php artisan horizon
    depends_on:
      - redis
      - postgres
```

### Third-Party Services

#### 1. Moz API (SEO Metrics)

**Usage :** Domain Authority, Page Authority, Spam Score

**Endpoints :**
- `POST https://lsapi.seomoz.com/v2/url_metrics` (batch jusqu'à 50 URLs)

**Rate Limits :** 10 requests / 10 secondes (gratuit), 120 req/min (paid)

**Coût :** $99/mois (plan Medium), 150,000 row queries/mois

**Mitigation Rate Limit :**
- Cache Redis TTL 24h (métriques changent lentement)
- Circuit breaker après 5 échecs consécutifs
- Fallback vers CustomProvider si quota épuisé

#### 2. Ahrefs API (SEO Metrics)

**Usage :** Domain Rating (DR), URL Rating (UR), Referring Domains

**Endpoints :**
- `GET https://api.ahrefs.com/v3/site-explorer/domain-rating?target={domain}`

**Rate Limits :** 500 requests/jour (Lite plan), 2,000/jour (Standard)

**Coût :** $99/mois (Lite), $179/mois (Standard)

**Mitigation Rate Limit :** Identique Moz (cache 24h + circuit breaker)

#### 3. NextLevel.link API (Backlink Orders)

**Usage :** Commander des backlinks high-quality

**Endpoints :**
- `POST /api/v1/orders` (créer commande)
- `GET /api/v1/orders/{id}` (statut commande)
- `POST /api/webhooks/nextlevel` (callback MAJ statut)

**Authentication :** API Key (Header `X-API-Key`)

**Webhook Signature :** HMAC-SHA256 pour validation sécurisée

#### 4. SEOClerks API (Backlink Orders)

**Usage :** Marketplace backlinks budget

**Endpoints :**
- `POST /api/v1/services/{id}/orders` (commander service)
- `GET /api/v1/orders/{id}` (tracking)

**Authentication :** OAuth 2.0

**Rate Limits :** 60 requests/minute

#### 5. SMTP (Email Notifications)

**Provider :** SendGrid / Mailgun / Amazon SES

**Usage :** Alertes backlinks perdus, rapports hebdomadaires

**Configuration Laravel :**
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=${SENDGRID_API_KEY}
MAIL_FROM_ADDRESS=alerts@linktracker.com
```

### Development & Deployment

**Version Control :** Git + GitHub

**CI/CD :** GitHub Actions

**Testing :**
- **Unit Tests :** PHPUnit 10+ (backend), Vitest (frontend)
- **Integration Tests :** Laravel HTTP tests, Database migrations
- **E2E Tests :** Playwright (optionnel pour v2)

**Code Quality :**
- **Linting :** PHP CS Fixer (PSR-12), ESLint (Vue.js)
- **Static Analysis :** PHPStan Level 6+
- **Coverage :** Minimum 70% (enforced via CI)

**Monitoring :**
- **APM :** Laravel Telescope (dev), Sentry (prod) pour error tracking
- **Logs :** Laravel Log (files), Papertrail / Logtail (centralized)
- **Metrics :** Laravel Horizon dashboard, PostgreSQL slow query log

**Deployment Environments :**
- **Development :** Local Docker Compose
- **Staging :** VPS (ex: DigitalOcean Droplet 4GB RAM)
- **Production :** VPS 8GB RAM + Blue-Green deployment

---

## System Components

### Component 1: Backlink Monitoring Engine

**Responsabilité :** Vérifier l'état des backlinks (HTTP status, attributs rel, anchor text)

**Services Principaux :**

```php
// app/Services/BacklinkMonitor/BacklinkChecker.php
class BacklinkChecker
{
    public function __construct(
        private Client $httpClient,           // Guzzle
        private UrlValidator $urlValidator,   // SSRF protection
        private CacheManager $cache
    ) {}

    public function checkBacklink(Backlink $backlink): BacklinkCheckResult
    {
        // 1. Validate URL (SSRF protection - NFR-004)
        $this->urlValidator->validate($backlink->source_url);

        // 2. HTTP request avec timeout 30s
        try {
            $response = $this->httpClient->get($backlink->source_url, [
                'timeout' => 30,
                'allow_redirects' => true,
                'headers' => [
                    'User-Agent' => 'LinkTrackerBot/1.0',
                ],
            ]);

            $httpStatus = $response->getStatusCode();
            $html = $response->getBody()->getContents();

            // 3. Parse HTML pour détecter backlink
            $linkData = $this->extractBacklinkData($html, $backlink->target_url);

            return new BacklinkCheckResult(
                httpStatus: $httpStatus,
                isPresent: $linkData['is_present'],
                relAttributes: $linkData['rel_attributes'],
                anchorText: $linkData['anchor_text'],
                responseTime: $response->getInfo('total_time'),
            );
        } catch (RequestException $e) {
            // Gestion erreurs HTTP (404, 500, timeout)
            return BacklinkCheckResult::failed(
                httpStatus: $e->getCode(),
                error: $e->getMessage()
            );
        }
    }

    private function extractBacklinkData(string $html, string $targetUrl): array
    {
        $dom = new DOMDocument();
        @$dom->loadHTML($html, LIBXML_NOERROR);
        $xpath = new DOMXPath($dom);

        // Chercher tous les liens <a href="...">
        $links = $xpath->query("//a[@href]");

        foreach ($links as $link) {
            $href = $link->getAttribute('href');

            if ($this->urlsMatch($href, $targetUrl)) {
                return [
                    'is_present' => true,
                    'rel_attributes' => $link->getAttribute('rel') ?: 'follow',
                    'anchor_text' => trim($link->textContent),
                ];
            }
        }

        return ['is_present' => false, 'rel_attributes' => null, 'anchor_text' => null];
    }
}
```

**UrlValidator (SSRF Protection) :**

```php
// app/Services/Security/UrlValidator.php
class UrlValidator
{
    private const BLOCKED_IPS = [
        '127.0.0.0/8',      // Localhost
        '10.0.0.0/8',       // Private network
        '172.16.0.0/12',    // Private network
        '192.168.0.0/16',   // Private network
        '169.254.0.0/16',   // Link-local
    ];

    public function validate(string $url): void
    {
        $parsed = parse_url($url);

        // 1. Check protocol (http/https only)
        if (!in_array($parsed['scheme'] ?? '', ['http', 'https'])) {
            throw new InvalidUrlException("Protocol {$parsed['scheme']} not allowed");
        }

        // 2. Resolve hostname to IP
        $host = $parsed['host'] ?? '';
        $ip = gethostbyname($host);

        // 3. Check if IP is in blocked ranges
        foreach (self::BLOCKED_IPS as $range) {
            if ($this->ipInRange($ip, $range)) {
                throw new SsrfException("Access to {$ip} ({$host}) is blocked");
            }
        }
    }
}
```

**Job Asynchrone :**

```php
// app/Jobs/CheckBacklink.php
class CheckBacklink implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public $tries = 3;  // Retry 3x si échec
    public $timeout = 60;  // Timeout global 60s

    public function __construct(public Backlink $backlink) {}

    public function handle(BacklinkChecker $checker, BacklinkAnalyzer $analyzer): void
    {
        // 1. Check backlink
        $result = $checker->checkBacklink($this->backlink);

        // 2. Store check result
        $check = BacklinkCheck::create([
            'backlink_id' => $this->backlink->id,
            'checked_at' => now(),
            'http_status' => $result->httpStatus,
            'is_present' => $result->isPresent,
            'rel_attributes' => $result->relAttributes,
            'anchor_text' => $result->anchorText,
            'response_time' => $result->responseTime,
        ]);

        // 3. Analyze changes (vs last check)
        $analyzer->analyzeChanges($this->backlink, $check);
    }

    public function failed(Throwable $exception): void
    {
        // Log failed check après 3 retries
        Log::error("Failed to check backlink {$this->backlink->id}", [
            'exception' => $exception->getMessage(),
        ]);
    }
}
```

### Component 2: Alert System

**Responsabilité :** Détecter changements backlinks et notifier utilisateur

**Service Principal :**

```php
// app/Services/Alerts/AlertService.php
class AlertService
{
    public function createAlert(
        Backlink $backlink,
        string $type,
        mixed $oldValue,
        mixed $newValue
    ): Alert {
        $alert = Alert::create([
            'backlink_id' => $backlink->id,
            'type' => $type,  // 'lost', 'nofollow', 'status_change', 'anchor_change'
            'detected_at' => now(),
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'is_read' => false,
        ]);

        // Dispatch notification job
        SendAlertNotification::dispatch($alert);

        return $alert;
    }
}

// app/Services/BacklinkMonitor/BacklinkAnalyzer.php
class BacklinkAnalyzer
{
    public function __construct(private AlertService $alertService) {}

    public function analyzeChanges(Backlink $backlink, BacklinkCheck $currentCheck): void
    {
        $lastCheck = BacklinkCheck::where('backlink_id', $backlink->id)
            ->where('id', '<', $currentCheck->id)
            ->orderBy('checked_at', 'desc')
            ->first();

        if (!$lastCheck) {
            return;  // First check, rien à comparer
        }

        // Détection 1: Backlink lost (présent → absent)
        if ($lastCheck->is_present && !$currentCheck->is_present) {
            $this->alertService->createAlert(
                $backlink,
                'lost',
                'present',
                'absent'
            );
            $backlink->update(['status' => 'lost']);
        }

        // Détection 2: Passage dofollow → nofollow
        if ($lastCheck->rel_attributes === 'follow' && $currentCheck->rel_attributes === 'nofollow') {
            $this->alertService->createAlert(
                $backlink,
                'nofollow',
                'dofollow',
                'nofollow'
            );
        }

        // Détection 3: Changement anchor text
        if ($lastCheck->anchor_text !== $currentCheck->anchor_text) {
            $this->alertService->createAlert(
                $backlink,
                'anchor_change',
                $lastCheck->anchor_text,
                $currentCheck->anchor_text
            );
        }

        // Détection 4: HTTP status change (200 → 404/500)
        if ($lastCheck->http_status === 200 && $currentCheck->http_status !== 200) {
            $this->alertService->createAlert(
                $backlink,
                'status_change',
                $lastCheck->http_status,
                $currentCheck->http_status
            );
        }
    }
}
```

### Component 3: SEO Metrics Service

**Responsabilité :** Récupérer métriques SEO (DA, DR) via APIs tierces avec circuit breaker

**Architecture Strategy Pattern :**

```php
// app/Services/SeoMetrics/SeoMetricsInterface.php
interface SeoMetricsInterface
{
    public function getMetrics(string $domain): SeoMetricsDTO;
}

// app/Services/SeoMetrics/MozProvider.php
class MozProvider implements SeoMetricsInterface
{
    public function __construct(
        private Client $httpClient,
        private CircuitBreaker $circuitBreaker,
        private CacheManager $cache
    ) {}

    public function getMetrics(string $domain): SeoMetricsDTO
    {
        // Check cache first (TTL 24h)
        $cacheKey = "seo_metrics:moz:{$domain}";
        if ($cached = $this->cache->get($cacheKey)) {
            return $cached;
        }

        // Circuit breaker protection (NFR-008)
        return $this->circuitBreaker->execute("moz_api", function() use ($domain, $cacheKey) {
            $response = $this->httpClient->post('https://lsapi.seomoz.com/v2/url_metrics', [
                'json' => [
                    'targets' => [$domain],
                ],
                'auth' => [env('MOZ_ACCESS_ID'), env('MOZ_SECRET_KEY')],
                'timeout' => 10,
            ]);

            $data = json_decode($response->getBody(), true);
            $metrics = new SeoMetricsDTO(
                domainAuthority: $data['results'][0]['domain_authority'] ?? null,
                spamScore: $data['results'][0]['spam_score'] ?? null,
                provider: 'moz'
            );

            // Cache 24h
            $this->cache->put($cacheKey, $metrics, now()->addDay());

            return $metrics;
        });
    }
}

// app/Services/Security/CircuitBreaker.php
class CircuitBreaker
{
    private const FAILURE_THRESHOLD = 5;
    private const TIMEOUT_SECONDS = 300;  // 5 minutes

    public function execute(string $key, callable $callback): mixed
    {
        $state = $this->getState($key);

        // Circuit OPEN → Reject immediately
        if ($state === 'open') {
            if ($this->shouldAttemptReset($key)) {
                $this->setState($key, 'half_open');
            } else {
                throw new CircuitBreakerOpenException("Circuit breaker for {$key} is open");
            }
        }

        try {
            $result = $callback();
            $this->recordSuccess($key);
            return $result;
        } catch (Throwable $e) {
            $this->recordFailure($key);

            if ($this->getFailureCount($key) >= self::FAILURE_THRESHOLD) {
                $this->setState($key, 'open');
            }

            throw $e;
        }
    }
}

// app/Services/SeoMetrics/CustomProvider.php (Fallback)
class CustomProvider implements SeoMetricsInterface
{
    // Scraping léger si APIs down ou rate limited
    public function getMetrics(string $domain): SeoMetricsDTO
    {
        // Exemple: scraper Moz Open Site Explorer public page
        // ou utiliser des approximations basées sur backlinks count
        return new SeoMetricsDTO(
            domainAuthority: null,  // Non disponible
            spamScore: null,
            provider: 'custom',
            note: 'Limited metrics (fallback mode)'
        );
    }
}
```

### Component 4: Backlink Orders Service

**Responsabilité :** Commander des backlinks via APIs plateformes (NextLevel, SEOClerks)

```php
// app/Services/BacklinkPlatforms/PlatformInterface.php
interface PlatformInterface
{
    public function placeOrder(BacklinkOrderDTO $order): PlatformOrderResponse;
    public function getOrderStatus(string $platformOrderId): OrderStatus;
}

// app/Services/BacklinkPlatforms/NextLevelProvider.php
class NextLevelProvider implements PlatformInterface
{
    public function placeOrder(BacklinkOrderDTO $order): PlatformOrderResponse
    {
        $response = $this->httpClient->post('https://nextlevel.link/api/v1/orders', [
            'json' => [
                'target_url' => $order->targetUrl,
                'anchor_text' => $order->anchorText,
                'domain_preference' => $order->domainPreference,
            ],
            'headers' => [
                'X-API-Key' => env('NEXTLEVEL_API_KEY'),
            ],
            'timeout' => 30,
        ]);

        $data = json_decode($response->getBody(), true);

        return new PlatformOrderResponse(
            platformOrderId: $data['order_id'],
            status: $data['status'],  // 'pending', 'approved', 'live'
            estimatedLiveDate: Carbon::parse($data['estimated_live_date']),
            price: $data['price'],
        );
    }

    public function handleWebhook(array $payload, string $signature): void
    {
        // Validate HMAC signature (security)
        if (!$this->validateSignature($payload, $signature)) {
            throw new InvalidWebhookSignatureException();
        }

        $order = BacklinkOrder::where('platform_order_id', $payload['order_id'])->first();
        $order->update([
            'status' => $payload['status'],
            'live_at' => $payload['live_at'] ?? null,
            'platform_response' => $payload,  // JSONB column
        ]);

        // Créer alert si statut = 'live'
        if ($payload['status'] === 'live') {
            Event::dispatch(new BacklinkOrderLive($order));
        }
    }
}
```

### Component 5: Dashboard Service

**Responsabilité :** Agréger stats pour visualisation (cache agressif)

```php
// app/Services/Dashboard/DashboardService.php
class DashboardService
{
    public function __construct(private CacheManager $cache) {}

    public function getStats(int $projectId): DashboardStatsDTO
    {
        $cacheKey = "dashboard:stats:{$projectId}";

        // Cache 5min (NFR-001: Performance <500ms)
        return $this->cache->remember($cacheKey, now()->addMinutes(5), function() use ($projectId) {
            return new DashboardStatsDTO(
                totalBacklinks: Backlink::where('project_id', $projectId)->count(),
                activeBacklinks: Backlink::where('project_id', $projectId)->where('status', 'active')->count(),
                lostBacklinks: Backlink::where('project_id', $projectId)->where('status', 'lost')->count(),
                unreadAlerts: Alert::whereHas('backlink', fn($q) => $q->where('project_id', $projectId))
                    ->where('is_read', false)
                    ->count(),
                avgDomainAuthority: $this->getAvgDomainAuthority($projectId),
                recentChecks: $this->getRecentChecks($projectId, 10),
            );
        });
    }

    public function getBacklinkEvolution(int $projectId, string $period = '30d'): array
    {
        // Query optimized with partitioning
        $checks = BacklinkCheck::query()
            ->whereHas('backlink', fn($q) => $q->where('project_id', $projectId))
            ->where('checked_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(checked_at) as date, COUNT(*) as checks_count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return $checks->map(fn($item) => [
            'date' => $item->date,
            'count' => $item->checks_count,
        ])->toArray();
    }
}
```

---

## Data Architecture

### Data Model

**Modèle Relationnel Complet :**

```mermaid
erDiagram
    USERS ||--o{ PROJECTS : owns
    PROJECTS ||--o{ BACKLINKS : contains
    PROJECTS ||--o{ BACKLINK_ORDERS : places
    BACKLINKS ||--o{ BACKLINK_CHECKS : has
    BACKLINKS ||--o{ ALERTS : triggers

    USERS {
        bigint id PK
        string email UK
        string name
        string password
        timestamp created_at
    }

    PROJECTS {
        bigint id PK
        bigint user_id FK
        string name
        string domain
        jsonb settings
        timestamp created_at
    }

    BACKLINKS {
        bigint id PK
        bigint project_id FK
        string source_url
        string target_url
        string anchor_text
        enum status "active,lost,changed"
        int http_status
        string rel_attributes
        boolean is_dofollow
        timestamp first_seen_at
        timestamp last_checked_at
        timestamp created_at
    }

    BACKLINK_CHECKS {
        bigint id PK
        bigint backlink_id FK
        timestamp checked_at
        int http_status
        boolean is_present
        string rel_attributes
        string anchor_text
        float response_time
        timestamp created_at
    }

    ALERTS {
        bigint id PK
        bigint backlink_id FK
        enum type "lost,nofollow,status_change,anchor_change"
        timestamp detected_at
        string old_value
        string new_value
        boolean is_read
        timestamp created_at
    }

    SEO_METRICS {
        bigint id PK
        string domain UK
        string provider
        int domain_authority
        int trust_flow
        int citation_flow
        int spam_score
        int referring_domains
        timestamp last_updated_at
        timestamp created_at
    }

    BACKLINK_ORDERS {
        bigint id PK
        bigint project_id FK
        string platform
        string platform_order_id
        string target_url
        string anchor_text
        string domain_requested
        decimal price
        enum status "pending,approved,live,rejected"
        timestamp placed_at
        timestamp live_at
        jsonb platform_response
        timestamp created_at
    }
```

### Database Design

**Table Definitions (PostgreSQL) :**

```sql
-- Users table
CREATE TABLE users (
    id BIGSERIAL PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW()
);

-- Projects table
CREATE TABLE projects (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    domain VARCHAR(255) NOT NULL,
    settings JSONB DEFAULT '{}',  -- Ex: {"check_frequency": "4h", "alert_email": true}
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW()
);

-- Backlinks table
CREATE TABLE backlinks (
    id BIGSERIAL PRIMARY KEY,
    project_id BIGINT NOT NULL REFERENCES projects(id) ON DELETE CASCADE,
    source_url TEXT NOT NULL,
    target_url TEXT NOT NULL,
    anchor_text TEXT,
    status VARCHAR(50) NOT NULL DEFAULT 'active',  -- active, lost, changed
    http_status INT,
    rel_attributes VARCHAR(100),  -- follow, nofollow
    is_dofollow BOOLEAN DEFAULT TRUE,
    first_seen_at TIMESTAMP NOT NULL DEFAULT NOW(),
    last_checked_at TIMESTAMP,
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_backlinks_project_status ON backlinks(project_id, status);
CREATE INDEX idx_backlinks_status ON backlinks(status) WHERE status = 'active';
CREATE INDEX idx_backlinks_anchor_text_gin ON backlinks USING gin(to_tsvector('english', anchor_text));

-- Backlink Checks table (PARTITIONED)
CREATE TABLE backlink_checks (
    id BIGSERIAL,
    backlink_id BIGINT NOT NULL REFERENCES backlinks(id) ON DELETE CASCADE,
    checked_at TIMESTAMP NOT NULL DEFAULT NOW(),
    http_status INT NOT NULL,
    is_present BOOLEAN NOT NULL,
    rel_attributes VARCHAR(100),
    anchor_text TEXT,
    response_time FLOAT,  -- seconds
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    PRIMARY KEY (id, checked_at)
) PARTITION BY RANGE (checked_at);

-- Create partitions (automated via cron)
CREATE TABLE backlink_checks_2026_02 PARTITION OF backlink_checks
    FOR VALUES FROM ('2026-02-01 00:00:00') TO ('2026-03-01 00:00:00');

CREATE INDEX idx_backlink_checks_backlink_id ON backlink_checks(backlink_id);
CREATE INDEX idx_backlink_checks_checked_at ON backlink_checks(checked_at DESC);

-- Alerts table
CREATE TABLE alerts (
    id BIGSERIAL PRIMARY KEY,
    backlink_id BIGINT NOT NULL REFERENCES backlinks(id) ON DELETE CASCADE,
    type VARCHAR(50) NOT NULL,  -- lost, nofollow, status_change, anchor_change
    detected_at TIMESTAMP NOT NULL DEFAULT NOW(),
    old_value TEXT,
    new_value TEXT,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_alerts_backlink_id ON alerts(backlink_id);
CREATE INDEX idx_alerts_is_read ON alerts(is_read, detected_at DESC);

-- SEO Metrics table
CREATE TABLE seo_metrics (
    id BIGSERIAL PRIMARY KEY,
    domain VARCHAR(255) UNIQUE NOT NULL,
    provider VARCHAR(50) NOT NULL,  -- moz, ahrefs, custom
    domain_authority INT,
    trust_flow INT,
    citation_flow INT,
    spam_score INT,
    referring_domains INT,
    last_updated_at TIMESTAMP NOT NULL DEFAULT NOW(),
    created_at TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_seo_metrics_domain ON seo_metrics(domain);

-- Backlink Orders table
CREATE TABLE backlink_orders (
    id BIGSERIAL PRIMARY KEY,
    project_id BIGINT NOT NULL REFERENCES projects(id) ON DELETE CASCADE,
    platform VARCHAR(50) NOT NULL,  -- nextlevel, seoclerks
    platform_order_id VARCHAR(255),
    target_url TEXT NOT NULL,
    anchor_text TEXT,
    domain_requested VARCHAR(255),
    price DECIMAL(10, 2),
    status VARCHAR(50) NOT NULL DEFAULT 'pending',  -- pending, approved, live, rejected
    placed_at TIMESTAMP NOT NULL DEFAULT NOW(),
    live_at TIMESTAMP,
    platform_response JSONB,  -- Store full API response
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_backlink_orders_project_id ON backlink_orders(project_id);
CREATE INDEX idx_backlink_orders_status ON backlink_orders(status);
```

### Data Flow

**Flux 1: Ajout Manuel Backlink**
```
User (Vue.js) → POST /api/v1/backlinks
    ↓
BacklinkController::store()
    ↓ Validation (SSRF check via UrlValidator)
    ↓
Backlink::create() → PostgreSQL INSERT
    ↓ Event: BacklinkCreated
    ↓
Listener: ScheduleFirstCheck
    ↓
CheckBacklink::dispatch() → Redis Queue (high priority)
    ↓
Horizon Worker → BacklinkChecker::checkBacklink()
    ↓
BacklinkCheck::create() → PostgreSQL INSERT (partition courante)
    ↓
BacklinkAnalyzer::analyzeChanges() (aucune alerte si first check)
```

**Flux 2: Monitoring Automatique (Scheduler)**
```
Laravel Scheduler (cron every 4h)
    ↓
MonitorProjectBacklinks::dispatch($project)
    ↓
ForEach backlink in project:
    CheckBacklink::dispatch($backlink) → Redis Queue
    ↓
Horizon Workers (3-10 workers auto-scaled)
    ↓
BacklinkChecker + BacklinkAnalyzer (parallel processing)
    ↓
IF changement détecté → Alert::create() + SendAlertNotification
```

**Flux 3: Dashboard Stats (Cached)**
```
User → GET /api/v1/dashboard/stats?project_id=1
    ↓
DashboardController::stats()
    ↓
Cache::remember('dashboard:stats:1', 5min, fn() =>
    DashboardService::getStats(1)
)
    ↓ (Cache HIT → Return immédiatement <50ms)
    ↓ (Cache MISS → Query PostgreSQL + Cache result)
    ↓
Return DashboardStatsDTO (JSON)
```

---

## API Design

### API Architecture

**Style :** RESTful API avec JSON

**Versioning :** URL-based versioning (`/api/v1/...`)

**Authentication :** Laravel Sanctum (session-based pour SPA)

**Rate Limiting :**
- Authenticated: 60 requests/minute
- Unauthenticated: 10 requests/minute

**Response Format Standard :**
```json
{
  "data": { /* resource ou collection */ },
  "meta": {
    "current_page": 1,
    "total": 150,
    "per_page": 50
  },
  "links": {
    "first": "/api/v1/backlinks?page=1",
    "last": "/api/v1/backlinks?page=3",
    "next": "/api/v1/backlinks?page=2"
  }
}
```

**Error Response Format :**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "source_url": ["The source url format is invalid."],
    "target_url": ["The target url is required."]
  }
}
```

### Endpoints

#### Authentication

```
POST   /api/v1/auth/login
Body: { email, password }
Response: { token, user }

POST   /api/v1/auth/logout
Response: { message: "Logged out successfully" }

GET    /api/v1/auth/user
Response: { id, email, name, created_at }
```

#### Projects

```
GET    /api/v1/projects
Query: ?page=1&per_page=50
Response: Collection<Project>

POST   /api/v1/projects
Body: { name, domain, settings? }
Response: Project

GET    /api/v1/projects/{id}
Response: Project (with backlinks_count, active_backlinks_count)

PATCH  /api/v1/projects/{id}
Body: { name?, domain?, settings? }
Response: Project

DELETE /api/v1/projects/{id}
Response: 204 No Content

GET    /api/v1/projects/{id}/backlinks
Query: ?status=active&page=1
Response: Collection<Backlink>
```

#### Backlinks

```
GET    /api/v1/backlinks
Query: ?project_id=1&status=active&page=1&per_page=50&sort=-last_checked_at
Response: Collection<Backlink>

POST   /api/v1/backlinks
Body: {
  project_id,
  source_url,
  target_url,
  anchor_text?
}
Response: Backlink

GET    /api/v1/backlinks/{id}
Response: Backlink (with latest_check, alerts_count)

PATCH  /api/v1/backlinks/{id}
Body: { source_url?, target_url?, anchor_text? }
Response: Backlink

DELETE /api/v1/backlinks/{id}
Response: 204 No Content

POST   /api/v1/backlinks/{id}/check
Trigger manual check (dispatch CheckBacklink job)
Response: { message: "Check scheduled", job_id }

POST   /api/v1/backlinks/import
Body: multipart/form-data (CSV file)
Columns: source_url, target_url, anchor_text
Response: { imported: 45, failed: 2, errors: [...] }

GET    /api/v1/backlinks/export
Query: ?project_id=1&status=active&format=csv
Response: CSV file download
```

#### Backlink Checks (History)

```
GET    /api/v1/backlinks/{backlink_id}/checks
Query: ?page=1&per_page=20&from_date=2026-01-01
Response: Collection<BacklinkCheck>

GET    /api/v1/checks/{id}
Response: BacklinkCheck
```

#### Alerts

```
GET    /api/v1/alerts
Query: ?is_read=false&type=lost&page=1&per_page=50
Response: Collection<Alert>

GET    /api/v1/alerts/{id}
Response: Alert (with backlink details)

PATCH  /api/v1/alerts/{id}
Body: { is_read: true }
Response: Alert

POST   /api/v1/alerts/mark-all-read
Body: { project_id? }
Response: { updated_count: 12 }
```

#### SEO Metrics

```
GET    /api/v1/seo-metrics
Query: ?domain=example.com
Response: SeoMetric (cached 24h)

POST   /api/v1/seo-metrics/refresh
Body: { domain }
Force refresh metrics (bypass cache)
Response: SeoMetric

GET    /api/v1/backlinks/{backlink_id}/seo-metrics
Get metrics for backlink's source domain
Response: SeoMetric
```

#### Backlink Orders

```
GET    /api/v1/orders
Query: ?project_id=1&status=live&page=1
Response: Collection<BacklinkOrder>

POST   /api/v1/orders
Body: {
  project_id,
  platform,           // "nextlevel" | "seoclerks"
  target_url,
  anchor_text,
  domain_requested?,
  budget?
}
Response: BacklinkOrder

GET    /api/v1/orders/{id}
Response: BacklinkOrder (with platform_response details)

PATCH  /api/v1/orders/{id}/cancel
Response: BacklinkOrder (status updated to "cancelled")
```

#### Dashboard

```
GET    /api/v1/dashboard/stats
Query: ?project_id=1
Response: {
  total_backlinks: 150,
  active_backlinks: 142,
  lost_backlinks: 8,
  unread_alerts: 5,
  avg_domain_authority: 35.4,
  recent_checks: [...]
}
Cache: 5 minutes

GET    /api/v1/dashboard/evolution
Query: ?project_id=1&period=30d
Response: [
  { date: "2026-02-01", active: 140, lost: 7, checks: 420 },
  { date: "2026-02-02", active: 141, lost: 7, checks: 422 },
  ...
]
Cache: 1 hour
```

#### Webhooks (External Platforms)

```
POST   /api/webhooks/nextlevel
Body: { order_id, status, live_at?, ... }
Headers: X-Webhook-Signature (HMAC validation)
Response: 200 OK

POST   /api/webhooks/seoclerks
Body: { order_id, status, ... }
Headers: X-Signature
Response: 200 OK
```

#### System

```
GET    /api/v1/health
Response: {
  status: "healthy",
  services: {
    database: "ok",
    redis: "ok",
    queue: "ok"
  },
  timestamp: "2026-02-09T22:30:00Z"
}

GET    /api/v1/metrics
(Internal monitoring endpoint)
Response: Prometheus-format metrics
```

### Authentication & Authorization

**Laravel Sanctum (Session-based SPA) :**

```php
// config/sanctum.php
'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', 'localhost,127.0.0.1')),

// app/Http/Kernel.php
'api' => [
    \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
    'throttle:60,1',  // 60 req/min
    \Illuminate\Routing\Middleware\SubstituteBindings::class,
],
```

**Login Flow :**
```
1. GET /sanctum/csrf-cookie (obtenir CSRF token)
2. POST /api/v1/auth/login { email, password }
   → Laravel crée session + cookie httpOnly
3. Subsequent requests: Cookie automatiquement envoyé
```

**Authorization (Policies) :**

```php
// app/Policies/BacklinkPolicy.php
class BacklinkPolicy
{
    public function view(User $user, Backlink $backlink): bool
    {
        return $backlink->project->user_id === $user->id;
    }

    public function update(User $user, Backlink $backlink): bool
    {
        return $backlink->project->user_id === $user->id;
    }

    public function delete(User $user, Backlink $backlink): bool
    {
        return $backlink->project->user_id === $user->id;
    }
}

// Usage in controller
public function show(Backlink $backlink)
{
    $this->authorize('view', $backlink);
    return new BacklinkResource($backlink);
}
```

**Rate Limiting (NFR-003) :**

```php
// app/Providers/RouteServiceProvider.php
RateLimiter::for('api', function (Request $request) {
    return $request->user()
        ? Limit::perMinute(60)->by($request->user()->id)
        : Limit::perMinute(10)->by($request->ip());
});

// Custom rate limit for checks
RateLimiter::for('checks', function (Request $request) {
    return Limit::perMinute(10)->by($request->user()->id);
});

// Apply in routes
Route::post('/backlinks/{id}/check', [BacklinkController::class, 'check'])
    ->middleware('throttle:checks');
```

---

## Non-Functional Requirements Coverage

### NFR-001: Performance API (<500ms au 95th percentile)

**Requirement :** Les endpoints API critiques doivent répondre en moins de 500ms pour 95% des requêtes.

**Architecture Solution :**

**1. Cache Redis (Dashboard Stats) :**
```php
// app/Services/Dashboard/DashboardService.php
public function getStats(int $projectId): DashboardStatsDTO
{
    return Cache::remember("dashboard:stats:{$projectId}", 300, function() use ($projectId) {
        // Requêtes PostgreSQL agrégées (sans cache: ~200ms, avec cache: <5ms)
        return new DashboardStatsDTO(...);
    });
}
```

**2. Index PostgreSQL :**
```sql
-- Index compound pour query fréquentes
CREATE INDEX idx_backlinks_project_status ON backlinks(project_id, status);

-- Index partial pour active backlinks uniquement
CREATE INDEX idx_backlinks_active ON backlinks(status) WHERE status = 'active';

-- Couvre 80% des queries dashboard
```

**3. Query Optimization (Eloquent Eager Loading) :**
```php
// ❌ N+1 Problem (slow)
$backlinks = Backlink::all();
foreach ($backlinks as $backlink) {
    echo $backlink->project->name;  // 1 query per backlink
}

// ✅ Eager Loading (fast)
$backlinks = Backlink::with('project', 'latestCheck')->paginate(50);
// 3 queries total: backlinks + projects + checks
```

**4. Pagination Stricte :**
```php
// Toutes les collections paginées (50 items max par page)
Route::get('/backlinks', function() {
    return Backlink::paginate(50);  // Never load all records
});
```

**Validation :**
- Load testing avec k6: 100 concurrent users → mesurer p95 latency
- Target: GET /api/v1/dashboard/stats < 300ms (p95)
- Target: GET /api/v1/backlinks?project_id=1 < 150ms (p95)

---

### NFR-002: Performance Queue System (100 jobs/minute)

**Requirement :** Système de queue capable de traiter 100 jobs/minute minimum.

**Architecture Solution :**

**1. Redis In-Memory Queue :**
```php
// config/queue.php
'connections' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => env('REDIS_QUEUE', 'default'),
        'retry_after' => 90,
        'block_for' => null,
    ],
],
```

**2. Laravel Horizon Auto-Scaling :**
```php
// config/horizon.php
'environments' => [
    'production' => [
        'supervisor-1' => [
            'connection' => 'redis',
            'queue' => ['high', 'default', 'low'],
            'balance' => 'auto',       // Auto-balance across queues
            'minProcesses' => 3,       // Minimum workers
            'maxProcesses' => 10,      // Scale up to 10 workers
            'balanceMaxShift' => 1,
            'balanceCooldown' => 3,
            'tries' => 3,
            'timeout' => 60,
        ],
    ],
],
```

**3. Queue Prioritization :**
```php
// High priority: Manual checks (user-triggered)
CheckBacklink::dispatch($backlink)->onQueue('high');

// Default priority: Scheduled monitoring
MonitorProjectBacklinks::dispatch($project)->onQueue('default');

// Low priority: SEO metrics refresh (non-urgent)
FetchSeoMetrics::dispatch($domain)->onQueue('low');
```

**Calcul Capacité :**
- 10 workers × 60 jobs/worker/heure = 600 jobs/heure = 10 jobs/minute/worker
- 10 workers → **100 jobs/minute** (target atteint)
- Avec avg job duration 30s: 10 workers × 2 jobs/min = 20 jobs/min → **Scale dynamiquement**

**Validation :**
- Dispatch 1000 jobs → mesurer throughput via Horizon dashboard
- Target: 100% des jobs complétés en <10 minutes (avg 100 jobs/min)

---

### NFR-003: Rate Limiting

**Requirement :** Protection contre abus API (60 req/min authenticated, 10 req/min unauthenticated).

**Architecture Solution :**

```php
// app/Providers/RouteServiceProvider.php
RateLimiter::for('api', function (Request $request) {
    return $request->user()
        ? Limit::perMinute(60)->by($request->user()->id)
        : Limit::perMinute(10)->by($request->ip());
});

// Rate limit custom pour actions coûteuses
RateLimiter::for('checks', function (Request $request) {
    return Limit::perMinute(10)->by($request->user()->id)
        ->response(fn() => response()->json([
            'message' => 'Too many check requests. Please try again later.'
        ], 429));
});

// Apply in routes
Route::middleware(['auth:sanctum', 'throttle:api'])->group(function() {
    Route::apiResource('projects', ProjectController::class);
    Route::apiResource('backlinks', BacklinkController::class);
});

Route::post('/backlinks/{id}/check', [BacklinkController::class, 'check'])
    ->middleware(['auth:sanctum', 'throttle:checks']);
```

**Response 429 (Too Many Requests) :**
```json
{
  "message": "Too Many Attempts.",
  "retry_after": 45  // seconds
}
```

**Validation :**
- Test automatisé: 61 requêtes en 60s → dernière doit retourner 429
- Test unauthenticated: 11 requêtes → 429

---

### NFR-004: SSRF Protection

**Requirement :** Protection contre Server-Side Request Forgery (accès réseau interne).

**Architecture Solution :**

```php
// app/Services/Security/UrlValidator.php
class UrlValidator
{
    private const BLOCKED_IPS = [
        '127.0.0.0/8',      // Localhost
        '10.0.0.0/8',       // RFC1918 private
        '172.16.0.0/12',    // RFC1918 private
        '192.168.0.0/16',   // RFC1918 private
        '169.254.0.0/16',   // Link-local
        '0.0.0.0/8',        // Current network
        '224.0.0.0/4',      // Multicast
    ];

    private const ALLOWED_PROTOCOLS = ['http', 'https'];

    public function validate(string $url): void
    {
        $parsed = parse_url($url);

        // 1. Check protocol
        if (!in_array($parsed['scheme'] ?? '', self::ALLOWED_PROTOCOLS)) {
            throw new InvalidUrlException("Protocol not allowed: {$parsed['scheme']}");
        }

        // 2. Resolve hostname
        $host = $parsed['host'] ?? '';
        $ips = dns_get_record($host, DNS_A + DNS_AAAA);

        if (empty($ips)) {
            throw new InvalidUrlException("Unable to resolve hostname: {$host}");
        }

        // 3. Check each resolved IP
        foreach ($ips as $record) {
            $ip = $record['ip'] ?? $record['ipv6'] ?? null;

            if ($this->isBlocked($ip)) {
                throw new SsrfException("Access to {$ip} ({$host}) is blocked (SSRF protection)");
            }
        }

        // 4. Additional check: Prevent HTTP redirects to blocked IPs
        // (handled in BacklinkChecker via Guzzle middleware)
    }

    private function isBlocked(string $ip): bool
    {
        foreach (self::BLOCKED_IPS as $range) {
            if ($this->ipInRange($ip, $range)) {
                return true;
            }
        }
        return false;
    }

    private function ipInRange(string $ip, string $range): bool
    {
        [$subnet, $mask] = explode('/', $range);
        return (ip2long($ip) & ~((1 << (32 - $mask)) - 1)) === ip2long($subnet);
    }
}

// Usage dans BacklinkChecker
public function checkBacklink(Backlink $backlink): BacklinkCheckResult
{
    $this->urlValidator->validate($backlink->source_url);  // ❗ CRITICAL

    // Guzzle middleware to prevent redirect to blocked IPs
    $response = $this->httpClient->get($backlink->source_url, [
        'allow_redirects' => [
            'max' => 5,
            'track_redirects' => true,
            'on_redirect' => function($request, $response, $uri) {
                $this->urlValidator->validate((string) $uri);  // Validate redirect target
            }
        ],
    ]);
}
```

**Validation :**
- Test: Tenter d'ajouter backlink avec `source_url=http://127.0.0.1/admin` → doit rejeter (400 Bad Request)
- Test: Tenter `source_url=http://169.254.169.254/latest/meta-data` (AWS metadata) → doit rejeter
- Pentest: Scanner avec SSRF payloads (HackTricks list) → 100% blocked

---

### NFR-005: Chiffrement Données

**Requirement :** Chiffrement TLS 1.3 transport, bcrypt passwords, encrypted config.

**Architecture Solution :**

**1. TLS 1.3 (Nginx) :**
```nginx
server {
    listen 443 ssl http2;
    ssl_protocols TLSv1.3;
    ssl_ciphers 'TLS_AES_128_GCM_SHA256:TLS_AES_256_GCM_SHA384';
    ssl_prefer_server_ciphers off;

    # HSTS (force HTTPS)
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
}
```

**2. Bcrypt Passwords (Laravel) :**
```php
// config/hashing.php
'bcrypt' => [
    'rounds' => 12,  // 2^12 iterations (secure, ~100ms hash time)
],

// app/Models/User.php
protected $hidden = ['password'];

protected function casts(): array
{
    return [
        'password' => 'hashed',  // Auto-bcrypt on save
    ];
}
```

**3. Encrypted Environment Variables :**
```bash
# .env.example (NOT committed)
DB_PASSWORD=
MOZ_SECRET_KEY=
AHREFS_API_KEY=
NEXTLEVEL_API_KEY=

# Production: Use encrypted .env via Laravel Envoy or Vault
```

**4. Database Encryption (Optional for sensitive fields) :**
```php
// app/Models/BacklinkOrder.php
protected $casts = [
    'platform_response' => 'encrypted:array',  // Encrypt JSONB field
];
```

**Validation :**
- SSL Labs scan: https://linktracker.com → Grade A+
- Password hash inspection: `bcrypt` prefix with $2y$12$
- .env never committed to Git (check .gitignore)

---

### NFR-006: Fiabilité Queue System

**Requirement :** Queue fiable avec retry, dead letter queue, HA Redis.

**Architecture Solution :**

**1. Redis Sentinel (High Availability) :**
```yaml
# docker-compose.yml
redis-master:
  image: redis:7.2
  command: redis-server --appendonly yes

redis-sentinel-1:
  image: redis:7.2
  command: redis-sentinel /etc/redis/sentinel.conf
  depends_on: [redis-master]

redis-sentinel-2:
  image: redis:7.2
  command: redis-sentinel /etc/redis/sentinel.conf

redis-sentinel-3:
  image: redis:7.2
  command: redis-sentinel /etc/redis/sentinel.conf
```

**sentinel.conf :**
```conf
sentinel monitor mymaster redis-master 6379 2
sentinel down-after-milliseconds mymaster 5000
sentinel failover-timeout mymaster 10000
```

**2. Job Retry Strategy :**
```php
// app/Jobs/CheckBacklink.php
class CheckBacklink implements ShouldQueue
{
    public $tries = 3;                          // Retry 3 times
    public $backoff = [10, 30, 60];            // Exponential backoff (seconds)
    public $timeout = 60;                       // Job timeout
    public $maxExceptions = 3;                  // Max exceptions before failure

    public function failed(Throwable $exception): void
    {
        // Job définitivement échoué après 3 retries
        Log::error("CheckBacklink failed permanently", [
            'backlink_id' => $this->backlink->id,
            'exception' => $exception->getMessage(),
        ]);

        // Créer alert pour admin
        Alert::create([
            'backlink_id' => $this->backlink->id,
            'type' => 'check_failed',
            'detected_at' => now(),
            'old_value' => null,
            'new_value' => $exception->getMessage(),
        ]);
    }
}
```

**3. Dead Letter Queue (Failed Jobs Table) :**
```bash
php artisan queue:failed-table
php artisan migrate
```

```php
// Retry failed job
php artisan queue:retry {job_id}

// Retry all failed jobs
php artisan queue:retry all

// Clear failed jobs
php artisan queue:flush
```

**Validation :**
- Chaos testing: Kill Redis master → Sentinel doit promouvoir replica en <10s
- Test retry: Job qui throw exception → doit retry 3x avec backoff
- Monitor Horizon: Failed jobs table doit capturer échecs permanents

---

### NFR-007: Scalabilité Horizontale

**Requirement :** Système scalable horizontalement (workers, app instances).

**Architecture Solution :**

**1. Stateless Application (Laravel) :**
```php
// Session storage: Redis (pas filesystem)
'driver' => 'redis',

// Cache: Redis (pas file)
'default' => 'redis',

// Permet multiple app instances derrière load balancer
```

**2. Horizon Workers Auto-Scaling :**
```php
// config/horizon.php
'minProcesses' => 3,
'maxProcesses' => 10,
'balanceMaxShift' => 1,    // Add/remove 1 worker at a time
'balanceCooldown' => 3,    // Wait 3 seconds between scaling

// Workers peuvent scaler indépendamment des app instances
```

**3. Database Connection Pooling :**
```php
// config/database.php
'pgsql' => [
    'options' => [
        PDO::ATTR_PERSISTENT => true,  // Persistent connections
    ],
],
```

**4. Load Balancer (Nginx) :**
```nginx
upstream app_backend {
    least_conn;  # Route to instance with fewest connections
    server app1:9000 weight=1;
    server app2:9000 weight=1;
    server app3:9000 weight=1;
}

server {
    location / {
        proxy_pass http://app_backend;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    }
}
```

**Validation :**
- Test: 3 app instances → session persiste entre requêtes (Redis)
- Load test: 500 concurrent users → response time stable
- Monitor: CPU/RAM usage per instance

---

### NFR-008: Résilience APIs Externes

**Requirement :** Circuit breaker pour APIs (Moz, Ahrefs) avec cache et fallback.

**Architecture Solution :**

**1. Circuit Breaker Implementation :**
```php
// app/Services/Security/CircuitBreaker.php
class CircuitBreaker
{
    private const FAILURE_THRESHOLD = 5;      // Open après 5 échecs
    private const TIMEOUT_SECONDS = 300;      // Half-open après 5min
    private const SUCCESS_THRESHOLD = 2;      // Close après 2 succès (half-open)

    public function execute(string $key, callable $callback): mixed
    {
        $state = Cache::get("circuit:{$key}:state", 'closed');
        $failures = Cache::get("circuit:{$key}:failures", 0);

        // State: OPEN → Reject immediately
        if ($state === 'open') {
            $openedAt = Cache::get("circuit:{$key}:opened_at");

            if (now()->diffInSeconds($openedAt) > self::TIMEOUT_SECONDS) {
                Cache::put("circuit:{$key}:state", 'half_open');
                $state = 'half_open';
            } else {
                throw new CircuitBreakerOpenException("Circuit breaker for {$key} is open");
            }
        }

        // State: HALF_OPEN → Test avec 1 requête
        try {
            $result = $callback();

            if ($state === 'half_open') {
                $successes = Cache::increment("circuit:{$key}:successes");
                if ($successes >= self::SUCCESS_THRESHOLD) {
                    $this->close($key);
                }
            } else {
                Cache::put("circuit:{$key}:failures", 0);  // Reset failures
            }

            return $result;

        } catch (Throwable $e) {
            $failures = Cache::increment("circuit:{$key}:failures");

            if ($failures >= self::FAILURE_THRESHOLD) {
                $this->open($key);
            }

            throw $e;
        }
    }

    private function open(string $key): void
    {
        Cache::put("circuit:{$key}:state", 'open');
        Cache::put("circuit:{$key}:opened_at", now());
        Log::warning("Circuit breaker opened for {$key}");
    }

    private function close(string $key): void
    {
        Cache::put("circuit:{$key}:state", 'closed');
        Cache::put("circuit:{$key}:failures", 0);
        Cache::put("circuit:{$key}:successes", 0);
        Log::info("Circuit breaker closed for {$key}");
    }
}
```

**2. Usage avec Fallback :**
```php
// app/Services/SeoMetrics/SeoMetricsService.php
public function getMetrics(string $domain): SeoMetricsDTO
{
    // Try Moz first
    try {
        return $this->circuitBreaker->execute('moz_api', fn() =>
            $this->mozProvider->getMetrics($domain)
        );
    } catch (CircuitBreakerOpenException $e) {
        Log::info("Moz circuit open, trying Ahrefs fallback");
    }

    // Fallback to Ahrefs
    try {
        return $this->circuitBreaker->execute('ahrefs_api', fn() =>
            $this->ahrefsProvider->getMetrics($domain)
        );
    } catch (CircuitBreakerOpenException $e) {
        Log::warning("Both Moz and Ahrefs circuits open, using CustomProvider");
    }

    // Final fallback: CustomProvider (limited data)
    return $this->customProvider->getMetrics($domain);
}
```

**3. Cache Long Terme (24h) :**
```php
// app/Services/SeoMetrics/MozProvider.php
public function getMetrics(string $domain): SeoMetricsDTO
{
    $cacheKey = "seo_metrics:moz:{$domain}";

    return Cache::remember($cacheKey, now()->addDay(), function() use ($domain) {
        // API call (avec circuit breaker dans SeoMetricsService)
        $response = $this->httpClient->post(/* ... */);
        return new SeoMetricsDTO(/* ... */);
    });
}
```

**Validation :**
- Simulate Moz API down (mock 500 errors) → après 5 échecs, circuit open
- Verify fallback to Ahrefs triggered
- Verify cache hit avoid API calls

---

### NFR-009: Haute Disponibilité (99.5% uptime)

**Requirement :** Infrastructure HA avec failover PostgreSQL/Redis <30s.

**Architecture Solution :**

**1. PostgreSQL Replication (Master-Replica) :**
```yaml
# docker-compose.yml
postgres-master:
  image: postgres:15
  environment:
    POSTGRES_REPLICATION_MODE: master
    POSTGRES_REPLICATION_USER: replicator
    POSTGRES_REPLICATION_PASSWORD: ${REPLICATION_PASSWORD}

postgres-replica:
  image: postgres:15
  environment:
    POSTGRES_REPLICATION_MODE: replica
    POSTGRES_MASTER_HOST: postgres-master
    POSTGRES_REPLICATION_USER: replicator
    POSTGRES_REPLICATION_PASSWORD: ${REPLICATION_PASSWORD}
```

**2. Redis Sentinel (déjà couvert NFR-006)**

**3. Health Checks :**
```php
// app/Http/Controllers/HealthController.php
public function check(): JsonResponse
{
    $status = 'healthy';
    $services = [];

    // Check PostgreSQL
    try {
        DB::connection()->getPdo();
        $services['database'] = 'ok';
    } catch (\Exception $e) {
        $services['database'] = 'error';
        $status = 'unhealthy';
    }

    // Check Redis
    try {
        Cache::store('redis')->get('health_check');
        $services['redis'] = 'ok';
    } catch (\Exception $e) {
        $services['redis'] = 'error';
        $status = 'unhealthy';
    }

    // Check Queue
    try {
        $services['queue'] = Queue::size() < 10000 ? 'ok' : 'degraded';
    } catch (\Exception $e) {
        $services['queue'] = 'error';
        $status = 'unhealthy';
    }

    return response()->json([
        'status' => $status,
        'services' => $services,
        'timestamp' => now()->toIso8601String(),
    ], $status === 'healthy' ? 200 : 503);
}

// routes/api.php
Route::get('/health', [HealthController::class, 'check']);
```

**4. Monitoring avec Uptime Checks :**
```bash
# Uptime Robot or similar monitoring service
GET https://linktracker.com/api/v1/health every 5 minutes
Alert if response != 200 or response time > 5s
```

**Validation :**
- Failover test: Kill PostgreSQL master → replica promue en <30s
- Downtime calculation: (43800 - 219) / 43800 = 99.5% uptime (219 min downtime/an allowed)

---

### NFR-010: Backup et Disaster Recovery

**(Voir section Reliability & Availability ci-dessous pour détails complets)**

---

### NFR-011: Code Maintenable (70% test coverage, PSR-12)

**(Voir section Development Architecture ci-dessous pour détails complets)**

---

### NFR-012: Compatibilité Navigateurs

**Requirement :** Support IE11+, Chrome, Firefox, Safari, Edge.

**Architecture Solution :**

**1. Vue.js 3 Support :**
```javascript
// vite.config.js
export default {
  build: {
    target: 'es2015',  // Compatible IE11 avec polyfills
  },
}

// package.json
{
  "browserslist": [
    "> 1%",
    "last 2 versions",
    "not dead",
    "IE 11"
  ]
}
```

**2. Tailwind CSS Autoprefixer :**
```javascript
// postcss.config.js
module.exports = {
  plugins: {
    tailwindcss: {},
    autoprefixer: {},  // Auto-add vendor prefixes (-webkit-, -moz-)
  },
}
```

**3. Progressive Enhancement :**
```html
<!-- Fallback pour JS disabled -->
<noscript>
  <div class="alert">
    JavaScript est requis pour utiliser Link Tracker.
  </div>
</noscript>
```

**Validation :**
- BrowserStack testing: IE11, Chrome 90+, Firefox 88+, Safari 14+, Edge 90+
- Manual testing: Core features (dashboard, add backlink, view checks) functional

---

## Security Architecture

### Authentication

**Mécanisme :** Laravel Sanctum (Session-Based SPA Authentication)

**Flow d'Authentification :**

```
1. Frontend (Vue.js) → GET /sanctum/csrf-cookie
   ← Set-Cookie: XSRF-TOKEN (CSRF token)

2. Frontend → POST /api/v1/auth/login
   Headers: X-XSRF-TOKEN (from cookie)
   Body: { email, password }
   ← Set-Cookie: laravel_session (httpOnly, secure)
   ← 200 OK { user: {...} }

3. Subsequent requests → GET /api/v1/projects
   Cookie: laravel_session (auto-sent by browser)
   ← 200 OK (authenticated)

4. Frontend → POST /api/v1/auth/logout
   ← 200 OK + Clear laravel_session cookie
```

**Configuration Sanctum :**

```php
// config/sanctum.php
return [
    'stateful' => explode(',', env(
        'SANCTUM_STATEFUL_DOMAINS',
        'localhost,127.0.0.1,linktracker.com,*.linktracker.com'
    )),

    'guard' => ['web'],

    'expiration' => null,  // Sessions never expire (Laravel session lifetime applies)

    'middleware' => [
        'verify_csrf_token' => App\Http\Middleware\VerifyCsrfToken::class,
        'encrypt_cookies' => App\Http\Middleware\EncryptCookies::class,
    ],
];
```

**CSRF Protection :**

```javascript
// Frontend: Axios configuration
axios.defaults.withCredentials = true;
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Automatically send CSRF token with requests
await axios.get('/sanctum/csrf-cookie');
await axios.post('/api/v1/auth/login', { email, password });
```

**Session Security :**

```php
// config/session.php
return [
    'driver' => 'redis',              // Store sessions in Redis (scalable)
    'lifetime' => 120,                 // 120 minutes
    'expire_on_close' => false,
    'encrypt' => true,
    'http_only' => true,              // ❗ Prevent XSS access to cookie
    'secure' => env('APP_ENV') === 'production',  // HTTPS only in prod
    'same_site' => 'lax',             // CSRF protection
];
```

### Authorization

**Policy-Based Authorization (Laravel Policies) :**

```php
// app/Policies/ProjectPolicy.php
class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return true;  // Authenticated users can list their projects
    }

    public function view(User $user, Project $project): bool
    {
        return $user->id === $project->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Project $project): bool
    {
        return $user->id === $project->user_id;
    }

    public function delete(User $user, Project $project): bool
    {
        return $user->id === $project->user_id;
    }
}

// app/Policies/BacklinkPolicy.php
class BacklinkPolicy
{
    public function view(User $user, Backlink $backlink): bool
    {
        return $backlink->project->user_id === $user->id;
    }

    public function update(User $user, Backlink $backlink): bool
    {
        return $backlink->project->user_id === $user->id;
    }

    public function delete(User $user, Backlink $backlink): bool
    {
        return $backlink->project->user_id === $user->id;
    }
}

// Register policies
// app/Providers/AuthServiceProvider.php
protected $policies = [
    Project::class => ProjectPolicy::class,
    Backlink::class => BacklinkPolicy::class,
];
```

**Usage dans Controllers :**

```php
// app/Http/Controllers/BacklinkController.php
public function show(Backlink $backlink)
{
    $this->authorize('view', $backlink);  // ❗ Automatic 403 if fails
    return new BacklinkResource($backlink);
}

public function update(Request $request, Backlink $backlink)
{
    $this->authorize('update', $backlink);

    $validated = $request->validate([
        'source_url' => 'sometimes|url|max:2048',
        'target_url' => 'sometimes|url|max:2048',
        'anchor_text' => 'sometimes|string|max:500',
    ]);

    $backlink->update($validated);
    return new BacklinkResource($backlink);
}
```

### Data Encryption

**1. Transport Layer (TLS 1.3) :**

```nginx
# /etc/nginx/sites-available/linktracker
server {
    listen 443 ssl http2;
    server_name linktracker.com;

    ssl_certificate /etc/letsencrypt/live/linktracker.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/linktracker.com/privkey.pem;

    ssl_protocols TLSv1.3;
    ssl_ciphers 'TLS_AES_128_GCM_SHA256:TLS_AES_256_GCM_SHA384';
    ssl_prefer_server_ciphers off;

    # Security headers
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains; preload" always;
    add_header X-Frame-Options "DENY" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
}
```

**2. Data at Rest :**

```php
// Passwords: Bcrypt (config/hashing.php)
'bcrypt' => [
    'rounds' => 12,  // 2^12 = 4096 iterations (~100ms)
],

// Sensitive fields: Laravel encryption
// app/Models/BacklinkOrder.php
protected $casts = [
    'platform_response' => 'encrypted:array',  // Encrypt JSONB
];

// Environment variables: Never commit .env
APP_KEY=base64:GENERATED_KEY  // Used for encryption
```

**3. API Keys Storage :**

```bash
# .env (gitignored, never committed)
MOZ_ACCESS_ID=secret_id
MOZ_SECRET_KEY=secret_key
AHREFS_API_KEY=secret_api_key
NEXTLEVEL_API_KEY=secret_key

# Production: Use secrets manager (AWS Secrets Manager, Vault)
```

### Security Best Practices

**1. Input Validation (Prevent SQL Injection, XSS) :**

```php
// app/Http/Requests/StoreBacklinkRequest.php
class StoreBacklinkRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'project_id' => 'required|exists:projects,id',
            'source_url' => [
                'required',
                'url',
                'max:2048',
                function ($attribute, $value, $fail) {
                    // SSRF validation
                    try {
                        app(UrlValidator::class)->validate($value);
                    } catch (SsrfException $e) {
                        $fail("The {$attribute} is not allowed (SSRF protection).");
                    }
                },
            ],
            'target_url' => 'required|url|max:2048',
            'anchor_text' => 'nullable|string|max:500',
        ];
    }
}

// Usage in controller
public function store(StoreBacklinkRequest $request)
{
    $validated = $request->validated();  // ✅ Sanitized input
    return Backlink::create($validated);
}
```

**2. SQL Injection Prevention (Eloquent ORM) :**

```php
// ❌ VULNERABLE (raw SQL)
$results = DB::select("SELECT * FROM backlinks WHERE project_id = " . $projectId);

// ✅ SAFE (parameterized query)
$results = DB::select("SELECT * FROM backlinks WHERE project_id = ?", [$projectId]);

// ✅ BEST (Eloquent ORM - automatic parameterization)
$results = Backlink::where('project_id', $projectId)->get();
```

**3. XSS Prevention (Vue.js auto-escaping) :**

```vue
<!-- ✅ SAFE: Vue auto-escapes -->
<p>{{ backlink.anchor_text }}</p>

<!-- ❌ DANGEROUS: Raw HTML (avoid unless necessary) -->
<p v-html="backlink.anchor_text"></p>

<!-- ✅ SAFE: Sanitize before v-html -->
<p v-html="sanitize(backlink.anchor_text)"></p>
```

**4. Mass Assignment Protection :**

```php
// app/Models/Backlink.php
protected $fillable = [
    'project_id',
    'source_url',
    'target_url',
    'anchor_text',
    'status',
];

protected $guarded = ['id', 'created_at', 'updated_at'];

// Prevents: Backlink::create($request->all()) from setting 'id'
```

**5. Logging & Audit Trail :**

```php
// app/Observers/BacklinkObserver.php
class BacklinkObserver
{
    public function created(Backlink $backlink): void
    {
        Log::info("Backlink created", [
            'user_id' => auth()->id(),
            'backlink_id' => $backlink->id,
            'project_id' => $backlink->project_id,
        ]);
    }

    public function deleted(Backlink $backlink): void
    {
        Log::warning("Backlink deleted", [
            'user_id' => auth()->id(),
            'backlink_id' => $backlink->id,
        ]);
    }
}
```

---

## Scalability & Performance

### Scaling Strategy

**1. Vertical Scaling (Initial) :**

- **Current Capacity :** VPS 8GB RAM, 4 vCPUs
  - PostgreSQL: 4GB RAM allocated
  - Redis: 2GB RAM allocated
  - Laravel: 2GB RAM allocated (PHP-FPM 20 workers)

- **Scale Limits :**
  - Up to 32GB RAM, 16 vCPUs (before horizontal scaling needed)
  - Supports ~5,000 backlinks, 150,000 checks/jour

**2. Horizontal Scaling (Future) :**

```yaml
# docker-compose.prod.yml (scaled deployment)
services:
  app:
    image: linktracker:latest
    deploy:
      replicas: 3  # 3 app instances
      resources:
        limits:
          cpus: '2'
          memory: 4G

  nginx:
    image: nginx:latest
    ports:
      - "443:443"
    depends_on:
      - app
    # Load balances between 3 app instances

  horizon:
    image: linktracker:latest
    command: php artisan horizon
    deploy:
      replicas: 2  # 2 Horizon instances (workers auto-scale internally)
```

**3. Database Scaling :**

**Read Replicas (PostgreSQL) :**
```php
// config/database.php
'pgsql' => [
    'read' => [
        'host' => [
            env('DB_READ_HOST_1', '127.0.0.1'),
            env('DB_READ_HOST_2', '127.0.0.1'),
        ],
    ],
    'write' => [
        'host' => [env('DB_WRITE_HOST', '127.0.0.1')],
    ],
    // Laravel auto-route SELECT to read replicas, INSERT/UPDATE/DELETE to master
],
```

**Table Partitioning (déjà implémenté) :**
```sql
-- BacklinkChecks partitionné par mois (scan 1 partition vs table entière)
-- Gain: Query dashboard (last 30 days) → 12x faster
```

### Performance Optimization

**1. Database Query Optimization :**

```php
// ❌ N+1 Problem (50 queries for 50 backlinks)
$backlinks = Backlink::all();
foreach ($backlinks as $backlink) {
    echo $backlink->project->name;
}

// ✅ Eager Loading (2 queries total)
$backlinks = Backlink::with('project')->get();

// ✅ Lazy Eager Loading (load relation only if needed)
$backlinks = Backlink::all();
if ($needProjects) {
    $backlinks->load('project');
}

// ✅ Select only needed columns
$backlinks = Backlink::select('id', 'source_url', 'status')->get();
```

**2. Index Optimization (déjà implémenté) :**

```sql
-- Query: WHERE project_id = ? AND status = 'active'
CREATE INDEX idx_backlinks_project_status ON backlinks(project_id, status);

-- Query: ORDER BY last_checked_at DESC
CREATE INDEX idx_backlinks_last_checked ON backlinks(last_checked_at DESC);

-- Full-text search: anchor text
CREATE INDEX idx_backlinks_anchor_gin ON backlinks USING gin(to_tsvector('english', anchor_text));
```

**3. Pagination (Éviter Load All Records) :**

```php
// ❌ BAD (loads 10,000 records into memory)
$backlinks = Backlink::all();

// ✅ GOOD (paginate 50 at a time)
$backlinks = Backlink::paginate(50);

// ✅ BETTER (cursor pagination for large datasets)
$backlinks = Backlink::cursorPaginate(50);
```

### Caching Strategy

**1. Cache Layers :**

```
┌─────────────────────────────────────────┐
│  Browser Cache (static assets, 1 year) │
└──────────────────┬──────────────────────┘
                   │
┌──────────────────┴──────────────────────┐
│  CDN Cache (CloudFlare, 1 hour)         │
└──────────────────┬──────────────────────┘
                   │
┌──────────────────┴──────────────────────┐
│  Application Cache (Redis, 5min-24h)    │
└──────────────────┬──────────────────────┘
                   │
┌──────────────────┴──────────────────────┐
│  Database (PostgreSQL)                   │
└─────────────────────────────────────────┘
```

**2. Redis Cache Strategy :**

```php
// Dashboard stats (TTL 5 minutes)
Cache::remember("dashboard:stats:{$projectId}", 300, fn() =>
    DashboardService::getStats($projectId)
);

// SEO Metrics (TTL 24 hours)
Cache::remember("seo_metrics:moz:{$domain}", 86400, fn() =>
    MozProvider::getMetrics($domain)
);

// User session (TTL 2 hours)
Cache::remember("user:{$userId}:session", 7200, fn() =>
    User::with('projects')->find($userId)
);
```

**3. Cache Invalidation :**

```php
// app/Observers/BacklinkObserver.php
public function updated(Backlink $backlink): void
{
    // Invalidate dashboard cache when backlink status changes
    if ($backlink->isDirty('status')) {
        Cache::forget("dashboard:stats:{$backlink->project_id}");
    }
}

// Manual cache clear (admin)
Route::post('/admin/cache/clear', function() {
    Cache::flush();
    return response()->json(['message' => 'Cache cleared']);
});
```

### Load Balancing

**Nginx Load Balancer :**

```nginx
upstream app_backend {
    least_conn;  # Route to instance with fewest active connections

    server app1.linktracker.local:9000 weight=1 max_fails=3 fail_timeout=30s;
    server app2.linktracker.local:9000 weight=1 max_fails=3 fail_timeout=30s;
    server app3.linktracker.local:9000 weight=1 max_fails=3 fail_timeout=30s;

    # Health check (requires nginx_upstream_check_module)
    check interval=5000 rise=2 fall=3 timeout=1000 type=http;
    check_http_send "GET /api/v1/health HTTP/1.0\r\n\r\n";
    check_http_expect_alive http_2xx http_3xx;
}

server {
    listen 443 ssl http2;
    server_name linktracker.com;

    location / {
        proxy_pass http://app_backend;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;

        # Connection pooling
        proxy_http_version 1.1;
        proxy_set_header Connection "";

        # Timeouts
        proxy_connect_timeout 60s;
        proxy_send_timeout 60s;
        proxy_read_timeout 60s;
    }
}
```

---

## Reliability & Availability

### High Availability Design

**1. PostgreSQL Master-Replica Replication :**

```bash
# Master configuration (postgresql.conf)
wal_level = replica
max_wal_senders = 3
wal_keep_size = 64MB
hot_standby = on

# Replica configuration
primary_conninfo = 'host=postgres-master port=5432 user=replicator password=xxx'
promote_trigger_file = '/tmp/postgresql.trigger'
```

**Automatic Failover (pg_auto_failover) :**
```bash
# Monitor node
pg_autoctl create monitor --pgdata /data/monitor

# Master node
pg_autoctl create postgres --pgdata /data/postgres --hostname master.local --monitor postgres://monitor.local

# Replica node
pg_autoctl create postgres --pgdata /data/postgres --hostname replica.local --monitor postgres://monitor.local

# Failover: If master fails, replica auto-promoted in <30 seconds
```

**2. Redis Sentinel (3-node cluster) :**

```conf
# sentinel.conf
sentinel monitor mymaster redis-master 6379 2
sentinel down-after-milliseconds mymaster 5000
sentinel parallel-syncs mymaster 1
sentinel failover-timeout mymaster 10000

# Quorum: 2/3 sentinels must agree master is down
```

**Laravel Redis Configuration :**
```php
// config/database.php
'redis' => [
    'client' => 'predis',
    'options' => [
        'replication' => 'sentinel',
        'service' => 'mymaster',
        'parameters' => [
            'password' => env('REDIS_PASSWORD'),
            'database' => 0,
        ],
    ],
    'clusters' => [
        'sentinel' => [
            'tcp://sentinel1:26379',
            'tcp://sentinel2:26379',
            'tcp://sentinel3:26379',
        ],
    ],
],
```

**3. Application Redundancy :**

```yaml
# docker-compose.ha.yml
services:
  app1:
    image: linktracker:latest
    networks: [app_network]

  app2:
    image: linktracker:latest
    networks: [app_network]

  app3:
    image: linktracker:latest
    networks: [app_network]

  nginx:
    image: nginx:latest
    volumes:
      - ./nginx-lb.conf:/etc/nginx/nginx.conf
    ports:
      - "443:443"
    depends_on: [app1, app2, app3]
```

### Disaster Recovery

**1. Backup Strategy :**

**Automated Daily Backups :**
```bash
#!/bin/bash
# /scripts/backup-postgres.sh

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/backups/postgres"
RETENTION_DAYS=30

# Full database dump
pg_dump -h postgres-master -U linktracker_user linktracker \
  | gzip > $BACKUP_DIR/linktracker_$DATE.sql.gz

# WAL archiving for PITR (Point-In-Time Recovery)
rsync -av /var/lib/postgresql/15/main/pg_wal/ $BACKUP_DIR/wal/

# Delete backups older than 30 days
find $BACKUP_DIR -name "*.sql.gz" -mtime +$RETENTION_DAYS -delete

# Upload to S3 (offsite backup)
aws s3 sync $BACKUP_DIR s3://linktracker-backups/postgres/
```

**Cron Schedule :**
```cron
0 2 * * * /scripts/backup-postgres.sh  # Daily at 2 AM
0 */6 * * * /scripts/backup-redis.sh   # Every 6 hours
```

**2. Recovery Procedures :**

**PostgreSQL Full Restore :**
```bash
# Stop application
docker-compose stop app horizon

# Restore from backup
gunzip < /backups/postgres/linktracker_20260209_020000.sql.gz \
  | psql -h postgres-master -U linktracker_user linktracker

# Restart application
docker-compose start app horizon
```

**PostgreSQL PITR (Point-In-Time Recovery) :**
```bash
# Restore base backup
pg_basebackup -h postgres-master -D /var/lib/postgresql/15/main -U replicator -P

# Configure recovery
cat > /var/lib/postgresql/15/main/recovery.conf <<EOF
restore_command = 'cp /backups/postgres/wal/%f %p'
recovery_target_time = '2026-02-09 14:30:00'
EOF

# Start PostgreSQL (will replay WAL until target time)
pg_ctl start
```

**3. RTO/RPO Targets :**

- **RTO (Recovery Time Objective) :** 1 hour
  - Full restore from backup: ~30 minutes
  - DNS failover + validation: ~20 minutes
  - Communication + coordination: ~10 minutes

- **RPO (Recovery Point Objective) :** 24 hours
  - Daily backups → Max 24h data loss (acceptable for usage personnel)
  - WAL archiving → PITR possible (0 data loss if needed)

### Backup Strategy

**Storage Locations :**
```
┌─────────────────────────────────────────┐
│  Primary: Local disk (/backups/)        │
│  Retention: 30 days                     │
└──────────────────┬──────────────────────┘
                   │ (rsync every 1h)
┌──────────────────┴──────────────────────┐
│  Secondary: S3 bucket (encrypted)       │
│  Retention: 90 days                     │
└──────────────────┬──────────────────────┘
                   │ (lifecycle policy)
┌──────────────────┴──────────────────────┐
│  Tertiary: Glacier Deep Archive         │
│  Retention: 7 years (compliance)        │
└─────────────────────────────────────────┘
```

**Backup Types :**

1. **Full Backup (Daily) :**
   - PostgreSQL: `pg_dump` (compressed)
   - Redis: `SAVE` command (RDB snapshot)
   - Application: Code repository (Git)
   - Size: ~500MB compressed

2. **Incremental Backup (Hourly) :**
   - PostgreSQL WAL files
   - Size: ~50MB/hour

3. **Configuration Backup (Weekly) :**
   - `.env` file (encrypted)
   - Nginx configs
   - Docker Compose files
   - Size: <1MB

### Monitoring & Alerting

**1. Application Monitoring (Laravel Telescope + Sentry) :**

```php
// config/telescope.php
'watchers' => [
    Watchers\QueryWatcher::class => [
        'enabled' => env('TELESCOPE_QUERY_WATCHER', true),
        'slow' => 100,  // Slow query threshold (ms)
    ],
    Watchers\RequestWatcher::class => true,
    Watchers\ExceptionWatcher::class => true,
    Watchers\JobWatcher::class => true,
],
```

**Sentry Error Tracking (Production) :**
```php
// config/sentry.php
'dsn' => env('SENTRY_LARAVEL_DSN'),
'environment' => env('APP_ENV'),
'sample_rate' => 1.0,  // 100% error capture
```

**2. Infrastructure Monitoring (Prometheus + Grafana) :**

```yaml
# docker-compose.monitoring.yml
services:
  prometheus:
    image: prom/prometheus:latest
    volumes:
      - ./prometheus.yml:/etc/prometheus/prometheus.yml
      - prometheus_data:/prometheus
    ports:
      - "9090:9090"

  grafana:
    image: grafana/grafana:latest
    ports:
      - "3000:3000"
    depends_on: [prometheus]

  node-exporter:
    image: prom/node-exporter:latest
    # Expose system metrics (CPU, RAM, disk)

  postgres-exporter:
    image: prometheuscommunity/postgres-exporter:latest
    environment:
      DATA_SOURCE_NAME: "postgresql://linktracker_user:xxx@postgres:5432/linktracker"

  redis-exporter:
    image: oliver006/redis_exporter:latest
    command: --redis.addr=redis:6379
```

**Prometheus Alerts :**
```yaml
# prometheus-alerts.yml
groups:
  - name: linktracker
    rules:
      - alert: HighErrorRate
        expr: rate(http_requests_total{status=~"5.."}[5m]) > 0.05
        for: 5m
        annotations:
          summary: "High error rate detected"

      - alert: DatabaseDown
        expr: up{job="postgres"} == 0
        for: 1m
        annotations:
          summary: "PostgreSQL is down"

      - alert: QueueBacklog
        expr: horizon_queue_size > 5000
        for: 10m
        annotations:
          summary: "Queue backlog exceeds 5000 jobs"

      - alert: SlowQueries
        expr: postgres_slow_queries > 10
        for: 5m
        annotations:
          summary: "Too many slow queries (>100ms)"
```

**3. Uptime Monitoring (External) :**

```yaml
# Uptime Robot configuration
monitors:
  - name: "Link Tracker API Health"
    type: HTTP
    url: "https://linktracker.com/api/v1/health"
    interval: 5  # minutes
    timeout: 10  # seconds
    alert_contacts: ["email@example.com", "slack_webhook"]

  - name: "Link Tracker Dashboard"
    type: HTTP
    url: "https://linktracker.com/"
    interval: 5
    keyword: "Link Tracker"  # Check page contains expected text
```

**Alert Channels :**
- **Email :** alerts@linktracker.com
- **Slack :** #alerts channel (webhook)
- **SMS :** Twilio (critical alerts only)

---

## Development Architecture

### Code Organization

**Structure Laravel (Modular Monolith) :**

```
linktracker/
├── app/
│   ├── Console/
│   │   └── Commands/
│   │       └── MonitorBacklinks.php
│   ├── Exceptions/
│   │   ├── SsrfException.php
│   │   └── CircuitBreakerOpenException.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AuthController.php
│   │   │   ├── ProjectController.php
│   │   │   ├── BacklinkController.php
│   │   │   ├── DashboardController.php
│   │   │   ├── SeoMetricsController.php
│   │   │   ├── BacklinkOrderController.php
│   │   │   └── HealthController.php
│   │   ├── Middleware/
│   │   │   └── EnsureUserOwnsResource.php
│   │   ├── Requests/
│   │   │   ├── StoreBacklinkRequest.php
│   │   │   └── UpdateBacklinkRequest.php
│   │   └── Resources/
│   │       ├── ProjectResource.php
│   │       ├── BacklinkResource.php
│   │       └── BacklinkCheckResource.php
│   ├── Jobs/
│   │   ├── CheckBacklink.php
│   │   ├── MonitorProjectBacklinks.php
│   │   ├── FetchSeoMetrics.php
│   │   ├── PlaceBacklinkOrder.php
│   │   └── SendAlertNotification.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── Project.php
│   │   ├── Backlink.php
│   │   ├── BacklinkCheck.php
│   │   ├── Alert.php
│   │   ├── SeoMetric.php
│   │   └── BacklinkOrder.php
│   ├── Observers/
│   │   ├── BacklinkObserver.php
│   │   └── ProjectObserver.php
│   ├── Policies/
│   │   ├── ProjectPolicy.php
│   │   └── BacklinkPolicy.php
│   ├── Services/
│   │   ├── BacklinkMonitor/
│   │   │   ├── BacklinkChecker.php
│   │   │   ├── BacklinkAnalyzer.php
│   │   │   └── UrlValidator.php
│   │   ├── SeoMetrics/
│   │   │   ├── SeoMetricsInterface.php
│   │   │   ├── MozProvider.php
│   │   │   ├── AhrefsProvider.php
│   │   │   └── CustomProvider.php
│   │   ├── BacklinkPlatforms/
│   │   │   ├── PlatformInterface.php
│   │   │   ├── NextLevelProvider.php
│   │   │   └── SeoClercProvider.php
│   │   ├── Dashboard/
│   │   │   └── DashboardService.php
│   │   ├── Alerts/
│   │   │   └── AlertService.php
│   │   └── Security/
│   │       └── CircuitBreaker.php
│   └── Providers/
│       ├── AppServiceProvider.php
│       ├── AuthServiceProvider.php
│       └── EventServiceProvider.php
├── config/
│   ├── app.php
│   ├── database.php
│   ├── horizon.php
│   ├── sanctum.php
│   └── services.php
├── database/
│   ├── migrations/
│   │   ├── 2026_02_01_000001_create_users_table.php
│   │   ├── 2026_02_01_000002_create_projects_table.php
│   │   ├── 2026_02_01_000003_create_backlinks_table.php
│   │   ├── 2026_02_01_000004_create_backlink_checks_table.php
│   │   ├── 2026_02_01_000005_create_alerts_table.php
│   │   ├── 2026_02_01_000006_create_seo_metrics_table.php
│   │   └── 2026_02_01_000007_create_backlink_orders_table.php
│   ├── seeders/
│   │   └── DemoDataSeeder.php
│   └── factories/
│       ├── ProjectFactory.php
│       └── BacklinkFactory.php
├── resources/
│   ├── js/
│   │   ├── app.js
│   │   ├── router/
│   │   │   └── index.js
│   │   ├── stores/
│   │   │   ├── auth.js
│   │   │   ├── projects.js
│   │   │   └── backlinks.js
│   │   ├── components/
│   │   │   ├── Dashboard/
│   │   │   │   ├── DashboardOverview.vue
│   │   │   │   ├── BacklinkChart.vue
│   │   │   │   └── AlertsList.vue
│   │   │   ├── Projects/
│   │   │   │   ├── ProjectList.vue
│   │   │   │   └── ProjectForm.vue
│   │   │   ├── Backlinks/
│   │   │   │   ├── BacklinkTable.vue
│   │   │   │   ├── BacklinkDetail.vue
│   │   │   │   └── BacklinkForm.vue
│   │   │   └── Orders/
│   │   │       ├── OrderForm.vue
│   │   │       └── OrderList.vue
│   │   └── utils/
│   │       ├── api.js
│   │       └── helpers.js
│   ├── css/
│   │   └── app.css
│   └── views/
│       └── app.blade.php
├── routes/
│   ├── api.php
│   ├── web.php
│   └── console.php
├── tests/
│   ├── Feature/
│   │   ├── BacklinkTest.php
│   │   ├── ProjectTest.php
│   │   ├── DashboardTest.php
│   │   └── AuthTest.php
│   └── Unit/
│       ├── BacklinkCheckerTest.php
│       ├── UrlValidatorTest.php
│       ├── CircuitBreakerTest.php
│       └── BacklinkAnalyzerTest.php
├── docker-compose.yml
├── Dockerfile
├── .github/
│   └── workflows/
│       └── ci.yml
├── .env.example
├── composer.json
├── package.json
└── README.md
```

### Module Structure

**Pattern Service Layer :**

```php
// Controller (thin, delegates to services)
class BacklinkController extends Controller
{
    public function __construct(
        private BacklinkChecker $checker,
        private DashboardService $dashboard
    ) {}

    public function check(Backlink $backlink)
    {
        $this->authorize('view', $backlink);
        CheckBacklink::dispatch($backlink);
        return response()->json(['message' => 'Check scheduled']);
    }
}

// Service (business logic)
class BacklinkChecker
{
    public function checkBacklink(Backlink $backlink): BacklinkCheckResult
    {
        // Complex logic here
    }
}

// Model (data access)
class Backlink extends Model
{
    // Relations, scopes, accessors only
}
```

### Testing Strategy

**Test Pyramid :**

```
        /\
       /  \         E2E Tests (5%)
      /    \        - Playwright (future)
     /------\       - 10 critical user flows
    /        \
   /  Unit    \     Integration Tests (25%)
  /  Tests     \    - Laravel HTTP tests
 /    (70%)     \   - Database interactions
/________________\  - 45 integration tests

                    Unit Tests (70%)
                    - Services, Helpers
                    - 126 unit tests
```

**Total Tests : 180+**

**1. Unit Tests (126 tests) :**

```php
// tests/Unit/UrlValidatorTest.php
class UrlValidatorTest extends TestCase
{
    /** @test */
    public function it_blocks_localhost()
    {
        $validator = new UrlValidator();

        $this->expectException(SsrfException::class);
        $validator->validate('http://127.0.0.1/admin');
    }

    /** @test */
    public function it_blocks_private_networks()
    {
        $validator = new UrlValidator();

        $this->expectException(SsrfException::class);
        $validator->validate('http://192.168.1.1/');
    }

    /** @test */
    public function it_allows_public_domains()
    {
        $validator = new UrlValidator();

        $validator->validate('https://example.com/page');
        $this->assertTrue(true);  // No exception thrown
    }
}

// tests/Unit/CircuitBreakerTest.php
class CircuitBreakerTest extends TestCase
{
    /** @test */
    public function it_opens_after_threshold_failures()
    {
        $breaker = new CircuitBreaker();

        // Simulate 5 failures
        for ($i = 0; $i < 5; $i++) {
            try {
                $breaker->execute('test', fn() => throw new Exception('fail'));
            } catch (Exception $e) {}
        }

        // 6th attempt should throw CircuitBreakerOpenException
        $this->expectException(CircuitBreakerOpenException::class);
        $breaker->execute('test', fn() => 'success');
    }
}
```

**2. Integration Tests (45 tests) :**

```php
// tests/Feature/BacklinkTest.php
class BacklinkTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_create_backlink()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->postJson('/api/v1/backlinks', [
            'project_id' => $project->id,
            'source_url' => 'https://example.com/page',
            'target_url' => 'https://mysite.com/',
            'anchor_text' => 'My Site',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('backlinks', [
            'source_url' => 'https://example.com/page',
        ]);
    }

    /** @test */
    public function it_rejects_ssrf_urls()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->postJson('/api/v1/backlinks', [
            'project_id' => $project->id,
            'source_url' => 'http://127.0.0.1/admin',
            'target_url' => 'https://mysite.com/',
        ]);

        $response->assertStatus(422);  // Validation error
        $response->assertJsonValidationErrors('source_url');
    }

    /** @test */
    public function user_cannot_access_others_backlinks()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user2->id]);
        $backlink = Backlink::factory()->create(['project_id' => $project->id]);

        $response = $this->actingAs($user1)->getJson("/api/v1/backlinks/{$backlink->id}");

        $response->assertStatus(403);  // Forbidden
    }
}
```

**3. E2E Tests (10 tests - future with Playwright) :**

```javascript
// tests/e2e/backlink-workflow.spec.js
test('complete backlink monitoring workflow', async ({ page }) => {
  // Login
  await page.goto('https://linktracker.com/login');
  await page.fill('[name=email]', 'user@example.com');
  await page.fill('[name=password]', 'password');
  await page.click('[type=submit]');

  // Create project
  await page.click('text=New Project');
  await page.fill('[name=name]', 'Test Project');
  await page.fill('[name=domain]', 'mysite.com');
  await page.click('text=Create');

  // Add backlink
  await page.click('text=Add Backlink');
  await page.fill('[name=source_url]', 'https://example.com/page');
  await page.fill('[name=target_url]', 'https://mysite.com/');
  await page.click('text=Save');

  // Verify backlink appears in list
  await expect(page.locator('text=example.com')).toBeVisible();
});
```

**Code Coverage Target : 70%**

```bash
# Run tests with coverage
vendor/bin/phpunit --coverage-html coverage/

# CI enforces minimum coverage
if coverage < 70%; then exit 1; fi
```

### CI/CD Pipeline

**GitHub Actions Workflow :**

```yaml
# .github/workflows/ci.yml
name: CI/CD Pipeline

on:
  push:
    branches: [main, develop]
  pull_request:
    branches: [main]

jobs:
  test:
    runs-on: ubuntu-latest
    services:
      postgres:
        image: postgres:15
        env:
          POSTGRES_DB: linktracker_test
          POSTGRES_USER: test_user
          POSTGRES_PASSWORD: test_password
        ports:
          - 5432:5432

      redis:
        image: redis:7.2
        ports:
          - 6379:6379

    steps:
      - uses: actions/checkout@v3

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          extensions: pdo, pdo_pgsql, redis
          coverage: xdebug

      - name: Install Dependencies
        run: composer install --no-interaction --prefer-dist

      - name: Copy .env
        run: cp .env.ci .env

      - name: Generate Key
        run: php artisan key:generate

      - name: Run Migrations
        run: php artisan migrate --force

      - name: Run Tests
        run: vendor/bin/phpunit --coverage-clover coverage.xml

      - name: Check Coverage
        run: |
          COVERAGE=$(php -r "echo round((simplexml_load_file('coverage.xml')->project->metrics['elements']-simplexml_load_file('coverage.xml')->project->metrics['coveredelements'])/simplexml_load_file('coverage.xml')->project->metrics['elements']*100, 2);")
          if (( $(echo "$COVERAGE < 70" | bc -l) )); then
            echo "Coverage $COVERAGE% is below 70%"
            exit 1
          fi

      - name: PHP CS Fixer
        run: vendor/bin/php-cs-fixer fix --dry-run --diff

      - name: PHPStan
        run: vendor/bin/phpstan analyse --level=6

  build:
    needs: test
    runs-on: ubuntu-latest
    if: github.ref == 'refs/heads/main'

    steps:
      - uses: actions/checkout@v3

      - name: Build Docker Image
        run: docker build -t linktracker:${{ github.sha }} .

      - name: Push to Registry
        run: |
          echo ${{ secrets.DOCKER_PASSWORD }} | docker login -u ${{ secrets.DOCKER_USERNAME }} --password-stdin
          docker tag linktracker:${{ github.sha }} linktracker:latest
          docker push linktracker:${{ github.sha }}
          docker push linktracker:latest

  deploy:
    needs: build
    runs-on: ubuntu-latest
    if: github.ref == 'refs/heads/main'

    steps:
      - name: Deploy to Production (Blue-Green)
        uses: appleboy/ssh-action@master
        with:
          host: ${{ secrets.PRODUCTION_HOST }}
          username: ${{ secrets.SSH_USERNAME }}
          key: ${{ secrets.SSH_PRIVATE_KEY }}
          script: |
            cd /opt/linktracker
            ./scripts/blue-green-deploy.sh ${{ github.sha }}
```

---

## Deployment Architecture

### Environments

**1. Development (Local) :**

```yaml
# docker-compose.dev.yml
services:
  app:
    build: .
    volumes:
      - .:/var/www/html  # Live code reload
    environment:
      APP_ENV: local
      APP_DEBUG: true
      TELESCOPE_ENABLED: true

  postgres:
    image: postgres:15
    environment:
      POSTGRES_DB: linktracker_dev

  redis:
    image: redis:7.2

  mailhog:
    image: mailhog/mailhog  # Email testing
    ports:
      - "8025:8025"
```

**2. Staging :**

```yaml
# Production-like environment
services:
  app:
    image: linktracker:staging
    environment:
      APP_ENV: staging
      APP_DEBUG: false
      SENTRY_DSN: ${STAGING_SENTRY_DSN}

  postgres:
    image: postgres:15
    # Smaller instance (4GB RAM)

  redis:
    image: redis:7.2
```

**3. Production :**

```yaml
# docker-compose.prod.yml
services:
  app:
    image: linktracker:latest
    deploy:
      replicas: 3
      resources:
        limits:
          cpus: '2'
          memory: 4G
    environment:
      APP_ENV: production
      APP_DEBUG: false
      SENTRY_DSN: ${PRODUCTION_SENTRY_DSN}

  postgres-master:
    image: postgres:15
    volumes:
      - postgres_data:/var/lib/postgresql/data
    # 8GB RAM allocated

  postgres-replica:
    image: postgres:15

  redis-sentinel-1:
    image: redis:7.2
  redis-sentinel-2:
    image: redis:7.2
  redis-sentinel-3:
    image: redis:7.2

  nginx:
    image: nginx:latest
    ports:
      - "443:443"
    volumes:
      - ./nginx-prod.conf:/etc/nginx/nginx.conf
      - /etc/letsencrypt:/etc/letsencrypt
```

### Deployment Strategy

**Blue-Green Deployment (Zero-Downtime) :**

```bash
#!/bin/bash
# scripts/blue-green-deploy.sh

NEW_VERSION=$1  # e.g., "abc123" (Git SHA)
CURRENT_ENV=$(cat /tmp/linktracker-current-env)  # "blue" or "green"

if [ "$CURRENT_ENV" == "blue" ]; then
    NEW_ENV="green"
    OLD_ENV="blue"
else
    NEW_ENV="blue"
    OLD_ENV="green"
fi

echo "Deploying version $NEW_VERSION to $NEW_ENV environment"

# 1. Pull new Docker image
docker pull linktracker:$NEW_VERSION

# 2. Run database migrations on NEW environment
docker-compose -f docker-compose.$NEW_ENV.yml run --rm app php artisan migrate --force

# 3. Start NEW environment containers
docker-compose -f docker-compose.$NEW_ENV.yml up -d

# 4. Wait for health checks
for i in {1..30}; do
    STATUS=$(curl -s http://localhost:8080/api/v1/health | jq -r '.status')
    if [ "$STATUS" == "healthy" ]; then
        echo "New environment is healthy"
        break
    fi
    sleep 2
done

if [ "$STATUS" != "healthy" ]; then
    echo "Health check failed, rolling back"
    docker-compose -f docker-compose.$NEW_ENV.yml down
    exit 1
fi

# 5. Switch Nginx load balancer to NEW environment
cp nginx.$NEW_ENV.conf /etc/nginx/sites-enabled/linktracker.conf
nginx -s reload

echo "Traffic switched to $NEW_ENV environment"

# 6. Wait 5 minutes for connections to drain
sleep 300

# 7. Shutdown OLD environment
docker-compose -f docker-compose.$OLD_ENV.yml down

# 8. Update current environment marker
echo $NEW_ENV > /tmp/linktracker-current-env

echo "Deployment complete. Old environment ($OLD_ENV) stopped."
```

**Rollback Procedure :**

```bash
#!/bin/bash
# scripts/rollback.sh

# Simply switch back to previous environment
CURRENT_ENV=$(cat /tmp/linktracker-current-env)
if [ "$CURRENT_ENV" == "blue" ]; then
    ROLLBACK_ENV="green"
else
    ROLLBACK_ENV="blue"
fi

# Switch Nginx
cp nginx.$ROLLBACK_ENV.conf /etc/nginx/sites-enabled/linktracker.conf
nginx -s reload

echo "Rolled back to $ROLLBACK_ENV environment"
```

### Infrastructure as Code

**Terraform (Optional - for Cloud Deployment) :**

```hcl
# terraform/main.tf
provider "digitalocean" {
  token = var.do_token
}

resource "digitalocean_droplet" "linktracker_app" {
  count    = 3
  name     = "linktracker-app-${count.index + 1}"
  size     = "s-4vcpu-8gb"
  image    = "ubuntu-22-04-x64"
  region   = "fra1"
  ssh_keys = [var.ssh_key_id]

  tags = ["production", "app"]
}

resource "digitalocean_droplet" "linktracker_db" {
  name     = "linktracker-db-master"
  size     = "g-2vcpu-8gb"
  image    = "ubuntu-22-04-x64"
  region   = "fra1"
  ssh_keys = [var.ssh_key_id]

  tags = ["production", "database"]
}

resource "digitalocean_loadbalancer" "public" {
  name   = "linktracker-lb"
  region = "fra1"

  forwarding_rule {
    entry_port     = 443
    entry_protocol = "https"

    target_port     = 443
    target_protocol = "https"

    certificate_id = digitalocean_certificate.cert.id
  }

  healthcheck {
    port     = 443
    protocol = "https"
    path     = "/api/v1/health"
  }

  droplet_ids = digitalocean_droplet.linktracker_app[*].id
}
```

---

## Requirements Traceability

### Functional Requirements Coverage

(Matrice complète déjà fournie dans Partie 12 - Section 12.1)

**Résumé :**
- 32/32 Functional Requirements couverts (100%)
- Chaque FR mappé à au moins 1 composant architectural
- Tous les FRs prioritaires (Must Have) implémentés dans Phase 1

### Non-Functional Requirements Coverage

(Matrice complète déjà fournie dans Partie 12 - Section 12.2)

**Résumé :**
- 12/12 Non-Functional Requirements adressés (100%)
- Solutions architecturales validées pour chaque NFR
- Tests de validation définis pour tous les NFRs

---

## Trade-offs & Decision Log

(Trade-offs détaillés déjà fournis dans Partie 12 - Section 12.3)

**Résumé des Décisions Clés :**

1. **Monolithe vs Microservices** → Monolithe (simplicité, couplage métier fort)
2. **PostgreSQL vs MySQL** → PostgreSQL (JSONB, partitioning, PITR)
3. **Sanctum vs Passport** → Sanctum (simplicité SPA, pas besoin OAuth public)
4. **Redis Queue vs Database Queue** → Redis (performance 10-100x supérieure)
5. **APIs SEO vs Scraping** → Hybride (APIs prioritaires + fallback CustomProvider)
6. **Partitioning vs Single Table** → Partitioning (2.19M records/an)
7. **Blue-Green vs Rolling Update** → Blue-Green (rollback instantané)

---

## Open Issues & Risks

### Open Issues

1. **SEO Metrics Provider Selection :**
   - **Issue :** Moz ($99/mois) vs Ahrefs ($99/mois) - quel provider choisir ?
   - **Impact :** Budget mensuel récurrent
   - **Résolution :** Décision utilisateur pendant Phase 4 (Sprint Planning)

2. **Notification Channels :**
   - **Issue :** Email + Slack suffisant ou ajouter SMS (Twilio $0.0075/SMS) ?
   - **Impact :** Coût supplémentaire ~$50/mois pour 6,000 SMS
   - **Résolution :** Email uniquement pour v1, Slack/SMS optionnel v2

3. **Backup Offsite Location :**
   - **Issue :** S3 (AWS) vs Backblaze B2 (50% cheaper) ?
   - **Impact :** Coût stockage ~$20/mois (S3) vs ~$10/mois (B2)
   - **Résolution :** Backblaze B2 pour cost-effectiveness

### Risks

(Matrice de risques complète dans Partie 12 - Section 12.4)

**Top 3 Risques Critiques :**

1. **SSRF Attack (P: Moyenne, I: Critique)**
   - Mitigation: UrlValidator avec IP blacklist + redirect validation

2. **API Rate Limit (P: Haute, I: Moyen)**
   - Mitigation: Circuit breaker + cache 24h + fallback provider

3. **Data Loss (P: Faible, I: Critique)**
   - Mitigation: Backups quotidiens + PITR + replication

---

## Assumptions & Constraints

### Assumptions

1. **Volume :** Max 5,000 backlinks, 150,000 checks/jour (usage personnel)
2. **User Base :** Single user (pas multi-tenant dans v1)
3. **Growth :** Croissance linéaire (+500 backlinks/an)
4. **APIs Externes :** Moz/Ahrefs maintiennent compatibilité API v2+
5. **Infrastructure :** VPS managed disponible (DigitalOcean, Linode, Vultr)
6. **Budget :** ~$200/mois (VPS $80, APIs $99, backups $20)

### Constraints

1. **Budget Limité :** Pas de solutions enterprise (Kubernetes, CloudFlare Enterprise)
2. **Single Developer :** Architecture doit être maintenable par 1 personne
3. **Pas de Mobile App :** Frontend web uniquement (responsive)
4. **Data Residency :** Aucune contrainte RGPD (usage personnel)
5. **Uptime Target :** 99.5% (acceptable ~4h downtime/mois)
6. **Language :** Backend/Frontend en anglais (code), docs en français

---

## Future Considerations

### Phase 2 Enhancements (v2.0)

1. **Multi-Tenant Support :**
   - Add `tenant_id` column to all tables
   - Row-level security PostgreSQL policies
   - Subscription billing (Stripe integration)

2. **Advanced Analytics :**
   - Competitor backlink analysis (scrape competitor sites)
   - Backlink quality score (ML model based on DA, TF, CF)
   - Predictive alerts (AI predicts which backlinks likely to be lost)

3. **Automation :**
   - Auto-purchase backlinks when DA drops below threshold
   - Auto-email outreach for broken backlinks
   - Integration with link-building outreach tools (Hunter.io, Pitchbox)

4. **Mobile App :**
   - React Native app (iOS + Android)
   - Push notifications for alerts
   - Offline mode with sync

5. **Additional Integrations :**
   - Google Search Console API (import existing backlinks)
   - Google Analytics API (track referral traffic from backlinks)
   - Slack/Discord bot ("@linktracker show stats")

### Scalability Roadmap

**Current Capacity (VPS 8GB) :**
- 5,000 backlinks
- 100 checks/minute
- ~10,000 backlinks/project

**Phase 1 Scaling (Vertical) :**
- VPS 32GB RAM, 16 vCPUs
- 50,000 backlinks
- 500 checks/minute

**Phase 2 Scaling (Horizontal) :**
- Kubernetes cluster (3 nodes)
- PostgreSQL sharding (by project_id)
- Redis Cluster (3 masters, 3 replicas)
- 500,000+ backlinks
- 5,000 checks/minute

### Technical Debt to Address

1. **Code Refactoring :**
   - Extract BacklinkChecker HTML parsing to dedicated library
   - Standardize error responses (JSON:API spec)

2. **Testing :**
   - Add E2E tests (Playwright) for critical flows
   - Increase unit test coverage to 80%

3. **Documentation :**
   - API documentation (OpenAPI/Swagger)
   - Architecture Decision Records (ADR) for key decisions

---

## Approval & Sign-off

**Review Status:**
- [ ] Technical Lead (axel)
- [ ] Product Owner (axel)
- [ ] Security Architect (N/A - personal project)
- [ ] DevOps Lead (axel)

**Approval Date :** _À compléter après validation_

---

## Revision History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0 | 2026-02-09 | axel | Initial architecture document |

---

## Next Steps

### Phase 4: Sprint Planning & Implementation

Run `/sprint-planning` to:
- Break epics into detailed user stories
- Estimate story complexity (story points)
- Plan sprint iterations (2-week sprints)
- Begin implementation following this architectural blueprint

**Key Implementation Principles:**
1. Follow component boundaries defined in this document
2. Implement NFR solutions as specified (SSRF protection, circuit breaker, etc.)
3. Use technology stack as defined (Laravel 10+, Vue.js 3, PostgreSQL 15+)
4. Follow API contracts exactly (RESTful, JSON format)
5. Adhere to security and performance guidelines (Sanctum, cache strategies)
6. Maintain test coverage ≥70% (unit + integration tests)
7. Use Blue-Green deployment for zero-downtime releases

**Recommended Sprint Sequence :**

**Sprint 1 (2 weeks) :** Foundation
- Setup Laravel + Vue.js project structure
- Database migrations (7 tables)
- Sanctum authentication
- Project CRUD (FR-001 to FR-004)

**Sprint 2 (2 weeks) :** Backlink Management
- Backlink CRUD (FR-005 to FR-008)
- CSV Import/Export (FR-009, FR-010)
- UrlValidator service (NFR-004: SSRF protection)

**Sprint 3 (2 weeks) :** Monitoring Engine
- BacklinkChecker service (FR-011)
- CheckBacklink job + Horizon setup (NFR-002)
- BacklinkAnalyzer + Alert system (FR-012, FR-016)

**Sprint 4 (2 weeks) :** SEO Metrics & Orders
- Moz/Ahrefs integration (FR-020, FR-021)
- Circuit breaker + cache (NFR-008)
- Backlink orders (FR-024, FR-025)

**Sprint 5 (2 weeks) :** Dashboard & Frontend
- Dashboard stats service (FR-028, FR-029)
- Vue.js components (Dashboard, Backlinks, Alerts)
- Charts (ApexCharts integration)

**Sprint 6 (2 weeks) :** Testing & Deployment
- Complete test coverage (70%+)
- CI/CD pipeline (GitHub Actions)
- Blue-Green deployment scripts
- Production deployment

**Estimated Total :** 12 weeks (3 mois) pour MVP complet

---

**This document was created using BMAD Method v6 - Phase 3 (Solutioning)**

*To continue: Run `/workflow-status` to see your progress and next recommended workflow.*

---

## Appendix A: Technology Evaluation Matrix

| Technology | Alternatives Considered | Score | Rationale |
|------------|-------------------------|-------|-----------|
| **Laravel 10+** | Symfony, Django, Express.js | 9/10 | Écosystème complet (Eloquent, Horizon, Sanctum), developer experience excellent, queue system natif |
| **Vue.js 3** | React, Angular, Svelte | 8/10 | Composition API moderne, performance optimale, courbe apprentissage douce, ecosystem mature |
| **PostgreSQL 15+** | MySQL, MongoDB, SQLite | 9/10 | JSONB natif, partitioning avancé, PITR, performance agrégations, open-source |
| **Redis 7.2** | Memcached, DynamoDB, File cache | 10/10 | In-memory performance, dual usage (cache+queue), Sentinel HA, persistence AOF |
| **Laravel Horizon** | Supervisor, Bull (Node), RabbitMQ | 9/10 | Dashboard intégré, auto-scaling workers, métriques built-in, retry logic automatique |
| **Tailwind CSS 4** | Bootstrap, Material UI, Bulma | 9/10 | Utility-first rapide, purge automatique, responsive built-in, customizable |
| **ApexCharts** | Chart.js, D3.js, Highcharts | 8/10 | Charts interactifs, responsive, animations fluides, Vue integration simple |
| **Guzzle HTTP** | cURL, Symfony HTTP Client | 8/10 | PSR-7/18 compliant, middleware support, promise-based async, widely adopted |
| **Laravel Sanctum** | Passport, JWT, Auth0 | 9/10 | Simplicité SPA, CSRF protection natif, session-based secure, pas overhead OAuth |
| **Docker** | Podman, LXC, bare metal | 9/10 | Isolation services, environnements consistants, orchestration future (K8s ready) |

**Scoring Criteria :**
- 1-3: Inadapté (missing features, performance issues)
- 4-6: Acceptable (fonctionne mais limitations)
- 7-8: Bon choix (répond aux besoins, compromis mineurs)
- 9-10: Excellent (optimal pour use case, future-proof)

---

## Appendix B: Capacity Planning

### Current Capacity (VPS 8GB RAM, 4 vCPUs)

**Workload Estimations :**

| Metric | Current | 6 Months | 1 Year | Scaling Trigger |
|--------|---------|----------|--------|-----------------|
| **Backlinks** | 500 | 2,000 | 5,000 | 10,000 backlinks |
| **Checks/Day** | 36,000 | 72,000 | 144,000 | 500,000 checks/day |
| **Users** | 1 | 1 | 1 | 10 users (multi-tenant) |
| **DB Size** | 2GB | 10GB | 25GB | 100GB |
| **Redis Memory** | 500MB | 1GB | 2GB | 8GB |
| **API Requests/Day** | 1,000 | 5,000 | 10,000 | 100,000 req/day |

**Resource Allocation (Current) :**

```
┌─────────────────────────────────────────┐
│  VPS Total: 8GB RAM, 4 vCPUs            │
├─────────────────────────────────────────┤
│  PostgreSQL:  4GB RAM, 2 vCPUs          │
│  Redis:       2GB RAM, 0.5 vCPUs        │
│  Laravel:     1.5GB RAM, 1 vCPU         │
│  Nginx:       0.3GB RAM, 0.3 vCPUs      │
│  System:      0.2GB RAM, 0.2 vCPUs      │
└─────────────────────────────────────────┘

Utilization: 80% RAM, 70% CPU (healthy headroom)
```

**Growth Projections (1 Year) :**

- **Database Growth :** 25GB (5,000 backlinks × 5MB metadata + 2.19M checks × 500 bytes)
- **Redis Growth :** 2GB (cache 10,000 keys × 200KB avg)
- **Storage Growth :** 30GB total (DB + backups + logs)

**Scaling Thresholds :**

| Trigger | Action | Cost Impact |
|---------|--------|-------------|
| DB > 50GB | Add read replica | +$40/month |
| Checks > 500K/day | Scale to VPS 16GB | +$80/month |
| Users > 10 | Migrate to K8s cluster | +$300/month |

---

## Appendix C: Cost Estimation

### Monthly Operating Costs (Year 1)

**Infrastructure :**

| Item | Provider | Specs | Cost/Month |
|------|----------|-------|------------|
| **VPS Production** | DigitalOcean | 8GB RAM, 4 vCPUs, 160GB SSD | $80 |
| **VPS Staging** | DigitalOcean | 4GB RAM, 2 vCPUs, 80GB SSD | $40 |
| **Backup Storage** | Backblaze B2 | 50GB | $10 |
| **Domain + SSL** | Namecheap + Let's Encrypt | linktracker.com | $1 |
| **Monitoring** | UptimeRobot Free | 50 monitors | $0 |

**Subtotal Infrastructure :** $131/month

**Third-Party APIs :**

| Service | Plan | Usage | Cost/Month |
|---------|------|-------|------------|
| **Moz API** | Medium | 150,000 queries/month | $99 |
| **Ahrefs API** | Lite | 500 req/day | $99 (optional) |
| **SendGrid** | Free | 100 emails/day | $0 |
| **Sentry** | Developer | 5K errors/month | $0 |

**Subtotal APIs :** $99/month (Moz only)

**Total Monthly Cost :** $230/month ($2,760/year)

**Total First Year Cost (with setup) :**
- Monthly recurring: $2,760
- Setup costs: ~$500 (development time, testing tools)
- **Total Year 1 :** ~$3,260

**Cost Optimization Opportunities :**

1. **Skip Ahrefs initially** → Save $99/month (use Moz only + CustomProvider fallback)
2. **Use Backblaze B2 instead of S3** → Save $10/month
3. **Self-host Monitoring (Prometheus)** → Save $0 (already free UptimeRobot)

**Optimized Monthly Cost :** $131/month infrastructure + $99 APIs = **$230/month**

---

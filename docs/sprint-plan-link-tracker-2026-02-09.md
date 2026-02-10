# Sprint Plan: Link Tracker

**Date:** 2026-02-09
**Scrum Master:** axel (Claude Sonnet 4.5)
**Project Level:** 4 (Enterprise - 40+ stories)
**Total Stories:** 72
**Total Points:** 236 points
**Planned Sprints:** 6 sprints

---

## Executive Summary

Ce sprint plan décompose les 12 epics du PRD Link Tracker en 72 user stories détaillées, estimées à 236 story points au total. L'implémentation est planifiée sur 6 sprints de 2 semaines (12 semaines/3 mois) avec une équipe de 1 développeur senior.

**Key Metrics:**
- Total Stories: 72
- Total Points: 236 points
- Sprints: 6 (2 semaines chacun)
- Team Capacity: 40 points par sprint
- Target Completion: 12 semaines (3 mois)
- Average Velocity: 39.3 points/sprint

**Sprint Allocation:**
- Sprint 1: Foundation & Projects (40 points)
- Sprint 2: Backlinks Management (40 points)
- Sprint 3: Monitoring Engine (38 points)
- Sprint 4: SEO Metrics & Orders (40 points)
- Sprint 5: Dashboard & Alerts (38 points)
- Sprint 6: Security, Testing & Polish (40 points)

---

## Story Inventory

### EPIC-001: Gestion des Projets de Monitoring (5 stories, 17 points)

#### STORY-001: Setup Laravel + Vue.js Project

**Epic:** EPIC-001
**Priority:** Must Have
**Points:** 5

**User Story:**
En tant que développeur
Je veux initialiser le projet Laravel avec Vue.js et PostgreSQL
Afin d'avoir une base technique solide pour l'implémentation

**Acceptance Criteria:**
- [ ] Laravel 10.x installé avec PHP 8.2+
- [ ] Vue.js 3 + Vue Router 4 configuré avec Vite
- [ ] PostgreSQL 15+ connecté et migrations basiques exécutées
- [ ] Redis configuré pour cache et queue
- [ ] Docker Compose configuré (app, postgres, redis, nginx)
- [ ] .env.example documenté avec toutes les variables
- [ ] README.md avec instructions de setup

**Technical Notes:**
- Suivre l'architecture définie dans `docs/architecture-link-tracker-2026-02-09.md`
- Structure modulaire : app/Services, app/Jobs, app/Policies
- Installer Laravel Horizon pour queue management
- Configurer Tailwind CSS 4

**Dependencies:** Aucune

---

#### STORY-002: Implement User Authentication with Sanctum

**Epic:** EPIC-001 (Auth infrastructure)
**Priority:** Must Have
**Points:** 5

**User Story:**
En tant qu'utilisateur
Je veux pouvoir me connecter de manière sécurisée
Afin de protéger mes données de backlinks

**Acceptance Criteria:**
- [ ] Laravel Sanctum installé et configuré
- [ ] Migration users table créée
- [ ] POST /api/v1/auth/login endpoint (email, password)
- [ ] POST /api/v1/auth/logout endpoint
- [ ] GET /api/v1/auth/user endpoint
- [ ] Session-based authentication (httpOnly cookies)
- [ ] CSRF protection activée
- [ ] Tests: AuthTest.php (login success, login fail, logout)

**Technical Notes:**
```php
// config/sanctum.php
'stateful' => ['localhost', '127.0.0.1', 'linktracker.com']

// Auth via session cookies (SPA)
// Frontend: axios.defaults.withCredentials = true
```

**Dependencies:** STORY-001

---

#### STORY-003: Create Project CRUD API

**Epic:** EPIC-001
**Priority:** Must Have
**Points:** 3

**User Story:**
En tant qu'utilisateur SEO
Je veux créer un projet pour organiser mes backlinks par site web
Afin de séparer clairement mes différents clients/sites

**Acceptance Criteria:**
- [ ] Migration projects table (id, user_id, name, domain, settings JSONB)
- [ ] Model Project avec relations
- [ ] POST /api/v1/projects (name, domain) - avec validation URL
- [ ] GET /api/v1/projects (liste paginée)
- [ ] GET /api/v1/projects/{id}
- [ ] PATCH /api/v1/projects/{id}
- [ ] DELETE /api/v1/projects/{id} (cascade delete backlinks)
- [ ] ProjectPolicy pour authorization
- [ ] Tests: ProjectTest.php (CRUD complet)

**Technical Notes:**
```php
// app/Models/Project.php
protected $fillable = ['name', 'domain', 'settings'];
protected $casts = ['settings' => 'array'];

public function backlinks() {
    return $this->hasMany(Backlink::class);
}
```

**Dependencies:** STORY-002 (need auth)

---

#### STORY-004: Build Projects List Vue Component

**Epic:** EPIC-001
**Priority:** Must Have
**Points:** 3

**User Story:**
En tant qu'utilisateur
Je veux voir tous mes projets dans une liste claire
Afin de naviguer rapidement vers le projet qui m'intéresse

**Acceptance Criteria:**
- [ ] Component ProjectList.vue créé
- [ ] Affiche: nom, domaine, nombre de backlinks, date création
- [ ] Bouton "Nouveau Projet" → ProjectForm.vue
- [ ] Clic sur projet → navigation vers /projects/{id}
- [ ] Boutons édition/suppression avec confirmation
- [ ] Design Tailwind responsive (desktop + mobile)
- [ ] Loading state et error handling

**Technical Notes:**
```vue
<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/utils/api'

const projects = ref([])
const loading = ref(true)

onMounted(async () => {
  const res = await api.get('/api/v1/projects')
  projects.value = res.data.data
  loading.value = false
})
</script>
```

**Dependencies:** STORY-003

---

#### STORY-005: Build Project Create/Edit Form

**Epic:** EPIC-001
**Priority:** Must Have
**Points:** 2

**User Story:**
En tant qu'utilisateur
Je veux un formulaire simple pour créer/éditer un projet
Afin de configurer rapidement mes projets de monitoring

**Acceptance Criteria:**
- [ ] Component ProjectForm.vue créé
- [ ] Champs: name (text), domain (URL)
- [ ] Validation frontend (domain format valide)
- [ ] POST /api/v1/projects au submit (création)
- [ ] PATCH /api/v1/projects/{id} (édition)
- [ ] Messages de succès/erreur
- [ ] Redirection vers liste après succès

**Technical Notes:**
- Réutilisable pour création ET édition (prop `projectId?`)
- Validation: domain doit être URL complète (https://example.com)

**Dependencies:** STORY-003

---

### EPIC-002: Gestion Manuelle des Backlinks (7 stories, 23 points)

#### STORY-006: Create Backlinks Table Migration

**Epic:** EPIC-002
**Priority:** Must Have
**Points:** 2

**User Story:**
En tant que développeur
Je veux créer la table backlinks en base
Afin de stocker tous les backlinks à monitorer

**Acceptance Criteria:**
- [ ] Migration backlinks table créée
- [ ] Colonnes: id, project_id, source_url, target_url, anchor_text, status, http_status, rel_attributes, is_dofollow, first_seen_at, last_checked_at
- [ ] Index sur project_id et status
- [ ] Foreign key project_id → projects.id (cascade delete)
- [ ] Seeder avec 10-20 backlinks de démo

**Technical Notes:**
```sql
CREATE TABLE backlinks (
    id BIGSERIAL PRIMARY KEY,
    project_id BIGINT NOT NULL REFERENCES projects(id) ON DELETE CASCADE,
    source_url TEXT NOT NULL,
    target_url TEXT NOT NULL,
    anchor_text TEXT,
    status VARCHAR(50) DEFAULT 'active',
    http_status INT,
    rel_attributes VARCHAR(100),
    is_dofollow BOOLEAN DEFAULT TRUE,
    first_seen_at TIMESTAMP DEFAULT NOW(),
    last_checked_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);
```

**Dependencies:** STORY-003 (projects table doit exister)

---

#### STORY-007: Implement Backlink CRUD API

**Epic:** EPIC-002
**Priority:** Must Have
**Points:** 3

**User Story:**
En tant qu'utilisateur
Je veux ajouter manuellement des backlinks à surveiller
Afin de commencer à monitorer mes liens existants

**Acceptance Criteria:**
- [ ] Model Backlink avec relations (belongsTo Project)
- [ ] POST /api/v1/backlinks (project_id, source_url, target_url, anchor_text)
- [ ] GET /api/v1/backlinks?project_id=X (filtrable par project, status)
- [ ] GET /api/v1/backlinks/{id}
- [ ] PATCH /api/v1/backlinks/{id}
- [ ] DELETE /api/v1/backlinks/{id}
- [ ] BacklinkPolicy pour authorization
- [ ] Tests: BacklinkTest.php

**Technical Notes:**
- Validation: source_url et target_url doivent être URLs valides
- status: 'active', 'lost', 'changed'

**Dependencies:** STORY-006

---

#### STORY-008: Implement URL Validator Service (SSRF Protection)

**Epic:** EPIC-002 (Security - NFR-004)
**Priority:** Must Have
**Points:** 5

**User Story:**
En tant que développeur
Je veux valider toutes les URLs avant vérification HTTP
Afin de prévenir les attaques SSRF (Server-Side Request Forgery)

**Acceptance Criteria:**
- [ ] Service UrlValidator créé (app/Services/Security/UrlValidator.php)
- [ ] Méthode validate(string $url): void
- [ ] Bloque localhost (127.0.0.0/8)
- [ ] Bloque réseaux privés (10.0.0.0/8, 172.16.0.0/12, 192.168.0.0/16)
- [ ] Bloque link-local (169.254.0.0/16)
- [ ] Whitelist protocoles (http/https uniquement)
- [ ] Exception SsrfException si URL bloquée
- [ ] Validation dans StoreBacklinkRequest
- [ ] Tests: UrlValidatorTest.php (10+ test cases)

**Technical Notes:**
```php
class UrlValidator {
    private const BLOCKED_IPS = [
        '127.0.0.0/8', '10.0.0.0/8', '172.16.0.0/12',
        '192.168.0.0/16', '169.254.0.0/16'
    ];

    public function validate(string $url): void {
        $parsed = parse_url($url);
        if (!in_array($parsed['scheme'], ['http', 'https'])) {
            throw new InvalidUrlException();
        }
        $ip = gethostbyname($parsed['host']);
        if ($this->isBlocked($ip)) {
            throw new SsrfException("Access to {$ip} blocked");
        }
    }
}
```

**Dependencies:** STORY-007

---

#### STORY-009: Build Backlinks Table Vue Component

**Epic:** EPIC-002
**Priority:** Must Have
**Points:** 5

**User Story:**
En tant qu'utilisateur
Je veux voir tous mes backlinks dans un tableau filtrable
Afin de consulter rapidement l'état de mes liens

**Acceptance Criteria:**
- [ ] Component BacklinkTable.vue créé
- [ ] Colonnes: Source URL, Target URL, Anchor Text, Status, Last Checked, Actions
- [ ] Filtres: par projet, par statut (active/lost/changed)
- [ ] Tri par colonne (clic sur header)
- [ ] Pagination (50 items/page)
- [ ] Badges colorés pour status (vert=active, rouge=lost, orange=changed)
- [ ] Boutons actions: Voir détail, Éditer, Supprimer, Vérifier maintenant
- [ ] Responsive design (table → cards sur mobile)

**Technical Notes:**
- Utiliser ApexCharts pour visualiser distribution statuts (optionnel)
- Cache API calls (5min)

**Dependencies:** STORY-007

---

#### STORY-010: Build Backlink Form Component

**Epic:** EPIC-002
**Priority:** Must Have
**Points:** 2

**User Story:**
En tant qu'utilisateur
Je veux un formulaire pour ajouter/éditer un backlink
Afin de configurer facilement mes backlinks à surveiller

**Acceptance Criteria:**
- [ ] Component BacklinkForm.vue créé
- [ ] Champs: project_id (dropdown), source_url, target_url, anchor_text
- [ ] Validation frontend (URLs valides)
- [ ] POST /api/v1/backlinks au submit
- [ ] Messages succès/erreur avec affichage SSRF si bloqué
- [ ] Redirection vers table après succès

**Technical Notes:**
- Dropdown projects: fetch GET /api/v1/projects au mount
- Afficher erreur claire si SSRF: "Cette URL est bloquée pour des raisons de sécurité"

**Dependencies:** STORY-007, STORY-008

---

#### STORY-011: Implement CSV Import for Backlinks

**Epic:** EPIC-002
**Priority:** Must Have
**Points:** 3

**User Story:**
En tant qu'utilisateur ayant déjà une liste de backlinks
Je veux importer en masse un fichier CSV
Afin de démarrer rapidement sans saisie manuelle

**Acceptance Criteria:**
- [ ] POST /api/v1/backlinks/import endpoint
- [ ] Accept multipart/form-data (CSV file)
- [ ] Format CSV: source_url, target_url, anchor_text (headers requis)
- [ ] Validation de chaque ligne (URLs valides, SSRF check)
- [ ] Job asynchrone BulkImportBacklinks si >50 lignes
- [ ] Response: {imported: X, failed: Y, errors: [...]}
- [ ] Tests: CSV valide, CSV avec erreurs, CSV vide

**Technical Notes:**
```php
// app/Jobs/BulkImportBacklinks.php
foreach ($rows as $row) {
    try {
        $this->urlValidator->validate($row['source_url']);
        Backlink::create([...]);
        $imported++;
    } catch (Exception $e) {
        $failed++;
        $errors[] = "Row {$index}: {$e->getMessage()}";
    }
}
```

**Dependencies:** STORY-007, STORY-008

---

#### STORY-012: Build CSV Import UI Component

**Epic:** EPIC-002
**Priority:** Must Have
**Points:** 3

**User Story:**
En tant qu'utilisateur
Je veux une interface pour uploader mon fichier CSV
Afin d'importer facilement mes backlinks existants

**Acceptance Criteria:**
- [ ] Component BacklinkImport.vue créé
- [ ] File input (accept=".csv")
- [ ] Preview des 5 premières lignes avant import
- [ ] Progress bar pendant upload
- [ ] Affichage résultats: X importés, Y échoués
- [ ] Liste des erreurs si échecs (scrollable)
- [ ] Bouton "Télécharger template CSV"

**Technical Notes:**
- Template CSV exemple: source_url,target_url,anchor_text
- Utiliser FileReader API pour preview

**Dependencies:** STORY-011

---

### EPIC-003: Moteur de Monitoring Automatique (9 stories, 38 points)

#### STORY-013: Create BacklinkChecks Table (Partitioned)

**Epic:** EPIC-003
**Priority:** Must Have
**Points:** 3

**User Story:**
En tant que développeur
Je veux une table partitionnée pour stocker l'historique des vérifications
Afin de gérer efficacement 2M+ checks par an

**Acceptance Criteria:**
- [ ] Migration backlink_checks table (partitioned by checked_at)
- [ ] Colonnes: id, backlink_id, checked_at, http_status, is_present, rel_attributes, anchor_text, response_time
- [ ] Partition initiale pour mois courant (2026-02)
- [ ] Script bash pour créer partitions futures (cron monthly)
- [ ] Index sur backlink_id et checked_at

**Technical Notes:**
```sql
CREATE TABLE backlink_checks (
    id BIGSERIAL,
    backlink_id BIGINT NOT NULL,
    checked_at TIMESTAMP NOT NULL DEFAULT NOW(),
    http_status INT NOT NULL,
    is_present BOOLEAN NOT NULL,
    rel_attributes VARCHAR(100),
    anchor_text TEXT,
    response_time FLOAT,
    created_at TIMESTAMP DEFAULT NOW(),
    PRIMARY KEY (id, checked_at)
) PARTITION BY RANGE (checked_at);

CREATE TABLE backlink_checks_2026_02 PARTITION OF backlink_checks
    FOR VALUES FROM ('2026-02-01') TO ('2026-03-01');
```

**Dependencies:** STORY-006 (backlinks table)

---

#### STORY-014: Implement BacklinkChecker Service

**Epic:** EPIC-003
**Priority:** Must Have
**Points:** 8

**User Story:**
En tant que système
Je veux vérifier automatiquement la présence d'un backlink
Afin de détecter s'il est actif, perdu, ou modifié

**Acceptance Criteria:**
- [ ] Service BacklinkChecker créé (app/Services/BacklinkMonitor/)
- [ ] Méthode checkBacklink(Backlink $backlink): BacklinkCheckResult
- [ ] HTTP GET sur source_url (timeout 30s, user-agent custom)
- [ ] Parse HTML avec DOMDocument pour trouver link vers target_url
- [ ] Détection rel="nofollow" vs dofollow
- [ ] Extraction anchor text
- [ ] Mesure response_time
- [ ] Gestion erreurs HTTP (404, 500, timeout)
- [ ] Tests: BacklinkCheckerTest.php (10+ scenarios)

**Technical Notes:**
```php
class BacklinkChecker {
    public function checkBacklink(Backlink $backlink): BacklinkCheckResult {
        $this->urlValidator->validate($backlink->source_url);

        $response = $this->httpClient->get($backlink->source_url, [
            'timeout' => 30,
            'headers' => ['User-Agent' => 'LinkTrackerBot/1.0'],
        ]);

        $html = $response->getBody();
        $linkData = $this->extractBacklinkData($html, $backlink->target_url);

        return new BacklinkCheckResult([
            'httpStatus' => $response->getStatusCode(),
            'isPresent' => $linkData['is_present'],
            'relAttributes' => $linkData['rel'],
            'anchorText' => $linkData['anchor'],
            'responseTime' => $response->getInfo('total_time'),
        ]);
    }
}
```

**Dependencies:** STORY-008 (UrlValidator), STORY-013

---

#### STORY-015: Implement CheckBacklink Job

**Epic:** EPIC-003
**Priority:** Must Have
**Points:** 5

**User Story:**
En tant que système
Je veux exécuter les vérifications de backlinks de manière asynchrone
Afin de ne pas bloquer l'application pendant les requêtes HTTP

**Acceptance Criteria:**
- [ ] Job CheckBacklink créé (app/Jobs/)
- [ ] Implement ShouldQueue interface
- [ ] Appelle BacklinkChecker::checkBacklink()
- [ ] Crée un BacklinkCheck record
- [ ] Retry 3x en cas d'échec (backoff exponentiel: 10s, 30s, 60s)
- [ ] Timeout 60s
- [ ] failed() method pour logger échecs permanents
- [ ] Tests: Mocking HTTP responses

**Technical Notes:**
```php
class CheckBacklink implements ShouldQueue {
    public $tries = 3;
    public $backoff = [10, 30, 60];
    public $timeout = 60;

    public function handle(BacklinkChecker $checker): void {
        $result = $checker->checkBacklink($this->backlink);

        BacklinkCheck::create([
            'backlink_id' => $this->backlink->id,
            'checked_at' => now(),
            'http_status' => $result->httpStatus,
            'is_present' => $result->isPresent,
            // ...
        ]);

        // Trigger analyzer for change detection
        app(BacklinkAnalyzer::class)->analyzeChanges($this->backlink);
    }
}
```

**Dependencies:** STORY-014

---

#### STORY-016: Setup Laravel Horizon for Queue Management

**Epic:** EPIC-003
**Priority:** Must Have
**Points:** 3

**User Story:**
En tant que développeur
Je veux un dashboard pour monitorer les jobs de vérification
Afin de garantir que le système traite 100 jobs/minute

**Acceptance Criteria:**
- [ ] Laravel Horizon installé (composer require laravel/horizon)
- [ ] config/horizon.php configuré (3-10 workers auto-scaling)
- [ ] 3 queues: high (checks manuels), default (checks auto), low (SEO metrics)
- [ ] Dashboard accessible à /horizon
- [ ] Middleware auth sur /horizon (admin only)
- [ ] Artisan command: php artisan horizon
- [ ] Supervisor config pour production

**Technical Notes:**
```php
// config/horizon.php
'environments' => [
    'production' => [
        'supervisor-1' => [
            'connection' => 'redis',
            'queue' => ['high', 'default', 'low'],
            'balance' => 'auto',
            'minProcesses' => 3,
            'maxProcesses' => 10,
            'tries' => 3,
            'timeout' => 60,
        ],
    ],
],
```

**Dependencies:** STORY-015

---

#### STORY-017: Implement Manual Check Trigger

**Epic:** EPIC-003
**Priority:** Must Have
**Points:** 2

**User Story:**
En tant qu'utilisateur
Je veux déclencher manuellement une vérification d'un backlink
Afin de tester immédiatement après un ajout

**Acceptance Criteria:**
- [ ] POST /api/v1/backlinks/{id}/check endpoint
- [ ] Dispatch CheckBacklink job sur queue 'high' (prioritaire)
- [ ] Response: {message: "Check scheduled", job_id: "xxx"}
- [ ] Rate limit: 10 checks/minute par user
- [ ] Tests: vérifie job dispatché

**Technical Notes:**
```php
public function check(Backlink $backlink) {
    $this->authorize('view', $backlink);

    $job = CheckBacklink::dispatch($backlink)->onQueue('high');

    return response()->json([
        'message' => 'Check scheduled',
        'job_id' => $job->id,
    ]);
}
```

**Dependencies:** STORY-015

---

#### STORY-018: Implement BacklinkAnalyzer Service

**Epic:** EPIC-003
**Priority:** Must Have
**Points:** 5

**User Story:**
En tant que système
Je veux comparer le dernier check avec le précédent
Afin de détecter automatiquement les changements

**Acceptance Criteria:**
- [ ] Service BacklinkAnalyzer créé
- [ ] Méthode analyzeChanges(Backlink $backlink, BacklinkCheck $currentCheck)
- [ ] Récupère le check précédent (dernier avant current)
- [ ] Détecte: backlink lost (présent → absent)
- [ ] Détecte: passage dofollow → nofollow
- [ ] Détecte: changement anchor text
- [ ] Détecte: HTTP status change (200 → 404/500)
- [ ] Dispatch événements: BacklinkStatusChanged
- [ ] Tests: BacklinkAnalyzerTest.php (tous les scénarios)

**Technical Notes:**
```php
class BacklinkAnalyzer {
    public function analyzeChanges(Backlink $backlink, BacklinkCheck $currentCheck): void {
        $lastCheck = BacklinkCheck::where('backlink_id', $backlink->id)
            ->where('id', '<', $currentCheck->id)
            ->latest('checked_at')
            ->first();

        if (!$lastCheck) return; // First check

        if ($lastCheck->is_present && !$currentCheck->is_present) {
            event(new BacklinkLost($backlink));
            $backlink->update(['status' => 'lost']);
        }

        // ... autres détections
    }
}
```

**Dependencies:** STORY-014, STORY-015

---

#### STORY-019: Implement Laravel Scheduler for Automated Checks

**Epic:** EPIC-003
**Priority:** Must Have
**Points:** 3

**User Story:**
En tant que système
Je veux vérifier automatiquement tous les backlinks toutes les 4 heures
Afin d'assurer un monitoring continu sans intervention manuelle

**Acceptance Criteria:**
- [ ] Command MonitorBacklinks créé (app/Console/Commands/)
- [ ] Récupère tous les projets actifs
- [ ] Pour chaque projet, dispatch CheckBacklink pour chaque backlink
- [ ] Schedule configuré (app/Console/Kernel.php)
- [ ] Cron: toutes les 4 heures
- [ ] Prevent overlap (->withoutOverlapping())
- [ ] Logging: nombre de checks lancés

**Technical Notes:**
```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule) {
    $schedule->command('backlinks:monitor')
        ->everyFourHours()
        ->withoutOverlapping()
        ->onSuccess(fn() => Log::info('Monitoring completed'))
        ->onFailure(fn() => Log::error('Monitoring failed'));
}

// app/Console/Commands/MonitorBacklinks.php
public function handle() {
    $count = 0;
    Backlink::where('status', 'active')->chunk(100, function($backlinks) use (&$count) {
        foreach ($backlinks as $backlink) {
            CheckBacklink::dispatch($backlink)->onQueue('default');
            $count++;
        }
    });
    $this->info("Dispatched {$count} check jobs");
}
```

**Dependencies:** STORY-015, STORY-018

---

#### STORY-020: Build Backlink Check History Component

**Epic:** EPIC-003
**Priority:** Should Have
**Points:** 3

**User Story:**
En tant qu'utilisateur
Je veux voir l'historique de vérifications d'un backlink
Afin de comprendre quand et comment il a changé

**Acceptance Criteria:**
- [ ] Component BacklinkCheckHistory.vue créé
- [ ] GET /api/v1/backlinks/{id}/checks?page=1
- [ ] Timeline verticale avec tous les checks
- [ ] Affiche: date, http_status, is_present, rel_attributes, response_time
- [ ] Highlight checks avec changements (bordure colorée)
- [ ] Pagination (20 checks/page)
- [ ] Graphique response_time evolution (ApexCharts)

**Technical Notes:**
- Utiliser partitioning PostgreSQL pour query performante
- Cache 5min

**Dependencies:** STORY-013, STORY-015

---

#### STORY-021: Add "Check Now" Button to Backlink Table

**Epic:** EPIC-003
**Priority:** Must Have
**Points:** 1

**User Story:**
En tant qu'utilisateur
Je veux un bouton "Vérifier maintenant" dans le tableau
Afin de lancer une vérification manuelle rapidement

**Acceptance Criteria:**
- [ ] Bouton "Check Now" ajouté à BacklinkTable.vue
- [ ] POST /api/v1/backlinks/{id}/check au clic
- [ ] Loading spinner pendant requête
- [ ] Toast notification: "Vérification lancée"
- [ ] Désactivé si déjà en cours (état loading)

**Technical Notes:**
- Icon: refresh SVG
- Throttle clicks (max 1 check/5s par backlink)

**Dependencies:** STORY-017, STORY-009

---

### EPIC-004: Système d'Alertes et Notifications (5 stories, 18 points)

#### STORY-022: Create Alerts Table Migration

**Epic:** EPIC-004
**Priority:** Must Have
**Points:** 2

**User Story:**
En tant que développeur
Je veux une table pour stocker les alertes générées
Afin de notifier l'utilisateur des changements critiques

**Acceptance Criteria:**
- [ ] Migration alerts table créée
- [ ] Colonnes: id, backlink_id, type, detected_at, old_value, new_value, is_read
- [ ] Types: 'lost', 'nofollow', 'status_change', 'anchor_change'
- [ ] Index sur backlink_id, is_read, detected_at
- [ ] Foreign key backlink_id → backlinks.id

**Technical Notes:**
```sql
CREATE TABLE alerts (
    id BIGSERIAL PRIMARY KEY,
    backlink_id BIGINT NOT NULL REFERENCES backlinks(id) ON DELETE CASCADE,
    type VARCHAR(50) NOT NULL,
    detected_at TIMESTAMP NOT NULL DEFAULT NOW(),
    old_value TEXT,
    new_value TEXT,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT NOW()
);
```

**Dependencies:** STORY-006

---

#### STORY-023: Implement AlertService

**Epic:** EPIC-004
**Priority:** Must Have
**Points:** 3

**User Story:**
En tant que système
Je veux créer automatiquement des alertes lors de changements
Afin d'informer l'utilisateur des problèmes

**Acceptance Criteria:**
- [ ] Service AlertService créé (app/Services/Alerts/)
- [ ] Méthode createAlert(Backlink, type, oldValue, newValue): Alert
- [ ] Listener BacklinkLost → créeAlert type='lost'
- [ ] Listener BacklinkNofollowed → créeAlert type='nofollow'
- [ ] Listener BacklinkAnchorChanged → créeAlert type='anchor_change'
- [ ] Événements dispatchés depuis BacklinkAnalyzer
- [ ] Tests: AlertServiceTest.php

**Technical Notes:**
```php
class AlertService {
    public function createAlert(Backlink $backlink, string $type, $oldValue, $newValue): Alert {
        return Alert::create([
            'backlink_id' => $backlink->id,
            'type' => $type,
            'detected_at' => now(),
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'is_read' => false,
        ]);
    }
}

// EventServiceProvider
Event::listen(BacklinkLost::class, function($event) {
    app(AlertService::class)->createAlert($event->backlink, 'lost', 'present', 'absent');
});
```

**Dependencies:** STORY-018 (BacklinkAnalyzer events), STORY-022

---

#### STORY-024: Implement Alerts API Endpoints

**Epic:** EPIC-004
**Priority:** Must Have
**Points:** 3

**User Story:**
En tant qu'utilisateur
Je veux consulter mes alertes non lues
Afin de réagir rapidement aux problèmes

**Acceptance Criteria:**
- [ ] GET /api/v1/alerts?is_read=false&type=lost (filtres optionnels)
- [ ] GET /api/v1/alerts/{id}
- [ ] PATCH /api/v1/alerts/{id} (is_read=true)
- [ ] POST /api/v1/alerts/mark-all-read
- [ ] AlertResource avec backlink details inclus
- [ ] Pagination 50 alerts/page
- [ ] Tests: AlertTest.php

**Technical Notes:**
```php
// app/Http/Resources/AlertResource.php
public function toArray($request) {
    return [
        'id' => $this->id,
        'type' => $this->type,
        'detected_at' => $this->detected_at,
        'old_value' => $this->old_value,
        'new_value' => $this->new_value,
        'is_read' => $this->is_read,
        'backlink' => new BacklinkResource($this->whenLoaded('backlink')),
    ];
}
```

**Dependencies:** STORY-022, STORY-023

---

#### STORY-025: Build Alerts List Component

**Epic:** EPIC-004
**Priority:** Must Have
**Points:** 5

**User Story:**
En tant qu'utilisateur
Je veux voir toutes mes alertes dans une liste claire
Afin de traiter les problèmes un par un

**Acceptance Criteria:**
- [ ] Component AlertsList.vue créé
- [ ] GET /api/v1/alerts?is_read=false au mount
- [ ] Cards pour chaque alerte avec:
  - Type icon (❌ lost, ⚠️ nofollow, ✏️ anchor_change)
  - Source URL du backlink
  - Ancien vs nouveau valeur
  - Date/heure détection
  - Bouton "Marquer lu"
- [ ] Filtres: type, is_read
- [ ] Badge nombre alertes non lues (header app)
- [ ] Polling toutes les 60s pour nouvelles alertes

**Technical Notes:**
- Couleurs: rouge (lost), orange (nofollow), bleu (anchor_change)
- Sound notification si nouvelle alerte (optionnel)

**Dependencies:** STORY-024

---

#### STORY-026: Implement Email Notifications (SendGrid)

**Epic:** EPIC-004
**Priority:** Should Have
**Points:** 5

**User Story:**
En tant qu'utilisateur
Je veux recevoir un email quand un backlink important est perdu
Afin d'être averti même si je ne consulte pas l'app

**Acceptance Criteria:**
- [ ] SendGrid configuré (.env: MAIL_MAILER=smtp, SENDGRID_API_KEY)
- [ ] Notification BacklinkLostNotification créée
- [ ] Job SendAlertNotification (queue 'notifications')
- [ ] Listener BacklinkLost → dispatch SendAlertNotification
- [ ] Email template: backlink-lost.blade.php (HTML + plain text)
- [ ] Rate limit: max 10 emails/heure par user
- [ ] Toggle notifications dans user settings
- [ ] Tests: EmailNotificationTest.php (mock SendGrid)

**Technical Notes:**
```php
// app/Notifications/BacklinkLostNotification.php
public function toMail($notifiable) {
    return (new MailMessage)
        ->subject('Backlink Lost: ' . $this->backlink->source_url)
        ->line('A backlink has been lost:')
        ->line('Source: ' . $this->backlink->source_url)
        ->line('Target: ' . $this->backlink->target_url)
        ->action('View Details', url('/backlinks/' . $this->backlink->id));
}
```

**Dependencies:** STORY-023

---

### EPIC-005: Intégration des Métriques SEO (6 stories, 26 points)

*(Continuer avec les 42 stories restantes...)*

---

**Note :** Le document complet avec les 72 stories serait trop volumineux pour un seul message. Je vais générer l'intégralité du fichier avec Write et continuer ensuite avec l'estimation et l'allocation.


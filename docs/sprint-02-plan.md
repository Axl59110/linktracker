# Sprint 2 Plan: Link Tracker - Backlinks CRUD + HTTP Monitoring

**Sprint:** 2/6
**Duration:** 2 semaines
**Start Date:** 2026-02-13
**Goal:** Implémenter le CRUD complet des backlinks avec monitoring HTTP automatique

---

## 🎯 Sprint Goal

Livrer un système complet de gestion et monitoring des backlinks permettant aux utilisateurs d'ajouter des backlinks, de les vérifier automatiquement toutes les 4 heures via Laravel Horizon, et de consulter l'historique des vérifications.

---

## 📊 Sprint Metrics

- **Committed Points:** 37 points (93% capacity)
- **Stories:** 8 stories
- **Capacity:** 40 points (buffer: 3 points)
- **Team:** Claude Code (autonomous development)

**Par Epic:**
- EPIC-002 (Backlinks CRUD): 13 points (3 stories)
- EPIC-003 (Monitoring Engine): 18 points (4 stories)
- EPIC-009 (Infrastructure): 3 points (1 story)

---

## 📋 Sprint Backlog

### STORY-009: Create Backlink Model + Factory ⭐ **CRITICAL PATH**

**Points:** 3
**Priority:** Must Have
**Epic:** EPIC-002 (Backlinks Management)

**User Story:**
En tant que développeur
Je veux créer le modèle Backlink avec relations et accessors
Afin de manipuler les backlinks en Eloquent ORM

**Acceptance Criteria:**
- [ ] Model `Backlink` créé (app/Models/Backlink.php)
- [ ] Fillable: source_url, target_url, anchor_text, status, http_status, rel_attributes, is_dofollow
- [ ] Casts: first_seen_at (datetime), last_checked_at (datetime), is_dofollow (boolean)
- [ ] Relation belongsTo(Project)
- [ ] Scopes: scopeActive(), scopeLost(), scopeChanged()
- [ ] Accessor: getStatusBadgeColorAttribute() pour UI
- [ ] Factory BacklinkFactory avec états (active, lost, changed)
- [ ] Tests: BacklinkModelTest.php (relations, scopes)

**Technical Implementation:**
```php
// app/Models/Backlink.php
protected $fillable = [
    'source_url', 'target_url', 'anchor_text',
    'status', 'http_status', 'rel_attributes', 'is_dofollow'
];

protected $casts = [
    'first_seen_at' => 'datetime',
    'last_checked_at' => 'datetime',
    'is_dofollow' => 'boolean',
];

public function project() {
    return $this->belongsTo(Project::class);
}

public function scopeActive($query) {
    return $query->where('status', 'active');
}

public function scopeLost($query) {
    return $query->where('status', 'lost');
}
```

**Dependencies:** STORY-006 (table exists)
**Blocks:** STORY-010, STORY-011, STORY-012, STORY-013

---

### STORY-010: Build Backlinks CRUD API

**Points:** 5
**Priority:** Must Have
**Epic:** EPIC-002

**User Story:**
En tant qu'utilisateur
Je veux ajouter, modifier, consulter et supprimer des backlinks via API
Afin de gérer ma liste de backlinks à surveiller

**Acceptance Criteria:**
- [ ] POST /api/v1/projects/{project}/backlinks (create)
- [ ] GET /api/v1/projects/{project}/backlinks (list with filters: status, search)
- [ ] GET /api/v1/backlinks/{backlink} (show with checks history)
- [ ] PATCH /api/v1/backlinks/{backlink} (update)
- [ ] DELETE /api/v1/backlinks/{backlink} (delete)
- [ ] Request: StoreBacklinkRequest avec validation + UrlValidator (SSRF)
- [ ] Request: UpdateBacklinkRequest
- [ ] Policy: BacklinkPolicy (user can only access own backlinks)
- [ ] Resource: BacklinkResource avec relations
- [ ] Pagination: 50 items par page
- [ ] Tests: BacklinkApiTest.php (CRUD + authorization + SSRF blocking)

**Technical Implementation:**
```php
// app/Http/Requests/StoreBacklinkRequest.php
public function rules() {
    return [
        'source_url' => [
            'required', 'url', 'max:2048',
            function ($attribute, $value, $fail) {
                try {
                    app(UrlValidator::class)->validate($value);
                } catch (SsrfException $e) {
                    $fail("L'URL est bloquée pour des raisons de sécurité : " . $e->getMessage());
                }
            }
        ],
        'target_url' => 'required|url|max:2048',
        'anchor_text' => 'nullable|string|max:500',
    ];
}
```

**Dependencies:** STORY-009
**Blocks:** STORY-011, STORY-012

---

### STORY-011: Build Backlinks List Vue Component

**Points:** 5
**Priority:** Must Have
**Epic:** EPIC-002

**User Story:**
En tant qu'utilisateur
Je veux voir tous les backlinks d'un projet avec leur statut
Afin de surveiller rapidement l'état de mes backlinks

**Acceptance Criteria:**
- [ ] Component BacklinksList.vue créé
- [ ] Affiche: source_url (tronqué), target_url, anchor_text, status (badge coloré), http_status, last_checked_at
- [ ] Filtres: status dropdown (all/active/lost/changed)
- [ ] Search bar: filtre par source_url ou anchor_text
- [ ] Bouton "Ajouter Backlink" → router.push create form
- [ ] Bouton "Vérifier maintenant" sur chaque ligne (trigger manual check)
- [ ] Actions: Edit, Delete avec confirmation
- [ ] Pagination 50 items avec infinite scroll ou buttons
- [ ] Loading states (skeleton)
- [ ] Empty state quand aucun backlink
- [ ] Responsive design (mobile table scroll)

**Technical Implementation:**
```vue
<script setup>
const filters = ref({
  status: 'all',
  search: ''
})

const backlinks = ref([])
const loading = ref(true)

const fetchBacklinks = async () => {
  const res = await api.get(`/api/v1/projects/${projectId}/backlinks`, {
    params: filters.value
  })
  backlinks.value = res.data
}

const checkNow = async (backlinkId) => {
  await api.post(`/api/v1/backlinks/${backlinkId}/check`)
  toast.success('Vérification lancée')
}
</script>
```

**Dependencies:** STORY-010
**Blocks:** None

---

### STORY-012: Build Backlink Create/Edit Form

**Points:** 3
**Priority:** Must Have
**Epic:** EPIC-002

**User Story:**
En tant qu'utilisateur
Je veux un formulaire pour ajouter/éditer un backlink
Afin de configurer les backlinks à monitorer

**Acceptance Criteria:**
- [ ] Component BacklinkForm.vue créé
- [ ] Props: projectId (required), backlinkId (optional pour edit)
- [ ] Champs: source_url (URL input), target_url (URL input), anchor_text (text)
- [ ] Validation frontend: URL format
- [ ] Affichage erreur SSRF si URL bloquée
- [ ] POST /api/v1/projects/{id}/backlinks au submit (create)
- [ ] PATCH /api/v1/backlinks/{id} (edit)
- [ ] Success toast + redirect vers liste
- [ ] Loading state sur bouton submit

**Dependencies:** STORY-010
**Blocks:** None

---

### STORY-013: Create BacklinkChecker Service

**Points:** 5
**Priority:** Must Have
**Epic:** EPIC-003 (Monitoring Engine)

**User Story:**
En tant que système
Je veux un service pour vérifier l'état HTTP d'un backlink
Afin de détecter si le backlink est présent et actif

**Acceptance Criteria:**
- [ ] Service BacklinkChecker créé (app/Services/Monitoring/BacklinkChecker.php)
- [ ] Méthode checkBacklink(Backlink $backlink): BacklinkCheckResult
- [ ] Utilise GuzzleHttp pour requête HTTP (timeout 30s)
- [ ] Enregistre: http_status, response_time, is_present
- [ ] Parse HTML avec DOMDocument pour détecter lien vers target_url
- [ ] Extrait rel attributes (follow/nofollow)
- [ ] Extrait anchor_text actuel si trouvé
- [ ] Gère erreurs HTTP gracieusement (timeout, 404, 500)
- [ ] Utilise UrlValidator pour SSRF check avant requête
- [ ] Tests: BacklinkCheckerTest.php avec mocked HTTP responses

**Technical Implementation:**
```php
// app/Services/Monitoring/BacklinkChecker.php
class BacklinkChecker {
    public function __construct(
        private UrlValidator $urlValidator,
        private Client $httpClient
    ) {}

    public function checkBacklink(Backlink $backlink): BacklinkCheckResult {
        // 1. SSRF protection
        $this->urlValidator->validate($backlink->source_url);

        // 2. HTTP request
        try {
            $response = $this->httpClient->get($backlink->source_url, [
                'timeout' => 30,
                'allow_redirects' => true,
            ]);

            $statusCode = $response->getStatusCode();
            $html = $response->getBody()->getContents();

            // 3. Parse HTML
            $linkFound = $this->findLinkInHtml($html, $backlink->target_url);

            return new BacklinkCheckResult([
                'http_status' => $statusCode,
                'is_present' => $linkFound !== null,
                'anchor_text' => $linkFound?->anchor ?? null,
                'rel_attributes' => $linkFound?->rel ?? 'follow',
            ]);
        } catch (RequestException $e) {
            return BacklinkCheckResult::failed($e);
        }
    }
}
```

**Dependencies:** STORY-009, STORY-008 (UrlValidator exists)
**Blocks:** STORY-014

---

### STORY-014: Create CheckBacklink Job

**Points:** 5
**Priority:** Must Have
**Epic:** EPIC-003

**User Story:**
En tant que système
Je veux un job asynchrone pour vérifier un backlink
Afin de ne pas bloquer l'application pendant la vérification HTTP

**Acceptance Criteria:**
- [ ] Job CheckBacklink créé (app/Jobs/Monitoring/CheckBacklink.php)
- [ ] Constructor: __construct(Backlink $backlink, bool $isManual = false)
- [ ] Queue: 'high' si isManual=true, 'default' sinon
- [ ] Tries: 3 avec backoff [60, 120, 300] secondes
- [ ] Appelle BacklinkChecker::checkBacklink()
- [ ] Crée BacklinkCheck record en DB
- [ ] Met à jour Backlink::last_checked_at
- [ ] Détecte changements: BacklinkAnalyzer::analyzeChanges()
- [ ] Dispatch Event: BacklinkStatusChanged si changement
- [ ] Tests: CheckBacklinkJobTest.php avec Queue::fake()

**Technical Implementation:**
```php
// app/Jobs/Monitoring/CheckBacklink.php
class CheckBacklink implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [60, 120, 300]; // Exponential backoff

    public function __construct(
        public Backlink $backlink,
        public bool $isManual = false
    ) {
        $this->onQueue($isManual ? 'high' : 'default');
    }

    public function handle(
        BacklinkChecker $checker,
        BacklinkAnalyzer $analyzer
    ) {
        // 1. Check backlink
        $result = $checker->checkBacklink($this->backlink);

        // 2. Store check result
        $check = BacklinkCheck::create([
            'backlink_id' => $this->backlink->id,
            'http_status' => $result->http_status,
            'is_present' => $result->is_present,
            'anchor_text' => $result->anchor_text,
            'rel_attributes' => $result->rel_attributes,
            'response_time' => $result->response_time,
        ]);

        // 3. Update backlink last_checked_at
        $this->backlink->update(['last_checked_at' => now()]);

        // 4. Analyze changes
        $changes = $analyzer->analyzeChanges($this->backlink, $check);

        // 5. Dispatch events
        if ($changes->hasChanges()) {
            event(new BacklinkStatusChanged($this->backlink, $changes));
        }
    }
}
```

**Dependencies:** STORY-013, STORY-015
**Blocks:** STORY-017

---

### STORY-015: Create BacklinkCheck Model + Migration

**Points:** 3
**Priority:** Must Have
**Epic:** EPIC-003

**User Story:**
En tant que développeur
Je veux stocker l'historique de toutes les vérifications
Afin de tracker l'évolution des backlinks dans le temps

**Acceptance Criteria:**
- [ ] Migration create_backlink_checks_table créée
- [ ] Colonnes: id, backlink_id, http_status, is_present, anchor_text, rel_attributes, response_time, checked_at, timestamps
- [ ] Foreign key backlink_id → backlinks.id (cascade delete)
- [ ] Index: (backlink_id, checked_at DESC)
- [ ] Model BacklinkCheck créé
- [ ] Relation belongsTo(Backlink)
- [ ] Cast: checked_at (datetime), is_present (boolean)
- [ ] Scope: scopeLatest() pour récupérer dernier check
- [ ] Factory BacklinkCheckFactory
- [ ] Tests: BacklinkCheckModelTest.php

**Technical Implementation:**
```php
// database/migrations/create_backlink_checks_table.php
Schema::create('backlink_checks', function (Blueprint $table) {
    $table->id();
    $table->foreignId('backlink_id')->constrained()->onDelete('cascade');
    $table->integer('http_status')->nullable();
    $table->boolean('is_present')->default(false);
    $table->text('anchor_text')->nullable();
    $table->string('rel_attributes', 100)->nullable();
    $table->integer('response_time')->nullable(); // milliseconds
    $table->timestamp('checked_at')->useCurrent();
    $table->timestamps();

    $table->index(['backlink_id', 'checked_at']);
});
```

**Dependencies:** STORY-009
**Blocks:** STORY-014

---

### STORY-017: Schedule Automatic Monitoring

**Points:** 5
**Priority:** Must Have
**Epic:** EPIC-003

**User Story:**
En tant que système
Je veux vérifier automatiquement tous les backlinks toutes les 4 heures
Afin de surveiller l'état des backlinks en continu

**Acceptance Criteria:**
- [ ] Command MonitorBacklinks créé (app/Console/Commands/MonitorBacklinks.php)
- [ ] Récupère tous les backlinks actifs (status = 'active')
- [ ] Dispatch CheckBacklink job pour chaque backlink
- [ ] Throttling: max 10 jobs simultanés dans la queue
- [ ] Logs: nombre de backlinks vérifiés
- [ ] Scheduled dans Kernel.php: toutes les 4 heures
- [ ] Endpoint API: POST /api/v1/backlinks/{id}/check (manual trigger)
- [ ] Tests: MonitorBacklinksTest.php avec Queue::fake()

**Technical Implementation:**
```php
// app/Console/Commands/MonitorBacklinks.php
class MonitorBacklinks extends Command {
    protected $signature = 'backlinks:monitor';

    public function handle() {
        $backlinks = Backlink::active()
            ->whereNotNull('source_url')
            ->get();

        $this->info("Monitoring {$backlinks->count()} backlinks...");

        $backlinks->each(function($backlink) {
            CheckBacklink::dispatch($backlink)
                ->onQueue('default');
        });

        $this->info("Jobs dispatched successfully.");
    }
}

// app/Console/Kernel.php
protected function schedule(Schedule $schedule) {
    $schedule->command('backlinks:monitor')
             ->everyFourHours()
             ->withoutOverlapping();
}
```

**Dependencies:** STORY-014
**Blocks:** None

---

### STORY-018: Configure Laravel Horizon for Production

**Points:** 3
**Priority:** Must Have
**Epic:** EPIC-009 (Infrastructure)

**User Story:**
En tant que développeur
Je veux configurer Horizon pour gérer 100 jobs/minute
Afin de traiter efficacement toutes les vérifications

**Acceptance Criteria:**
- [ ] config/horizon.php ajusté pour production
- [ ] 3 queues: high (priority manual checks), default (auto checks), low (metrics)
- [ ] Auto-scaling: minProcesses=3, maxProcesses=10
- [ ] Balance strategy: 'auto'
- [ ] Timeout: 60 secondes par job
- [ ] Failed jobs retention: 7 jours
- [ ] Métriques accessibles via /horizon/dashboard
- [ ] Tests: Vérifier que jobs sont dispatched correctement

**Technical Implementation:**
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
            'balanceMaxShift' => 1,
            'balanceCooldown' => 3,
            'tries' => 3,
            'timeout' => 60,
        ],
    ],
],
```

**Dependencies:** None
**Blocks:** None (indépendant)

---

## 🔀 Dependencies Graph Sprint 2

```
STORY-009 (Backlink Model) **CRITICAL PATH**
    ↓
    ├─→ STORY-010 (API CRUD)
    │       ↓
    │       ├─→ STORY-011 (List UI)
    │       └─→ STORY-012 (Form UI)
    │
    ├─→ STORY-015 (BacklinkCheck Model)
    │       ↓
    └───────┴─→ STORY-013 (BacklinkChecker Service)
                    ↓
                STORY-014 (CheckBacklink Job)
                    ↓
                STORY-017 (Scheduled Monitoring)

STORY-018 (Horizon Config) - Parallel (indépendant)
```

---

## 📝 Ordre Recommandé d'Implémentation

1. **STORY-018** (Horizon - setup tôt) - 3 pts
2. **STORY-009** (Backlink Model - bloque tout) - 3 pts
3. **STORY-015** (BacklinkCheck Model) - 3 pts
4. **STORY-010** (API CRUD) - 5 pts
5. **STORY-013** (BacklinkChecker Service) - 5 pts
6. **STORY-014** (CheckBacklink Job) - 5 pts
7. **STORY-011** + **STORY-012** (UI en parallèle) - 8 pts
8. **STORY-017** (Scheduled Monitoring - finalisation) - 5 pts

---

## ✅ Definition of Done Sprint 2

Une story est complète quand:
- [ ] Code implémenté selon acceptance criteria
- [ ] Tests écrits (Feature + Unit, coverage ≥70%)
- [ ] Tests passent (php artisan test)
- [ ] Protection SSRF vérifiée pour toutes les URLs
- [ ] Code review interne (PHPDoc, conventions Laravel)
- [ ] Testé manuellement (Herd local)
- [ ] Documentation inline ajoutée
- [ ] Aucune erreur dans logs Laravel

---

## 📊 Sprint 2 Success Metrics

À la fin du Sprint 2, nous aurons:

✅ **Backlinks CRUD complet:**
- API REST complète (CRUD)
- UI Vue.js responsive
- Validation + SSRF protection

✅ **Monitoring HTTP automatique:**
- BacklinkChecker service fonctionnel
- Jobs Laravel asynchrones
- Vérification toutes les 4h via Scheduler
- Vérification manuelle disponible

✅ **Historique des checks:**
- Table backlink_checks avec historique
- Analyse des changements

✅ **Infrastructure queue:**
- Horizon configuré pour prod
- 3 queues (high/default/low)
- Auto-scaling 3-10 workers

**Tests attendus:** +15 tests minimum (total ~55 tests)

---

## ⚠️ Risques et Mitigations Sprint 2

**Risque 1:** Parsing HTML complexe (sites avec JS rendering)
- **Mitigation:** Limiter le scope à HTML statique pour v1, noter limitation

**Risque 2:** Performance des checks HTTP (timeout)
- **Mitigation:** Timeout 30s + retry 3x avec backoff

**Risque 3:** Horizon installation Windows
- **Mitigation:** Déjà géré dans STORY-016, documentation claire

**Risque 4:** Volume de checks élevé
- **Mitigation:** Throttling + auto-scaling workers

---

## 🔮 Preview Sprint 3

**Sprint 3 Goal:** Système d'alertes et notifications

**Features clés:**
- Alerts CRUD (détection automatique changements)
- Email notifications
- Dashboard avec statistiques
- Graphiques évolution backlinks

**Estimated:** 35-40 points

---

## 📁 Fichiers Critiques à Créer

- `app/Models/Backlink.php` - Modèle principal
- `app/Services/Monitoring/BacklinkChecker.php` - Service vérification HTTP
- `app/Jobs/Monitoring/CheckBacklink.php` - Job asynchrone
- `app/Http/Controllers/Api/V1/BacklinkController.php` - Controller API
- `database/migrations/*_create_backlink_checks_table.php` - Historique
- `app/Console/Commands/MonitorBacklinks.php` - Command schedulé
- `resources/js/components/Backlinks/BacklinksList.vue` - Liste UI
- `resources/js/components/Backlinks/BacklinkForm.vue` - Formulaire UI

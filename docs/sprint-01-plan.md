# Sprint 1 Plan: Link Tracker - Foundation

**Sprint:** 1/6
**Duration:** 2 semaines
**Start Date:** 2026-02-09
**Goal:** Établir l'infrastructure technique solide et la gestion des projets

---

## 🎯 Sprint Goal

Livrer une application Laravel + Vue.js fonctionnelle avec authentification sécurisée, CRUD complet des projets, et l'infrastructure de base (Docker, Horizon, sécurité SSRF) permettant de démarrer l'implémentation des backlinks au Sprint 2.

---

## 📊 Sprint Metrics

- **Committed Points:** 36 points
- **Stories:** 9 stories
- **Capacity:** 40 points (buffer: 10%)
- **Team:** Claude Code (autonomous development)

---

## 📋 Sprint Backlog

### STORY-001: Setup Laravel + Vue.js Project ⭐ **CRITICAL PATH**

**Points:** 5
**Priority:** Must Have
**Epic:** Infrastructure

**User Story:**
En tant que développeur
Je veux initialiser le projet Laravel avec Vue.js et PostgreSQL
Afin d'avoir une base technique solide pour l'implémentation

**Acceptance Criteria:**
- [ ] Laravel 10.48+ installé avec PHP 8.2+
- [ ] Composer dependencies installées (horizon, sanctum, telescope)
- [ ] Vue.js 3.4+ avec Vite 5 configuré
- [ ] Vue Router 4 configuré
- [ ] Tailwind CSS 4 configuré avec PostCSS
- [ ] PostgreSQL 15+ database créée et connectée
- [ ] Redis configuré pour cache et queue
- [ ] `.env.example` documenté
- [ ] README.md avec instructions setup

**Technical Implementation:**
```bash
# 1. Create Laravel project
composer create-project laravel/laravel linktracker "^10.0"
cd linktracker

# 2. Install dependencies
composer require laravel/sanctum laravel/horizon predis/predis
composer require --dev laravel/telescope

# 3. Install frontend
npm install vue@^3.4 vue-router@^4.2 axios@^1.6
npm install -D tailwindcss@^4.0 postcss autoprefixer

# 4. Initialize configs
php artisan vendor:publish --provider="Laravel\Sanctum\ServiceProvider"
php artisan vendor:publish --tag=horizon-config
php artisan telescope:install
```

**Files to Create:**
- `vite.config.js` (Vue + Vite setup)
- `tailwind.config.js` (Tailwind config)
- `resources/js/app.js` (Vue mount)
- `resources/js/router/index.js` (Vue Router)
- `resources/views/app.blade.php` (SPA entry point)

**Database:**
```
PostgreSQL connection:
  DB_CONNECTION=pgsql
  DB_HOST=postgres
  DB_PORT=5432
  DB_DATABASE=linktracker
  DB_USERNAME=linktracker_user
  DB_PASSWORD=secret
```

**Dependencies:** None
**Blocks:** All other stories

---

### STORY-002: Implement User Authentication with Sanctum

**Points:** 5
**Priority:** Must Have
**Epic:** EPIC-008 (Auth & Config)

**User Story:**
En tant qu'utilisateur
Je veux pouvoir me connecter de manière sécurisée
Afin de protéger mes données de backlinks

**Acceptance Criteria:**
- [ ] Laravel Sanctum configuré (session-based SPA)
- [ ] Migration `users` table exécutée
- [ ] POST `/api/v1/auth/login` (email, password) → session cookie
- [ ] POST `/api/v1/auth/logout` → clear session
- [ ] GET `/api/v1/auth/user` → user data if authenticated
- [ ] CSRF protection activée
- [ ] Middleware `auth:sanctum` sur routes API
- [ ] Tests: `AuthTest.php` (login success, login fail, logout, unauthorized)

**Technical Implementation:**
```php
// config/sanctum.php
'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', 'localhost,127.0.0.1')),

// routes/api.php
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::get('/auth/user', [AuthController::class, 'user'])->middleware('auth:sanctum');

// app/Http/Controllers/AuthController.php
public function login(Request $request) {
    $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    if (Auth::attempt($request->only('email', 'password'))) {
        $request->session()->regenerate();
        return response()->json(['user' => Auth::user()]);
    }

    return response()->json(['message' => 'Invalid credentials'], 401);
}
```

**Frontend (Vue.js):**
```javascript
// resources/js/stores/auth.js
import { ref } from 'vue'
import api from '@/utils/api'

export const useAuthStore = () => {
  const user = ref(null)
  const isAuthenticated = ref(false)

  const login = async (email, password) => {
    await api.get('/sanctum/csrf-cookie')
    const res = await api.post('/api/v1/auth/login', { email, password })
    user.value = res.data.user
    isAuthenticated.value = true
  }

  return { user, isAuthenticated, login }
}
```

**Tests:**
```php
// tests/Feature/AuthTest.php
public function test_user_can_login_with_correct_credentials() {
    $user = User::factory()->create(['password' => bcrypt('password')]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertOk();
    $this->assertAuthenticated();
}
```

**Dependencies:** STORY-001
**Blocks:** STORY-003 (need auth for projects)

---

### STORY-003: Create Project CRUD API

**Points:** 3
**Priority:** Must Have
**Epic:** EPIC-001 (Projects)

**User Story:**
En tant qu'utilisateur SEO
Je veux créer un projet pour organiser mes backlinks par site web
Afin de séparer clairement mes différents clients/sites

**Acceptance Criteria:**
- [ ] Migration `projects` table (id, user_id, name, domain, settings JSONB, timestamps)
- [ ] Model `Project` avec fillable, casts, relations
- [ ] POST `/api/v1/projects` (name, domain) avec validation
- [ ] GET `/api/v1/projects` (paginated, 50/page)
- [ ] GET `/api/v1/projects/{id}` (with backlinks_count)
- [ ] PATCH `/api/v1/projects/{id}` (name, domain)
- [ ] DELETE `/api/v1/projects/{id}` (cascade delete backlinks)
- [ ] `ProjectPolicy` pour authorization (user can only access own projects)
- [ ] `ProjectResource` pour API responses
- [ ] Tests: `ProjectTest.php` (CRUD + authorization)

**Technical Implementation:**
```php
// database/migrations/create_projects_table.php
Schema::create('projects', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->string('name');
    $table->string('domain');
    $table->jsonb('settings')->default('{}');
    $table->timestamps();

    $table->index(['user_id', 'created_at']);
});

// app/Models/Project.php
class Project extends Model {
    protected $fillable = ['name', 'domain', 'settings'];
    protected $casts = ['settings' => 'array'];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function backlinks() {
        return $this->hasMany(Backlink::class);
    }
}

// app/Http/Controllers/ProjectController.php
class ProjectController extends Controller {
    public function index(Request $request) {
        $projects = $request->user()->projects()
            ->withCount('backlinks')
            ->paginate(50);
        return ProjectResource::collection($projects);
    }

    public function store(Request $request) {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'domain' => 'required|url|max:255',
        ]);

        $project = $request->user()->projects()->create($validated);
        return new ProjectResource($project);
    }
}

// app/Policies/ProjectPolicy.php
public function view(User $user, Project $project) {
    return $user->id === $project->user_id;
}
```

**Dependencies:** STORY-002 (need auth)
**Blocks:** STORY-004, STORY-005

---

### STORY-004: Build Projects List Vue Component

**Points:** 3
**Priority:** Must Have
**Epic:** EPIC-001

**User Story:**
En tant qu'utilisateur
Je veux voir tous mes projets dans une liste claire
Afin de naviguer rapidement vers le projet qui m'intéresse

**Acceptance Criteria:**
- [ ] Component `ProjectList.vue` créé
- [ ] Fetch GET `/api/v1/projects` au mount
- [ ] Display: name, domain, backlinks_count, created_at
- [ ] Bouton "Nouveau Projet" → router push `/projects/new`
- [ ] Clic sur projet → router push `/projects/{id}`
- [ ] Boutons Edit/Delete avec confirmation modal
- [ ] DELETE appelle API puis refresh liste
- [ ] Loading state (skeleton)
- [ ] Error handling avec toast
- [ ] Responsive design (Tailwind grid)

**Technical Implementation:**
```vue
<!-- resources/js/components/Projects/ProjectList.vue -->
<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/utils/api'

const router = useRouter()
const projects = ref([])
const loading = ref(true)
const error = ref(null)

onMounted(async () => {
  try {
    const res = await api.get('/api/v1/projects')
    projects.value = res.data.data
  } catch (err) {
    error.value = err.message
  } finally {
    loading.value = false
  }
})

const deleteProject = async (id) => {
  if (!confirm('Supprimer ce projet et tous ses backlinks ?')) return
  await api.delete(`/api/v1/projects/${id}`)
  projects.value = projects.value.filter(p => p.id !== id)
}
</script>

<template>
  <div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-3xl font-bold">Mes Projets</h1>
      <button @click="router.push('/projects/new')"
              class="btn btn-primary">
        Nouveau Projet
      </button>
    </div>

    <div v-if="loading" class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div v-for="i in 3" :key="i" class="skeleton h-32"></div>
    </div>

    <div v-else-if="error" class="alert alert-error">{{ error }}</div>

    <div v-else class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div v-for="project in projects" :key="project.id"
           class="card bg-base-100 shadow-xl hover:shadow-2xl transition-shadow cursor-pointer"
           @click="router.push(`/projects/${project.id}`)">
        <div class="card-body">
          <h2 class="card-title">{{ project.name }}</h2>
          <p class="text-sm text-gray-600">{{ project.domain }}</p>
          <div class="badge badge-primary">{{ project.backlinks_count }} backlinks</div>
          <div class="card-actions justify-end mt-4">
            <button @click.stop="router.push(`/projects/${project.id}/edit`)"
                    class="btn btn-sm btn-ghost">
              Éditer
            </button>
            <button @click.stop="deleteProject(project.id)"
                    class="btn btn-sm btn-error">
              Supprimer
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
```

**Dependencies:** STORY-003
**Blocks:** None (parallel with STORY-005)

---

### STORY-005: Build Project Create/Edit Form

**Points:** 2
**Priority:** Must Have
**Epic:** EPIC-001

**User Story:**
En tant qu'utilisateur
Je veux un formulaire simple pour créer/éditer un projet
Afin de configurer rapidement mes projets de monitoring

**Acceptance Criteria:**
- [ ] Component `ProjectForm.vue` créé
- [ ] Props: `projectId` (optional, pour mode édition)
- [ ] Champs: name (text), domain (URL)
- [ ] Validation frontend (domain format URL valide)
- [ ] POST `/api/v1/projects` au submit (création)
- [ ] PATCH `/api/v1/projects/{id}` (édition)
- [ ] Messages succès (toast) + redirection `/projects`
- [ ] Messages erreur affichés (validation API)
- [ ] Loading state sur bouton submit

**Technical Implementation:**
```vue
<!-- resources/js/components/Projects/ProjectForm.vue -->
<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/utils/api'

const props = defineProps({
  projectId: { type: Number, default: null }
})

const router = useRouter()
const form = ref({ name: '', domain: '' })
const errors = ref({})
const loading = ref(false)

const isEditMode = !!props.projectId

onMounted(async () => {
  if (isEditMode) {
    const res = await api.get(`/api/v1/projects/${props.projectId}`)
    form.value = res.data.data
  }
})

const submit = async () => {
  loading.value = true
  errors.value = {}

  try {
    if (isEditMode) {
      await api.patch(`/api/v1/projects/${props.projectId}`, form.value)
    } else {
      await api.post('/api/v1/projects', form.value)
    }

    alert('Projet sauvegardé !')
    router.push('/projects')
  } catch (err) {
    if (err.response?.data?.errors) {
      errors.value = err.response.data.errors
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="container mx-auto px-4 py-8 max-w-2xl">
    <h1 class="text-3xl font-bold mb-6">
      {{ isEditMode ? 'Éditer Projet' : 'Nouveau Projet' }}
    </h1>

    <form @submit.prevent="submit" class="space-y-4">
      <div class="form-control">
        <label class="label">Nom du projet</label>
        <input v-model="form.name" type="text" class="input input-bordered"
               placeholder="Mon Site E-commerce" required />
        <span v-if="errors.name" class="text-error text-sm">{{ errors.name[0] }}</span>
      </div>

      <div class="form-control">
        <label class="label">Domaine</label>
        <input v-model="form.domain" type="url" class="input input-bordered"
               placeholder="https://example.com" required />
        <span v-if="errors.domain" class="text-error text-sm">{{ errors.domain[0] }}</span>
      </div>

      <div class="flex gap-4">
        <button type="submit" :disabled="loading"
                class="btn btn-primary">
          {{ loading ? 'Sauvegarde...' : 'Sauvegarder' }}
        </button>
        <button type="button" @click="router.push('/projects')"
                class="btn btn-ghost">
          Annuler
        </button>
      </div>
    </form>
  </div>
</template>
```

**Dependencies:** STORY-003
**Blocks:** None

---

### STORY-006: Create Backlinks Table Migration

**Points:** 2
**Priority:** Must Have
**Epic:** EPIC-002 (Backlinks)

**User Story:**
En tant que développeur
Je veux créer la table backlinks en base
Afin de stocker tous les backlinks à monitorer

**Acceptance Criteria:**
- [ ] Migration `backlinks` table créée
- [ ] Colonnes: id, project_id, source_url, target_url, anchor_text, status, http_status, rel_attributes, is_dofollow, first_seen_at, last_checked_at, timestamps
- [ ] Foreign key project_id → projects.id (cascade delete)
- [ ] Index compound: (project_id, status)
- [ ] Index: status WHERE status = 'active'
- [ ] Factory `BacklinkFactory` pour tests
- [ ] Seeder avec 20 backlinks de démo

**Technical Implementation:**
```php
// database/migrations/create_backlinks_table.php
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

    $table->index(['project_id', 'status']);
    $table->index('status')->where('status', 'active');
});

// database/factories/BacklinkFactory.php
public function definition() {
    return [
        'project_id' => Project::factory(),
        'source_url' => $this->faker->url(),
        'target_url' => $this->faker->url(),
        'anchor_text' => $this->faker->words(3, true),
        'status' => 'active',
        'is_dofollow' => $this->faker->boolean(80),
    ];
}

// database/seeders/DemoDataSeeder.php
public function run() {
    $user = User::factory()->create(['email' => 'demo@linktracker.com']);
    $project = Project::factory()->create(['user_id' => $user->id]);
    Backlink::factory()->count(20)->create(['project_id' => $project->id]);
}
```

**Dependencies:** STORY-003 (projects table)
**Blocks:** Sprint 2 backlinks CRUD

---

### STORY-008: Implement URL Validator Service (SSRF Protection)

**Points:** 5
**Priority:** Must Have (Security - NFR-004)
**Epic:** EPIC-010 (Security)

**User Story:**
En tant que développeur
Je veux valider toutes les URLs avant vérification HTTP
Afin de prévenir les attaques SSRF (Server-Side Request Forgery)

**Acceptance Criteria:**
- [ ] Service `UrlValidator` créé (app/Services/Security/)
- [ ] Exception `SsrfException` créée
- [ ] Méthode `validate(string $url): void` throws SsrfException
- [ ] Bloque localhost (127.0.0.0/8)
- [ ] Bloque réseaux privés (10.0.0.0/8, 172.16.0.0/12, 192.168.0.0/16)
- [ ] Bloque link-local (169.254.0.0/16)
- [ ] Whitelist protocoles: http, https uniquement
- [ ] Resolve DNS et check IP (gethostbyname)
- [ ] Tests: `UrlValidatorTest.php` (15+ test cases)

**Technical Implementation:**
```php
// app/Services/Security/UrlValidator.php
namespace App\Services\Security;

use App\Exceptions\SsrfException;

class UrlValidator
{
    private const BLOCKED_IP_RANGES = [
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
        if (!isset($parsed['scheme']) || !in_array($parsed['scheme'], self::ALLOWED_PROTOCOLS)) {
            throw new SsrfException("Protocol not allowed: {$parsed['scheme']}");
        }

        // 2. Resolve hostname to IP
        $host = $parsed['host'] ?? '';
        $ip = gethostbyname($host);

        if ($ip === $host) {
            throw new SsrfException("Unable to resolve hostname: {$host}");
        }

        // 3. Check if IP is in blocked ranges
        foreach (self::BLOCKED_IP_RANGES as $range) {
            if ($this->ipInRange($ip, $range)) {
                throw new SsrfException("Access to {$ip} ({$host}) is blocked (SSRF protection)");
            }
        }
    }

    private function ipInRange(string $ip, string $range): bool
    {
        [$subnet, $mask] = explode('/', $range);
        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        $maskLong = -1 << (32 - (int)$mask);

        return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
    }
}

// app/Exceptions/SsrfException.php
namespace App\Exceptions;

class SsrfException extends \Exception {}

// tests/Unit/UrlValidatorTest.php
public function test_blocks_localhost() {
    $validator = new UrlValidator();
    $this->expectException(SsrfException::class);
    $validator->validate('http://127.0.0.1/admin');
}

public function test_blocks_private_network() {
    $validator = new UrlValidator();
    $this->expectException(SsrfException::class);
    $validator->validate('http://192.168.1.1/');
}

public function test_allows_public_domains() {
    $validator = new UrlValidator();
    $validator->validate('https://example.com/page');
    $this->assertTrue(true); // No exception = success
}
```

**Usage in API:**
```php
// app/Http/Requests/StoreBacklinkRequest.php
public function rules() {
    return [
        'source_url' => [
            'required',
            'url',
            'max:2048',
            function ($attribute, $value, $fail) {
                try {
                    app(UrlValidator::class)->validate($value);
                } catch (SsrfException $e) {
                    $fail("The {$attribute} is blocked for security reasons.");
                }
            },
        ],
    ];
}
```

**Dependencies:** None (standalone)
**Blocks:** All backlink checking features (Sprint 2+)

---

### STORY-016: Setup Laravel Horizon for Queue Management

**Points:** 3
**Priority:** Must Have (NFR-002 - Performance)
**Epic:** EPIC-009 (Infrastructure)

**User Story:**
En tant que développeur
Je veux un dashboard pour monitorer les jobs de vérification
Afin de garantir que le système traite 100 jobs/minute

**Acceptance Criteria:**
- [ ] Laravel Horizon installé (`composer require laravel/horizon`)
- [ ] config/horizon.php configuré
- [ ] 3 queues définies: high, default, low
- [ ] Auto-scaling: minProcesses=3, maxProcesses=10
- [ ] Middleware auth sur route `/horizon`
- [ ] Artisan command: `php artisan horizon` démarre workers
- [ ] Supervisor config créé pour production
- [ ] Tests: Queue dispatching fonctionne

**Technical Implementation:**
```php
// config/horizon.php
return [
    'use' => 'default',

    'prefix' => env('HORIZON_PREFIX', 'horizon:'),

    'middleware' => ['web', 'auth'],

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

        'local' => [
            'supervisor-1' => [
                'connection' => 'redis',
                'queue' => ['high', 'default', 'low'],
                'balance' => 'auto',
                'minProcesses' => 1,
                'maxProcesses' => 3,
                'tries' => 3,
                'timeout' => 60,
            ],
        ],
    ],
];

// routes/web.php
Route::middleware(['auth'])->group(function () {
    Route::get('/horizon', fn() => redirect('/horizon/dashboard'));
});

// Supervisor config (for production)
// /etc/supervisor/conf.d/linktracker-horizon.conf
[program:linktracker-horizon]
process_name=%(program_name)s
command=php /var/www/linktracker/artisan horizon
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/linktracker/storage/logs/horizon.log
stopwaitsecs=3600
```

**Testing:**
```php
// tests/Feature/HorizonTest.php
public function test_horizon_dashboard_requires_auth() {
    $response = $this->get('/horizon');
    $response->assertRedirect('/login');
}

public function test_can_dispatch_job_to_queue() {
    Queue::fake();

    dispatch(new TestJob())->onQueue('default');

    Queue::assertPushed(TestJob::class, function ($job) {
        return $job->queue === 'default';
    });
}
```

**Dependencies:** STORY-001 (Redis configured)
**Blocks:** All async jobs (Sprint 3 monitoring)

---

### STORY-064: Setup Docker Compose Stack

**Points:** 5
**Priority:** Must Have
**Epic:** EPIC-009 (Infrastructure)

**User Story:**
En tant que développeur
Je veux un environnement Docker complet
Afin de déployer facilement en dev, staging et prod

**Acceptance Criteria:**
- [ ] `Dockerfile` pour Laravel app créé
- [ ] `docker-compose.yml` avec services: app, postgres, redis, nginx
- [ ] PostgreSQL 15 avec volume persistent
- [ ] Redis 7.2 avec AOF persistence
- [ ] Nginx avec config SSL ready
- [ ] `.dockerignore` configuré
- [ ] Script `./docker/start.sh` pour démarrage rapide
- [ ] Documentation dans README.md

**Technical Implementation:**
```dockerfile
# Dockerfile
FROM php:8.2-fpm

# Install dependencies
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    unzip \
    && docker-php-ext-install pdo pdo_pgsql zip

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Set permissions
RUN chown -R www-data:www-data storage bootstrap/cache

CMD php-fpm
```

```yaml
# docker-compose.yml
version: '3.8'

services:
  app:
    build: .
    volumes:
      - .:/var/www/html
    environment:
      - DB_CONNECTION=pgsql
      - DB_HOST=postgres
      - REDIS_HOST=redis
    depends_on:
      - postgres
      - redis

  postgres:
    image: postgres:15
    environment:
      POSTGRES_DB: linktracker
      POSTGRES_USER: linktracker_user
      POSTGRES_PASSWORD: ${DB_PASSWORD:-secret}
    volumes:
      - postgres_data:/var/lib/postgresql/data
    ports:
      - "5432:5432"

  redis:
    image: redis:7.2
    command: redis-server --appendonly yes
    volumes:
      - redis_data:/data
    ports:
      - "6379:6379"

  nginx:
    image: nginx:latest
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - .:/var/www/html
      - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf
    depends_on:
      - app

volumes:
  postgres_data:
  redis_data:
```

**Dependencies:** STORY-001
**Blocks:** Deployment to production

---

### STORY-065: Configure Nginx + TLS 1.3

**Points:** 3
**Priority:** Must Have (NFR-005 - Security)
**Epic:** EPIC-010 (Security)

**User Story:**
En tant que développeur
Je veux un serveur web Nginx avec TLS 1.3
Afin de sécuriser les communications

**Acceptance Criteria:**
- [ ] Nginx config créé (`docker/nginx/default.conf`)
- [ ] TLS 1.3 configuré
- [ ] HSTS headers activés
- [ ] Proxy vers PHP-FPM (app:9000)
- [ ] Static files servies directement
- [ ] Compression gzip activée
- [ ] Rate limiting configuré (60 req/min)

**Technical Implementation:**
```nginx
# docker/nginx/default.conf
server {
    listen 80;
    listen [::]:80;
    server_name linktracker.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name linktracker.com;
    root /var/www/html/public;

    ssl_certificate /etc/ssl/certs/linktracker.crt;
    ssl_certificate_key /etc/ssl/private/linktracker.key;
    ssl_protocols TLSv1.3;
    ssl_ciphers 'TLS_AES_128_GCM_SHA256:TLS_AES_256_GCM_SHA384';
    ssl_prefer_server_ciphers off;

    # Security headers
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains; preload" always;
    add_header X-Frame-Options "DENY" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    # Rate limiting
    limit_req_zone $binary_remote_addr zone=api_limit:10m rate=60r/m;

    # Static files
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # PHP-FPM
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass app:9000;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # API rate limiting
    location /api/ {
        limit_req zone=api_limit burst=20 nodelay;
        try_files $uri $uri/ /index.php?$query_string;
    }
}
```

**Dependencies:** STORY-064
**Blocks:** Production deployment

---

## 🔗 Sprint 1 Dependencies Graph

```
STORY-001 (Setup)
    ↓
    ├─→ STORY-002 (Auth)
    │       ↓
    │       └─→ STORY-003 (Projects API)
    │               ↓
    │               ├─→ STORY-004 (Projects List UI)
    │               ├─→ STORY-005 (Project Form UI)
    │               └─→ STORY-006 (Backlinks Table)
    │
    ├─→ STORY-008 (SSRF Protection)
    ├─→ STORY-016 (Horizon)
    └─→ STORY-064 (Docker)
            ↓
            └─→ STORY-065 (Nginx)
```

---

## ✅ Definition of Done (Sprint 1)

Une story est complète quand:
- [ ] Code implémenté selon acceptance criteria
- [ ] Tests unitaires et feature écrits (≥70% coverage)
- [ ] Code committed avec message descriptif
- [ ] Documentation inline (PHPDoc, JSDoc) ajoutée
- [ ] Testé manuellement dans Docker
- [ ] Pas d'erreurs PHP/JS dans logs
- [ ] Performance vérifiée (<500ms)

---

## 🚀 How to Start Sprint 1

### Option 1: Développement Story par Story

```bash
# Story par story avec /dev-story
/bmad:dev-story STORY-001
# Attendre complétion, tester, puis:
/bmad:dev-story STORY-002
# etc...
```

### Option 2: Développement en Batch

```bash
# Demander à Claude Code de faire plusieurs stories
"Implémente STORY-001, STORY-002, et STORY-003 en séquence"
```

### Ordre Recommandé

1. **STORY-001** (CRITICAL - bloque tout)
2. **STORY-064** (Docker - pour tester facilement)
3. **STORY-065** (Nginx - complète l'infra)
4. **STORY-002** (Auth - bloque features métier)
5. **STORY-008** (SSRF - sécurité critique)
6. **STORY-016** (Horizon - prépare Sprint 3)
7. **STORY-003** (Projects API)
8. **STORY-004** (Projects List UI)
9. **STORY-005** (Project Form UI)
10. **STORY-006** (Backlinks Table - prépare Sprint 2)

---

## 📊 Sprint 1 Success Metrics

À la fin du Sprint 1, nous aurons:

✅ **Infrastructure complète:**
- Laravel 10 + Vue.js 3 fonctionnel
- Docker Compose stack opérationnel
- PostgreSQL + Redis configurés
- Nginx avec TLS 1.3

✅ **Authentification sécurisée:**
- Sanctum session-based
- CSRF protection
- Login/Logout fonctionnel

✅ **Projects CRUD complet:**
- API backend avec authorization
- UI frontend responsive
- Tests >70% coverage

✅ **Sécurité de base:**
- SSRF protection (UrlValidator)
- Rate limiting (Nginx)
- Policies Laravel

✅ **Queue infrastructure:**
- Horizon configuré
- 3 queues (high/default/low)
- Auto-scaling 3-10 workers

---

## 🎯 Next: Sprint 2 Preview

**Sprint 2 Goal:** Gestion complète des backlinks (CRUD + import/export CSV)

**Estimated Stories:** ~10 stories, 38-40 points

**Key Features:**
- Backlinks CRUD API
- Backlinks Table Vue (filtres, tri, pagination)
- CSV Import/Export
- Backlink Form avec validation

**Sprint 2 planning sera créé après Sprint 1 Review**

---

**Document créé le 2026-02-09 avec BMAD Method v6 - Phase 4 (Sprint Planning)**

*Ready for Claude Code autonomous implementation*

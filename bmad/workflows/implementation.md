# Implementation Workflow

**Version:** 1.0.0
**Phase:** Phase 4 - Implementation
**Type:** Continuous / Per Story

## Description

Workflow pour implémenter une user story du sprint backlog. Guide le développement du code, des tests, jusqu'à la validation complète.

## When to Use

- **Pour chaque story** du sprint
- **En continu** pendant le sprint
- **Après sprint planning** - Une fois les stories définies

## Inputs

- `docs/sprint-status.yaml` - Statut du sprint
- `docs/stories/STORY-{id}.md` - Story à implémenter
- `docs/architecture-{project}-{date}.md` - Architecture de référence
- Codebase existante

## Outputs

- Code implémenté (fichiers sources)
- Tests (unitaires, intégration, E2E)
- Documentation mise à jour
- `docs/sprint-status.yaml` mis à jour
- Git commits + branch

## Workflow Steps

### 1. Sélectionner la Story

```
Per helpers.md#Get-Next-Story, find next story to implement
Per helpers.md#Check-Dependencies-Met, verify dependencies are satisfied
```

Si dépendances non-satisfaites → choisir une autre story

### 2. Comprendre les Requirements

#### Lire la story complète
```bash
cat docs/stories/STORY-{id}.md
```

#### Questions à répondre :
- **Quoi:** Quelle est la fonctionnalité ?
- **Pourquoi:** Quelle valeur apporte-t-elle ?
- **Pour qui:** Qui est l'utilisateur ?
- **Comment:** Comment valider que c'est fait ?

#### Clarifier si nécessaire
Si quelque chose n'est pas clair :
1. Consulter le PRD
2. Consulter l'architecture
3. Poser une question au PO/tech lead

### 3. Créer une Branche Git

```bash
# Convention: feature/STORY-{id}-{slug}
git checkout -b feature/STORY-002-user-authentication

# Vérifier qu'on est bien sur la nouvelle branche
git branch
```

### 4. Marquer la Story "In Progress"

```
Per helpers.md#Update-Sprint-Status, story_id="STORY-002", new_status="in_progress"
```

### 5. Concevoir la Solution

#### Analyser le code existant
```bash
# Explorer la structure
ls -la app/ resources/ routes/

# Comprendre les patterns
grep -r "class.*Controller" app/Http/Controllers/
grep -r "class.*Model" app/Models/
```

#### Identifier les fichiers à créer/modifier

**Example pour STORY-002 (User Authentication):**
```
Files to create:
- app/Http/Controllers/AuthController.php
- app/Http/Requests/LoginRequest.php
- app/Http/Requests/RegisterRequest.php
- tests/Feature/AuthTest.php
- routes/api.php (modify)

Files to modify:
- config/sanctum.php (review)
- README.md (add auth documentation)
```

#### Pseudocode / Algorithme

Si logique complexe, écrire le pseudocode d'abord :
```
Function: login(email, password)
  1. Validate inputs (email format, password not empty)
  2. Find user by email
  3. If user not found → return 401 "Invalid credentials"
  4. Check password hash
  5. If password invalid → return 401 "Invalid credentials"
  6. Generate Sanctum token
  7. Return token + user data
```

### 6. Implémenter (TDD Approach)

#### A. Écrire les tests d'abord (TDD)

```php
// tests/Feature/AuthTest.php
class AuthTest extends TestCase
{
    public function test_user_can_login_with_valid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['token', 'user']);
    }

    public function test_user_cannot_login_with_invalid_credentials()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrong',
        ]);

        $response->assertStatus(401);
    }
}
```

#### B. Exécuter les tests (ils doivent échouer)

```bash
php artisan test tests/Feature/AuthTest.php
# Expected: FAILED (not implemented yet)
```

#### C. Implémenter le code minimum pour passer les tests

```php
// app/Http/Controllers/AuthController.php
class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        // Validate credentials
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        $user = Auth::user();
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user,
        ]);
    }
}
```

#### D. Exécuter les tests (ils doivent passer)

```bash
php artisan test tests/Feature/AuthTest.php
# Expected: PASSED
```

#### E. Refactor (améliorer le code)

```php
// Extract to service if logic becomes complex
class AuthService
{
    public function login(string $email, string $password): array
    {
        // ...
    }
}
```

### 7. Implémenter tous les Acceptance Criteria

Pour chaque critère d'acceptance :

```markdown
- [ ] Un utilisateur peut se connecter avec email/password
```

1. Écrire test
2. Implémenter code
3. Valider test passe
4. Marquer critère comme validé ✓

```
Per helpers.md#Validate-Acceptance-Criteria, story_id="STORY-002", criterion_index=0, is_met=true
```

### 8. Écrire les Tests Complets

#### Tests unitaires (60% du testing effort)
```php
// tests/Unit/UserTest.php
public function test_user_can_generate_auth_token()
{
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $this->assertNotNull($token);
    $this->assertIsString($token);
}
```

#### Tests d'intégration (30%)
```php
// tests/Feature/AuthTest.php
public function test_authenticated_user_can_access_protected_route()
{
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withToken($token)->getJson('/api/user');

    $response->assertStatus(200);
}
```

#### Tests E2E (10%) - si applicable
```javascript
// tests/e2e/auth.spec.js
test('user can login via UI', async ({ page }) => {
  await page.goto('/login');
  await page.fill('#email', 'test@example.com');
  await page.fill('#password', 'password');
  await page.click('button[type="submit"]');

  await expect(page).toHaveURL('/dashboard');
});
```

### 9. Vérifier la Qualité du Code

#### Linting
```bash
# PHP
./vendor/bin/phpcs
./vendor/bin/phpstan analyse

# JavaScript
npm run lint
```

#### Security
```bash
# Check for vulnerabilities
composer audit
npm audit
```

#### Coverage
```bash
php artisan test --coverage
# Target: >= 80%
```

### 10. Documenter

#### Inline comments (pour logique complexe)
```php
// Rate limit authentication attempts to prevent brute force
// Allow 5 attempts per minute per IP
RateLimiter::for('login', function (Request $request) {
    return Limit::perMinute(5)->by($request->ip());
});
```

#### API Documentation
```php
/**
 * Authenticate user
 *
 * @param LoginRequest $request
 * @return JsonResponse
 *
 * @OA\Post(
 *     path="/api/auth/login",
 *     tags={"Authentication"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"email","password"},
 *             @OA\Property(property="email", type="string", format="email"),
 *             @OA\Property(property="password", type="string", format="password")
 *         )
 *     ),
 *     @OA\Response(response=200, description="Login successful"),
 *     @OA\Response(response=401, description="Invalid credentials")
 * )
 */
```

#### README updates
Si nécessaire, mettre à jour le README avec nouvelles instructions.

### 11. Commiter le Code

```bash
# Add files
git add app/Http/Controllers/AuthController.php
git add app/Http/Requests/LoginRequest.php
git add tests/Feature/AuthTest.php
git add routes/api.php

# Commit avec message clair
git commit -m "feat(auth): implement user authentication (STORY-002)

- Add AuthController with login/logout endpoints
- Add LoginRequest validation
- Add tests for authentication flow
- Configure Sanctum for SPA authentication

Acceptance Criteria:
✓ Users can login with email/password
✓ Users can logout
✓ Invalid credentials return 401
✓ Rate limiting prevents brute force"

# Push to remote
git push -u origin feature/STORY-002-user-authentication
```

### 12. Self Code Review

Avant de marquer comme "completed", faire une auto-review :

#### Checklist:
- [ ] Tous les acceptance criteria satisfaits
- [ ] Tous les tests passent
- [ ] Coverage >= 80%
- [ ] Pas de code commenté
- [ ] Pas de console.log / dd() oubliés
- [ ] Pas de TODOs critiques
- [ ] Conventions respectées
- [ ] Performance acceptable
- [ ] Sécurité vérifiée (OWASP)
- [ ] Documentation à jour

### 13. Exécuter le Code Reviewer Agent

```bash
claude --agent bmad/agents/code-reviewer.md --story STORY-002
```

Si issues trouvées → corriger et recommencer review

### 14. Marquer la Story "Completed"

```
Per helpers.md#Update-Sprint-Status, story_id="STORY-002", new_status="completed", actual_points=5
```

### 15. Documenter l'Implémentation

Ajouter dans `docs/stories/STORY-002.md` :

```markdown
## Implementation Notes

**Completed:** 2026-02-10T15:30:00Z
**Actual Points:** 5 (estimated: 5)
**Branch:** feature/STORY-002-user-authentication

### Files Created
- `app/Http/Controllers/AuthController.php` - Authentication controller
- `app/Http/Requests/LoginRequest.php` - Login validation
- `app/Http/Requests/RegisterRequest.php` - Register validation
- `tests/Feature/AuthTest.php` - Authentication tests

### Files Modified
- `routes/api.php` - Added auth routes
- `config/sanctum.php` - Configured token expiry

### Key Decisions
1. **Sanctum over Passport:** Simpler for SPA, sufficient for our needs
2. **Rate limiting:** 5 attempts/minute to prevent brute force
3. **Token expiry:** 24 hours (configurable via .env)

### Tests
- 12 tests written (8 unit, 4 integration)
- Coverage: 95%
- All tests passing ✓

### Performance
- Login endpoint: avg 85ms
- Logout endpoint: avg 42ms
- Token generation: avg 15ms

### Security Considerations
- Passwords hashed with bcrypt (cost: 12)
- Rate limiting on login endpoint
- CSRF protection via Sanctum
- Token stored in httpOnly cookie

### Known Issues / Tech Debt
None for this story

### Next Steps
- STORY-003: Implement password reset flow
```

## Implementation Patterns

### Pattern 1: Controller → Service → Repository

```php
// Controller (thin)
class ProjectController
{
    public function store(StoreProjectRequest $request, ProjectService $service)
    {
        $project = $service->createProject($request->validated());
        return new ProjectResource($project);
    }
}

// Service (business logic)
class ProjectService
{
    public function createProject(array $data): Project
    {
        // Business logic here
        return $this->repository->create($data);
    }
}

// Repository (data access)
class ProjectRepository
{
    public function create(array $data): Project
    {
        return Project::create($data);
    }
}
```

### Pattern 2: Form Requests for Validation

```php
// app/Http/Requests/StoreProjectRequest.php
class StoreProjectRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:projects,name,NULL,id,user_id,' . auth()->id()],
            'url' => ['required', 'url', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'You already have a project with this name.',
        ];
    }
}
```

### Pattern 3: API Resources for Responses

```php
// app/Http/Resources/ProjectResource.php
class ProjectResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'url' => $this->url,
            'created_at' => $this->created_at->toISOString(),
            'backlinks_count' => $this->whenLoaded('backlinks', fn() => $this->backlinks->count()),
        ];
    }
}
```

## Best Practices

### Code Quality
1. **SOLID principles** - Single responsibility, etc.
2. **DRY** - Don't repeat yourself
3. **KISS** - Keep it simple
4. **YAGNI** - You ain't gonna need it

### Testing
1. **Test first** - TDD approach
2. **Test coverage** - >= 80%
3. **Test naming** - Descriptive names
4. **Test isolation** - Each test independent

### Git
1. **Small commits** - Atomic, focused
2. **Clear messages** - Conventional commits format
3. **Branch naming** - feature/STORY-{id}-{slug}
4. **Push regularly** - Don't lose work

### Security
1. **Validate inputs** - Never trust user data
2. **Sanitize outputs** - Prevent XSS
3. **Use prepared statements** - Prevent SQL injection
4. **Rate limit** - Prevent abuse

## Common Pitfalls

❌ **Avoid:**
- Implementing before understanding requirements
- Skipping tests "for speed"
- Large commits (100+ files)
- Hardcoding values (use config/env)
- Ignoring error handling

✅ **Do:**
- Read the story carefully
- Write tests first
- Commit frequently
- Use configuration
- Handle errors gracefully

## Usage Example

```bash
# Implémenter la prochaine story
claude --workflow bmad/workflows/implementation.md

# Implémenter une story spécifique
claude --workflow bmad/workflows/implementation.md --story STORY-002
```

## Related Workflows

- `workflows/sprint-planning.md` - Plan du sprint
- `workflows/testing.md` - Stratégie de testing
- `workflows/code-review.md` - Review du code

## Related Agents

- `agents/story-implementer.md` - Automatise ce workflow
- `agents/code-reviewer.md` - Review le code
- `agents/story-validator.md` - Valide la story

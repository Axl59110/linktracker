<?php

namespace Tests\Feature;

use App\Models\Backlink;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Tests\TestCase;

/**
 * Tests STORY-044 : Rate limiting avancé par IP et par utilisateur
 *
 * Vérifie que les named rate limiters retournent 429 après dépassement des seuils.
 * Les limites configurées dans AppServiceProvider::configureRateLimiting() :
 *   - backlink-check   : 10 req/min par utilisateur authentifié
 *   - backlink-import  : 5 req/min par utilisateur authentifié
 *   - seo-refresh      : 3 req/min par utilisateur authentifié
 */
class RateLimitingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Project $project;
    private Backlink $backlink;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user     = User::factory()->create();
        $this->project  = Project::factory()->for($this->user)->create();
        $this->backlink = Backlink::factory()->create([
            'project_id' => $this->project->id,
            'source_url' => 'https://example.com/article',
            'target_url' => 'https://example.org',
        ]);
    }

    // ============================================================
    // Tests : seo-refresh (3 req/min) - limite la plus petite, test direct
    // ============================================================

    public function test_seo_refresh_is_blocked_after_3_requests(): void
    {
        // Envoyer 3 requêtes (toutes passent)
        for ($i = 0; $i < 3; $i++) {
            $response = $this->actingAs($this->user)
                ->post(route('backlinks.seo-metrics', $this->backlink));
            $this->assertNotEquals(429, $response->getStatusCode(),
                "La requête #{$i} ne devrait pas être bloquée");
        }

        // La 4ème doit retourner 429
        $response = $this->actingAs($this->user)
            ->post(route('backlinks.seo-metrics', $this->backlink));

        $response->assertStatus(429);
    }

    public function test_seo_refresh_429_includes_retry_after_header(): void
    {
        // Envoyer 3 requêtes pour atteindre la limite
        for ($i = 0; $i < 3; $i++) {
            $this->actingAs($this->user)
                ->post(route('backlinks.seo-metrics', $this->backlink));
        }

        $response = $this->actingAs($this->user)
            ->post(route('backlinks.seo-metrics', $this->backlink));

        $response->assertStatus(429);
        $response->assertHeader('Retry-After');
    }

    // ============================================================
    // Tests : différents utilisateurs ont des compteurs indépendants
    // ============================================================

    public function test_different_users_have_independent_rate_limits(): void
    {
        $user2 = User::factory()->create();

        // Atteindre la limite seo-refresh pour user1 (3 requêtes + 1 bloquée)
        for ($i = 0; $i < 3; $i++) {
            $this->actingAs($this->user)
                ->post(route('backlinks.seo-metrics', $this->backlink));
        }

        // user1 est maintenant bloqué
        $this->actingAs($this->user)
            ->post(route('backlinks.seo-metrics', $this->backlink))
            ->assertStatus(429);

        // user2 n'est PAS bloqué (compteur indépendant)
        $response = $this->actingAs($user2)
            ->post(route('backlinks.seo-metrics', $this->backlink));
        $this->assertNotEquals(429, $response->getStatusCode(),
            'user2 ne devrait pas être affecté par la limite de user1');
    }

    // ============================================================
    // Tests : configuration des named rate limiters
    // ============================================================

    public function test_named_rate_limiters_are_registered(): void
    {
        // Vérifier que les named rate limiters ont été enregistrés
        $this->assertTrue(
            \Illuminate\Support\Facades\RateLimiter::limiter('backlink-check') !== null,
            'Le rate limiter backlink-check doit être enregistré'
        );
        $this->assertTrue(
            \Illuminate\Support\Facades\RateLimiter::limiter('backlink-import') !== null,
            'Le rate limiter backlink-import doit être enregistré'
        );
        $this->assertTrue(
            \Illuminate\Support\Facades\RateLimiter::limiter('seo-refresh') !== null,
            'Le rate limiter seo-refresh doit être enregistré'
        );
    }

    public function test_backlink_check_route_accepts_first_request(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('backlinks.check', $this->backlink));

        // La première requête ne doit PAS être bloquée par le rate limiter
        $this->assertNotEquals(429, $response->getStatusCode(),
            'La première requête ne devrait pas être bloquée');
    }

    public function test_backlink_import_get_form_is_accessible(): void
    {
        // Le formulaire GET n'a pas de rate limit (seulement le POST)
        $response = $this->actingAs($this->user)
            ->get(route('backlinks.import'));

        $response->assertStatus(200);
    }

    public function test_backlink_import_post_is_throttled(): void
    {
        // Envoyer 5 requêtes POST (elles passent, même si validation échoue)
        for ($i = 0; $i < 5; $i++) {
            $response = $this->actingAs($this->user)
                ->post(route('backlinks.import.process'), []);
            // Ne pas vérifier le status ici car la validation peut échouer (422)
            // L'important est que ça ne soit pas 429
            $this->assertNotEquals(429, $response->getStatusCode(),
                "La requête #{$i} ne devrait pas être bloquée par le rate limiter");
        }

        // La 6ème doit retourner 429 (rate limit atteinte)
        $response = $this->actingAs($this->user)
            ->post(route('backlinks.import.process'), []);
        $response->assertStatus(429);
    }

    public function test_backlink_check_rate_limit_headers_are_present(): void
    {
        // La première requête doit avoir les en-têtes X-RateLimit-*
        $response = $this->actingAs($this->user)
            ->post(route('backlinks.check', $this->backlink));

        $this->assertNotEquals(429, $response->getStatusCode());
        // Les en-têtes X-RateLimit sont présents sur toutes les réponses throttlées
        $response->assertHeader('X-RateLimit-Limit');
        $response->assertHeader('X-RateLimit-Remaining');
    }
}

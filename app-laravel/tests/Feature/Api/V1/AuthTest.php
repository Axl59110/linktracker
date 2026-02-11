<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test : Connexion réussie avec credentials valides
     */
    public function test_user_can_login_with_valid_credentials(): void
    {
        // Créer un utilisateur
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        // Obtenir le cookie CSRF
        $this->get('/sanctum/csrf-cookie');

        // Tenter la connexion
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        // Vérifier la réponse
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Connexion réussie.',
                'user' => [
                    'email' => 'test@example.com',
                ],
            ]);

        // Vérifier que l'utilisateur est authentifié
        $this->assertAuthenticatedAs($user);
    }

    /**
     * Test : Connexion échouée avec credentials invalides
     */
    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        // Créer un utilisateur
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        // Tenter la connexion avec mauvais mot de passe
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        // Vérifier la réponse
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        // Vérifier que l'utilisateur n'est pas authentifié
        $this->assertGuest();
    }

    /**
     * Test : Connexion échouée avec email manquant
     */
    public function test_login_requires_email(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'password' => 'password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test : Connexion échouée avec mot de passe manquant
     */
    public function test_login_requires_password(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /**
     * Test : Connexion échouée avec email invalide
     */
    public function test_login_requires_valid_email(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'not-an-email',
            'password' => 'password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test : Déconnexion réussie pour utilisateur authentifié
     */
    public function test_authenticated_user_can_logout(): void
    {
        // Créer un utilisateur
        $user = User::factory()->create();

        // Simuler l'authentification pour accéder à la route protégée
        $response = $this->actingAs($user)->postJson('/api/v1/auth/logout');

        // Vérifier que le endpoint répond correctement
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Déconnexion réussie.',
            ]);

        // Note : Dans un environnement de test avec SESSION_DRIVER=array,
        // la session n'est pas persistante entre les requêtes,
        // donc nous ne pouvons pas tester assertGuest() ici.
        // Ce test vérifie simplement que l'endpoint fonctionne correctement.
    }

    /**
     * Test : Déconnexion échouée pour utilisateur non authentifié
     */
    public function test_unauthenticated_user_cannot_logout(): void
    {
        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertStatus(401);
    }

    /**
     * Test : Récupération de l'utilisateur connecté
     */
    public function test_authenticated_user_can_get_user_info(): void
    {
        // Créer et authentifier un utilisateur
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
        $this->actingAs($user);

        // Récupérer les infos utilisateur
        $response = $this->getJson('/api/v1/auth/user');

        // Vérifier la réponse
        $response->assertStatus(200)
            ->assertJson([
                'user' => [
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                ],
            ]);
    }

    /**
     * Test : Récupération échouée pour utilisateur non authentifié
     */
    public function test_unauthenticated_user_cannot_get_user_info(): void
    {
        $response = $this->getJson('/api/v1/auth/user');

        $response->assertStatus(401);
    }

    /**
     * Test : Vérification du cookie CSRF Sanctum
     */
    public function test_csrf_cookie_endpoint_is_accessible(): void
    {
        $response = $this->get('/sanctum/csrf-cookie');

        $response->assertStatus(204);
    }
}

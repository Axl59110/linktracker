<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Tests STORY-046 : Page profil utilisateur
 */
class ProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'password' => Hash::make('current-password-123'),
        ]);
    }

    public function test_profile_page_is_accessible(): void
    {
        $response = $this->actingAs($this->user)->get(route('profile.show'));

        $response->assertStatus(200);
        $response->assertSee($this->user->name);
        $response->assertSee($this->user->email);
        $response->assertSee('Changer le mot de passe');
    }

    public function test_profile_page_requires_auth(): void
    {
        $response = $this->get(route('profile.show'));

        // Redirigé vers login ou sous-page (dépend de la configuration auth)
        $this->assertNotEquals(200, $response->status());
    }

    public function test_password_can_be_updated(): void
    {
        $response = $this->actingAs($this->user)
            ->patch(route('profile.password'), [
                'current_password'      => 'current-password-123',
                'password'              => 'new-secure-password',
                'password_confirmation' => 'new-secure-password',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Vérifier que le mot de passe a bien été mis à jour
        $this->user->refresh();
        $this->assertTrue(Hash::check('new-secure-password', $this->user->password));
    }

    public function test_wrong_current_password_is_rejected(): void
    {
        $response = $this->actingAs($this->user)
            ->patch(route('profile.password'), [
                'current_password'      => 'wrong-password',
                'password'              => 'new-secure-password',
                'password_confirmation' => 'new-secure-password',
            ]);

        $response->assertSessionHasErrors('current_password');

        // Le mot de passe n'a pas changé
        $this->user->refresh();
        $this->assertTrue(Hash::check('current-password-123', $this->user->password));
    }

    public function test_password_too_short_is_rejected(): void
    {
        $response = $this->actingAs($this->user)
            ->patch(route('profile.password'), [
                'current_password'      => 'current-password-123',
                'password'              => 'short',
                'password_confirmation' => 'short',
            ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_password_confirmation_mismatch_is_rejected(): void
    {
        $response = $this->actingAs($this->user)
            ->patch(route('profile.password'), [
                'current_password'      => 'current-password-123',
                'password'              => 'new-secure-password',
                'password_confirmation' => 'different-password',
            ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_current_password_is_required(): void
    {
        $response = $this->actingAs($this->user)
            ->patch(route('profile.password'), [
                'password'              => 'new-secure-password',
                'password_confirmation' => 'new-secure-password',
            ]);

        $response->assertSessionHasErrors('current_password');
    }
}

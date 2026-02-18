<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'check_frequency'      => 'daily',
            'http_timeout'         => 30,
            'email_alerts_enabled' => true,
            'seo_provider'         => 'custom',
        ]);
        $this->actingAs($this->user);
    }

    public function test_settings_page_loads(): void
    {
        $response = $this->get('/settings');
        $response->assertStatus(200);
        $response->assertSee('Paramètres');
        $response->assertSee('Monitoring');
    }

    public function test_can_update_monitoring_settings(): void
    {
        $response = $this->patch('/settings/monitoring', [
            'check_frequency'      => 'hourly',
            'http_timeout'         => 45,
            'email_alerts_enabled' => '1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->user->refresh();
        $this->assertEquals('hourly', $this->user->check_frequency);
        $this->assertEquals(45, $this->user->http_timeout);
        $this->assertTrue($this->user->email_alerts_enabled);
    }

    public function test_can_disable_email_alerts(): void
    {
        $response = $this->patch('/settings/monitoring', [
            'check_frequency' => 'daily',
            'http_timeout'    => 30,
            // email_alerts_enabled absent = false (hidden input envoie 0)
        ]);

        $response->assertRedirect();
        $this->user->refresh();
        $this->assertFalse($this->user->email_alerts_enabled);
    }

    public function test_monitoring_validation_rejects_invalid_frequency(): void
    {
        $response = $this->patch('/settings/monitoring', [
            'check_frequency' => 'invalid',
            'http_timeout'    => 30,
        ]);

        $response->assertSessionHasErrors('check_frequency');
    }

    public function test_monitoring_validation_rejects_bad_timeout(): void
    {
        $response = $this->patch('/settings/monitoring', [
            'check_frequency' => 'daily',
            'http_timeout'    => 200,
        ]);

        $response->assertSessionHasErrors('http_timeout');
    }

    public function test_can_update_seo_provider_to_custom(): void
    {
        $response = $this->patch('/settings/seo', [
            'seo_provider' => 'custom',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->user->refresh();
        $this->assertEquals('custom', $this->user->seo_provider);
    }

    public function test_can_update_seo_provider_to_moz_with_key(): void
    {
        $response = $this->patch('/settings/seo', [
            'seo_provider' => 'moz',
            'seo_api_key'  => 'myaccessid:mysecret',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->user->refresh();
        $this->assertEquals('moz', $this->user->seo_provider);
        $this->assertNotNull($this->user->seo_api_key_encrypted);
    }

    public function test_settings_requires_auth(): void
    {
        auth()->logout();
        $response = $this->get('/settings');
        // Redirigé vers login ou 404 selon le guard
        $this->assertNotEquals(200, $response->status());
    }
}

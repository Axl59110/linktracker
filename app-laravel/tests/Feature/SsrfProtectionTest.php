<?php

namespace Tests\Feature;

use App\Models\Backlink;
use App\Models\Order;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests STORY-043 : Protection SSRF sur les champs URL
 * Vérifie que les IPs privées et localhost sont bloqués
 */
class SsrfProtectionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user    = User::factory()->create();
        $this->project = Project::factory()->for($this->user)->create();
    }

    // ============================================================
    // BacklinkController — store()
    // ============================================================

    public function test_backlink_store_blocks_localhost_source_url(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('backlinks.store'), [
                'project_id' => $this->project->id,
                'source_url' => 'http://localhost/article',
                'target_url' => 'https://mysite.com',
                'tier_level' => 'tier1',
                'spot_type'  => 'external',
            ]);

        $response->assertSessionHasErrors('source_url');
        $this->assertEquals(0, Backlink::count());
    }

    public function test_backlink_store_blocks_private_ip_source_url(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('backlinks.store'), [
                'project_id' => $this->project->id,
                'source_url' => 'http://192.168.1.1/article',
                'target_url' => 'https://mysite.com',
                'tier_level' => 'tier1',
                'spot_type'  => 'external',
            ]);

        $response->assertSessionHasErrors('source_url');
        $this->assertEquals(0, Backlink::count());
    }

    public function test_backlink_store_blocks_loopback_ip_source_url(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('backlinks.store'), [
                'project_id' => $this->project->id,
                'source_url' => 'http://127.0.0.1/page',
                'target_url' => 'https://mysite.com',
                'tier_level' => 'tier1',
                'spot_type'  => 'external',
            ]);

        $response->assertSessionHasErrors('source_url');
        $this->assertEquals(0, Backlink::count());
    }

    public function test_backlink_store_allows_valid_public_source_url(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('backlinks.store'), [
                'project_id' => $this->project->id,
                'source_url' => 'https://example.com/article',
                'target_url' => 'https://example.org',
                'tier_level' => 'tier1',
                'spot_type'  => 'external',
            ]);

        $response->assertSessionDoesntHaveErrors('source_url');
        $this->assertEquals(1, Backlink::count());
    }

    // ============================================================
    // BacklinkController — update()
    // ============================================================

    public function test_backlink_update_blocks_private_ip_source_url(): void
    {
        $backlink = Backlink::factory()->create([
            'project_id' => $this->project->id,
            'source_url' => 'https://example.com/article',
        ]);

        $response = $this->actingAs($this->user)
            ->put(route('backlinks.update', $backlink), [
                'project_id' => $this->project->id,
                'source_url' => 'http://10.0.0.1/malicious',
                'target_url' => 'https://mysite.com',
                'tier_level' => 'tier1',
                'spot_type'  => 'external',
            ]);

        $response->assertSessionHasErrors('source_url');

        // L'URL ne doit pas avoir changé
        $backlink->refresh();
        $this->assertEquals('https://example.com/article', $backlink->source_url);
    }

    public function test_backlink_update_allows_valid_public_source_url(): void
    {
        $backlink = Backlink::factory()->create([
            'project_id' => $this->project->id,
            'source_url' => 'https://example.com/article',
        ]);

        $response = $this->actingAs($this->user)
            ->put(route('backlinks.update', $backlink), [
                'project_id' => $this->project->id,
                'source_url' => 'https://example.net/new-article',
                'target_url' => 'https://example.org',
                'tier_level' => 'tier1',
                'spot_type'  => 'external',
            ]);

        $response->assertSessionDoesntHaveErrors('source_url');

        $backlink->refresh();
        $this->assertEquals('https://example.net/new-article', $backlink->source_url);
    }

    // ============================================================
    // OrderController — store()
    // ============================================================

    public function test_order_store_blocks_localhost_source_url(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('orders.store'), [
                'project_id' => $this->project->id,
                'target_url' => 'https://mysite.com',
                'source_url' => 'http://localhost/internal',
                'tier_level' => 'tier1',
                'spot_type'  => 'external',
            ]);

        $response->assertSessionHasErrors('source_url');
        $this->assertEquals(0, Order::count());
    }

    public function test_order_store_blocks_private_ip_source_url(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('orders.store'), [
                'project_id' => $this->project->id,
                'target_url' => 'https://mysite.com',
                'source_url' => 'http://172.16.0.1/page',
                'tier_level' => 'tier1',
                'spot_type'  => 'external',
            ]);

        $response->assertSessionHasErrors('source_url');
        $this->assertEquals(0, Order::count());
    }

    public function test_order_store_allows_null_source_url(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('orders.store'), [
                'project_id' => $this->project->id,
                'target_url' => 'https://mysite.com',
                'source_url' => null,
                'tier_level' => 'tier1',
                'spot_type'  => 'external',
            ]);

        $response->assertSessionDoesntHaveErrors('source_url');
        $this->assertEquals(1, Order::count());
    }

    public function test_order_store_allows_valid_public_source_url(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('orders.store'), [
                'project_id' => $this->project->id,
                'target_url' => 'https://mysite.com',
                'source_url' => 'https://example.com/article',
                'tier_level' => 'tier1',
                'spot_type'  => 'external',
            ]);

        $response->assertSessionDoesntHaveErrors('source_url');
        $this->assertEquals(1, Order::count());
    }

    // ============================================================
    // OrderController — update()
    // ============================================================

    public function test_order_update_blocks_private_ip_source_url(): void
    {
        $order = Order::factory()->create([
            'project_id' => $this->project->id,
            'source_url' => 'https://example.com/article',
        ]);

        $response = $this->actingAs($this->user)
            ->put(route('orders.update', $order), [
                'project_id' => $this->project->id,
                'target_url' => 'https://mysite.com',
                'source_url' => 'http://192.168.0.100/internal',
                'tier_level' => 'tier1',
                'spot_type'  => 'external',
            ]);

        $response->assertSessionHasErrors('source_url');

        $order->refresh();
        $this->assertEquals('https://example.com/article', $order->source_url);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Backlink;
use App\Models\Order;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests STORY-036 : Auto-création Backlink lors publication Order
 */
class OrderAutoBacklinkTest extends TestCase
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

    public function test_publishing_order_creates_backlink_automatically(): void
    {
        $order = Order::factory()->create([
            'project_id' => $this->project->id,
            'source_url' => 'https://example.com/article',
            'target_url' => 'https://mysite.com',
            'anchor_text' => 'Mon site',
            'tier_level' => 'tier1',
            'spot_type' => 'external',
            'status' => 'in_progress',
        ]);

        $response = $this->actingAs($this->user)
            ->patch(route('orders.status', $order), ['status' => 'published']);

        $response->assertRedirect();

        $order->refresh();

        $this->assertNotNull($order->backlink_id);
        $this->assertDatabaseHas('backlinks', [
            'project_id' => $this->project->id,
            'source_url' => 'https://example.com/article',
            'target_url' => 'https://mysite.com',
            'anchor_text' => 'Mon site',
            'tier_level' => 'tier1',
            'spot_type' => 'external',
            'status' => 'active',
        ]);
    }

    public function test_publishing_order_links_existing_backlink_if_duplicate(): void
    {
        $existingBacklink = Backlink::factory()->create([
            'project_id' => $this->project->id,
            'source_url' => 'https://existing-source.com/article',
            'target_url' => 'https://mysite.com',
        ]);

        $order = Order::factory()->create([
            'project_id' => $this->project->id,
            'source_url' => 'https://existing-source.com/article',
            'target_url' => 'https://mysite.com',
            'status' => 'in_progress',
        ]);

        $this->actingAs($this->user)
            ->patch(route('orders.status', $order), ['status' => 'published']);

        $order->refresh();

        $this->assertEquals($existingBacklink->id, $order->backlink_id);
        $this->assertCount(1, Backlink::where('source_url', 'https://existing-source.com/article')->get());
    }

    public function test_publishing_order_without_source_url_does_not_create_backlink(): void
    {
        $order = Order::factory()->create([
            'project_id' => $this->project->id,
            'source_url' => null,
            'target_url' => 'https://mysite.com',
            'status' => 'pending',
        ]);

        $this->actingAs($this->user)
            ->patch(route('orders.status', $order), ['status' => 'published']);

        $order->refresh();

        $this->assertNull($order->backlink_id);
    }

    public function test_non_published_status_does_not_create_backlink(): void
    {
        $order = Order::factory()->create([
            'project_id' => $this->project->id,
            'source_url' => 'https://example.com/article',
            'target_url' => 'https://mysite.com',
            'status' => 'pending',
        ]);

        $this->actingAs($this->user)
            ->patch(route('orders.status', $order), ['status' => 'in_progress']);

        $order->refresh();

        $this->assertNull($order->backlink_id);
        $this->assertEquals(0, Backlink::count());
    }

    public function test_publishing_already_linked_order_does_not_duplicate(): void
    {
        $backlink = Backlink::factory()->create([
            'project_id' => $this->project->id,
        ]);

        $order = Order::factory()->create([
            'project_id' => $this->project->id,
            'source_url' => 'https://example.com/article',
            'target_url' => 'https://mysite.com',
            'status' => 'in_progress',
            'backlink_id' => $backlink->id,
        ]);

        $this->actingAs($this->user)
            ->patch(route('orders.status', $order), ['status' => 'published']);

        // Pas de nouveau backlink créé car backlink_id déjà défini
        $this->assertEquals(1, Backlink::count());
    }
}

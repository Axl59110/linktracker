<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Platform;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests STORY-032 : Modèle Order / migration
 * Tests STORY-033 : UI Marketplace (CRUD Commandes)
 */
class OrderTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user    = User::factory()->create();
        $this->project = Project::factory()->for($this->user)->create();
        $this->actingAs($this->user);
    }

    // ── Modèle (STORY-032) ───────────────────────────────────────────────

    public function test_order_can_be_created(): void
    {
        $order = Order::create([
            'project_id' => $this->project->id,
            'target_url' => 'https://monsite.com/page',
            'tier_level' => 'tier1',
            'spot_type'  => 'external',
            'status'     => 'pending',
        ]);

        $this->assertInstanceOf(Order::class, $order);
        $this->assertDatabaseHas('orders', ['target_url' => 'https://monsite.com/page']);
    }

    public function test_order_belongs_to_project(): void
    {
        $order = Order::create([
            'project_id' => $this->project->id,
            'target_url' => 'https://monsite.com',
            'tier_level' => 'tier1',
            'spot_type'  => 'external',
            'status'     => 'pending',
        ]);

        $this->assertEquals($this->project->id, $order->project->id);
    }

    public function test_order_status_label_accessor(): void
    {
        $order = new Order(['status' => 'pending']);
        $this->assertEquals('En attente', $order->status_label);

        $order->status = 'published';
        $this->assertEquals('Publié', $order->status_label);
    }

    public function test_order_status_badge_accessor(): void
    {
        $order = new Order(['status' => 'pending']);
        $this->assertEquals('warning', $order->status_badge);

        $order->status = 'published';
        $this->assertEquals('success', $order->status_badge);

        $order->status = 'cancelled';
        $this->assertEquals('neutral', $order->status_badge);
    }

    public function test_project_has_orders_relation(): void
    {
        Order::create([
            'project_id' => $this->project->id,
            'target_url' => 'https://monsite.com',
            'tier_level' => 'tier1',
            'spot_type'  => 'external',
            'status'     => 'pending',
        ]);

        $this->assertEquals(1, $this->project->orders()->count());
    }

    // ── CRUD UI (STORY-033) ──────────────────────────────────────────────

    public function test_orders_index_is_accessible(): void
    {
        $response = $this->get(route('orders.index'));

        $response->assertStatus(200);
        $response->assertSee('Commandes');
    }

    public function test_orders_index_shows_orders(): void
    {
        Order::create([
            'project_id' => $this->project->id,
            'target_url' => 'https://monsite.com/specific-page',
            'tier_level' => 'tier1',
            'spot_type'  => 'external',
            'status'     => 'pending',
        ]);

        $response = $this->get(route('orders.index'));

        $response->assertStatus(200);
        $response->assertSee('monsite.com');
    }

    public function test_orders_create_form_is_accessible(): void
    {
        $response = $this->get(route('orders.create'));

        $response->assertStatus(200);
        $response->assertSee('Nouvelle commande');
    }

    public function test_order_can_be_stored(): void
    {
        $response = $this->post(route('orders.store'), [
            'project_id' => $this->project->id,
            'target_url' => 'https://monsite.com/page',
            'tier_level' => 'tier1',
            'spot_type'  => 'external',
        ]);

        $response->assertRedirect(route('orders.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('orders', [
            'project_id' => $this->project->id,
            'target_url' => 'https://monsite.com/page',
            'status'     => 'pending',
        ]);
    }

    public function test_order_store_validates_required_fields(): void
    {
        $response = $this->post(route('orders.store'), []);

        $response->assertSessionHasErrors(['project_id', 'target_url', 'tier_level', 'spot_type']);
    }

    public function test_order_show_is_accessible(): void
    {
        $order = Order::create([
            'project_id' => $this->project->id,
            'target_url' => 'https://monsite.com/page',
            'tier_level' => 'tier1',
            'spot_type'  => 'external',
            'status'     => 'pending',
        ]);

        $response = $this->get(route('orders.show', $order));

        $response->assertStatus(200);
        $response->assertSee('monsite.com/page');
    }

    public function test_order_status_can_be_updated(): void
    {
        $order = Order::create([
            'project_id' => $this->project->id,
            'target_url' => 'https://monsite.com',
            'tier_level' => 'tier1',
            'spot_type'  => 'external',
            'status'     => 'pending',
        ]);

        $response = $this->patch(route('orders.status', $order), ['status' => 'published']);

        $response->assertRedirect();
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'published']);
    }

    public function test_order_status_update_validates_status(): void
    {
        $order = Order::create([
            'project_id' => $this->project->id,
            'target_url' => 'https://monsite.com',
            'tier_level' => 'tier1',
            'spot_type'  => 'external',
            'status'     => 'pending',
        ]);

        $response = $this->patch(route('orders.status', $order), ['status' => 'invalid_status']);

        $response->assertSessionHasErrors('status');
    }

    public function test_order_can_be_deleted(): void
    {
        $order = Order::create([
            'project_id' => $this->project->id,
            'target_url' => 'https://monsite.com',
            'tier_level' => 'tier1',
            'spot_type'  => 'external',
            'status'     => 'pending',
        ]);

        $response = $this->delete(route('orders.destroy', $order));

        $response->assertRedirect(route('orders.index'));
        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
    }
}

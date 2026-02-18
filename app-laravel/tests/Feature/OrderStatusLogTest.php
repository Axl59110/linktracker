<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderStatusLog;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests STORY-037 : Timeline historique statut commande
 */
class OrderStatusLogTest extends TestCase
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

    public function test_status_change_creates_log_entry(): void
    {
        $order = Order::factory()->create([
            'project_id' => $this->project->id,
            'status'     => 'pending',
        ]);

        $this->actingAs($this->user)
            ->patch(route('orders.status', $order), ['status' => 'in_progress']);

        $this->assertDatabaseHas('order_status_logs', [
            'order_id'   => $order->id,
            'old_status' => 'pending',
            'new_status' => 'in_progress',
        ]);
    }

    public function test_multiple_status_changes_create_multiple_logs(): void
    {
        $order = Order::factory()->create([
            'project_id' => $this->project->id,
            'status'     => 'pending',
        ]);

        $this->actingAs($this->user)
            ->patch(route('orders.status', $order), ['status' => 'in_progress']);

        $order->refresh();

        $this->actingAs($this->user)
            ->patch(route('orders.status', $order), ['status' => 'published']);

        $this->assertDatabaseCount('order_status_logs', 2);

        $logs = OrderStatusLog::where('order_id', $order->id)
            ->orderBy('changed_at')
            ->get();

        $this->assertEquals('pending', $logs[0]->old_status);
        $this->assertEquals('in_progress', $logs[0]->new_status);
        $this->assertEquals('in_progress', $logs[1]->old_status);
        $this->assertEquals('published', $logs[1]->new_status);
    }

    public function test_order_has_status_logs_relation(): void
    {
        $order = Order::factory()->create([
            'project_id' => $this->project->id,
            'status'     => 'pending',
        ]);

        $this->actingAs($this->user)
            ->patch(route('orders.status', $order), ['status' => 'published']);

        $order->refresh();
        $logs = $order->statusLogs;

        $this->assertCount(1, $logs);
        $this->assertEquals('published', $logs->first()->new_status);
    }

    public function test_status_log_labels_are_human_readable(): void
    {
        $log = new OrderStatusLog([
            'old_status' => 'pending',
            'new_status' => 'in_progress',
        ]);

        $this->assertEquals('En attente', $log->old_status_label);
        $this->assertEquals('En cours', $log->new_status_label);
    }

    public function test_show_page_displays_status_timeline(): void
    {
        $order = Order::factory()->create([
            'project_id' => $this->project->id,
            'status'     => 'in_progress',
        ]);

        OrderStatusLog::create([
            'order_id'   => $order->id,
            'old_status' => 'pending',
            'new_status' => 'in_progress',
            'changed_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('orders.show', $order));

        $response->assertStatus(200);
        $response->assertSee('Historique des statuts');
        $response->assertSee('En cours');
        $response->assertSee('En attente');
    }
}

<?php

namespace Tests\Feature;

use App\Models\Backlink;
use App\Models\BacklinkCheck;
use App\Models\DomainMetric;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests STORY-029 : Graphique évolution backlinks (Chart.js)
 * Tests STORY-030 : Graphique disponibilité globale (uptime donut)
 */
class DashboardChartsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    // ── STORY-029 : chartData endpoint ──────────────────────────────────

    public function test_chart_data_endpoint_returns_json(): void
    {
        $response = $this->get(route('dashboard.chart'));

        $response->assertStatus(200);
        $response->assertJsonStructure(['labels', 'active', 'perfect', 'not_indexed', 'nofollow', 'gained', 'lost', 'delta']);
    }

    public function test_chart_data_default_30_days(): void
    {
        $response = $this->get(route('dashboard.chart'));

        $data = $response->json();
        $this->assertCount(30, $data['labels']);
        $this->assertCount(30, $data['active']);
        $this->assertCount(30, $data['perfect']);
        $this->assertCount(30, $data['not_indexed']);
        $this->assertCount(30, $data['nofollow']);
        $this->assertCount(30, $data['gained']);
        $this->assertCount(30, $data['lost']);
        $this->assertCount(30, $data['delta']);
    }

    public function test_chart_data_7_days(): void
    {
        $response = $this->get(route('dashboard.chart', ['days' => 7]));

        $data = $response->json();
        $this->assertCount(7, $data['labels']);
    }

    public function test_chart_data_90_days(): void
    {
        $response = $this->get(route('dashboard.chart', ['days' => 90]));

        $data = $response->json();
        $this->assertCount(90, $data['labels']);
    }

    public function test_chart_data_invalid_days_defaults_to_30(): void
    {
        $response = $this->get(route('dashboard.chart', ['days' => 999]));

        $data = $response->json();
        $this->assertCount(30, $data['labels']);
    }

    public function test_chart_data_labels_are_formatted_dates(): void
    {
        $response = $this->get(route('dashboard.chart', ['days' => 7]));

        $labels = $response->json('labels');
        // Labels should be in d/m format like "18/02"
        foreach ($labels as $label) {
            $this->assertMatchesRegularExpression('/^\d{2}\/\d{2}$/', $label);
        }
    }

    public function test_chart_data_counts_active_backlinks(): void
    {
        $project = Project::factory()->for($this->user)->create();
        Backlink::factory()->for($project)->create(['status' => 'active']);
        Backlink::factory()->for($project)->create(['status' => 'active']);
        Backlink::factory()->for($project)->create(['status' => 'lost']);

        $response = $this->get(route('dashboard.chart', ['days' => 7]));

        $data = $response->json();
        // At least one day should have active >= 2
        $maxActive = max($data['active']);
        $this->assertGreaterThanOrEqual(2, $maxActive);
    }

    public function test_chart_data_filtered_by_project(): void
    {
        $project1 = Project::factory()->for($this->user)->create();
        $project2 = Project::factory()->for($this->user)->create();

        Backlink::factory()->for($project1)->create(['status' => 'active']);
        Backlink::factory()->for($project1)->create(['status' => 'active']);
        Backlink::factory()->for($project2)->create(['status' => 'active']);

        $allResponse = $this->get(route('dashboard.chart', ['days' => 7]));
        $filteredResponse = $this->get(route('dashboard.chart', ['days' => 7, 'project_id' => $project1->id]));

        $allMax      = max($allResponse->json('active'));
        $filteredMax = max($filteredResponse->json('active'));

        $this->assertGreaterThanOrEqual($filteredMax, $allMax);
    }

    // ── STORY-030 : Dashboard uptime stats ──────────────────────────────

    public function test_dashboard_shows_uptime_rate_when_checks_exist(): void
    {
        $project  = Project::factory()->for($this->user)->create();
        $backlink = Backlink::factory()->for($project)->create();

        // 9 présents sur 10 → 90%
        BacklinkCheck::factory()->count(9)->for($backlink)->create([
            'is_present' => true,
            'checked_at' => now()->subHours(1),
        ]);
        BacklinkCheck::factory()->for($backlink)->create([
            'is_present' => false,
            'checked_at' => now()->subHours(2),
        ]);

        $response = $this->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('90');  // uptimeRate = 90%
        $response->assertSee('vérifs');
    }

    public function test_dashboard_shows_no_checks_message_when_no_data(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('pas de données');
    }

    public function test_dashboard_ignores_checks_older_than_30_days(): void
    {
        $project  = Project::factory()->for($this->user)->create();
        $backlink = Backlink::factory()->for($project)->create();

        // Vérification d'il y a 31 jours → ne doit pas compter
        BacklinkCheck::factory()->for($backlink)->create([
            'is_present' => false,
            'checked_at' => now()->subDays(31),
        ]);

        $response = $this->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('pas de données');
    }

    public function test_dashboard_stats_cards_show_correct_counts(): void
    {
        $project = Project::factory()->for($this->user)->create();
        Backlink::factory()->count(3)->for($project)->create(['status' => 'active']);
        Backlink::factory()->count(2)->for($project)->create(['status' => 'lost']);
        Backlink::factory()->count(1)->for($project)->create(['status' => 'changed']);

        $response = $this->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertViewHas('activeBacklinks', 3);
        $response->assertViewHas('lostBacklinks', 2);
        $response->assertViewHas('changedBacklinks', 1);
    }
}

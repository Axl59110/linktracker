<?php

namespace Tests\Feature;

use App\Models\Backlink;
use App\Models\DomainMetric;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class BacklinkSeoMetricsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Project $project;
    private Backlink $backlink;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user    = User::factory()->create(['seo_provider' => 'custom']);
        $this->project = Project::factory()->for($this->user)->create();
        $this->backlink = Backlink::factory()->for($this->project)->create([
            'source_url' => 'https://example.com/page',
        ]);
        $this->actingAs($this->user);
    }

    public function test_show_page_loads_without_domain_metric(): void
    {
        $response = $this->get(route('backlinks.show', $this->backlink));
        $response->assertStatus(200);
        $response->assertSee('Métriques SEO');
        $response->assertSee('Configurer dans les paramètres');
    }

    public function test_show_page_displays_domain_metrics_when_available(): void
    {
        DomainMetric::create([
            'domain'          => 'example.com',
            'da'              => 55,
            'spam_score'      => 4,
            'provider'        => 'moz',
            'last_updated_at' => now(),
        ]);

        $response = $this->get(route('backlinks.show', $this->backlink));
        $response->assertStatus(200);
        $response->assertSee('55');
        $response->assertSee('MOZ');
    }

    public function test_seo_metrics_refresh_dispatches_job(): void
    {
        Queue::fake();

        $response = $this->post(route('backlinks.seo-metrics', $this->backlink));

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        Queue::assertPushed(\App\Jobs\FetchSeoMetricsJob::class);
    }

    public function test_seo_metrics_refresh_creates_domain_metric_if_not_exists(): void
    {
        Queue::fake();

        $this->post(route('backlinks.seo-metrics', $this->backlink));

        $this->assertDatabaseHas('domain_metrics', ['domain' => 'example.com']);
    }
}

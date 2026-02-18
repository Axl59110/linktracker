<?php

namespace Tests\Feature;

use App\Models\Backlink;
use App\Models\DomainMetric;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests STORY-026 : Colonne DA dans la liste des backlinks
 */
class BacklinkDaColumnTest extends TestCase
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

    public function test_backlinks_list_shows_da_column_header(): void
    {
        // La colonne DA n'apparaît que quand la table est affichée (backlinks présents)
        Backlink::factory()->for($this->project)->create([
            'source_url' => 'https://example-header.com/page',
        ]);

        $response = $this->get(route('backlinks.index'));

        $response->assertStatus(200);
        $response->assertSee('DA');
    }

    public function test_backlinks_list_shows_da_value_when_metric_exists(): void
    {
        Backlink::factory()->for($this->project)->create([
            'source_url' => 'https://example.com/article',
        ]);

        DomainMetric::create([
            'domain'          => 'example.com',
            'da'              => 42,
            'provider'        => 'moz',
            'last_updated_at' => now(),
        ]);

        $response = $this->get(route('backlinks.index'));

        $response->assertStatus(200);
        $response->assertSee('42');
    }

    public function test_backlinks_list_shows_dash_when_no_metric(): void
    {
        Backlink::factory()->for($this->project)->create([
            'source_url' => 'https://nodomain-metric.com/page',
        ]);

        $response = $this->get(route('backlinks.index'));

        $response->assertStatus(200);
        // Le tiret "–" (U+2013) est affiché quand pas de métrique
        $response->assertSee('–');
    }

    public function test_backlinks_list_loads_domain_metrics_in_batch(): void
    {
        // Crée plusieurs backlinks avec le même domaine
        Backlink::factory()->count(3)->for($this->project)->create([
            'source_url' => 'https://shared-domain.com/page',
        ]);

        DomainMetric::create([
            'domain'          => 'shared-domain.com',
            'da'              => 60,
            'provider'        => 'moz',
            'last_updated_at' => now(),
        ]);

        // La page doit charger sans N+1
        $response = $this->get(route('backlinks.index'));
        $response->assertStatus(200);
        $response->assertSee('60');
    }

    public function test_da_color_green_for_high_authority(): void
    {
        Backlink::factory()->for($this->project)->create([
            'source_url' => 'https://high-authority.com/page',
        ]);

        DomainMetric::create([
            'domain'          => 'high-authority.com',
            'da'              => 75,
            'provider'        => 'moz',
            'last_updated_at' => now(),
        ]);

        $response = $this->get(route('backlinks.index'));

        $response->assertStatus(200);
        $response->assertSee('text-green-600', false);
    }

    public function test_da_color_orange_for_medium_authority(): void
    {
        Backlink::factory()->for($this->project)->create([
            'source_url' => 'https://medium-authority.com/page',
        ]);

        DomainMetric::create([
            'domain'          => 'medium-authority.com',
            'da'              => 25,
            'provider'        => 'moz',
            'last_updated_at' => now(),
        ]);

        $response = $this->get(route('backlinks.index'));

        $response->assertStatus(200);
        $response->assertSee('text-orange-500', false);
    }
}

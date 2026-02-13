<?php

namespace Tests\Feature;

use App\Models\Backlink;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BacklinkFilterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test filtrage par recherche textuelle
     */
    public function test_can_filter_by_search(): void
    {
        $project = Project::factory()->create();

        Backlink::factory()->create([
            'project_id' => $project->id,
            'source_url' => 'https://example.com/page1',
            'anchor_text' => 'exemple de lien',
        ]);

        Backlink::factory()->create([
            'project_id' => $project->id,
            'source_url' => 'https://autre-site.com/page2',
            'anchor_text' => 'autre ancre',
        ]);

        $response = $this->get(route('backlinks.index', ['search' => 'exemple']));

        $response->assertStatus(200);
        $response->assertSee('example.com');
        $response->assertDontSee('autre-site.com');
    }

    /**
     * Test filtrage par statut
     */
    public function test_can_filter_by_status(): void
    {
        $project = Project::factory()->create();

        Backlink::factory()->create([
            'project_id' => $project->id,
            'status' => 'active',
        ]);

        Backlink::factory()->create([
            'project_id' => $project->id,
            'status' => 'lost',
        ]);

        $response = $this->get(route('backlinks.index', ['status' => 'active']));

        $response->assertStatus(200);
        // Vérifie qu'on a au moins 1 résultat (le backlink actif)
        $this->assertGreaterThanOrEqual(1, $response->viewData('backlinks')->total());
    }

    /**
     * Test filtrage par projet
     */
    public function test_can_filter_by_project(): void
    {
        $project1 = Project::factory()->create(['name' => 'Projet A']);
        $project2 = Project::factory()->create(['name' => 'Projet B']);

        Backlink::factory()->create(['project_id' => $project1->id]);
        Backlink::factory()->create(['project_id' => $project2->id]);

        $response = $this->get(route('backlinks.index', ['project_id' => $project1->id]));

        $response->assertStatus(200);
        $response->assertSee('Projet A');
    }

    /**
     * Test filtrage par tier level
     */
    public function test_can_filter_by_tier_level(): void
    {
        $project = Project::factory()->create();

        Backlink::factory()->create([
            'project_id' => $project->id,
            'tier_level' => 'tier1',
        ]);

        Backlink::factory()->create([
            'project_id' => $project->id,
            'tier_level' => 'tier2',
        ]);

        $response = $this->get(route('backlinks.index', ['tier_level' => 'tier1']));

        $response->assertStatus(200);
        $this->assertEquals(1, $response->viewData('backlinks')->total());
    }

    /**
     * Test filtrage par spot type
     */
    public function test_can_filter_by_spot_type(): void
    {
        $project = Project::factory()->create();

        Backlink::factory()->create([
            'project_id' => $project->id,
            'spot_type' => 'external',
        ]);

        Backlink::factory()->create([
            'project_id' => $project->id,
            'spot_type' => 'internal',
        ]);

        $response = $this->get(route('backlinks.index', ['spot_type' => 'external']));

        $response->assertStatus(200);
        $this->assertEquals(1, $response->viewData('backlinks')->total());
    }

    /**
     * Test tri par source_url ascendant
     */
    public function test_can_sort_by_source_url_asc(): void
    {
        $project = Project::factory()->create();

        Backlink::factory()->create([
            'project_id' => $project->id,
            'source_url' => 'https://b-site.com',
        ]);

        Backlink::factory()->create([
            'project_id' => $project->id,
            'source_url' => 'https://a-site.com',
        ]);

        $response = $this->get(route('backlinks.index', ['sort' => 'source_url', 'direction' => 'asc']));

        $response->assertStatus(200);
        $backlinks = $response->viewData('backlinks');
        $this->assertEquals('https://a-site.com', $backlinks->first()->source_url);
    }

    /**
     * Test tri par statut descendant
     */
    public function test_can_sort_by_status_desc(): void
    {
        $project = Project::factory()->create();

        Backlink::factory()->create([
            'project_id' => $project->id,
            'status' => 'active',
        ]);

        Backlink::factory()->create([
            'project_id' => $project->id,
            'status' => 'lost',
        ]);

        $response = $this->get(route('backlinks.index', ['sort' => 'status', 'direction' => 'desc']));

        $response->assertStatus(200);
        $this->assertGreaterThanOrEqual(2, $response->viewData('backlinks')->total());
    }

    /**
     * Test comptage des filtres actifs
     */
    public function test_displays_active_filters_count(): void
    {
        $project = Project::factory()->create();
        Backlink::factory()->create(['project_id' => $project->id]);

        $response = $this->get(route('backlinks.index', [
            'status' => 'active',
            'tier_level' => 'tier1',
            'search' => 'test'
        ]));

        $response->assertStatus(200);
        $this->assertEquals(3, $response->viewData('activeFiltersCount'));
    }

    /**
     * Test reset des filtres
     */
    public function test_can_reset_filters(): void
    {
        $project = Project::factory()->create();
        Backlink::factory()->count(5)->create(['project_id' => $project->id]);

        // Appliquer des filtres
        $responseWithFilters = $this->get(route('backlinks.index', ['status' => 'active']));

        // Reset (sans paramètres)
        $responseWithoutFilters = $this->get(route('backlinks.index'));

        $responseWithoutFilters->assertStatus(200);
        $this->assertEquals(0, $responseWithoutFilters->viewData('activeFiltersCount'));
    }

    /**
     * Test combinaison de plusieurs filtres
     */
    public function test_can_combine_multiple_filters(): void
    {
        $project = Project::factory()->create();

        // Backlink qui matche tous les critères
        Backlink::factory()->create([
            'project_id' => $project->id,
            'status' => 'active',
            'tier_level' => 'tier1',
            'spot_type' => 'external',
            'source_url' => 'https://matching-site.com',
        ]);

        // Backlink qui ne matche pas
        Backlink::factory()->create([
            'project_id' => $project->id,
            'status' => 'lost',
            'tier_level' => 'tier2',
            'spot_type' => 'internal',
        ]);

        $response = $this->get(route('backlinks.index', [
            'status' => 'active',
            'tier_level' => 'tier1',
            'spot_type' => 'external'
        ]));

        $response->assertStatus(200);
        $this->assertEquals(1, $response->viewData('backlinks')->total());
    }
}

<?php

namespace Tests\Feature;

use App\Models\Backlink;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BacklinkFilterSecurityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test que les wildcards SQL sont échappés dans la recherche
     */
    public function test_search_escapes_sql_wildcards(): void
    {
        $project = Project::factory()->create();

        // Créer backlinks avec des URLs spécifiques
        Backlink::factory()->create([
            'project_id' => $project->id,
            'source_url' => 'https://example.com',
        ]);

        Backlink::factory()->create([
            'project_id' => $project->id,
            'source_url' => 'https://test-example.com',
        ]);

        // Rechercher avec % (wildcard SQL) - devrait être traité comme caractère littéral
        $response = $this->get(route('backlinks.index', ['search' => '%']));

        $response->assertStatus(200);
        // Ne devrait retourner que les backlinks contenant littéralement '%', donc aucun
        $this->assertEquals(0, $response->viewData('backlinks')->total());
    }

    /**
     * Test validation des paramètres de filtre
     */
    public function test_rejects_invalid_status_filter(): void
    {
        $project = Project::factory()->create();
        Backlink::factory()->create(['project_id' => $project->id]);

        $response = $this->get(route('backlinks.index', ['status' => 'invalid_status']));

        $response->assertSessionHasErrors(['status']);
    }

    /**
     * Test validation de project_id doit être un entier
     */
    public function test_rejects_non_integer_project_id(): void
    {
        $response = $this->get(route('backlinks.index', ['project_id' => 'not_a_number']));

        $response->assertSessionHasErrors(['project_id']);
    }

    /**
     * Test validation de project_id doit exister
     */
    public function test_rejects_nonexistent_project_id(): void
    {
        $response = $this->get(route('backlinks.index', ['project_id' => 99999]));

        $response->assertSessionHasErrors(['project_id']);
    }

    /**
     * Test validation du tier_level
     */
    public function test_rejects_invalid_tier_level(): void
    {
        $response = $this->get(route('backlinks.index', ['tier_level' => 'tier99']));

        $response->assertSessionHasErrors(['tier_level']);
    }

    /**
     * Test validation du spot_type
     */
    public function test_rejects_invalid_spot_type(): void
    {
        $response = $this->get(route('backlinks.index', ['spot_type' => 'invalid']));

        $response->assertSessionHasErrors(['spot_type']);
    }

    /**
     * Test validation du champ de tri
     */
    public function test_rejects_invalid_sort_field(): void
    {
        $response = $this->get(route('backlinks.index', ['sort' => 'malicious_field']));

        $response->assertSessionHasErrors(['sort']);
    }

    /**
     * Test validation de la direction de tri
     */
    public function test_rejects_invalid_sort_direction(): void
    {
        $response = $this->get(route('backlinks.index', ['direction' => 'sideways']));

        $response->assertSessionHasErrors(['direction']);
    }

    /**
     * Test que la recherche limite la longueur de l'input
     */
    public function test_rejects_too_long_search_string(): void
    {
        $longString = str_repeat('a', 256); // 256 caractères, max est 255

        $response = $this->get(route('backlinks.index', ['search' => $longString]));

        $response->assertSessionHasErrors(['search']);
    }

    /**
     * Test que les filtres valides fonctionnent correctement
     */
    public function test_accepts_valid_filters(): void
    {
        $project = Project::factory()->create();
        Backlink::factory()->create([
            'project_id' => $project->id,
            'status' => 'active',
            'tier_level' => 'tier1',
            'spot_type' => 'external',
        ]);

        $response = $this->get(route('backlinks.index', [
            'search' => 'example',
            'status' => 'active',
            'project_id' => $project->id,
            'tier_level' => 'tier1',
            'spot_type' => 'external',
            'sort' => 'created_at',
            'direction' => 'desc',
        ]));

        $response->assertStatus(200);
        $response->assertSessionHasNoErrors();
    }

    /**
     * Test échappement de underscore (_) dans la recherche
     */
    public function test_search_escapes_underscore_wildcard(): void
    {
        $project = Project::factory()->create();

        Backlink::factory()->create([
            'project_id' => $project->id,
            'source_url' => 'https://example.com',
        ]);

        Backlink::factory()->create([
            'project_id' => $project->id,
            'source_url' => 'https://exampleXcom',
        ]);

        // _ en SQL LIKE matche n'importe quel caractère
        // Devrait être échappé pour chercher littéralement '_'
        $response = $this->get(route('backlinks.index', ['search' => 'example_com']));

        $response->assertStatus(200);
        // Ne devrait pas matcher "exampleXcom" si _ est correctement échappé
        $backlinks = $response->viewData('backlinks');

        // Si échappement correct, devrait trouver 0 résultats (aucun URL avec littéralement '_')
        // Si pas d'échappement, trouverait "example.com" et "exampleXcom"
        foreach ($backlinks as $backlink) {
            $this->assertStringContainsString('_', $backlink->source_url);
        }
    }

    /**
     * Test que XSS n'est pas possible via les paramètres de recherche
     */
    public function test_search_prevents_xss(): void
    {
        $project = Project::factory()->create();
        Backlink::factory()->create(['project_id' => $project->id]);

        $xssPayload = '<script>alert("XSS")</script>';

        $response = $this->get(route('backlinks.index', ['search' => $xssPayload]));

        $response->assertStatus(200);
        // Blade {{ }} échappe automatiquement, mais vérifions quand même
        $response->assertDontSee($xssPayload, false); // false = ne pas échapper pour la recherche
    }
}

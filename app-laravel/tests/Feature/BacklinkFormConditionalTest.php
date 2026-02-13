<?php

namespace Tests\Feature;

use App\Models\Backlink;
use App\Models\Platform;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BacklinkFormConditionalTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test création backlink interne (PBN) sans prix ni contact.
     */
    public function test_can_create_internal_backlink_without_price_and_contact(): void
    {
        $project = Project::factory()->create();

        $response = $this->post(route('backlinks.store'), [
            'project_id' => $project->id,
            'source_url' => 'https://pbn-site.com/page',
            'target_url' => 'https://target.com',
            'tier_level' => 'tier1',
            'spot_type' => 'internal',
            // Pas de prix, pas de contact (PBN)
        ]);

        $response->assertRedirect(route('backlinks.index'));

        $this->assertDatabaseHas('backlinks', [
            'project_id' => $project->id,
            'source_url' => 'https://pbn-site.com/page',
            'spot_type' => 'internal',
            'price' => null,
            'currency' => null,
            'platform_id' => null,
            'contact_name' => null,
            'contact_email' => null,
        ]);
    }

    /**
     * Test création backlink externe avec plateforme (contact optionnel).
     */
    public function test_can_create_external_backlink_with_platform(): void
    {
        $project = Project::factory()->create();
        $platform = Platform::factory()->create();

        $response = $this->post(route('backlinks.store'), [
            'project_id' => $project->id,
            'source_url' => 'https://external-site.com/page',
            'target_url' => 'https://target.com',
            'tier_level' => 'tier1',
            'spot_type' => 'external',
            'price' => 150.00,
            'currency' => 'EUR',
            'platform_id' => $platform->id,
            // Pas de contact_name/email car plateforme renseignée
        ]);

        $response->assertRedirect(route('backlinks.index'));

        $this->assertDatabaseHas('backlinks', [
            'project_id' => $project->id,
            'spot_type' => 'external',
            'price' => '150.00',
            'currency' => 'EUR',
            'platform_id' => $platform->id,
        ]);
    }

    /**
     * Test création backlink externe sans plateforme NÉCESSITE contact.
     */
    public function test_external_backlink_without_platform_requires_contact(): void
    {
        $project = Project::factory()->create();

        $response = $this->post(route('backlinks.store'), [
            'project_id' => $project->id,
            'source_url' => 'https://external-site.com/page',
            'target_url' => 'https://target.com',
            'tier_level' => 'tier1',
            'spot_type' => 'external',
            'price' => 150.00,
            'currency' => 'EUR',
            // Pas de platform_id et pas de contact -> devrait échouer
        ]);

        $response->assertSessionHasErrors(['contact_name', 'contact_email']);
    }

    /**
     * Test création backlink externe sans plateforme AVEC contact.
     */
    public function test_can_create_external_backlink_without_platform_with_contact(): void
    {
        $project = Project::factory()->create();

        $response = $this->post(route('backlinks.store'), [
            'project_id' => $project->id,
            'source_url' => 'https://external-site.com/page',
            'target_url' => 'https://target.com',
            'tier_level' => 'tier1',
            'spot_type' => 'external',
            'price' => 150.00,
            'currency' => 'EUR',
            'contact_name' => 'John Doe',
            'contact_email' => 'john@example.com',
            // Pas de platform_id mais contact renseigné -> OK
        ]);

        $response->assertRedirect(route('backlinks.index'));

        $this->assertDatabaseHas('backlinks', [
            'project_id' => $project->id,
            'spot_type' => 'external',
            'contact_name' => 'John Doe',
            'contact_email' => 'john@example.com',
            'platform_id' => null,
        ]);
    }

    /**
     * Test que les champs financiers sont supprimés pour un backlink interne.
     */
    public function test_internal_backlink_removes_financial_fields(): void
    {
        $project = Project::factory()->create();
        $platform = Platform::factory()->create();

        // Tenter de créer un backlink interne AVEC des infos financières
        $response = $this->post(route('backlinks.store'), [
            'project_id' => $project->id,
            'source_url' => 'https://pbn-site.com/page',
            'target_url' => 'https://target.com',
            'tier_level' => 'tier1',
            'spot_type' => 'internal',
            'price' => 999.99, // Devrait être ignoré
            'currency' => 'USD', // Devrait être ignoré
            'platform_id' => $platform->id, // Devrait être ignoré
            'contact_name' => 'Ignored', // Devrait être ignoré
        ]);

        $response->assertRedirect(route('backlinks.index'));

        // Vérifier que les champs financiers/contact sont null
        $backlink = Backlink::latest()->first();
        $this->assertEquals('internal', $backlink->spot_type);
        $this->assertNull($backlink->price);
        $this->assertNull($backlink->currency);
        $this->assertNull($backlink->platform_id);
        $this->assertNull($backlink->contact_name);
    }

    /**
     * Test tier2 avec parent backlink fonctionne.
     */
    public function test_tier2_with_parent_works(): void
    {
        $project = Project::factory()->create();
        $parentBacklink = Backlink::factory()->create([
            'project_id' => $project->id,
            'tier_level' => 'tier1',
        ]);

        $response = $this->post(route('backlinks.store'), [
            'project_id' => $project->id,
            'source_url' => 'https://tier2-site.com/page',
            'target_url' => $parentBacklink->source_url,
            'tier_level' => 'tier2',
            'parent_backlink_id' => $parentBacklink->id,
            'spot_type' => 'internal', // Tier 2 peut être interne
        ]);

        $response->assertRedirect(route('backlinks.index'));

        $this->assertDatabaseHas('backlinks', [
            'tier_level' => 'tier2',
            'parent_backlink_id' => $parentBacklink->id,
            'spot_type' => 'internal',
        ]);
    }
}

<?php

namespace Tests\Feature\Api\V1;

use App\Models\Backlink;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BacklinkApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->project = Project::factory()->for($this->user)->create();
    }

    /**
     * Test listing backlinks for a project
     */
    public function test_can_list_backlinks_for_project(): void
    {
        Backlink::factory()->for($this->project)->count(3)->create();

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks");

        $response->assertOk()
            ->assertJsonCount(3)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'project_id',
                    'source_url',
                    'target_url',
                    'anchor_text',
                    'status',
                    'created_at',
                ]
            ]);
    }

    /**
     * Test cannot list backlinks for another user's project
     */
    public function test_cannot_list_backlinks_for_other_users_project(): void
    {
        $otherUser = User::factory()->create();
        $otherProject = Project::factory()->for($otherUser)->create();

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$otherProject->id}/backlinks");

        $response->assertForbidden();
    }

    /**
     * Test creating a backlink
     */
    public function test_can_create_backlink(): void
    {
        $data = [
            'source_url' => 'https://example.com/page',
            'target_url' => 'https://mysite.com',
            'anchor_text' => 'My awesome site',
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/projects/{$this->project->id}/backlinks", $data);

        $response->assertCreated()
            ->assertJsonFragment([
                'source_url' => 'https://example.com/page',
                'target_url' => 'https://mysite.com',
                'anchor_text' => 'My awesome site',
                'status' => 'active',
            ]);

        $this->assertDatabaseHas('backlinks', [
            'project_id' => $this->project->id,
            'source_url' => 'https://example.com/page',
            'status' => 'active',
        ]);
    }

    /**
     * Test SSRF protection blocks localhost URLs
     */
    public function test_ssrf_protection_blocks_localhost(): void
    {
        $data = [
            'source_url' => 'http://localhost/admin',
            'target_url' => 'https://mysite.com',
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/projects/{$this->project->id}/backlinks", $data);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('source_url');
    }

    /**
     * Test SSRF protection blocks private IP ranges
     */
    public function test_ssrf_protection_blocks_private_ips(): void
    {
        $data = [
            'source_url' => 'http://192.168.1.1/admin',
            'target_url' => 'https://mysite.com',
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/projects/{$this->project->id}/backlinks", $data);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('source_url');
    }

    /**
     * Test validation requires source_url
     */
    public function test_validation_requires_source_url(): void
    {
        $data = [
            'target_url' => 'https://mysite.com',
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/projects/{$this->project->id}/backlinks", $data);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('source_url');
    }

    /**
     * Test validation requires target_url
     */
    public function test_validation_requires_target_url(): void
    {
        $data = [
            'source_url' => 'https://example.com',
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/projects/{$this->project->id}/backlinks", $data);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('target_url');
    }

    /**
     * Test cannot create backlink for another user's project
     */
    public function test_cannot_create_backlink_for_other_users_project(): void
    {
        $otherUser = User::factory()->create();
        $otherProject = Project::factory()->for($otherUser)->create();

        $data = [
            'source_url' => 'https://example.com',
            'target_url' => 'https://mysite.com',
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/projects/{$otherProject->id}/backlinks", $data);

        $response->assertForbidden();
    }

    /**
     * Test showing a backlink
     */
    public function test_can_show_backlink(): void
    {
        $backlink = Backlink::factory()->for($this->project)->create();

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$this->project->id}/backlinks/{$backlink->id}");

        $response->assertOk()
            ->assertJsonFragment([
                'id' => $backlink->id,
                'source_url' => $backlink->source_url,
            ]);
    }

    /**
     * Test cannot show another user's backlink
     */
    public function test_cannot_show_other_users_backlink(): void
    {
        $otherUser = User::factory()->create();
        $otherProject = Project::factory()->for($otherUser)->create();
        $backlink = Backlink::factory()->for($otherProject)->create();

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/projects/{$otherProject->id}/backlinks/{$backlink->id}");

        $response->assertForbidden();
    }

    /**
     * Test updating a backlink
     */
    public function test_can_update_backlink(): void
    {
        $backlink = Backlink::factory()->for($this->project)->create();

        $data = [
            'anchor_text' => 'Updated anchor text',
            'status' => 'changed',
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/projects/{$this->project->id}/backlinks/{$backlink->id}", $data);

        $response->assertOk()
            ->assertJsonFragment([
                'anchor_text' => 'Updated anchor text',
                'status' => 'changed',
            ]);

        $this->assertDatabaseHas('backlinks', [
            'id' => $backlink->id,
            'anchor_text' => 'Updated anchor text',
            'status' => 'changed',
        ]);
    }

    /**
     * Test validation rejects invalid status
     */
    public function test_validation_rejects_invalid_status(): void
    {
        $backlink = Backlink::factory()->for($this->project)->create();

        $data = [
            'status' => 'invalid_status',
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/projects/{$this->project->id}/backlinks/{$backlink->id}", $data);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('status');
    }

    /**
     * Test cannot update another user's backlink
     */
    public function test_cannot_update_other_users_backlink(): void
    {
        $otherUser = User::factory()->create();
        $otherProject = Project::factory()->for($otherUser)->create();
        $backlink = Backlink::factory()->for($otherProject)->create();

        $data = [
            'anchor_text' => 'Hacked',
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/projects/{$otherProject->id}/backlinks/{$backlink->id}", $data);

        $response->assertForbidden();
    }

    /**
     * Test deleting a backlink
     */
    public function test_can_delete_backlink(): void
    {
        $backlink = Backlink::factory()->for($this->project)->create();

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/projects/{$this->project->id}/backlinks/{$backlink->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('backlinks', [
            'id' => $backlink->id,
        ]);
    }

    /**
     * Test cannot delete another user's backlink
     */
    public function test_cannot_delete_other_users_backlink(): void
    {
        $otherUser = User::factory()->create();
        $otherProject = Project::factory()->for($otherUser)->create();
        $backlink = Backlink::factory()->for($otherProject)->create();

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/projects/{$otherProject->id}/backlinks/{$backlink->id}");

        $response->assertForbidden();

        $this->assertDatabaseHas('backlinks', [
            'id' => $backlink->id,
        ]);
    }

    /**
     * Test requires authentication
     */
    public function test_requires_authentication(): void
    {
        $response = $this->getJson("/api/v1/projects/{$this->project->id}/backlinks");

        $response->assertUnauthorized();
    }
}

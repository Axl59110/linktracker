<?php

namespace Tests\Feature\Api\V1;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ProjectApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test authenticated user can list their projects
     */
    public function test_authenticated_user_can_list_their_projects(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        // Create projects for authenticated user
        Project::factory()->count(3)->create(['user_id' => $user->id]);

        // Create projects for other user (should not appear)
        Project::factory()->count(2)->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/projects');

        $response->assertStatus(200)
            ->assertJsonCount(3)
            ->assertJsonStructure([
                '*' => ['id', 'name', 'url', 'status', 'user_id', 'created_at', 'updated_at']
            ]);
    }

    /**
     * Test unauthenticated user cannot list projects
     */
    public function test_unauthenticated_user_cannot_list_projects(): void
    {
        $response = $this->getJson('/api/v1/projects');

        $response->assertStatus(401);
    }

    /**
     * Test authenticated user can create project
     */
    public function test_authenticated_user_can_create_project(): void
    {
        $user = User::factory()->create();

        $projectData = [
            'name' => 'My Test Project',
            'url' => 'https://example.com',
            'status' => 'active',
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/v1/projects', $projectData);

        $response->assertStatus(201)
            ->assertJson([
                'name' => 'My Test Project',
                'url' => 'https://example.com',
                'status' => 'active',
                'user_id' => $user->id,
            ]);

        $this->assertDatabaseHas('projects', [
            'name' => 'My Test Project',
            'url' => 'https://example.com',
            'user_id' => $user->id,
        ]);
    }

    /**
     * Test user cannot create project with duplicate name
     */
    public function test_user_cannot_create_project_with_duplicate_name(): void
    {
        $user = User::factory()->create();

        // Create first project
        Project::factory()->create([
            'user_id' => $user->id,
            'name' => 'Duplicate Project',
        ]);

        // Try to create project with same name
        $projectData = [
            'name' => 'Duplicate Project',
            'url' => 'https://example.com',
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/v1/projects', $projectData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    /**
     * Test authenticated user can view their project
     */
    public function test_authenticated_user_can_view_their_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/projects/{$project->id}");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $project->id,
                'name' => $project->name,
                'url' => $project->url,
                'status' => $project->status,
            ]);
    }

    /**
     * Test user cannot view other user's project
     */
    public function test_user_cannot_view_other_users_project(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/projects/{$project->id}");

        $response->assertStatus(403);
    }

    /**
     * Test authenticated user can update their project
     */
    public function test_authenticated_user_can_update_their_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $updateData = [
            'name' => 'Updated Project Name',
            'url' => 'https://updated-example.com',
            'status' => 'paused',
        ];

        $response = $this->actingAs($user)
            ->putJson("/api/v1/projects/{$project->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'id' => $project->id,
                'name' => 'Updated Project Name',
                'url' => 'https://updated-example.com',
                'status' => 'paused',
            ]);

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'Updated Project Name',
            'url' => 'https://updated-example.com',
            'status' => 'paused',
        ]);
    }

    /**
     * Test user cannot update other user's project
     */
    public function test_user_cannot_update_other_users_project(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $otherUser->id]);

        $updateData = [
            'name' => 'Hacked Project',
        ];

        $response = $this->actingAs($user)
            ->putJson("/api/v1/projects/{$project->id}", $updateData);

        $response->assertStatus(403);
    }

    /**
     * Test authenticated user can delete their project (soft delete)
     */
    public function test_authenticated_user_can_delete_their_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/projects/{$project->id}");

        $response->assertStatus(204);

        // Check soft delete
        $this->assertSoftDeleted('projects', [
            'id' => $project->id,
        ]);
    }

    /**
     * Test user cannot delete other user's project
     */
    public function test_user_cannot_delete_other_users_project(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)
            ->deleteJson("/api/v1/projects/{$project->id}");

        $response->assertStatus(403);

        // Project should still exist
        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'deleted_at' => null,
        ]);
    }
}

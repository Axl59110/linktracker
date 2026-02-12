<?php

namespace Tests\Unit\Models;

use App\Models\Backlink;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BacklinkModelTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test backlink creation with factory
     */
    public function test_can_create_backlink_with_factory(): void
    {
        $project = Project::factory()->create();
        $backlink = Backlink::factory()->for($project)->create();

        $this->assertDatabaseHas('backlinks', [
            'id' => $backlink->id,
            'project_id' => $project->id,
        ]);
    }

    /**
     * Test backlink belongs to project relation
     */
    public function test_backlink_belongs_to_project(): void
    {
        $project = Project::factory()->create();
        $backlink = Backlink::factory()->for($project)->create();

        $this->assertInstanceOf(Project::class, $backlink->project);
        $this->assertEquals($project->id, $backlink->project->id);
    }

    /**
     * Test active scope returns only active backlinks
     */
    public function test_active_scope_returns_only_active_backlinks(): void
    {
        $project = Project::factory()->create();

        Backlink::factory()->for($project)->active()->count(3)->create();
        Backlink::factory()->for($project)->lost()->count(2)->create();
        Backlink::factory()->for($project)->changed()->create();

        $activeBacklinks = Backlink::active()->get();

        $this->assertCount(3, $activeBacklinks);
        $activeBacklinks->each(function ($backlink) {
            $this->assertEquals('active', $backlink->status);
        });
    }

    /**
     * Test lost scope returns only lost backlinks
     */
    public function test_lost_scope_returns_only_lost_backlinks(): void
    {
        $project = Project::factory()->create();

        Backlink::factory()->for($project)->active()->count(2)->create();
        Backlink::factory()->for($project)->lost()->count(3)->create();

        $lostBacklinks = Backlink::lost()->get();

        $this->assertCount(3, $lostBacklinks);
        $lostBacklinks->each(function ($backlink) {
            $this->assertEquals('lost', $backlink->status);
        });
    }

    /**
     * Test changed scope returns only changed backlinks
     */
    public function test_changed_scope_returns_only_changed_backlinks(): void
    {
        $project = Project::factory()->create();

        Backlink::factory()->for($project)->active()->count(2)->create();
        Backlink::factory()->for($project)->changed()->count(2)->create();

        $changedBacklinks = Backlink::changed()->get();

        $this->assertCount(2, $changedBacklinks);
        $changedBacklinks->each(function ($backlink) {
            $this->assertEquals('changed', $backlink->status);
        });
    }

    /**
     * Test status badge color accessor
     */
    public function test_status_badge_color_accessor(): void
    {
        $project = Project::factory()->create();

        $activeBacklink = Backlink::factory()->for($project)->active()->create();
        $lostBacklink = Backlink::factory()->for($project)->lost()->create();
        $changedBacklink = Backlink::factory()->for($project)->changed()->create();

        $this->assertStringContainsString('green', $activeBacklink->status_badge_color);
        $this->assertStringContainsString('red', $lostBacklink->status_badge_color);
        $this->assertStringContainsString('yellow', $changedBacklink->status_badge_color);
    }

    /**
     * Test status label accessor
     */
    public function test_status_label_accessor(): void
    {
        $project = Project::factory()->create();

        $activeBacklink = Backlink::factory()->for($project)->active()->create();
        $lostBacklink = Backlink::factory()->for($project)->lost()->create();
        $changedBacklink = Backlink::factory()->for($project)->changed()->create();

        $this->assertEquals('Actif', $activeBacklink->status_label);
        $this->assertEquals('Perdu', $lostBacklink->status_label);
        $this->assertEquals('Modifié', $changedBacklink->status_label);
    }

    /**
     * Test isActive method
     */
    public function test_is_active_method(): void
    {
        $project = Project::factory()->create();

        $activeBacklink = Backlink::factory()->for($project)->active()->create();
        $lostBacklink = Backlink::factory()->for($project)->lost()->create();

        $this->assertTrue($activeBacklink->isActive());
        $this->assertFalse($lostBacklink->isActive());
    }

    /**
     * Test isLost method
     */
    public function test_is_lost_method(): void
    {
        $project = Project::factory()->create();

        $activeBacklink = Backlink::factory()->for($project)->active()->create();
        $lostBacklink = Backlink::factory()->for($project)->lost()->create();

        $this->assertFalse($activeBacklink->isLost());
        $this->assertTrue($lostBacklink->isLost());
    }

    /**
     * Test hasChanged method
     */
    public function test_has_changed_method(): void
    {
        $project = Project::factory()->create();

        $activeBacklink = Backlink::factory()->for($project)->active()->create();
        $changedBacklink = Backlink::factory()->for($project)->changed()->create();

        $this->assertFalse($activeBacklink->hasChanged());
        $this->assertTrue($changedBacklink->hasChanged());
    }

    /**
     * Test factory states work correctly
     */
    public function test_factory_states(): void
    {
        $project = Project::factory()->create();

        $activeBacklink = Backlink::factory()->for($project)->active()->create();
        $this->assertEquals('active', $activeBacklink->status);
        $this->assertEquals(200, $activeBacklink->http_status);
        $this->assertTrue($activeBacklink->is_dofollow);

        $lostBacklink = Backlink::factory()->for($project)->lost()->create();
        $this->assertEquals('lost', $lostBacklink->status);
        $this->assertEquals(404, $lostBacklink->http_status);

        $changedBacklink = Backlink::factory()->for($project)->changed()->create();
        $this->assertEquals('changed', $changedBacklink->status);
        $this->assertFalse($changedBacklink->is_dofollow);
        $this->assertEquals('nofollow', $changedBacklink->rel_attributes);

        $nofollowBacklink = Backlink::factory()->for($project)->nofollow()->create();
        $this->assertFalse($nofollowBacklink->is_dofollow);
        $this->assertEquals('nofollow', $nofollowBacklink->rel_attributes);
    }

    /**
     * Test casts work properly
     */
    public function test_casts_work_properly(): void
    {
        $project = Project::factory()->create();
        $backlink = Backlink::factory()->for($project)->create();

        $this->assertIsBool($backlink->is_dofollow);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $backlink->first_seen_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $backlink->created_at);
    }

    /**
     * Test fillable attributes
     */
    public function test_fillable_attributes(): void
    {
        $project = Project::factory()->create();

        $data = [
            'source_url' => 'https://example.com/article',
            'target_url' => 'https://mysite.com',
            'anchor_text' => 'Click here',
            'status' => 'active',
            'http_status' => 200,
            'rel_attributes' => 'follow',
            'is_dofollow' => true,
        ];

        $backlink = $project->backlinks()->create($data);

        $this->assertEquals('https://example.com/article', $backlink->source_url);
        $this->assertEquals('https://mysite.com', $backlink->target_url);
        $this->assertEquals('Click here', $backlink->anchor_text);
        $this->assertEquals('active', $backlink->status);
        $this->assertEquals($project->id, $backlink->project_id);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Backlink;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests STORY-039 : Rapport HTML exportable par projet
 */
class ProjectReportTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_report_page_is_accessible(): void
    {
        $project = Project::factory()->for($this->user)->create();

        $response = $this->actingAs($this->user)
            ->get(route('projects.report', $project));

        $response->assertStatus(200);
        $response->assertSee($project->name);
        $response->assertSee('Rapport de suivi des backlinks');
    }

    public function test_report_shows_backlink_stats(): void
    {
        $project = Project::factory()->for($this->user)->create();

        Backlink::factory()->count(3)->create(['project_id' => $project->id, 'status' => 'active']);
        Backlink::factory()->count(2)->create(['project_id' => $project->id, 'status' => 'lost']);
        Backlink::factory()->create(['project_id' => $project->id, 'status' => 'changed']);

        $response = $this->actingAs($this->user)
            ->get(route('projects.report', $project));

        $response->assertStatus(200);
        $response->assertSee('6');  // total
        $response->assertSee('3');  // active
        $response->assertSee('2');  // lost
        $response->assertSee('1');  // changed
    }

    public function test_report_shows_backlinks_table(): void
    {
        $project = Project::factory()->for($this->user)->create();

        $backlink = Backlink::factory()->create([
            'project_id'  => $project->id,
            'source_url'  => 'https://example.com/article',
            'anchor_text' => 'Mon ancre test',
            'status'      => 'active',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('projects.report', $project));

        $response->assertStatus(200);
        $response->assertSee('example.com/article');
        $response->assertSee('Mon ancre test');
    }

    public function test_report_shows_empty_message_when_no_backlinks(): void
    {
        $project = Project::factory()->for($this->user)->create();

        $response = $this->actingAs($this->user)
            ->get(route('projects.report', $project));

        $response->assertStatus(200);
        $response->assertSee('Aucun backlink');
    }

    public function test_report_has_print_button(): void
    {
        $project = Project::factory()->for($this->user)->create();

        $response = $this->actingAs($this->user)
            ->get(route('projects.report', $project));

        $response->assertStatus(200);
        $response->assertSee('Imprimer');
    }

    public function test_project_show_page_has_report_link(): void
    {
        $project = Project::factory()->for($this->user)->create();

        $response = $this->actingAs($this->user)
            ->get(route('projects.show', $project));

        $response->assertStatus(200);
        $response->assertSee(route('projects.report', $project));
    }
}

<?php

namespace Tests\Feature\Jobs;

use Tests\TestCase;
use App\Jobs\CheckBacklinkJob;
use App\Models\Backlink;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

class CheckBacklinkJobTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->project = Project::factory()->create(['user_id' => $this->user->id]);
    }

    public function test_job_creates_check_record_when_backlink_found(): void
    {
        $backlink = Backlink::factory()->create([
            'project_id' => $this->project->id,
            'source_url' => 'https://example.com/article',
            'target_url' => 'https://mysite.com',
            'status' => 'active',
        ]);

        $html = '<html><body><a href="https://mysite.com">Great site</a></body></html>';

        Http::fake([
            'example.com/*' => Http::response($html, 200),
        ]);

        $job = new CheckBacklinkJob($backlink);
        $job->handle(app(\App\Services\Backlink\BacklinkCheckerService::class));

        // Vérifier qu'un check a été créé
        $this->assertDatabaseHas('backlink_checks', [
            'backlink_id' => $backlink->id,
            'is_present' => true,
            'http_status' => 200,
        ]);

        $this->assertEquals(1, $backlink->checks()->count());
    }

    public function test_job_updates_backlink_last_checked_at(): void
    {
        $backlink = Backlink::factory()->create([
            'project_id' => $this->project->id,
            'source_url' => 'https://example.com/article',
            'target_url' => 'https://mysite.com',
            'last_checked_at' => null,
        ]);

        $html = '<html><body><a href="https://mysite.com">Link</a></body></html>';

        Http::fake([
            'example.com/*' => Http::response($html, 200),
        ]);

        $job = new CheckBacklinkJob($backlink);
        $job->handle(app(\App\Services\Backlink\BacklinkCheckerService::class));

        $backlink->refresh();

        $this->assertNotNull($backlink->last_checked_at);
        $this->assertTrue($backlink->last_checked_at->isToday());
    }

    public function test_job_updates_backlink_attributes_when_found(): void
    {
        $backlink = Backlink::factory()->create([
            'project_id' => $this->project->id,
            'source_url' => 'https://example.com/article',
            'target_url' => 'https://mysite.com',
            'anchor_text' => null,
            'rel_attributes' => null,
            'is_dofollow' => false,
        ]);

        $html = '<html><body><a href="https://mysite.com" rel="nofollow sponsored">Visit site</a></body></html>';

        Http::fake([
            'example.com/*' => Http::response($html, 200),
        ]);

        $job = new CheckBacklinkJob($backlink);
        $job->handle(app(\App\Services\Backlink\BacklinkCheckerService::class));

        $backlink->refresh();

        $this->assertEquals('Visit site', $backlink->anchor_text);
        $this->assertEquals('nofollow,sponsored', $backlink->rel_attributes);
        $this->assertFalse($backlink->is_dofollow);
    }

    public function test_job_changes_status_to_lost_when_not_found(): void
    {
        $backlink = Backlink::factory()->create([
            'project_id' => $this->project->id,
            'source_url' => 'https://example.com/article',
            'target_url' => 'https://mysite.com',
            'status' => 'active',
        ]);

        $html = '<html><body><p>No backlink here</p></body></html>';

        Http::fake([
            'example.com/*' => Http::response($html, 200),
        ]);

        $job = new CheckBacklinkJob($backlink);
        $job->handle(app(\App\Services\Backlink\BacklinkCheckerService::class));

        $backlink->refresh();

        $this->assertEquals('lost', $backlink->status);
    }

    public function test_job_changes_status_to_active_when_lost_backlink_found_again(): void
    {
        $backlink = Backlink::factory()->create([
            'project_id' => $this->project->id,
            'source_url' => 'https://example.com/article',
            'target_url' => 'https://mysite.com',
            'status' => 'lost',
        ]);

        $html = '<html><body><a href="https://mysite.com">Back again!</a></body></html>';

        Http::fake([
            'example.com/*' => Http::response($html, 200),
        ]);

        $job = new CheckBacklinkJob($backlink);
        $job->handle(app(\App\Services\Backlink\BacklinkCheckerService::class));

        $backlink->refresh();

        $this->assertEquals('active', $backlink->status);
    }

    public function test_job_changes_status_to_changed_when_attributes_modified(): void
    {
        $backlink = Backlink::factory()->create([
            'project_id' => $this->project->id,
            'source_url' => 'https://example.com/article',
            'target_url' => 'https://mysite.com',
            'status' => 'active',
            'rel_attributes' => null,
            'is_dofollow' => true,
        ]);

        // Maintenant le lien a rel="nofollow"
        $html = '<html><body><a href="https://mysite.com" rel="nofollow">Link</a></body></html>';

        Http::fake([
            'example.com/*' => Http::response($html, 200),
        ]);

        $job = new CheckBacklinkJob($backlink);
        $job->handle(app(\App\Services\Backlink\BacklinkCheckerService::class));

        $backlink->refresh();

        $this->assertEquals('changed', $backlink->status);
        $this->assertEquals('nofollow', $backlink->rel_attributes);
        $this->assertFalse($backlink->is_dofollow);
    }

    public function test_job_creates_check_with_error_when_http_404(): void
    {
        $backlink = Backlink::factory()->create([
            'project_id' => $this->project->id,
            'source_url' => 'https://example.com/missing',
            'target_url' => 'https://mysite.com',
        ]);

        Http::fake([
            'example.com/*' => Http::response('Not Found', 404),
        ]);

        $job = new CheckBacklinkJob($backlink);
        $job->handle(app(\App\Services\Backlink\BacklinkCheckerService::class));

        $this->assertDatabaseHas('backlink_checks', [
            'backlink_id' => $backlink->id,
            'is_present' => false,
            'http_status' => 404,
        ]);
    }

    public function test_job_can_be_dispatched_to_queue(): void
    {
        Queue::fake();

        $backlink = Backlink::factory()->create([
            'project_id' => $this->project->id,
        ]);

        CheckBacklinkJob::dispatch($backlink);

        Queue::assertPushed(CheckBacklinkJob::class, function ($job) use ($backlink) {
            return $job->backlink->id === $backlink->id;
        });
    }

    public function test_job_does_not_update_anchor_text_if_unchanged(): void
    {
        $backlink = Backlink::factory()->create([
            'project_id' => $this->project->id,
            'source_url' => 'https://example.com/article',
            'target_url' => 'https://mysite.com',
            'anchor_text' => 'My Site',
        ]);

        // Même anchor text
        $html = '<html><body><a href="https://mysite.com">My Site</a></body></html>';

        Http::fake([
            'example.com/*' => Http::response($html, 200),
        ]);

        $job = new CheckBacklinkJob($backlink);
        $job->handle(app(\App\Services\Backlink\BacklinkCheckerService::class));

        $backlink->refresh();

        // Anchor text ne change pas
        $this->assertEquals('My Site', $backlink->anchor_text);
    }

    public function test_job_updates_anchor_text_if_changed(): void
    {
        $backlink = Backlink::factory()->create([
            'project_id' => $this->project->id,
            'source_url' => 'https://example.com/article',
            'target_url' => 'https://mysite.com',
            'anchor_text' => 'Old Text',
        ]);

        // Nouveau anchor text
        $html = '<html><body><a href="https://mysite.com">New Text</a></body></html>';

        Http::fake([
            'example.com/*' => Http::response($html, 200),
        ]);

        $job = new CheckBacklinkJob($backlink);
        $job->handle(app(\App\Services\Backlink\BacklinkCheckerService::class));

        $backlink->refresh();

        $this->assertEquals('New Text', $backlink->anchor_text);
    }

    public function test_job_handles_ssrf_protection_errors(): void
    {
        $backlink = Backlink::factory()->create([
            'project_id' => $this->project->id,
            'source_url' => 'http://localhost/admin',
            'target_url' => 'https://mysite.com',
        ]);

        Http::fake(); // SSRF bloque avant même d'envoyer la requête

        $job = new CheckBacklinkJob($backlink);
        $job->handle(app(\App\Services\Backlink\BacklinkCheckerService::class));

        // Un check avec erreur SSRF doit être créé
        $check = $backlink->checks()->latest()->first();
        $this->assertNotNull($check);
        $this->assertFalse($check->is_present);
        $this->assertStringContainsString('SSRF', $check->error_message);

        Http::assertNothingSent();
    }

    public function test_job_keeps_status_lost_if_still_not_found(): void
    {
        $backlink = Backlink::factory()->create([
            'project_id' => $this->project->id,
            'source_url' => 'https://example.com/article',
            'target_url' => 'https://mysite.com',
            'status' => 'lost',
        ]);

        $html = '<html><body><p>Still no backlink</p></body></html>';

        Http::fake([
            'example.com/*' => Http::response($html, 200),
        ]);

        $job = new CheckBacklinkJob($backlink);
        $job->handle(app(\App\Services\Backlink\BacklinkCheckerService::class));

        $backlink->refresh();

        // Status reste lost
        $this->assertEquals('lost', $backlink->status);
    }

    public function test_job_keeps_status_changed_if_attributes_still_different(): void
    {
        $backlink = Backlink::factory()->create([
            'project_id' => $this->project->id,
            'source_url' => 'https://example.com/article',
            'target_url' => 'https://mysite.com',
            'status' => 'changed',
            'rel_attributes' => 'nofollow',
            'is_dofollow' => false,
        ]);

        // Attributs toujours différents
        $html = '<html><body><a href="https://mysite.com" rel="nofollow">Link</a></body></html>';

        Http::fake([
            'example.com/*' => Http::response($html, 200),
        ]);

        $job = new CheckBacklinkJob($backlink);
        $job->handle(app(\App\Services\Backlink\BacklinkCheckerService::class));

        $backlink->refresh();

        // Status reste changed
        $this->assertEquals('changed', $backlink->status);
    }
}

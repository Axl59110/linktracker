<?php

namespace Tests\Feature\Console;

use Tests\TestCase;
use App\Models\Backlink;
use App\Models\Project;
use App\Models\User;
use App\Jobs\CheckBacklinkJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Artisan;

class CheckBacklinksCommandTest extends TestCase
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

    public function test_command_dispatches_jobs_for_active_backlinks(): void
    {
        Queue::fake();

        $backlinks = Backlink::factory()->count(3)->create([
            'project_id' => $this->project->id,
            'status' => 'active',
            'last_checked_at' => null,
        ]);

        Artisan::call('app:check-backlinks', ['--frequency' => 'daily']);

        Queue::assertPushed(CheckBacklinkJob::class, 3);

        foreach ($backlinks as $backlink) {
            Queue::assertPushed(CheckBacklinkJob::class, function ($job) use ($backlink) {
                return $job->backlink->id === $backlink->id;
            });
        }
    }

    public function test_command_filters_by_daily_frequency(): void
    {
        Queue::fake();

        // Backlink récent (< 24h)
        Backlink::factory()->create([
            'project_id' => $this->project->id,
            'status' => 'active',
            'last_checked_at' => now()->subHours(12),
        ]);

        // Backlink ancien (> 24h)
        $oldBacklink = Backlink::factory()->create([
            'project_id' => $this->project->id,
            'status' => 'active',
            'last_checked_at' => now()->subDays(2),
        ]);

        // Backlink jamais vérifié
        $neverChecked = Backlink::factory()->create([
            'project_id' => $this->project->id,
            'status' => 'active',
            'last_checked_at' => null,
        ]);

        Artisan::call('app:check-backlinks', ['--frequency' => 'daily']);

        // Seulement 2 jobs dispatched (le récent est exclu)
        Queue::assertPushed(CheckBacklinkJob::class, 2);

        Queue::assertPushed(CheckBacklinkJob::class, function ($job) use ($oldBacklink) {
            return $job->backlink->id === $oldBacklink->id;
        });

        Queue::assertPushed(CheckBacklinkJob::class, function ($job) use ($neverChecked) {
            return $job->backlink->id === $neverChecked->id;
        });
    }

    public function test_command_filters_by_weekly_frequency(): void
    {
        Queue::fake();

        // Backlink vérifié il y a 3 jours
        Backlink::factory()->create([
            'project_id' => $this->project->id,
            'status' => 'active',
            'last_checked_at' => now()->subDays(3),
        ]);

        // Backlink vérifié il y a 10 jours
        $oldBacklink = Backlink::factory()->create([
            'project_id' => $this->project->id,
            'status' => 'active',
            'last_checked_at' => now()->subDays(10),
        ]);

        Artisan::call('app:check-backlinks', ['--frequency' => 'weekly']);

        // Seulement 1 job (celui de 10 jours)
        Queue::assertPushed(CheckBacklinkJob::class, 1);

        Queue::assertPushed(CheckBacklinkJob::class, function ($job) use ($oldBacklink) {
            return $job->backlink->id === $oldBacklink->id;
        });
    }

    public function test_command_filters_by_project(): void
    {
        Queue::fake();

        $project2 = Project::factory()->create(['user_id' => $this->user->id]);

        Backlink::factory()->create([
            'project_id' => $this->project->id,
            'status' => 'active',
        ]);

        $backlink2 = Backlink::factory()->create([
            'project_id' => $project2->id,
            'status' => 'active',
        ]);

        Artisan::call('app:check-backlinks', [
            '--frequency' => 'all',
            '--project' => $project2->id,
        ]);

        // Seulement 1 job pour project2
        Queue::assertPushed(CheckBacklinkJob::class, 1);

        Queue::assertPushed(CheckBacklinkJob::class, function ($job) use ($backlink2) {
            return $job->backlink->id === $backlink2->id;
        });
    }

    public function test_command_filters_by_status(): void
    {
        Queue::fake();

        Backlink::factory()->create([
            'project_id' => $this->project->id,
            'status' => 'active',
        ]);

        $lostBacklink = Backlink::factory()->create([
            'project_id' => $this->project->id,
            'status' => 'lost',
        ]);

        Artisan::call('app:check-backlinks', [
            '--frequency' => 'all',
            '--status' => 'lost',
        ]);

        // Seulement 1 job pour le backlink lost
        Queue::assertPushed(CheckBacklinkJob::class, 1);

        Queue::assertPushed(CheckBacklinkJob::class, function ($job) use ($lostBacklink) {
            return $job->backlink->id === $lostBacklink->id;
        });
    }

    public function test_command_filters_by_status_all(): void
    {
        Queue::fake();

        Backlink::factory()->create([
            'project_id' => $this->project->id,
            'status' => 'active',
        ]);

        Backlink::factory()->create([
            'project_id' => $this->project->id,
            'status' => 'lost',
        ]);

        Backlink::factory()->create([
            'project_id' => $this->project->id,
            'status' => 'changed',
        ]);

        Artisan::call('app:check-backlinks', [
            '--frequency' => 'all',
            '--status' => 'all',
        ]);

        // Tous les backlinks (3)
        Queue::assertPushed(CheckBacklinkJob::class, 3);
    }

    public function test_command_respects_limit_option(): void
    {
        Queue::fake();

        Backlink::factory()->count(5)->create([
            'project_id' => $this->project->id,
            'status' => 'active',
        ]);

        Artisan::call('app:check-backlinks', [
            '--frequency' => 'all',
            '--limit' => 2,
        ]);

        // Seulement 2 jobs dispatched
        Queue::assertPushed(CheckBacklinkJob::class, 2);
    }

    public function test_command_prioritizes_never_checked_backlinks(): void
    {
        Queue::fake();

        // Backlink vérifié récemment
        $recentBacklink = Backlink::factory()->create([
            'project_id' => $this->project->id,
            'status' => 'active',
            'last_checked_at' => now()->subDays(5),
        ]);

        // Backlink jamais vérifié (doit être prioritaire)
        $neverChecked = Backlink::factory()->create([
            'project_id' => $this->project->id,
            'status' => 'active',
            'last_checked_at' => null,
        ]);

        // Backlink très ancien
        $veryOld = Backlink::factory()->create([
            'project_id' => $this->project->id,
            'status' => 'active',
            'last_checked_at' => now()->subDays(30),
        ]);

        Artisan::call('app:check-backlinks', [
            '--frequency' => 'all',
            '--limit' => 1,
        ]);

        // Le jamais vérifié doit être dispatched en premier
        Queue::assertPushed(CheckBacklinkJob::class, 1);

        Queue::assertPushed(CheckBacklinkJob::class, function ($job) use ($neverChecked) {
            return $job->backlink->id === $neverChecked->id;
        });
    }

    public function test_command_returns_zero_when_no_backlinks_found(): void
    {
        // Aucun backlink

        $exitCode = Artisan::call('app:check-backlinks', ['--frequency' => 'daily']);

        $this->assertEquals(0, $exitCode);
    }

    public function test_command_returns_error_on_invalid_frequency(): void
    {
        $exitCode = Artisan::call('app:check-backlinks', ['--frequency' => 'invalid']);

        $this->assertEquals(1, $exitCode);
    }

    public function test_command_outputs_progress_information(): void
    {
        Queue::fake();

        Backlink::factory()->count(3)->create([
            'project_id' => $this->project->id,
            'status' => 'active',
        ]);

        $this->artisan('app:check-backlinks', ['--frequency' => 'all'])
             ->expectsOutput('🔍 Starting backlink check process...')
             ->expectsOutputToContain('Found 3 backlink(s) to check')
             ->expectsOutputToContain('Successfully dispatched 3 job(s)')
             ->assertExitCode(0);
    }
}

<?php

namespace Tests\Feature\Jobs;

use App\Jobs\FetchSeoMetricsJob;
use App\Models\Backlink;
use App\Models\DomainMetric;
use App\Models\Project;
use App\Models\User;
use App\Services\Seo\DomainMetricsDTO;
use App\Services\Seo\SeoMetricService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class FetchSeoMetricsJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_calls_seo_service_and_stores_metrics(): void
    {
        Http::fake([
            'lsapi.seomoz.com/*' => Http::response([
                'results' => [[
                    'domain_authority' => 42.0,
                    'spam_score'       => 0.05,
                    'linking_domains'  => 800,
                ]],
            ], 200),
        ]);

        config([
            'seo.provider'       => 'moz',
            'seo.moz_access_id'  => 'test-id',
            'seo.moz_secret_key' => 'test-secret',
        ]);

        $record = DomainMetric::forDomain('example.com');
        $job = new FetchSeoMetricsJob($record);
        $job->handle(new SeoMetricService());

        $this->assertDatabaseHas('domain_metrics', [
            'domain'   => 'example.com',
            'da'       => 42,
            'provider' => 'moz',
        ]);

        $updated = DomainMetric::where('domain', 'example.com')->first();
        $this->assertNotNull($updated->last_updated_at);
    }

    public function test_job_handles_custom_provider_gracefully(): void
    {
        config(['seo.provider' => 'custom']);

        $record = DomainMetric::forDomain('noapi.com');
        $job = new FetchSeoMetricsJob($record);
        $job->handle(new SeoMetricService());

        $this->assertDatabaseHas('domain_metrics', ['domain' => 'noapi.com']);
    }

    public function test_refresh_command_dispatches_jobs_for_stale_domains(): void
    {
        Queue::fake();

        $user    = User::factory()->create();
        $project = Project::factory()->for($user)->create();

        Backlink::factory()->for($project)->create([
            'source_url' => 'https://stale-domain.com/page',
            'status'     => 'active',
        ]);

        // Crée un DomainMetric périmé
        DomainMetric::create([
            'domain'          => 'stale-domain.com',
            'provider'        => 'custom',
            'last_updated_at' => now()->subDays(2),
        ]);

        $this->artisan('app:refresh-seo-metrics')
             ->assertExitCode(0);

        Queue::assertPushed(FetchSeoMetricsJob::class);
    }

    public function test_refresh_command_skips_fresh_domains(): void
    {
        Queue::fake();

        $user    = User::factory()->create();
        $project = Project::factory()->for($user)->create();

        Backlink::factory()->for($project)->create([
            'source_url' => 'https://fresh-domain.com/page',
            'status'     => 'active',
        ]);

        DomainMetric::create([
            'domain'          => 'fresh-domain.com',
            'provider'        => 'moz',
            'last_updated_at' => now()->subHours(2),
        ]);

        $this->artisan('app:refresh-seo-metrics')
             ->assertExitCode(0);

        Queue::assertNothingPushed();
    }

    public function test_refresh_command_with_force_flag_updates_all(): void
    {
        Queue::fake();

        $user    = User::factory()->create();
        $project = Project::factory()->for($user)->create();

        Backlink::factory()->for($project)->create([
            'source_url' => 'https://fresh-domain.com/page',
            'status'     => 'active',
        ]);

        DomainMetric::create([
            'domain'          => 'fresh-domain.com',
            'provider'        => 'moz',
            'last_updated_at' => now()->subHours(2),
        ]);

        $this->artisan('app:refresh-seo-metrics --force')
             ->assertExitCode(0);

        Queue::assertPushed(FetchSeoMetricsJob::class);
    }
}

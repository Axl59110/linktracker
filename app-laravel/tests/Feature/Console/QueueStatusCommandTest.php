<?php

namespace Tests\Feature\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Tests STORY-042 : Commande app:queue-status
 */
class QueueStatusCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_shows_empty_queue_status(): void
    {
        $this->artisan('app:queue-status')
            ->assertExitCode(0)
            ->expectsOutputToContain('Jobs en attente')
            ->expectsOutputToContain('Jobs échoués');
    }

    public function test_command_shows_pending_jobs_count(): void
    {
        DB::table('jobs')->insert([
            'queue'        => 'default',
            'payload'      => json_encode(['displayName' => 'App\\Jobs\\CheckBacklinkJob']),
            'attempts'     => 0,
            'reserved_at'  => null,
            'available_at' => now()->timestamp,
            'created_at'   => now()->timestamp,
        ]);

        $this->artisan('app:queue-status')
            ->assertExitCode(0)
            ->expectsOutputToContain('Jobs en attente');
    }

    public function test_failed_option_shows_no_failures_when_empty(): void
    {
        $this->artisan('app:queue-status', ['--failed' => true])
            ->assertExitCode(0)
            ->expectsOutputToContain('Aucun job échoué');
    }

    public function test_failed_option_shows_failed_jobs(): void
    {
        DB::table('failed_jobs')->insert([
            'uuid'       => \Illuminate\Support\Str::uuid(),
            'connection' => 'database',
            'queue'      => 'default',
            'payload'    => json_encode(['displayName' => 'App\\Jobs\\CheckBacklinkJob']),
            'exception'  => 'RuntimeException: Connection refused in /path/to/file.php:42',
            'failed_at'  => now(),
        ]);

        $this->artisan('app:queue-status', ['--failed' => true])
            ->assertExitCode(0)
            ->expectsOutputToContain('CheckBacklinkJob')
            ->expectsOutputToContain('Connection refused');
    }

    public function test_failed_count_warning_shown_in_main_status(): void
    {
        DB::table('failed_jobs')->insert([
            'uuid'       => \Illuminate\Support\Str::uuid(),
            'connection' => 'database',
            'queue'      => 'default',
            'payload'    => json_encode(['displayName' => 'App\\Jobs\\TestJob']),
            'exception'  => 'Exception: Test error',
            'failed_at'  => now(),
        ]);

        $this->artisan('app:queue-status')
            ->assertExitCode(0)
            ->expectsOutputToContain('job(s) échoué(s)');
    }

    public function test_limit_option_restricts_failed_output(): void
    {
        for ($i = 0; $i < 5; $i++) {
            DB::table('failed_jobs')->insert([
                'uuid'       => \Illuminate\Support\Str::uuid(),
                'connection' => 'database',
                'queue'      => 'default',
                'payload'    => json_encode(['displayName' => "App\\Jobs\\Job{$i}"]),
                'exception'  => "Exception: Error {$i}",
                'failed_at'  => now(),
            ]);
        }

        $this->artisan('app:queue-status', ['--failed' => true, '--limit' => 2])
            ->assertExitCode(0)
            ->expectsOutputToContain('3 autres');
    }
}

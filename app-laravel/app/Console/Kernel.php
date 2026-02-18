<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Vérification quotidienne des backlinks actifs
        $schedule->command('app:check-backlinks --frequency=daily')
                 ->dailyAt('02:00')
                 ->withoutOverlapping()
                 ->appendOutputTo(storage_path('logs/scheduler.log'));

        // Vérification hebdomadaire complète (tous les dimanches)
        $schedule->command('app:check-backlinks --frequency=weekly --status=all')
                 ->weekly()
                 ->sundays()
                 ->at('03:00')
                 ->withoutOverlapping()
                 ->appendOutputTo(storage_path('logs/scheduler.log'));

        // Mise à jour quotidienne des métriques SEO
        $schedule->command('app:refresh-seo-metrics --limit=100')
                 ->dailyAt('04:00')
                 ->withoutOverlapping()
                 ->appendOutputTo(storage_path('logs/scheduler.log'));
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}

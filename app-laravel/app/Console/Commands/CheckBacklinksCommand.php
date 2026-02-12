<?php

namespace App\Console\Commands;

use App\Jobs\CheckBacklinkJob;
use App\Models\Backlink;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckBacklinksCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-backlinks
                            {--frequency=daily : Frequency filter (daily, weekly, all)}
                            {--project= : Check only backlinks for a specific project ID}
                            {--limit= : Maximum number of backlinks to check}
                            {--status=active : Filter by status (active, lost, changed, all)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch jobs to check backlinks based on frequency and filters';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $frequency = $this->option('frequency');
        $projectId = $this->option('project');
        $limit = $this->option('limit');
        $status = $this->option('status');

        $this->info("🔍 Starting backlink check process...");
        $this->info("Frequency: {$frequency}");

        // Construire la query
        $query = Backlink::query();

        // Filtre par projet
        if ($projectId) {
            $query->where('project_id', $projectId);
            $this->info("Project filter: {$projectId}");
        }

        // Filtre par statut
        if ($status !== 'all') {
            $query->where('status', $status);
            $this->info("Status filter: {$status}");
        }

        // Filtre par fréquence
        switch ($frequency) {
            case 'daily':
                // Backlinks qui n'ont pas été vérifiés dans les dernières 24h
                $query->where(function ($q) {
                    $q->whereNull('last_checked_at')
                      ->orWhere('last_checked_at', '<', now()->subDay());
                });
                break;

            case 'weekly':
                // Backlinks qui n'ont pas été vérifiés dans les 7 derniers jours
                $query->where(function ($q) {
                    $q->whereNull('last_checked_at')
                      ->orWhere('last_checked_at', '<', now()->subWeek());
                });
                break;

            case 'all':
                // Tous les backlinks (pas de filtre)
                break;

            default:
                $this->error("Invalid frequency: {$frequency}. Use daily, weekly, or all.");
                return 1;
        }

        // Ordonner par priorité : jamais vérifiés en premier, puis les plus anciens
        $query->orderByRaw('CASE WHEN last_checked_at IS NULL THEN 0 ELSE 1 END')
              ->orderBy('last_checked_at', 'asc');

        // Appliquer la limite si spécifiée
        if ($limit) {
            $query->limit((int) $limit);
            $this->info("Limit: {$limit}");
        }

        $backlinks = $query->get();

        if ($backlinks->isEmpty()) {
            $this->warn("📭 No backlinks found matching the criteria.");
            return 0;
        }

        $this->info("Found {$backlinks->count()} backlink(s) to check.");

        $progressBar = $this->output->createProgressBar($backlinks->count());
        $progressBar->start();

        $dispatched = 0;

        foreach ($backlinks as $backlink) {
            try {
                CheckBacklinkJob::dispatch($backlink);
                $dispatched++;
                $progressBar->advance();
            } catch (\Exception $e) {
                Log::error('Failed to dispatch CheckBacklinkJob', [
                    'backlink_id' => $backlink->id,
                    'error' => $e->getMessage(),
                ]);
                $this->newLine();
                $this->error("Failed to dispatch job for backlink {$backlink->id}: {$e->getMessage()}");
            }
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info("✅ Successfully dispatched {$dispatched} job(s) to the queue.");

        Log::info('Backlink check command completed', [
            'frequency' => $frequency,
            'project_id' => $projectId,
            'status' => $status,
            'backlinks_found' => $backlinks->count(),
            'jobs_dispatched' => $dispatched,
        ]);

        return 0;
    }
}

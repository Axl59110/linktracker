<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Commande STORY-042 : Monitoring des queues
 * Affiche l'état des jobs en attente, échoués et récemment exécutés
 */
class QueueStatusCommand extends Command
{
    protected $signature = 'app:queue-status
                            {--failed : Lister les jobs échoués avec leur message d\'erreur}
                            {--reset-failed : Remettre en queue tous les jobs échoués}
                            {--limit=10 : Nombre de jobs à afficher}';

    protected $description = 'Affiche l\'état des queues et des jobs (en attente, échoués, récents)';

    public function handle(): int
    {
        if ($this->option('reset-failed')) {
            return $this->resetFailedJobs();
        }

        if ($this->option('failed')) {
            return $this->showFailedJobs();
        }

        return $this->showQueueStatus();
    }

    private function showQueueStatus(): int
    {
        $limit = (int) $this->option('limit');

        // Jobs en attente
        $pendingCount = DB::table('jobs')->count();
        $pendingByQueue = DB::table('jobs')
            ->select('queue', DB::raw('count(*) as count'))
            ->groupBy('queue')
            ->get();

        // Jobs échoués
        $failedCount = DB::table('failed_jobs')->count();

        // Stats affichage
        $this->info('=== État des Queues LinkTracker ===');
        $this->newLine();

        $this->line("Jobs en attente : <comment>{$pendingCount}</comment>");
        $this->line("Jobs échoués    : <error>{$failedCount}</error>");
        $this->newLine();

        if ($pendingCount > 0) {
            $this->info('Jobs en attente par queue :');
            $rows = $pendingByQueue->map(fn($row) => [
                $row->queue,
                $row->count,
            ])->toArray();
            $this->table(['Queue', 'Nombre'], $rows);
        }

        // Prochain job à exécuter
        $nextJob = DB::table('jobs')
            ->orderBy('available_at')
            ->first();

        if ($nextJob) {
            $payload = json_decode($nextJob->payload, true);
            $jobClass = $payload['displayName'] ?? $payload['job'] ?? 'Unknown';
            $availableAt = date('Y-m-d H:i:s', $nextJob->available_at);

            $this->newLine();
            $this->info('Prochain job :');
            $this->table(
                ['Job', 'Queue', 'Disponible à'],
                [[$jobClass, $nextJob->queue, $availableAt]]
            );
        }

        if ($failedCount > 0) {
            $this->newLine();
            $this->warn("⚠ {$failedCount} job(s) échoué(s). Utilisez --failed pour les détails ou --reset-failed pour les relancer.");
        }

        return self::SUCCESS;
    }

    private function showFailedJobs(): int
    {
        $limit = (int) $this->option('limit');

        $failedJobs = DB::table('failed_jobs')
            ->orderByDesc('failed_at')
            ->limit($limit)
            ->get();

        if ($failedJobs->isEmpty()) {
            $this->info('Aucun job échoué. ✓');
            return self::SUCCESS;
        }

        $this->error("=== Jobs Échoués ({$failedJobs->count()}) ===");
        $this->newLine();

        foreach ($failedJobs as $job) {
            $payload = json_decode($job->payload, true);
            $jobClass = $payload['displayName'] ?? $payload['job'] ?? 'Unknown';

            $this->line("<fg=red>#{$job->id}</> — <comment>{$jobClass}</comment>");
            $this->line("  Queue     : {$job->queue}");
            $this->line("  Échoué le : {$job->failed_at}");

            // Extraire les premières lignes de l'exception
            $exceptionLines = explode("\n", $job->exception);
            $exceptionSummary = implode("\n  ", array_slice($exceptionLines, 0, 3));
            $this->line("  Erreur    : {$exceptionSummary}");
            $this->newLine();
        }

        $total = DB::table('failed_jobs')->count();
        if ($total > $limit) {
            $this->line("<fg=yellow>... et " . ($total - $limit) . " autres. Augmentez --limit pour voir plus.</>");
        }

        $this->line('Utilisez <comment>--reset-failed</comment> pour relancer tous les jobs échoués.');

        return self::SUCCESS;
    }

    private function resetFailedJobs(): int
    {
        $failedJobs = DB::table('failed_jobs')->get();

        if ($failedJobs->isEmpty()) {
            $this->info('Aucun job échoué à relancer.');
            return self::SUCCESS;
        }

        $count = $failedJobs->count();

        if (!$this->confirm("Relancer {$count} job(s) échoué(s) ?", true)) {
            $this->line('Annulé.');
            return self::SUCCESS;
        }

        $relaunched = 0;
        foreach ($failedJobs as $failedJob) {
            try {
                $payload = json_decode($failedJob->payload, true);

                DB::table('jobs')->insert([
                    'queue'        => $failedJob->queue,
                    'payload'      => $failedJob->payload,
                    'attempts'     => 0,
                    'reserved_at'  => null,
                    'available_at' => now()->timestamp,
                    'created_at'   => now()->timestamp,
                ]);

                DB::table('failed_jobs')->where('id', $failedJob->id)->delete();
                $relaunched++;
            } catch (\Exception $e) {
                $this->error("Impossible de relancer le job #{$failedJob->id} : {$e->getMessage()}");
            }
        }

        $this->info("✓ {$relaunched}/{$count} job(s) relancé(s) avec succès.");

        return self::SUCCESS;
    }
}

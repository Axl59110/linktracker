<?php

namespace App\Console\Commands;

use App\Models\Backlink;
use App\Models\BacklinkSnapshot;
use App\Models\Project;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SnapshotBacklinksCommand extends Command
{
    protected $signature = 'app:snapshot-backlinks
                            {--date= : Date du snapshot (Y-m-d, défaut : aujourd\'hui)}
                            {--backfill=0 : Nombre de jours à rétro-remplir depuis les données existantes}';

    protected $description = 'Enregistre un snapshot quotidien du nombre de backlinks par statut et par projet';

    public function handle(): int
    {
        $date = $this->option('date')
            ? \Carbon\Carbon::parse($this->option('date'))->toDateString()
            : today()->toDateString();

        $backfillDays = (int) $this->option('backfill');

        if ($backfillDays > 0) {
            return $this->runBackfill($backfillDays);
        }

        $this->takeSnapshot($date);
        $this->info("✅ Snapshot du {$date} enregistré.");

        return self::SUCCESS;
    }

    private function takeSnapshot(string $date): void
    {
        // Snapshot global (toutes projets confondus)
        $this->upsertSnapshot($date, null);

        // Snapshot par projet
        $projectIds = Project::pluck('id');
        foreach ($projectIds as $projectId) {
            $this->upsertSnapshot($date, $projectId);
        }
    }

    private function upsertSnapshot(string $date, ?int $projectId): void
    {
        $query = Backlink::query()->when($projectId, fn($q) => $q->where('project_id', $projectId));

        BacklinkSnapshot::whereRaw("DATE(snapshot_date) = ?", [$date])
            ->where('project_id', $projectId)
            ->delete();

        BacklinkSnapshot::create([
            'snapshot_date' => $date,
            'project_id'    => $projectId,
            'count_active'  => (clone $query)->where('status', 'active')->count(),
            'count_lost'    => (clone $query)->where('status', 'lost')->count(),
            'count_changed' => (clone $query)->where('status', 'changed')->count(),
            'count_total'   => (clone $query)->count(),
        ]);
    }

    /**
     * Rétro-remplissage : reconstitue les snapshots passés à partir des données
     * actuelles + historique des alertes (approximation).
     * Utile au premier lancement pour avoir un historique immédiat.
     */
    private function runBackfill(int $days): int
    {
        $this->info("🔄 Rétro-remplissage sur {$days} jours...");

        // Pour le backfill, on utilise published_at comme proxy de la date de création.
        // On reconstitue jour par jour le nombre de backlinks qui existaient à cette date.
        $projectIds = array_merge([null], Project::pluck('id')->toArray());

        $bar = $this->output->createProgressBar($days);
        $bar->start();

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = today()->subDays($i)->toDateString();

            foreach ($projectIds as $projectId) {
                $base = Backlink::query()
                    ->when($projectId, fn($q) => $q->where('project_id', $projectId))
                    ->where(DB::raw("DATE(COALESCE(published_at, DATE(created_at)))"), '<=', $date);

                $isToday = $date === today()->toDateString();

                BacklinkSnapshot::whereRaw("DATE(snapshot_date) = ?", [$date])
                    ->where('project_id', $projectId)
                    ->delete();

                BacklinkSnapshot::create([
                    'snapshot_date' => $date,
                    'project_id'    => $projectId,
                    // Pour les jours passés : on ne connaît pas le statut d'alors,
                    // on met tout en count_total (approximation). Aujourd'hui = vrais statuts.
                    'count_active'  => $isToday ? (clone $base)->where('status', 'active')->count() : (clone $base)->count(),
                    'count_lost'    => $isToday ? (clone $base)->where('status', 'lost')->count()   : 0,
                    'count_changed' => $isToday ? (clone $base)->where('status', 'changed')->count() : 0,
                    'count_total'   => (clone $base)->count(),
                ]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("✅ Rétro-remplissage terminé ({$days} jours).");

        return self::SUCCESS;
    }
}

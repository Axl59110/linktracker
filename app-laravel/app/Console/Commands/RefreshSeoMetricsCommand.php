<?php

namespace App\Console\Commands;

use App\Jobs\FetchSeoMetricsJob;
use App\Models\Backlink;
use App\Models\DomainMetric;
use Illuminate\Console\Command;

class RefreshSeoMetricsCommand extends Command
{
    protected $signature = 'app:refresh-seo-metrics
                            {--force : Mettre à jour même les métriques récentes}
                            {--domain= : Mettre à jour un seul domaine}
                            {--limit=50 : Nombre maximum de domaines à traiter}';

    protected $description = 'Rafraîchit les métriques SEO des domaines sources de backlinks';

    public function handle(): int
    {
        $this->info('Démarrage de la mise à jour des métriques SEO...');

        // Récupère les domaines uniques des backlinks actifs
        $domains = Backlink::distinct()
            ->whereIn('status', ['active', 'changed'])
            ->pluck('source_url')
            ->map(fn ($url) => DomainMetric::extractDomain($url))
            ->unique()
            ->filter()
            ->values();

        if ($this->option('domain')) {
            $domains = collect([$this->option('domain')]);
        }

        $this->info("Domaines trouvés : {$domains->count()}");

        // Prépare les enregistrements DomainMetric
        $toProcess = collect();

        foreach ($domains as $domain) {
            $record = DomainMetric::forDomain($domain);

            if (! $this->option('force') && ! $record->isStale()) {
                $this->line("  <comment>Ignoré</comment> {$domain} (mis à jour récemment)");
                continue;
            }

            $toProcess->push($record);
        }

        $limit = (int) $this->option('limit');
        $toProcess = $toProcess->take($limit);

        if ($toProcess->isEmpty()) {
            $this->info('Aucun domaine périmé à mettre à jour.');
            return Command::SUCCESS;
        }

        $this->info("Dispatch de {$toProcess->count()} jobs (max {$limit})...");

        $rateLimitMs = (int) config('seo.rate_limit_ms', 1000);

        foreach ($toProcess as $i => $record) {
            $delaySeconds = (int) ($i * $rateLimitMs / 1000);

            FetchSeoMetricsJob::dispatch($record)
                ->delay(now()->addSeconds($delaySeconds));

            $this->line("  <info>Dispatché</info> {$record->domain} (délai: {$delaySeconds}s)");
        }

        $this->info('Tous les jobs ont été mis en queue.');

        return Command::SUCCESS;
    }
}

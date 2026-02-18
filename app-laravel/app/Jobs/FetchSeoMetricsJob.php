<?php

namespace App\Jobs;

use App\Models\DomainMetric;
use App\Services\Seo\SeoMetricService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FetchSeoMetricsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 60;

    public function __construct(
        public readonly DomainMetric $domainMetric
    ) {
        $this->onQueue('default');
    }

    public function handle(SeoMetricService $seoService): void
    {
        $domain = $this->domainMetric->domain;

        Log::info('FetchSeoMetricsJob: start', [
            'domain'   => $domain,
            'provider' => $seoService->getProviderName(),
        ]);

        $dto = $seoService->fetchAndStore($domain);

        if ($dto->hasError()) {
            Log::warning('FetchSeoMetricsJob: provider returned error', [
                'domain' => $domain,
                'error'  => $dto->error,
            ]);
        } else {
            Log::info('FetchSeoMetricsJob: done', [
                'domain' => $domain,
                'da'     => $dto->da,
                'dr'     => $dto->dr,
            ]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('FetchSeoMetricsJob: failed after retries', [
            'domain' => $this->domainMetric->domain,
            'error'  => $exception->getMessage(),
        ]);
    }
}

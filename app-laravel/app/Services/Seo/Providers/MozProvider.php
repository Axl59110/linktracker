<?php

namespace App\Services\Seo\Providers;

use App\Services\Seo\DomainMetricsDTO;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Provider Moz API v2 — Domain Authority, Page Authority, Spam Score.
 *
 * Documentation : https://moz.com/api/v2
 * Authentification : Basic Auth avec accessId:secretKey encodé en base64
 */
class MozProvider implements SeoMetricProviderInterface
{
    private const API_URL = 'https://lsapi.seomoz.com/v2/url_metrics';

    public function __construct(
        private readonly string $accessId,
        private readonly string $secretKey,
        private readonly int $timeoutSeconds = 15,
    ) {}

    public function getName(): string
    {
        return 'moz';
    }

    public function isAvailable(): bool
    {
        return ! empty($this->accessId) && ! empty($this->secretKey);
    }

    public function getMetrics(string $domain): DomainMetricsDTO
    {
        try {
            $response = Http::withBasicAuth($this->accessId, $this->secretKey)
                ->timeout($this->timeoutSeconds)
                ->post(self::API_URL, [
                    'targets' => [$domain],
                    'metrics' => ['domain_authority', 'page_authority', 'spam_score', 'linking_domains'],
                ]);

            if (! $response->successful()) {
                Log::warning('Moz API error', [
                    'domain' => $domain,
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);

                return new DomainMetricsDTO(
                    domain: $domain,
                    provider: $this->getName(),
                    error: "Moz API HTTP {$response->status()}",
                );
            }

            $data = $response->json('results.0') ?? [];

            return new DomainMetricsDTO(
                domain: $domain,
                da: isset($data['domain_authority']) ? (int) round($data['domain_authority']) : null,
                spam_score: isset($data['spam_score']) ? (int) round($data['spam_score'] * 100) : null, // Moz retourne 0-17
                backlinks_count: isset($data['linking_domains']) ? (int) $data['linking_domains'] : null,
                provider: $this->getName(),
            );

        } catch (\Illuminate\Http\Client\RequestException $e) {
            Log::error('Moz API request failed', ['domain' => $domain, 'error' => $e->getMessage()]);

            return new DomainMetricsDTO(
                domain: $domain,
                provider: $this->getName(),
                error: $e->getMessage(),
            );
        } catch (\Exception $e) {
            Log::error('Moz API unexpected error', ['domain' => $domain, 'error' => $e->getMessage()]);

            return new DomainMetricsDTO(
                domain: $domain,
                provider: $this->getName(),
                error: $e->getMessage(),
            );
        }
    }
}

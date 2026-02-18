<?php

namespace App\Services\Seo\Providers;

use App\Services\Seo\DomainMetricsDTO;

/**
 * Provider fallback — retourne des métriques nulles quand aucune API n'est configurée.
 * Permet à l'application de fonctionner sans clé API SEO.
 */
class CustomProvider implements SeoMetricProviderInterface
{
    public function getName(): string
    {
        return 'custom';
    }

    public function isAvailable(): bool
    {
        return true; // Toujours disponible (fallback)
    }

    public function getMetrics(string $domain): DomainMetricsDTO
    {
        return new DomainMetricsDTO(
            domain: $domain,
            provider: $this->getName(),
            error: 'Aucun provider SEO configuré. Configurez votre API dans les paramètres.',
        );
    }
}

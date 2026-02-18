<?php

namespace App\Services\Seo\Providers;

use App\Services\Seo\DomainMetricsDTO;

/**
 * Contrat pour tous les providers de métriques SEO.
 */
interface SeoMetricProviderInterface
{
    /**
     * Récupère les métriques SEO pour un domaine.
     *
     * @param  string $domain  Le domaine sans www ni protocole (ex: example.com)
     * @return DomainMetricsDTO
     */
    public function getMetrics(string $domain): DomainMetricsDTO;

    /**
     * Vérifie si le provider est configuré et disponible.
     */
    public function isAvailable(): bool;

    /**
     * Retourne le nom du provider.
     */
    public function getName(): string;
}

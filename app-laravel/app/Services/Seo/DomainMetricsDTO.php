<?php

namespace App\Services\Seo;

/**
 * Value Object représentant les métriques SEO d'un domaine.
 */
class DomainMetricsDTO
{
    public function __construct(
        public readonly string $domain,
        public readonly ?int $da = null,
        public readonly ?int $dr = null,
        public readonly ?int $tf = null,
        public readonly ?int $cf = null,
        public readonly ?int $spam_score = null,
        public readonly ?int $backlinks_count = null,
        public readonly string $provider = 'custom',
        public readonly ?string $error = null,
    ) {}

    /**
     * Vérifie si les métriques contiennent au moins une valeur.
     */
    public function hasData(): bool
    {
        return ! is_null($this->da)
            || ! is_null($this->dr)
            || ! is_null($this->tf);
    }

    /**
     * Vérifie si la récupération a échoué.
     */
    public function hasError(): bool
    {
        return ! is_null($this->error);
    }

    /**
     * Convertit en tableau pour la mise à jour du modèle.
     */
    public function toArray(): array
    {
        return [
            'da'              => $this->da,
            'dr'              => $this->dr,
            'tf'              => $this->tf,
            'cf'              => $this->cf,
            'spam_score'      => $this->spam_score,
            'backlinks_count' => $this->backlinks_count,
            'provider'        => $this->provider,
            'last_updated_at' => now(),
        ];
    }
}

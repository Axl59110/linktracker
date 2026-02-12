<?php

namespace App\Services\Security;

use App\Exceptions\SsrfException;

class UrlValidator
{
    /**
     * Liste des plages d'adresses IP bloquées pour la protection SSRF
     */
    private const BLOCKED_IP_RANGES = [
        '127.0.0.0/8',      // Localhost
        '10.0.0.0/8',       // RFC1918 private network
        '172.16.0.0/12',    // RFC1918 private network
        '192.168.0.0/16',   // RFC1918 private network
        '169.254.0.0/16',   // Link-local
        '0.0.0.0/8',        // Current network
        '224.0.0.0/4',      // Multicast
        '240.0.0.0/4',      // Reserved
    ];

    /**
     * Protocoles autorisés
     */
    private const ALLOWED_PROTOCOLS = ['http', 'https'];

    /**
     * Valide une URL pour prévenir les attaques SSRF
     *
     * @param string $url URL à valider
     * @throws SsrfException Si l'URL est bloquée pour des raisons de sécurité
     * @return void
     */
    public function validate(string $url): void
    {
        // 1. Parse l'URL
        $parsed = parse_url($url);

        if ($parsed === false) {
            throw new SsrfException("URL invalide : impossible de parser l'URL");
        }

        // 2. Vérifier le protocole
        if (!isset($parsed['scheme']) || !in_array(strtolower($parsed['scheme']), self::ALLOWED_PROTOCOLS)) {
            $scheme = $parsed['scheme'] ?? 'none';
            throw new SsrfException("Protocole non autorisé : {$scheme}. Seuls http et https sont acceptés.");
        }

        // 3. Vérifier qu'il y a un host
        if (!isset($parsed['host']) || empty($parsed['host'])) {
            throw new SsrfException("URL invalide : pas d'hôte spécifié");
        }

        $host = $parsed['host'];

        // 4. Résoudre le DNS pour obtenir l'IP
        $ip = gethostbyname($host);

        // Si gethostbyname retourne le hostname, c'est qu'il n'a pas pu résoudre
        if ($ip === $host && !filter_var($host, FILTER_VALIDATE_IP)) {
            throw new SsrfException("Impossible de résoudre le nom de domaine : {$host}");
        }

        // 5. Vérifier si l'IP est dans une plage bloquée
        foreach (self::BLOCKED_IP_RANGES as $range) {
            if ($this->ipInRange($ip, $range)) {
                throw new SsrfException("Accès à {$ip} ({$host}) bloqué pour des raisons de sécurité (protection SSRF)");
            }
        }

        // 6. Vérifier si c'est directement une IP privée (pour les IPs en hostname)
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            foreach (self::BLOCKED_IP_RANGES as $range) {
                if ($this->ipInRange($host, $range)) {
                    throw new SsrfException("Accès à {$host} bloqué pour des raisons de sécurité (protection SSRF)");
                }
            }
        }
    }

    /**
     * Vérifie si une IP appartient à une plage CIDR
     *
     * @param string $ip Adresse IP à vérifier
     * @param string $range Plage CIDR (ex: 192.168.0.0/16)
     * @return bool True si l'IP est dans la plage
     */
    private function ipInRange(string $ip, string $range): bool
    {
        [$subnet, $mask] = explode('/', $range);

        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        $maskLong = -1 << (32 - (int)$mask);

        // Gestion des erreurs de conversion
        if ($ipLong === false || $subnetLong === false) {
            return false;
        }

        return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
    }
}

<?php

namespace Tests\Unit\Services\Security;

use App\Exceptions\SsrfException;
use App\Services\Security\UrlValidator;
use PHPUnit\Framework\TestCase;

class UrlValidatorTest extends TestCase
{
    private UrlValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new UrlValidator();
    }

    /**
     * Test 1: Bloque localhost avec 127.0.0.1
     */
    public function test_blocks_localhost_127_0_0_1(): void
    {
        $this->expectException(SsrfException::class);
        $this->expectExceptionMessageMatches('/bloqué.*sécurité/i');

        $this->validator->validate('http://127.0.0.1/admin');
    }

    /**
     * Test 2: Bloque localhost avec le mot "localhost"
     */
    public function test_blocks_localhost_hostname(): void
    {
        $this->expectException(SsrfException::class);

        $this->validator->validate('http://localhost/api');
    }

    /**
     * Test 3: Bloque réseau privé 10.x.x.x
     */
    public function test_blocks_private_network_10(): void
    {
        $this->expectException(SsrfException::class);

        $this->validator->validate('http://10.0.0.1/internal');
    }

    /**
     * Test 4: Bloque réseau privé 192.168.x.x
     */
    public function test_blocks_private_network_192_168(): void
    {
        $this->expectException(SsrfException::class);

        $this->validator->validate('http://192.168.1.1/router');
    }

    /**
     * Test 5: Bloque réseau privé 172.16-31.x.x
     */
    public function test_blocks_private_network_172_16(): void
    {
        $this->expectException(SsrfException::class);

        $this->validator->validate('http://172.16.0.1/internal');
    }

    /**
     * Test 6: Bloque link-local 169.254.x.x
     */
    public function test_blocks_link_local_169_254(): void
    {
        $this->expectException(SsrfException::class);

        $this->validator->validate('http://169.254.169.254/metadata');
    }

    /**
     * Test 7: Autorise domaine public (example.com)
     */
    public function test_allows_public_domain_example_com(): void
    {
        // Ne devrait pas lancer d'exception
        $this->validator->validate('https://example.com/page');

        $this->assertTrue(true); // Test passe si pas d'exception
    }

    /**
     * Test 8: Autorise domaine public (google.com)
     */
    public function test_allows_public_domain_google(): void
    {
        $this->validator->validate('https://www.google.com/search');

        $this->assertTrue(true);
    }

    /**
     * Test 9: Rejette protocole FTP
     */
    public function test_rejects_ftp_protocol(): void
    {
        $this->expectException(SsrfException::class);
        $this->expectExceptionMessageMatches('/protocole.*autorisé/i');

        $this->validator->validate('ftp://example.com/file.txt');
    }

    /**
     * Test 10: Rejette protocole file://
     */
    public function test_rejects_file_protocol(): void
    {
        $this->expectException(SsrfException::class);
        $this->expectExceptionMessageMatches('/protocole/i');

        $this->validator->validate('file:///etc/passwd');
    }

    /**
     * Test 11: Accepte HTTPS (protocole sécurisé)
     */
    public function test_accepts_https_protocol(): void
    {
        $this->validator->validate('https://example.com');

        $this->assertTrue(true);
    }

    /**
     * Test 12: Accepte HTTP (protocole non-sécurisé mais autorisé)
     */
    public function test_accepts_http_protocol(): void
    {
        $this->validator->validate('http://example.com');

        $this->assertTrue(true);
    }

    /**
     * Test 13: Rejette URL sans protocole
     */
    public function test_rejects_url_without_protocol(): void
    {
        $this->expectException(SsrfException::class);

        $this->validator->validate('example.com/page');
    }

    /**
     * Test 14: Rejette URL invalide/malformée
     */
    public function test_rejects_malformed_url(): void
    {
        $this->expectException(SsrfException::class);

        $this->validator->validate('http://');
    }

    /**
     * Test 15: Bloque 0.0.0.0 (current network)
     */
    public function test_blocks_0_0_0_0(): void
    {
        $this->expectException(SsrfException::class);

        $this->validator->validate('http://0.0.0.0/');
    }

    /**
     * Test 16: Bloque multicast 224.0.0.0/4
     */
    public function test_blocks_multicast(): void
    {
        $this->expectException(SsrfException::class);

        $this->validator->validate('http://224.0.0.1/');
    }

    /**
     * Test 17: Autorise IP publique directe (8.8.8.8 - Google DNS)
     */
    public function test_allows_public_ip(): void
    {
        $this->validator->validate('http://8.8.8.8/');

        $this->assertTrue(true);
    }

    /**
     * Test 18: Gère les sous-domaines publics
     */
    public function test_allows_public_subdomain(): void
    {
        // Utilise www.google.com qui est un vrai sous-domaine résolvable
        $this->validator->validate('https://www.google.com/maps');

        $this->assertTrue(true);
    }
}

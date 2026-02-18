<?php

namespace Tests\Unit\Services\Seo;

use App\Models\DomainMetric;
use App\Services\Seo\DomainMetricsDTO;
use App\Services\Seo\Providers\CustomProvider;
use App\Services\Seo\Providers\MozProvider;
use App\Services\Seo\SeoMetricService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SeoMetricServiceTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Tests du DTO
    // -------------------------------------------------------------------------

    public function test_dto_has_data_returns_true_with_da(): void
    {
        $dto = new DomainMetricsDTO(domain: 'example.com', da: 40, provider: 'moz');
        $this->assertTrue($dto->hasData());
    }

    public function test_dto_has_data_returns_false_when_empty(): void
    {
        $dto = new DomainMetricsDTO(domain: 'example.com', provider: 'custom', error: 'no API');
        $this->assertFalse($dto->hasData());
    }

    public function test_dto_has_error_returns_true_when_error_set(): void
    {
        $dto = new DomainMetricsDTO(domain: 'example.com', provider: 'moz', error: 'timeout');
        $this->assertTrue($dto->hasError());
    }

    public function test_dto_to_array_includes_required_keys(): void
    {
        $dto = new DomainMetricsDTO(domain: 'example.com', da: 50, provider: 'moz');
        $arr = $dto->toArray();

        $this->assertArrayHasKey('da', $arr);
        $this->assertArrayHasKey('provider', $arr);
        $this->assertArrayHasKey('last_updated_at', $arr);
        $this->assertEquals(50, $arr['da']);
        $this->assertEquals('moz', $arr['provider']);
    }

    // -------------------------------------------------------------------------
    // Tests du CustomProvider
    // -------------------------------------------------------------------------

    public function test_custom_provider_is_always_available(): void
    {
        $provider = new CustomProvider();
        $this->assertTrue($provider->isAvailable());
        $this->assertEquals('custom', $provider->getName());
    }

    public function test_custom_provider_returns_error_dto(): void
    {
        $provider = new CustomProvider();
        $dto = $provider->getMetrics('example.com');

        $this->assertTrue($dto->hasError());
        $this->assertFalse($dto->hasData());
        $this->assertEquals('custom', $dto->provider);
    }

    // -------------------------------------------------------------------------
    // Tests du MozProvider (avec HTTP fake)
    // -------------------------------------------------------------------------

    public function test_moz_provider_is_available_with_credentials(): void
    {
        $provider = new MozProvider('access-id', 'secret-key');
        $this->assertTrue($provider->isAvailable());
    }

    public function test_moz_provider_not_available_without_credentials(): void
    {
        $provider = new MozProvider('', '');
        $this->assertFalse($provider->isAvailable());
    }

    public function test_moz_provider_parses_successful_response(): void
    {
        Http::fake([
            'lsapi.seomoz.com/*' => Http::response([
                'results' => [[
                    'domain_authority' => 45.7,
                    'spam_score'       => 0.03,
                    'linking_domains'  => 1200,
                ]],
            ], 200),
        ]);

        $provider = new MozProvider('id', 'secret');
        $dto = $provider->getMetrics('example.com');

        $this->assertEquals(46, $dto->da); // arrondi
        $this->assertEquals(3, $dto->spam_score); // 0.03 * 100 = 3
        $this->assertEquals(1200, $dto->backlinks_count);
        $this->assertEquals('moz', $dto->provider);
        $this->assertFalse($dto->hasError());
    }

    public function test_moz_provider_returns_error_on_http_failure(): void
    {
        Http::fake([
            'lsapi.seomoz.com/*' => Http::response(['error' => 'Unauthorized'], 401),
        ]);

        $provider = new MozProvider('bad-id', 'bad-secret');
        $dto = $provider->getMetrics('example.com');

        $this->assertTrue($dto->hasError());
        $this->assertStringContainsString('401', $dto->error);
    }

    public function test_moz_provider_returns_error_on_timeout(): void
    {
        Http::fake([
            'lsapi.seomoz.com/*' => Http::response('Gateway Timeout', 504),
        ]);

        $provider = new MozProvider('id', 'secret');
        $dto = $provider->getMetrics('example.com');

        $this->assertTrue($dto->hasError());
        $this->assertStringContainsString('504', $dto->error);
    }

    // -------------------------------------------------------------------------
    // Tests du SeoMetricService
    // -------------------------------------------------------------------------

    public function test_service_uses_custom_provider_by_default(): void
    {
        config(['seo.provider' => 'custom']);

        $service = new SeoMetricService();
        $this->assertEquals('custom', $service->getProviderName());
        $this->assertFalse($service->hasRealProvider());
    }

    public function test_service_fetch_and_store_creates_domain_metric(): void
    {
        config(['seo.provider' => 'custom']);

        $service = new SeoMetricService();
        $service->fetchAndStore('example.com');

        $this->assertDatabaseHas('domain_metrics', ['domain' => 'example.com']);
    }

    public function test_service_fetch_and_store_with_moz_updates_metrics(): void
    {
        Http::fake([
            'lsapi.seomoz.com/*' => Http::response([
                'results' => [[
                    'domain_authority' => 55.0,
                    'spam_score'       => 0.01,
                    'linking_domains'  => 500,
                ]],
            ], 200),
        ]);

        config([
            'seo.provider'        => 'moz',
            'seo.moz_access_id'   => 'test-id',
            'seo.moz_secret_key'  => 'test-secret',
        ]);

        $service = new SeoMetricService();
        $dto = $service->fetchAndStore('example.com');

        $this->assertEquals(55, $dto->da);
        $this->assertDatabaseHas('domain_metrics', [
            'domain'   => 'example.com',
            'da'       => 55,
            'provider' => 'moz',
        ]);
    }

    public function test_service_falls_back_to_custom_when_moz_credentials_missing(): void
    {
        config([
            'seo.provider'       => 'moz',
            'seo.moz_access_id'  => '',
            'seo.moz_secret_key' => '',
        ]);

        $service = new SeoMetricService();
        $this->assertEquals('custom', $service->getProviderName());
    }

    public function test_service_fetch_and_store_updates_existing_record(): void
    {
        config(['seo.provider' => 'custom']);

        DomainMetric::create(['domain' => 'example.com', 'da' => 30, 'provider' => 'moz']);

        $service = new SeoMetricService();
        $service->fetchAndStore('example.com');

        $this->assertDatabaseCount('domain_metrics', 1);
    }
}

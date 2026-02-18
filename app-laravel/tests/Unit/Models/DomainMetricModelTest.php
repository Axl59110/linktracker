<?php

namespace Tests\Unit\Models;

use App\Models\DomainMetric;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DomainMetricModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_domain_metric(): void
    {
        $metric = DomainMetric::create([
            'domain' => 'example.com',
            'da' => 45,
            'dr' => 38,
            'spam_score' => 3,
            'provider' => 'moz',
            'last_updated_at' => now(),
        ]);

        $this->assertDatabaseHas('domain_metrics', [
            'domain' => 'example.com',
            'da' => 45,
            'provider' => 'moz',
        ]);
    }

    public function test_for_domain_creates_if_not_exists(): void
    {
        $metric = DomainMetric::forDomain('newsite.com');

        $this->assertInstanceOf(DomainMetric::class, $metric);
        $this->assertEquals('newsite.com', $metric->domain);
        $this->assertEquals('custom', $metric->provider);
        $this->assertDatabaseCount('domain_metrics', 1);
    }

    public function test_for_domain_returns_existing(): void
    {
        DomainMetric::create(['domain' => 'existing.com', 'da' => 55, 'provider' => 'moz']);
        $metric = DomainMetric::forDomain('existing.com');

        $this->assertEquals(55, $metric->da);
        $this->assertDatabaseCount('domain_metrics', 1);
    }

    public function test_extract_domain_strips_www(): void
    {
        $this->assertEquals('example.com', DomainMetric::extractDomain('https://www.example.com/page'));
        $this->assertEquals('example.com', DomainMetric::extractDomain('http://example.com/page'));
        $this->assertEquals('sub.example.com', DomainMetric::extractDomain('https://sub.example.com'));
    }

    public function test_extract_domain_lowercases(): void
    {
        $this->assertEquals('example.com', DomainMetric::extractDomain('https://EXAMPLE.COM/page'));
    }

    public function test_stale_scope_returns_never_updated(): void
    {
        DomainMetric::create(['domain' => 'fresh.com', 'provider' => 'moz', 'last_updated_at' => now()]);
        DomainMetric::create(['domain' => 'stale.com', 'provider' => 'custom', 'last_updated_at' => null]);
        DomainMetric::create(['domain' => 'old.com', 'provider' => 'moz', 'last_updated_at' => now()->subDays(2)]);

        $stale = DomainMetric::stale()->pluck('domain')->toArray();

        $this->assertContains('stale.com', $stale);
        $this->assertContains('old.com', $stale);
        $this->assertNotContains('fresh.com', $stale);
    }

    public function test_is_stale_returns_true_when_no_update(): void
    {
        $metric = DomainMetric::create(['domain' => 'test.com', 'provider' => 'custom']);
        $this->assertTrue($metric->isStale());
    }

    public function test_is_stale_returns_false_when_recently_updated(): void
    {
        $metric = DomainMetric::create([
            'domain' => 'test.com',
            'provider' => 'moz',
            'last_updated_at' => now()->subHours(6),
        ]);
        $this->assertFalse($metric->isStale());
    }

    public function test_has_data_returns_true_with_da(): void
    {
        $metric = DomainMetric::create(['domain' => 'test.com', 'da' => 30, 'provider' => 'moz']);
        $this->assertTrue($metric->hasData());
    }

    public function test_has_data_returns_false_with_no_metrics(): void
    {
        $metric = DomainMetric::create(['domain' => 'test.com', 'provider' => 'custom']);
        $this->assertFalse($metric->hasData());
    }

    public function test_authority_color_returns_green_for_high_da(): void
    {
        $metric = new DomainMetric(['da' => 50]);
        $this->assertEquals('green', $metric->authority_color);
    }

    public function test_authority_color_returns_orange_for_medium_da(): void
    {
        $metric = new DomainMetric(['da' => 25]);
        $this->assertEquals('orange', $metric->authority_color);
    }

    public function test_authority_color_returns_red_for_low_da(): void
    {
        $metric = new DomainMetric(['da' => 10]);
        $this->assertEquals('red', $metric->authority_color);
    }

    public function test_spam_color_returns_green_for_low_spam(): void
    {
        $metric = new DomainMetric(['spam_score' => 2]);
        $this->assertEquals('green', $metric->spam_color);
    }

    public function test_spam_color_returns_red_for_high_spam(): void
    {
        $metric = new DomainMetric(['spam_score' => 20]);
        $this->assertEquals('red', $metric->spam_color);
    }

    public function test_domain_is_unique(): void
    {
        DomainMetric::create(['domain' => 'unique.com', 'provider' => 'custom']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        DomainMetric::create(['domain' => 'unique.com', 'provider' => 'moz']);
    }
}

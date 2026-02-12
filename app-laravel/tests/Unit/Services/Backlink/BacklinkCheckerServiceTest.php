<?php

namespace Tests\Unit\Services\Backlink;

use Tests\TestCase;
use App\Services\Backlink\BacklinkCheckerService;
use App\Services\Security\UrlValidator;
use App\Models\Backlink;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BacklinkCheckerServiceTest extends TestCase
{
    use RefreshDatabase;

    protected BacklinkCheckerService $service;
    protected User $user;
    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new BacklinkCheckerService(new UrlValidator());

        $this->user = User::factory()->create();
        $this->project = Project::factory()->create(['user_id' => $this->user->id]);
    }

    public function test_can_detect_present_backlink_with_dofollow(): void
    {
        $backlink = Backlink::factory()->create([
            'project_id' => $this->project->id,
            'source_url' => 'https://example.com/article',
            'target_url' => 'https://mysite.com',
        ]);

        $html = '
            <!DOCTYPE html>
            <html>
            <body>
                <p>Check out <a href="https://mysite.com">this great site</a> for more info.</p>
            </body>
            </html>
        ';

        Http::fake([
            'example.com/*' => Http::response($html, 200),
        ]);

        $result = $this->service->check($backlink);

        $this->assertTrue($result['is_present']);
        $this->assertEquals(200, $result['http_status']);
        $this->assertEquals('this great site', $result['anchor_text']);
        $this->assertNull($result['rel_attributes']);
        $this->assertTrue($result['is_dofollow']);
        $this->assertNull($result['error_message']);
    }

    public function test_can_detect_present_backlink_with_nofollow(): void
    {
        $backlink = Backlink::factory()->create([
            'project_id' => $this->project->id,
            'source_url' => 'https://example.com/article',
            'target_url' => 'https://mysite.com',
        ]);

        $html = '
            <!DOCTYPE html>
            <html>
            <body>
                <p>Sponsored link: <a href="https://mysite.com" rel="nofollow sponsored">Visit here</a></p>
            </body>
            </html>
        ';

        Http::fake([
            'example.com/*' => Http::response($html, 200),
        ]);

        $result = $this->service->check($backlink);

        $this->assertTrue($result['is_present']);
        $this->assertEquals('Visit here', $result['anchor_text']);
        $this->assertEquals('nofollow,sponsored', $result['rel_attributes']);
        $this->assertFalse($result['is_dofollow']);
    }

    public function test_can_detect_ugc_link(): void
    {
        $backlink = Backlink::factory()->create([
            'project_id' => $this->project->id,
            'source_url' => 'https://example.com/article',
            'target_url' => 'https://mysite.com',
        ]);

        $html = '
            <!DOCTYPE html>
            <html>
            <body>
                <p>User comment: <a href="https://mysite.com" rel="ugc nofollow">User link</a></p>
            </body>
            </html>
        ';

        Http::fake([
            'example.com/*' => Http::response($html, 200),
        ]);

        $result = $this->service->check($backlink);

        $this->assertTrue($result['is_present']);
        $this->assertStringContainsString('ugc', $result['rel_attributes']);
        $this->assertStringContainsString('nofollow', $result['rel_attributes']);
        $this->assertFalse($result['is_dofollow']);
    }

    public function test_detects_missing_backlink(): void
    {
        $backlink = Backlink::factory()->create([
            'project_id' => $this->project->id,
            'source_url' => 'https://example.com/article',
            'target_url' => 'https://mysite.com',
        ]);

        $html = '
            <!DOCTYPE html>
            <html>
            <body>
                <p>No backlink here, just some text.</p>
                <a href="https://othersite.com">Another site</a>
            </body>
            </html>
        ';

        Http::fake([
            'example.com/*' => Http::response($html, 200),
        ]);

        $result = $this->service->check($backlink);

        $this->assertFalse($result['is_present']);
        $this->assertEquals(200, $result['http_status']);
        $this->assertEquals('Backlink non trouvé dans la page', $result['error_message']);
    }

    public function test_handles_http_404_error(): void
    {
        $backlink = Backlink::factory()->create([
            'project_id' => $this->project->id,
            'source_url' => 'https://example.com/missing',
            'target_url' => 'https://mysite.com',
        ]);

        Http::fake([
            'example.com/*' => Http::response('Not Found', 404),
        ]);

        $result = $this->service->check($backlink);

        $this->assertFalse($result['is_present']);
        $this->assertEquals(404, $result['http_status']);
        $this->assertStringContainsString('404', $result['error_message']);
    }

    public function test_handles_http_500_error(): void
    {
        $backlink = Backlink::factory()->create([
            'project_id' => $this->project->id,
            'source_url' => 'https://example.com/error',
            'target_url' => 'https://mysite.com',
        ]);

        Http::fake([
            'example.com/*' => Http::response('Server Error', 500),
        ]);

        $result = $this->service->check($backlink);

        $this->assertFalse($result['is_present']);
        $this->assertEquals(500, $result['http_status']);
        $this->assertStringContainsString('500', $result['error_message']);
    }

    public function test_blocks_ssrf_attempt_localhost(): void
    {
        $backlink = Backlink::factory()->create([
            'project_id' => $this->project->id,
            'source_url' => 'http://localhost/admin',
            'target_url' => 'https://mysite.com',
        ]);

        Http::fake();

        $result = $this->service->check($backlink);

        $this->assertFalse($result['is_present']);
        $this->assertNull($result['http_status']);
        $this->assertStringContainsString('SSRF', $result['error_message']);

        Http::assertNothingSent();
    }

    public function test_blocks_ssrf_attempt_private_network(): void
    {
        $backlink = Backlink::factory()->create([
            'project_id' => $this->project->id,
            'source_url' => 'http://192.168.1.1/admin',
            'target_url' => 'https://mysite.com',
        ]);

        Http::fake();

        $result = $this->service->check($backlink);

        $this->assertFalse($result['is_present']);
        $this->assertStringContainsString('SSRF', $result['error_message']);

        Http::assertNothingSent();
    }

    public function test_normalizes_urls_with_trailing_slash(): void
    {
        $backlink = Backlink::factory()->create([
            'project_id' => $this->project->id,
            'source_url' => 'https://example.com/article',
            'target_url' => 'https://mysite.com/page',
        ]);

        $html = '
            <!DOCTYPE html>
            <html>
            <body>
                <a href="https://mysite.com/page/">Link with trailing slash</a>
            </body>
            </html>
        ';

        Http::fake([
            'example.com/*' => Http::response($html, 200),
        ]);

        $result = $this->service->check($backlink);

        $this->assertTrue($result['is_present']);
        $this->assertEquals('Link with trailing slash', $result['anchor_text']);
    }

    public function test_normalizes_urls_with_http_vs_https(): void
    {
        $backlink = Backlink::factory()->create([
            'project_id' => $this->project->id,
            'source_url' => 'https://example.com/article',
            'target_url' => 'https://mysite.com',
        ]);

        $html = '
            <!DOCTYPE html>
            <html>
            <body>
                <a href="http://mysite.com">Link with http</a>
            </body>
            </html>
        ';

        Http::fake([
            'example.com/*' => Http::response($html, 200),
        ]);

        $result = $this->service->check($backlink);

        $this->assertTrue($result['is_present']);
    }

    public function test_normalizes_urls_with_www(): void
    {
        $backlink = Backlink::factory()->create([
            'project_id' => $this->project->id,
            'source_url' => 'https://example.com/article',
            'target_url' => 'https://mysite.com',
        ]);

        $html = '
            <!DOCTYPE html>
            <html>
            <body>
                <a href="https://www.mysite.com">Link with www</a>
            </body>
            </html>
        ';

        Http::fake([
            'example.com/*' => Http::response($html, 200),
        ]);

        $result = $this->service->check($backlink);

        $this->assertTrue($result['is_present']);
    }

    public function test_handles_empty_anchor_text(): void
    {
        $backlink = Backlink::factory()->create([
            'project_id' => $this->project->id,
            'source_url' => 'https://example.com/article',
            'target_url' => 'https://mysite.com',
        ]);

        $html = '
            <!DOCTYPE html>
            <html>
            <body>
                <a href="https://mysite.com"></a>
            </body>
            </html>
        ';

        Http::fake([
            'example.com/*' => Http::response($html, 200),
        ]);

        $result = $this->service->check($backlink);

        $this->assertTrue($result['is_present']);
        $this->assertNull($result['anchor_text']);
    }

    public function test_handles_multiple_links_to_same_target(): void
    {
        $backlink = Backlink::factory()->create([
            'project_id' => $this->project->id,
            'source_url' => 'https://example.com/article',
            'target_url' => 'https://mysite.com',
        ]);

        $html = '
            <!DOCTYPE html>
            <html>
            <body>
                <a href="https://othersite.com">Other</a>
                <a href="https://mysite.com" rel="nofollow">First link</a>
                <a href="https://mysite.com">Second link</a>
            </body>
            </html>
        ';

        Http::fake([
            'example.com/*' => Http::response($html, 200),
        ]);

        $result = $this->service->check($backlink);

        $this->assertTrue($result['is_present']);
        // Devrait trouver le premier lien correspondant
        $this->assertEquals('First link', $result['anchor_text']);
        $this->assertFalse($result['is_dofollow']);
    }

    public function test_can_configure_timeout(): void
    {
        $this->service->setTimeout(60);

        $backlink = Backlink::factory()->create([
            'project_id' => $this->project->id,
            'source_url' => 'https://example.com/article',
            'target_url' => 'https://mysite.com',
        ]);

        Http::fake([
            'example.com/*' => Http::response('<html><body><a href="https://mysite.com">Link</a></body></html>', 200),
        ]);

        $result = $this->service->check($backlink);

        $this->assertTrue($result['is_present']);
    }

    public function test_can_configure_user_agent(): void
    {
        $this->service->setUserAgent('CustomBot/1.0');

        $backlink = Backlink::factory()->create([
            'project_id' => $this->project->id,
            'source_url' => 'https://example.com/article',
            'target_url' => 'https://mysite.com',
        ]);

        Http::fake([
            'example.com/*' => Http::response('<html><body><a href="https://mysite.com">Link</a></body></html>', 200),
        ]);

        $result = $this->service->check($backlink);

        $this->assertTrue($result['is_present']);
    }
}

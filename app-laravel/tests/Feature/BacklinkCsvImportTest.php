<?php

namespace Tests\Feature;

use App\Models\Backlink;
use App\Models\Project;
use App\Models\User;
use App\Services\BacklinkCsvImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Tests STORY-031 : Import CSV de backlinks
 * Tests STORY-035 : Export CSV de backlinks
 */
class BacklinkCsvImportTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user    = User::factory()->create();
        $this->project = Project::factory()->for($this->user)->create();
        $this->actingAs($this->user);
    }

    // ── Import form ──────────────────────────────────────────────────────

    public function test_import_form_is_accessible(): void
    {
        $response = $this->get(route('backlinks.import'));

        $response->assertStatus(200);
        $response->assertSee('Importer des backlinks');
    }

    // ── BacklinkCsvImportService ─────────────────────────────────────────

    private function makeCsvFile(string $content): UploadedFile
    {
        $path = sys_get_temp_dir() . '/test_backlinks_' . uniqid() . '.csv';
        file_put_contents($path, $content);

        return new UploadedFile($path, 'backlinks.csv', 'text/csv', null, true);
    }

    public function test_service_imports_valid_csv(): void
    {
        $csv = "source_url,target_url,anchor_text\nhttps://example.com/page,https://monsite.com,Mon site\n";
        $file = $this->makeCsvFile($csv);

        $service = new BacklinkCsvImportService();
        $result  = $service->import($file, $this->project);

        $this->assertEquals(1, $result['imported']);
        $this->assertEquals(0, $result['skipped']);
        $this->assertEmpty($result['errors']);
        $this->assertDatabaseHas('backlinks', [
            'source_url'  => 'https://example.com/page',
            'target_url'  => 'https://monsite.com',
            'anchor_text' => 'Mon site',
        ]);
    }

    public function test_service_skips_duplicates(): void
    {
        Backlink::factory()->for($this->project)->create([
            'source_url' => 'https://existing.com/page',
            'target_url' => 'https://monsite.com',
        ]);

        $csv = "source_url,target_url\nhttps://existing.com/page,https://monsite.com\n";
        $file = $this->makeCsvFile($csv);

        $service = new BacklinkCsvImportService();
        $result  = $service->import($file, $this->project);

        $this->assertEquals(0, $result['imported']);
        $this->assertEquals(1, $result['skipped']);
    }

    public function test_service_returns_error_for_missing_required_column(): void
    {
        $csv = "source_url,anchor_text\nhttps://example.com,Mon site\n";
        $file = $this->makeCsvFile($csv);

        $service = new BacklinkCsvImportService();
        $result  = $service->import($file, $this->project);

        $this->assertEquals(0, $result['imported']);
        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('target_url', $result['errors'][0]);
    }

    public function test_service_skips_invalid_url(): void
    {
        $csv = "source_url,target_url\nnot-a-url,https://monsite.com\nhttps://valid.com/page,https://monsite.com\n";
        $file = $this->makeCsvFile($csv);

        $service = new BacklinkCsvImportService();
        $result  = $service->import($file, $this->project);

        $this->assertEquals(1, $result['imported']);
        $this->assertEquals(1, $result['skipped']);
        $this->assertNotEmpty($result['errors']);
    }

    public function test_service_uses_default_values(): void
    {
        $csv = "source_url,target_url\nhttps://example.com/page,https://monsite.com\n";
        $file = $this->makeCsvFile($csv);

        $service = new BacklinkCsvImportService();
        $service->import($file, $this->project);

        $this->assertDatabaseHas('backlinks', [
            'source_url' => 'https://example.com/page',
            'status'     => 'active',
            'tier_level' => 'tier1',
            'spot_type'  => 'external',
        ]);
    }

    public function test_service_imports_multiple_rows(): void
    {
        $csv = "source_url,target_url,price,currency\n"
             . "https://site1.com/p,https://monsite.com,25.50,EUR\n"
             . "https://site2.com/p,https://monsite.com,0,USD\n"
             . "https://site3.com/p,https://monsite.com,,\n";
        $file = $this->makeCsvFile($csv);

        $service = new BacklinkCsvImportService();
        $result  = $service->import($file, $this->project);

        $this->assertEquals(3, $result['imported']);
    }

    // ── HTTP import endpoint ─────────────────────────────────────────────

    public function test_import_endpoint_processes_valid_csv(): void
    {
        $csv = "source_url,target_url\nhttps://example.com/new-page,https://monsite.com\n";
        $file = UploadedFile::fake()->createWithContent('backlinks.csv', $csv);

        $response = $this->post(route('backlinks.import.process'), [
            'csv_file'   => $file,
            'project_id' => $this->project->id,
        ]);

        $response->assertRedirect(route('backlinks.index'));
        $response->assertSessionHas('success');
    }

    public function test_import_endpoint_rejects_invalid_project(): void
    {
        $csv = "source_url,target_url\nhttps://example.com,https://monsite.com\n";
        $file = UploadedFile::fake()->createWithContent('backlinks.csv', $csv);

        $response = $this->post(route('backlinks.import.process'), [
            'csv_file'   => $file,
            'project_id' => 99999,
        ]);

        $response->assertSessionHasErrors('project_id');
    }

    // ── Export CSV (STORY-035) ────────────────────────────────────────────

    public function test_export_csv_returns_csv_file(): void
    {
        Backlink::factory()->count(3)->for($this->project)->create(['status' => 'active']);

        $response = $this->get(route('backlinks.export'));

        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
    }

    public function test_export_csv_contains_backlinks_data(): void
    {
        Backlink::factory()->for($this->project)->create([
            'source_url'  => 'https://test-export.com/page',
            'target_url'  => 'https://monsite.com',
            'anchor_text' => 'Ancre test',
            'status'      => 'active',
        ]);

        $response = $this->get(route('backlinks.export'));
        $content  = $response->streamedContent();

        $this->assertStringContainsString('test-export.com', $content);
        $this->assertStringContainsString('Ancre test', $content);
    }

    public function test_export_csv_filtered_by_status(): void
    {
        Backlink::factory()->for($this->project)->create(['status' => 'active', 'source_url' => 'https://active.com/p']);
        Backlink::factory()->for($this->project)->create(['status' => 'lost', 'source_url' => 'https://lost.com/p']);

        $response = $this->get(route('backlinks.export', ['status' => 'active']));
        $content  = $response->streamedContent();

        $this->assertStringContainsString('active.com', $content);
        $this->assertStringNotContainsString('lost.com', $content);
    }
}

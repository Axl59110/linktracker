<?php

namespace Tests\Unit;

use App\Models\Backlink;
use App\Models\Platform;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test platform can be created with required fields.
     */
    public function test_platform_can_be_created(): void
    {
        $platform = Platform::create([
            'name' => 'Test Marketplace',
            'type' => 'marketplace',
        ]);

        $this->assertDatabaseHas('platforms', [
            'name' => 'Test Marketplace',
            'type' => 'marketplace',
        ]);
    }

    /**
     * Test platform can have all fields.
     */
    public function test_platform_can_have_all_fields(): void
    {
        $platform = Platform::create([
            'name' => 'NextLevel Links',
            'url' => 'https://nextlevellinks.com',
            'description' => 'Premium marketplace for high-quality backlinks',
            'type' => 'marketplace',
            'is_active' => true,
        ]);

        $this->assertEquals('NextLevel Links', $platform->name);
        $this->assertEquals('https://nextlevellinks.com', $platform->url);
        $this->assertEquals('Premium marketplace for high-quality backlinks', $platform->description);
        $this->assertEquals('marketplace', $platform->type);
        $this->assertTrue($platform->is_active);
    }

    /**
     * Test platform type can be marketplace.
     */
    public function test_platform_type_can_be_marketplace(): void
    {
        $platform = Platform::create([
            'name' => 'Marketplace Platform',
            'type' => 'marketplace',
        ]);

        $this->assertEquals('marketplace', $platform->type);
    }

    /**
     * Test platform type can be direct.
     */
    public function test_platform_type_can_be_direct(): void
    {
        $platform = Platform::create([
            'name' => 'Direct Contact',
            'type' => 'direct',
        ]);

        $this->assertEquals('direct', $platform->type);
    }

    /**
     * Test platform type can be other.
     */
    public function test_platform_type_can_be_other(): void
    {
        $platform = Platform::create([
            'name' => 'Other Platform',
            'type' => 'other',
        ]);

        $this->assertEquals('other', $platform->type);
    }

    /**
     * Test is_active defaults to true.
     */
    public function test_is_active_defaults_to_true(): void
    {
        $platform = Platform::create([
            'name' => 'Test Platform',
            'type' => 'marketplace',
        ]);

        $this->assertTrue($platform->is_active);
    }

    /**
     * Test is_active is cast to boolean.
     */
    public function test_is_active_is_cast_to_boolean(): void
    {
        $platform = Platform::create([
            'name' => 'Test Platform',
            'type' => 'marketplace',
            'is_active' => false,
        ]);

        $this->assertIsBool($platform->is_active);
        $this->assertFalse($platform->is_active);
    }

    /**
     * Test platform can have multiple backlinks.
     */
    public function test_platform_can_have_multiple_backlinks(): void
    {
        $project = Project::factory()->create();
        $platform = Platform::factory()->create();

        $backlink1 = Backlink::factory()->create([
            'project_id' => $project->id,
            'platform_id' => $platform->id,
        ]);

        $backlink2 = Backlink::factory()->create([
            'project_id' => $project->id,
            'platform_id' => $platform->id,
        ]);

        $this->assertCount(2, $platform->backlinks);
        $this->assertTrue($platform->backlinks->contains($backlink1));
        $this->assertTrue($platform->backlinks->contains($backlink2));
    }

    /**
     * Test active scope returns only active platforms.
     */
    public function test_active_scope_returns_only_active_platforms(): void
    {
        Platform::factory()->create(['name' => 'Active Platform 1', 'is_active' => true]);
        Platform::factory()->create(['name' => 'Active Platform 2', 'is_active' => true]);
        Platform::factory()->create(['name' => 'Inactive Platform', 'is_active' => false]);

        $activePlatforms = Platform::active()->get();

        $this->assertCount(2, $activePlatforms);
        $this->assertTrue($activePlatforms->every(fn($platform) => $platform->is_active === true));
    }

    /**
     * Test platform deletion sets backlinks platform_id to null.
     */
    public function test_platform_deletion_sets_backlinks_platform_to_null(): void
    {
        $project = Project::factory()->create();
        $platform = Platform::factory()->create();

        $backlink = Backlink::factory()->create([
            'project_id' => $project->id,
            'platform_id' => $platform->id,
        ]);

        $this->assertEquals($platform->id, $backlink->platform_id);

        // Delete platform
        $platform->delete();

        // Refresh backlink from database
        $backlink->refresh();

        // platform_id should be set to null (onDelete('set null'))
        $this->assertNull($backlink->platform_id);
    }

    /**
     * Test platform timestamps are set.
     */
    public function test_platform_timestamps_are_set(): void
    {
        $platform = Platform::create([
            'name' => 'Test Platform',
            'type' => 'marketplace',
        ]);

        $this->assertNotNull($platform->created_at);
        $this->assertNotNull($platform->updated_at);
    }

    /**
     * Test platform timestamps are cast to datetime.
     */
    public function test_platform_timestamps_are_cast_to_datetime(): void
    {
        $platform = Platform::factory()->create();

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $platform->created_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $platform->updated_at);
    }
}

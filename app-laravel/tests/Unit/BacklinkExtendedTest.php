<?php

namespace Tests\Unit;

use App\Models\Backlink;
use App\Models\Platform;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BacklinkExtendedTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test backlink can be created with tier_level.
     */
    public function test_backlink_can_be_created_with_tier_level(): void
    {
        $project = Project::factory()->create();

        $backlink = Backlink::create([
            'project_id' => $project->id,
            'source_url' => 'https://example.com',
            'target_url' => 'https://target.com',
            'tier_level' => 'tier1',
        ]);

        $this->assertDatabaseHas('backlinks', [
            'id' => $backlink->id,
            'tier_level' => 'tier1',
        ]);
    }

    /**
     * Test tier_level defaults to tier1.
     */
    public function test_tier_level_defaults_to_tier1(): void
    {
        $project = Project::factory()->create();

        $backlink = Backlink::create([
            'project_id' => $project->id,
            'source_url' => 'https://example.com',
            'target_url' => 'https://target.com',
        ]);

        $this->assertEquals('tier1', $backlink->tier_level);
    }

    /**
     * Test backlink can have parent backlink (tier2 relationship).
     */
    public function test_backlink_can_have_parent_backlink(): void
    {
        $project = Project::factory()->create();

        $parentBacklink = Backlink::factory()->create([
            'project_id' => $project->id,
            'tier_level' => 'tier1',
        ]);

        $childBacklink = Backlink::create([
            'project_id' => $project->id,
            'source_url' => 'https://tier2.com',
            'target_url' => $parentBacklink->source_url,
            'tier_level' => 'tier2',
            'parent_backlink_id' => $parentBacklink->id,
        ]);

        $this->assertEquals($parentBacklink->id, $childBacklink->parentBacklink->id);
    }

    /**
     * Test parent backlink can have multiple child backlinks.
     */
    public function test_parent_backlink_can_have_multiple_children(): void
    {
        $project = Project::factory()->create();

        $parentBacklink = Backlink::factory()->create([
            'project_id' => $project->id,
            'tier_level' => 'tier1',
        ]);

        $child1 = Backlink::factory()->create([
            'project_id' => $project->id,
            'tier_level' => 'tier2',
            'parent_backlink_id' => $parentBacklink->id,
        ]);

        $child2 = Backlink::factory()->create([
            'project_id' => $project->id,
            'tier_level' => 'tier2',
            'parent_backlink_id' => $parentBacklink->id,
        ]);

        $this->assertCount(2, $parentBacklink->childBacklinks);
        $this->assertTrue($parentBacklink->childBacklinks->contains($child1));
        $this->assertTrue($parentBacklink->childBacklinks->contains($child2));
    }

    /**
     * Test backlink can have spot_type.
     */
    public function test_backlink_can_have_spot_type(): void
    {
        $project = Project::factory()->create();

        $backlink = Backlink::create([
            'project_id' => $project->id,
            'source_url' => 'https://example.com',
            'target_url' => 'https://target.com',
            'spot_type' => 'internal',
        ]);

        $this->assertEquals('internal', $backlink->spot_type);
    }

    /**
     * Test spot_type defaults to external.
     */
    public function test_spot_type_defaults_to_external(): void
    {
        $project = Project::factory()->create();

        $backlink = Backlink::create([
            'project_id' => $project->id,
            'source_url' => 'https://example.com',
            'target_url' => 'https://target.com',
        ]);

        $this->assertEquals('external', $backlink->spot_type);
    }

    /**
     * Test backlink can have published_at and expires_at dates.
     */
    public function test_backlink_can_have_publication_dates(): void
    {
        $project = Project::factory()->create();

        $backlink = Backlink::create([
            'project_id' => $project->id,
            'source_url' => 'https://example.com',
            'target_url' => 'https://target.com',
            'published_at' => '2024-01-01',
            'expires_at' => '2024-12-31',
        ]);

        $this->assertEquals('2024-01-01', $backlink->published_at->format('Y-m-d'));
        $this->assertEquals('2024-12-31', $backlink->expires_at->format('Y-m-d'));
    }

    /**
     * Test backlink can have price and currency.
     */
    public function test_backlink_can_have_price_and_currency(): void
    {
        $project = Project::factory()->create();

        $backlink = Backlink::create([
            'project_id' => $project->id,
            'source_url' => 'https://example.com',
            'target_url' => 'https://target.com',
            'price' => 150.50,
            'currency' => 'EUR',
        ]);

        $this->assertEquals('150.50', $backlink->price);
        $this->assertEquals('EUR', $backlink->currency);
    }

    /**
     * Test price is cast to decimal with 2 decimals.
     */
    public function test_price_is_cast_to_decimal(): void
    {
        $project = Project::factory()->create();

        $backlink = Backlink::create([
            'project_id' => $project->id,
            'source_url' => 'https://example.com',
            'target_url' => 'https://target.com',
            'price' => 99.999,
        ]);

        // Should be rounded to 2 decimals
        $this->assertEquals('100.00', $backlink->price);
    }

    /**
     * Test backlink can have invoice_paid boolean.
     */
    public function test_backlink_can_have_invoice_paid(): void
    {
        $project = Project::factory()->create();

        $backlink = Backlink::create([
            'project_id' => $project->id,
            'source_url' => 'https://example.com',
            'target_url' => 'https://target.com',
            'invoice_paid' => true,
        ]);

        $this->assertTrue($backlink->invoice_paid);
    }

    /**
     * Test invoice_paid defaults to false.
     */
    public function test_invoice_paid_defaults_to_false(): void
    {
        $project = Project::factory()->create();

        $backlink = Backlink::create([
            'project_id' => $project->id,
            'source_url' => 'https://example.com',
            'target_url' => 'https://target.com',
        ]);

        $this->assertFalse($backlink->invoice_paid);
    }

    /**
     * Test backlink can be associated with a platform.
     */
    public function test_backlink_can_be_associated_with_platform(): void
    {
        $project = Project::factory()->create();
        $platform = Platform::factory()->create(['name' => 'Test Platform']);

        $backlink = Backlink::create([
            'project_id' => $project->id,
            'source_url' => 'https://example.com',
            'target_url' => 'https://target.com',
            'platform_id' => $platform->id,
        ]);

        $this->assertEquals($platform->id, $backlink->platform->id);
        $this->assertEquals('Test Platform', $backlink->platform->name);
    }

    /**
     * Test backlink can have contact_info.
     */
    public function test_backlink_can_have_contact_info(): void
    {
        $project = Project::factory()->create();

        $backlink = Backlink::create([
            'project_id' => $project->id,
            'source_url' => 'https://example.com',
            'target_url' => 'https://target.com',
            'contact_info' => 'John Doe - john@example.com - +33 6 12 34 56 78',
        ]);

        $this->assertEquals('John Doe - john@example.com - +33 6 12 34 56 78', $backlink->contact_info);
    }

    /**
     * Test backlink can be associated with user who created it.
     */
    public function test_backlink_can_be_associated_with_creator(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create(['name' => 'John Doe']);

        $backlink = Backlink::create([
            'project_id' => $project->id,
            'source_url' => 'https://example.com',
            'target_url' => 'https://target.com',
            'created_by_user_id' => $user->id,
        ]);

        $this->assertEquals($user->id, $backlink->createdBy->id);
        $this->assertEquals('John Doe', $backlink->createdBy->name);
    }

    /**
     * Test backlink with all extended fields.
     */
    public function test_backlink_with_all_extended_fields(): void
    {
        $project = Project::factory()->create();
        $platform = Platform::factory()->create();
        $user = User::factory()->create();
        $parentBacklink = Backlink::factory()->create(['project_id' => $project->id]);

        $backlink = Backlink::create([
            'project_id' => $project->id,
            'source_url' => 'https://example.com',
            'target_url' => 'https://target.com',
            'tier_level' => 'tier2',
            'parent_backlink_id' => $parentBacklink->id,
            'spot_type' => 'internal',
            'published_at' => '2024-01-15',
            'expires_at' => '2024-12-15',
            'price' => 250.00,
            'currency' => 'USD',
            'invoice_paid' => true,
            'platform_id' => $platform->id,
            'contact_info' => 'Jane Doe - jane@example.com',
            'created_by_user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('backlinks', [
            'id' => $backlink->id,
            'tier_level' => 'tier2',
            'parent_backlink_id' => $parentBacklink->id,
            'spot_type' => 'internal',
            'price' => '250.00',
            'currency' => 'USD',
            'invoice_paid' => true,
            'platform_id' => $platform->id,
            'created_by_user_id' => $user->id,
        ]);

        $this->assertEquals($parentBacklink->id, $backlink->parentBacklink->id);
        $this->assertEquals($platform->id, $backlink->platform->id);
        $this->assertEquals($user->id, $backlink->createdBy->id);
    }
}

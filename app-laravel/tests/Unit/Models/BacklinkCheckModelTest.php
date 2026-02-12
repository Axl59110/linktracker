<?php

namespace Tests\Unit\Models;

use App\Models\Backlink;
use App\Models\BacklinkCheck;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BacklinkCheckModelTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test backlink check creation with factory
     */
    public function test_can_create_backlink_check_with_factory(): void
    {
        $project = Project::factory()->create();
        $backlink = Backlink::factory()->for($project)->create();
        $check = BacklinkCheck::factory()->for($backlink)->create();

        $this->assertDatabaseHas('backlink_checks', [
            'id' => $check->id,
            'backlink_id' => $backlink->id,
        ]);
    }

    /**
     * Test backlink check belongs to backlink relation
     */
    public function test_backlink_check_belongs_to_backlink(): void
    {
        $project = Project::factory()->create();
        $backlink = Backlink::factory()->for($project)->create();
        $check = BacklinkCheck::factory()->for($backlink)->create();

        $this->assertInstanceOf(Backlink::class, $check->backlink);
        $this->assertEquals($backlink->id, $check->backlink->id);
    }

    /**
     * Test backlink has many checks relation
     */
    public function test_backlink_has_many_checks(): void
    {
        $project = Project::factory()->create();
        $backlink = Backlink::factory()->for($project)->create();

        BacklinkCheck::factory()->for($backlink)->count(3)->create();

        $this->assertCount(3, $backlink->checks);
        $backlink->checks->each(function ($check) use ($backlink) {
            $this->assertEquals($backlink->id, $check->backlink_id);
        });
    }

    /**
     * Test backlink has one latest check relation
     */
    public function test_backlink_has_one_latest_check(): void
    {
        $project = Project::factory()->create();
        $backlink = Backlink::factory()->for($project)->create();

        // Create checks at different times
        $oldCheck = BacklinkCheck::factory()->for($backlink)->create([
            'checked_at' => now()->subHours(2),
        ]);

        $latestCheck = BacklinkCheck::factory()->for($backlink)->create([
            'checked_at' => now(),
        ]);

        $this->assertInstanceOf(BacklinkCheck::class, $backlink->latestCheck);
        $this->assertEquals($latestCheck->id, $backlink->latestCheck->id);
    }

    /**
     * Test latest scope orders by checked_at desc
     */
    public function test_latest_scope_orders_by_checked_at(): void
    {
        $project = Project::factory()->create();
        $backlink = Backlink::factory()->for($project)->create();

        $check1 = BacklinkCheck::factory()->for($backlink)->create([
            'checked_at' => now()->subHours(3),
        ]);

        $check2 = BacklinkCheck::factory()->for($backlink)->create([
            'checked_at' => now()->subHours(1),
        ]);

        $check3 = BacklinkCheck::factory()->for($backlink)->create([
            'checked_at' => now(),
        ]);

        $checks = $backlink->checks;

        $this->assertCount(3, $checks);
        $this->assertEquals($check3->id, $checks->first()->id);
        $this->assertEquals($check1->id, $checks->last()->id);
    }

    /**
     * Test isSuccessful method for 2xx status
     */
    public function test_is_successful_method(): void
    {
        $project = Project::factory()->create();
        $backlink = Backlink::factory()->for($project)->create();

        $successCheck = BacklinkCheck::factory()->for($backlink)->create([
            'http_status' => 200,
        ]);

        $redirectCheck = BacklinkCheck::factory()->for($backlink)->create([
            'http_status' => 301,
        ]);

        $notFoundCheck = BacklinkCheck::factory()->for($backlink)->create([
            'http_status' => 404,
        ]);

        $this->assertTrue($successCheck->isSuccessful());
        $this->assertFalse($redirectCheck->isSuccessful());
        $this->assertFalse($notFoundCheck->isSuccessful());
    }

    /**
     * Test wasFound method
     */
    public function test_was_found_method(): void
    {
        $project = Project::factory()->create();
        $backlink = Backlink::factory()->for($project)->create();

        $foundCheck = BacklinkCheck::factory()->for($backlink)->create([
            'is_present' => true,
        ]);

        $notFoundCheck = BacklinkCheck::factory()->for($backlink)->create([
            'is_present' => false,
        ]);

        $this->assertTrue($foundCheck->wasFound());
        $this->assertFalse($notFoundCheck->wasFound());
    }

    /**
     * Test factory successful state
     */
    public function test_factory_successful_state(): void
    {
        $project = Project::factory()->create();
        $backlink = Backlink::factory()->for($project)->create();

        $check = BacklinkCheck::factory()->for($backlink)->successful()->create();

        $this->assertEquals(200, $check->http_status);
        $this->assertTrue($check->is_present);
        $this->assertNotNull($check->response_time);
    }

    /**
     * Test factory notFound state
     */
    public function test_factory_not_found_state(): void
    {
        $project = Project::factory()->create();
        $backlink = Backlink::factory()->for($project)->create();

        $check = BacklinkCheck::factory()->for($backlink)->notFound()->create();

        $this->assertEquals(404, $check->http_status);
        $this->assertFalse($check->is_present);
        $this->assertNull($check->anchor_text);
    }

    /**
     * Test factory failed state
     */
    public function test_factory_failed_state(): void
    {
        $project = Project::factory()->create();
        $backlink = Backlink::factory()->for($project)->create();

        $check = BacklinkCheck::factory()->for($backlink)->failed()->create();

        $this->assertNull($check->http_status);
        $this->assertFalse($check->is_present);
        $this->assertNull($check->response_time);
    }

    /**
     * Test casts work properly
     */
    public function test_casts_work_properly(): void
    {
        $project = Project::factory()->create();
        $backlink = Backlink::factory()->for($project)->create();
        $check = BacklinkCheck::factory()->for($backlink)->create();

        $this->assertIsBool($check->is_present);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $check->checked_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $check->created_at);
    }

    /**
     * Test fillable attributes
     */
    public function test_fillable_attributes(): void
    {
        $project = Project::factory()->create();
        $backlink = Backlink::factory()->for($project)->create();

        $data = [
            'http_status' => 200,
            'is_present' => true,
            'anchor_text' => 'Test anchor',
            'rel_attributes' => 'follow',
            'response_time' => 500,
            'checked_at' => now(),
        ];

        $check = $backlink->checks()->create($data);

        $this->assertEquals(200, $check->http_status);
        $this->assertTrue($check->is_present);
        $this->assertEquals('Test anchor', $check->anchor_text);
        $this->assertEquals('follow', $check->rel_attributes);
        $this->assertEquals(500, $check->response_time);
    }

    /**
     * Test cascade delete when backlink is deleted
     */
    public function test_cascade_delete_when_backlink_deleted(): void
    {
        $project = Project::factory()->create();
        $backlink = Backlink::factory()->for($project)->create();

        $check1 = BacklinkCheck::factory()->for($backlink)->create();
        $check2 = BacklinkCheck::factory()->for($backlink)->create();

        $this->assertDatabaseHas('backlink_checks', ['id' => $check1->id]);
        $this->assertDatabaseHas('backlink_checks', ['id' => $check2->id]);

        $backlink->delete();

        $this->assertDatabaseMissing('backlink_checks', ['id' => $check1->id]);
        $this->assertDatabaseMissing('backlink_checks', ['id' => $check2->id]);
    }
}

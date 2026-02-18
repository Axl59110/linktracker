<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\Backlink;
use App\Models\Project;
use App\Models\User;
use App\Notifications\CriticalAlertNotification;
use App\Services\Alert\AlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Tests STORY-034 : Notifications email pour alertes critiques
 */
class CriticalAlertNotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Project $project;
    private Backlink $backlink;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user    = User::factory()->create(['email_alerts_enabled' => true]);
        $this->project = Project::factory()->for($this->user)->create();
        $this->backlink = Backlink::factory()->for($this->project)->create([
            'tier_level'          => 'tier1',
            'created_by_user_id'  => $this->user->id,
        ]);
    }

    public function test_critical_alert_sends_email_notification(): void
    {
        Notification::fake();

        $alertService = new AlertService();
        $alertService->createBacklinkLostAlert($this->backlink);

        Notification::assertSentTo($this->user, CriticalAlertNotification::class);
    }

    public function test_high_severity_changed_alert_sends_notification(): void
    {
        Notification::fake();

        $alertService = new AlertService();
        $alertService->createBacklinkChangedAlert($this->backlink, [
            'anchor_text' => ['old' => 'Ancien texte', 'new' => 'Nouveau texte'],
        ]);

        Notification::assertSentTo($this->user, CriticalAlertNotification::class);
    }

    public function test_recovered_alert_does_not_send_email(): void
    {
        Notification::fake();

        $alertService = new AlertService();
        $alertService->createBacklinkRecoveredAlert($this->backlink);

        Notification::assertNotSentTo($this->user, CriticalAlertNotification::class);
    }

    public function test_no_email_when_alerts_disabled(): void
    {
        Notification::fake();

        $this->user->update(['email_alerts_enabled' => false]);

        $alertService = new AlertService();
        $alertService->createBacklinkLostAlert($this->backlink);

        Notification::assertNotSentTo($this->user, CriticalAlertNotification::class);
    }

    public function test_no_email_when_no_user_associated(): void
    {
        Notification::fake();

        $backlink = Backlink::factory()->for($this->project)->create([
            'tier_level'         => 'tier1',
            'created_by_user_id' => null,
        ]);

        $alertService = new AlertService();
        $alertService->createBacklinkLostAlert($backlink);

        Notification::assertNothingSent();
    }

    public function test_notification_contains_alert_info(): void
    {
        $alert = Alert::create([
            'backlink_id' => $this->backlink->id,
            'type'        => Alert::TYPE_BACKLINK_LOST,
            'severity'    => Alert::SEVERITY_CRITICAL,
            'title'       => 'Backlink perdu sur example.com',
            'message'     => 'Test message',
        ]);

        $notification = new CriticalAlertNotification($alert);
        $mail = $notification->toMail($this->user);

        $this->assertStringContainsString('CRITICAL', $mail->subject);
        $this->assertStringContainsString('Backlink perdu', $mail->subject);
    }
}

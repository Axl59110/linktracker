<?php

namespace App\Jobs;

use App\Models\Alert;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 15;

    public function __construct(
        private Alert $alert,
        private User $user
    ) {}

    public function handle(): void
    {
        if (!$this->user->webhook_url) {
            return;
        }

        $payload = $this->buildPayload();
        $signature = $this->buildSignature($payload);

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Webhook-Signature' => $signature,
                    'User-Agent' => 'LinkTracker-Webhook/1.0',
                ])
                ->post($this->user->webhook_url, $payload);

            if (!$response->successful()) {
                Log::warning('Webhook delivery failed', [
                    'user_id' => $this->user->id,
                    'alert_id' => $this->alert->id,
                    'status' => $response->status(),
                    'url' => $this->user->webhook_url,
                ]);

                $this->fail(new \Exception("Webhook returned HTTP {$response->status()}"));
                return;
            }

            Log::info('Webhook delivered successfully', [
                'user_id' => $this->user->id,
                'alert_id' => $this->alert->id,
                'status' => $response->status(),
            ]);
        } catch (\Exception $e) {
            Log::error('Webhook delivery error', [
                'user_id' => $this->user->id,
                'alert_id' => $this->alert->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function buildPayload(): array
    {
        $backlink = $this->alert->backlink;

        return [
            'event' => $this->alert->type,
            'timestamp' => $this->alert->created_at->toIso8601String(),
            'alert' => [
                'id' => $this->alert->id,
                'type' => $this->alert->type,
                'severity' => $this->alert->severity,
                'title' => $this->alert->title,
                'message' => $this->alert->message,
            ],
            'backlink' => $backlink ? [
                'id' => $backlink->id,
                'source_url' => $backlink->source_url,
                'target_url' => $backlink->target_url,
                'anchor_text' => $backlink->anchor_text,
                'status' => $backlink->status,
                'tier_level' => $backlink->tier_level,
            ] : null,
        ];
    }

    private function buildSignature(array $payload): string
    {
        $secret = $this->user->webhook_secret ?? '';
        return 'sha256=' . hash_hmac('sha256', json_encode($payload), $secret);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Webhook job failed after all retries', [
            'user_id' => $this->user->id,
            'alert_id' => $this->alert->id,
            'error' => $exception->getMessage(),
        ]);
    }
}

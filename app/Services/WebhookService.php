<?php

namespace App\Services;

use App\Models\Webhook;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WebhookService
{
    /**
     * Dispatch an event to all active webhooks that are subscribed to it.
     *
     * @param  string $event  One of the event names defined in Webhook::EVENTS.
     * @param  array  $payload  The data to send with the event.
     */
    public function dispatch(string $event, array $payload): void
    {
        $webhooks = Webhook::where('is_active', true)
            ->whereJsonContains('events', $event)
            ->get();

        foreach ($webhooks as $webhook) {
            $this->send($webhook, $event, $payload);
        }
    }

    /**
     * Send a webhook payload to a single endpoint and track the result.
     */
    public function send(Webhook $webhook, string $event, array $payload): bool
    {
        $body = json_encode([
            'event'      => $event,
            'timestamp'  => now()->toIso8601String(),
            'payload'    => $payload,
        ]);

        $signature = $this->sign($body, $webhook->secret);

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Content-Type'        => 'application/json',
                    'X-Webhook-Event'     => $event,
                    'X-Webhook-Signature' => $signature,
                    'X-Webhook-Id'        => (string) $webhook->id,
                ])
                ->send('POST', $webhook->url, ['body' => $body]);

            if ($response->successful()) {
                $webhook->update([
                    'last_triggered_at' => now(),
                    'failure_count'     => 0,
                ]);

                return true;
            }

            $this->handleFailure($webhook, "HTTP {$response->status()}");

            return false;
        } catch (\Throwable $e) {
            $this->handleFailure($webhook, $e->getMessage());

            return false;
        }
    }

    /**
     * Register a new webhook endpoint.
     *
     * @param  array $data  Keys: name, url, events (array), secret (optional).
     */
    public function register(array $data): Webhook
    {
        return Webhook::create([
            'name'      => $data['name'],
            'url'       => $data['url'],
            'events'    => $data['events'],
            'secret'    => $data['secret'] ?? Str::random(32),
            'is_active' => $data['is_active'] ?? true,
            'team_id'   => $data['team_id'] ?? null,
        ]);
    }

    /**
     * Update an existing webhook.
     */
    public function update(Webhook $webhook, array $data): Webhook
    {
        $webhook->update(array_filter([
            'name'      => $data['name'] ?? null,
            'url'       => $data['url'] ?? null,
            'events'    => $data['events'] ?? null,
            'is_active' => $data['is_active'] ?? null,
        ], fn ($v) => !is_null($v)));

        return $webhook->refresh();
    }

    /**
     * Regenerate the signing secret for a webhook.
     */
    public function regenerateSecret(Webhook $webhook): string
    {
        $secret = Str::random(32);
        $webhook->update(['secret' => $secret]);

        return $secret;
    }

    /**
     * Verify an incoming webhook signature.
     *
     * @param  string $body       Raw request body.
     * @param  string $signature  Value from X-Webhook-Signature header.
     * @param  string $secret     The webhook's secret.
     */
    public function verifySignature(string $body, string $signature, string $secret): bool
    {
        return hash_equals($this->sign($body, $secret), $signature);
    }

    // -------------------------------------------------------------------------

    /**
     * Generate an HMAC-SHA256 signature for a payload.
     */
    private function sign(string $body, ?string $secret): string
    {
        if (!$secret) {
            return '';
        }

        return 'sha256=' . hash_hmac('sha256', $body, $secret);
    }

    /**
     * Increment failure counter and optionally disable the webhook after too many failures.
     */
    private function handleFailure(Webhook $webhook, string $reason): void
    {
        $failures = $webhook->failure_count + 1;

        $updates = ['failure_count' => $failures];

        // Auto-disable after 10 consecutive failures
        if ($failures >= 10) {
            $updates['is_active'] = false;
            Log::warning("Webhook #{$webhook->id} auto-disabled after {$failures} consecutive failures.", [
                'webhook_url' => $webhook->url,
                'reason'      => $reason,
            ]);
        } else {
            Log::warning("Webhook #{$webhook->id} delivery failed ({$failures}/10).", [
                'webhook_url' => $webhook->url,
                'reason'      => $reason,
            ]);
        }

        $webhook->update($updates);
    }
}

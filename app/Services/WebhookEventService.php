<?php

namespace App\Services;

use App\Models\WebhookEvent;
use Illuminate\Support\Arr;

class WebhookEventService
{
    public function record(string $provider, array $payload, ?string $eventType = null, ?string $idempotencyKey = null): WebhookEvent
    {
        $eventType = $eventType ?? Arr::get($payload, 'type');

        return WebhookEvent::create([
            'provider' => $provider,
            'event_type' => $eventType,
            'idempotency_key' => $idempotencyKey,
            'payload' => $payload,
            'status' => 'received',
            'received_at' => now(),
        ]);
    }

    /**
     * @return array{0: WebhookEvent, 1: bool} [event, isDuplicate]
     */
    public function recordOrGet(string $provider, array $payload, ?string $eventType = null, ?string $idempotencyKey = null): array
    {
        $eventType = $eventType ?? Arr::get($payload, 'type');

        if ($idempotencyKey) {
            $existing = WebhookEvent::query()
                ->where('provider', $provider)
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existing) {
                return [$existing, true];
            }
        }

        $event = WebhookEvent::create([
            'provider' => $provider,
            'event_type' => $eventType,
            'idempotency_key' => $idempotencyKey,
            'payload' => $payload,
            'status' => 'received',
            'received_at' => now(),
        ]);

        return [$event, false];
    }

    public function markProcessed(WebhookEvent $event): void
    {
        $event->update([
            'status' => 'processed',
            'processed_at' => now(),
        ]);
    }

    public function markFailed(WebhookEvent $event, string $error): void
    {
        $event->update([
            'status' => 'failed',
            'last_error' => $error,
        ]);
    }
}

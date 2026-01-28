<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\OutboundMessage;
use App\Services\OutboundMessageStatusService;
use App\Services\WebhookEventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class OutboundMessageWebhookController extends Controller
{
    public function handle(string $provider, Request $request, WebhookEventService $events, OutboundMessageStatusService $statuses): JsonResponse
    {
        $secret = config('services.webhooks.outbound_messages_secret');
        if ($secret) {
            $signature = (string) $request->header('X-Webhook-Secret');
            if (!$signature || !hash_equals($secret, $signature)) {
                return response()->json(['ok' => false, 'error' => 'Unauthorized'], 401);
            }
        }

        $payload = $request->all();
        $eventType = Arr::get($payload, 'event')
            ?? Arr::get($payload, 'type')
            ?? Arr::get($payload, 'event_type')
            ?? Arr::get($payload, 'data.event');
        $idempotencyKey = $request->header('Idempotency-Key')
            ?? Arr::get($payload, 'id')
            ?? Arr::get($payload, 'event_id')
            ?? Arr::get($payload, 'event.id')
            ?? Arr::get($payload, 'data.id');

        [$event, $isDuplicate] = $events->recordOrGet($provider, $payload, $eventType, $idempotencyKey);
        if ($isDuplicate && $event->status === 'processed') {
            return response()->json(['ok' => true, 'duplicate' => true]);
        }

        $message = $this->resolveMessage($payload);
        if (!$message) {
            $events->markFailed($event, 'Outbound message not found');
            return response()->json(['ok' => false, 'error' => 'Message not found'], 202);
        }

        $providerMessageId = Arr::get($payload, 'provider_message_id')
            ?? Arr::get($payload, 'message_id')
            ?? Arr::get($payload, 'data.message_id')
            ?? Arr::get($payload, 'mail.message_id');
        if ($providerMessageId && !$message->provider_message_id) {
            $message->update(['provider_message_id' => (string) $providerMessageId]);
        }

        $status = Str::lower((string) ($eventType ?? Arr::get($payload, 'status', '')));
        $statusHandled = false;

        if (in_array($status, ['bounce', 'bounced', 'undelivered', 'soft_bounce', 'hard_bounce'], true)) {
            $statuses->markBounced($message, Arr::get($payload, 'reason') ?? Arr::get($payload, 'error'));
            $statusHandled = true;
        }

        if (in_array($status, ['failed', 'dropped', 'rejected', 'invalid', 'blocked'], true)) {
            $statuses->markFailed($message, Arr::get($payload, 'reason') ?? Arr::get($payload, 'error'));
            $statusHandled = true;
        }

        if (!$statusHandled) {
            $message->update([
                'last_error' => $message->last_error ?? 'Webhook received without status update.',
            ]);
        }

        $events->markProcessed($event);

        return response()->json(['ok' => true, 'status' => $status ?: 'received']);
    }

    private function resolveMessage(array $payload): ?OutboundMessage
    {
        $messageId = Arr::get($payload, 'outbound_message_id')
            ?? Arr::get($payload, 'data.outbound_message_id')
            ?? Arr::get($payload, 'metadata.outbound_message_id');
        if ($messageId) {
            return OutboundMessage::query()->find($messageId);
        }

        $dedupeKey = Arr::get($payload, 'dedupe_key')
            ?? Arr::get($payload, 'metadata.dedupe_key');
        if ($dedupeKey) {
            $query = OutboundMessage::query()->where('dedupe_key', $dedupeKey);
            $tenantId = Arr::get($payload, 'tenant_id') ?? Arr::get($payload, 'metadata.tenant_id');
            if ($tenantId) {
                $query->where('tenant_id', $tenantId);
            }
            return $query->first();
        }

        $providerMessageId = Arr::get($payload, 'provider_message_id')
            ?? Arr::get($payload, 'message_id')
            ?? Arr::get($payload, 'data.message_id')
            ?? Arr::get($payload, 'mail.message_id');
        if ($providerMessageId) {
            return OutboundMessage::query()
                ->where('provider_message_id', $providerMessageId)
                ->first();
        }

        $toAddress = Arr::get($payload, 'to')
            ?? Arr::get($payload, 'recipient')
            ?? Arr::get($payload, 'data.to');
        $subject = Arr::get($payload, 'subject') ?? Arr::get($payload, 'data.subject');

        if ($toAddress) {
            return OutboundMessage::query()
                ->where('to_address', $toAddress)
                ->when($subject, function ($query) use ($subject) {
                    $query->where('subject', $subject);
                })
                ->orderByDesc('created_at')
                ->first();
        }

        return null;
    }
}

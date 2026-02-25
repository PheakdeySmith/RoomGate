<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessBakongPaymentWebhook;
use App\Models\PaymentGatewaySetting;
use App\Services\WebhookEventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class BakongPaymentWebhookController extends Controller
{
    public function handle(Request $request, WebhookEventService $events): JsonResponse
    {
        $secret = PaymentGatewaySetting::query()
            ->where('gateway_name', 'bakong')
            ->value('webhook_secret');
        $secret = $secret ?: config('services.webhooks.bakong_secret');
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
            ?? Arr::get($payload, 'status');
        $idempotencyKey = $request->header('Idempotency-Key')
            ?? Arr::get($payload, 'idempotency_key')
            ?? Arr::get($payload, 'event_id')
            ?? Arr::get($payload, 'id')
            ?? Arr::get($payload, 'transaction_id');

        [$event, $isDuplicate] = $events->recordOrGet('bakong', $payload, $eventType, $idempotencyKey);
        if ($isDuplicate && $event->status === 'processed') {
            return response()->json(['ok' => true, 'duplicate' => true]);
        }

        ProcessBakongPaymentWebhook::dispatch($event->id);

        return response()->json([
            'ok' => true,
            'queued' => true,
            'event_id' => $event->id,
            'duplicate' => $isDuplicate,
        ], 202);
    }
}

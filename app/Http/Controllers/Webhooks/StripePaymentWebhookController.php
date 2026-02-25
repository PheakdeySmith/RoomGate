<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPayment;
use App\Services\SubscriptionPaymentStateService;
use App\Services\WebhookEventService;
use App\Services\Payments\StripeGatewayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class StripePaymentWebhookController extends Controller
{
    public function handle(Request $request, WebhookEventService $events, StripeGatewayService $stripe, SubscriptionPaymentStateService $states): JsonResponse
    {
        $settings = $stripe->settings();
        $payload = (string) $request->getContent();
        $signature = (string) $request->header('Stripe-Signature');
        $secret = (string) ($settings->webhook_secret ?? '');

        if (!$stripe->verifyWebhook($payload, $signature, $secret)) {
            return response()->json(['ok' => false, 'error' => 'Invalid signature'], 401);
        }

        $decoded = (array) json_decode($payload, true);
        $eventType = (string) ($decoded['type'] ?? 'unknown');
        $idempotencyKey = (string) ($decoded['id'] ?? '');

        [$event, $isDuplicate] = $events->recordOrGet('stripe', $decoded, $eventType, $idempotencyKey ?: null);
        if ($isDuplicate && $event->status === 'processed') {
            return response()->json(['ok' => true, 'duplicate' => true]);
        }

        $object = Arr::get($decoded, 'data.object', []);
        $payment = $this->resolvePayment($object);
        if (!$payment) {
            $events->markFailed($event, 'Subscription payment not found for Stripe webhook.');
            return response()->json(['ok' => false, 'error' => 'Payment not found'], 202);
        }

        if ($eventType === 'checkout.session.completed' && Arr::get($object, 'payment_status') === 'paid') {
            $states->markPaid($payment, ['stripe' => ['event_id' => $idempotencyKey, 'session' => $object]]);
        } elseif (in_array($eventType, ['checkout.session.async_payment_failed', 'payment_intent.payment_failed'], true)) {
            $states->markFailed($payment, ['stripe' => ['event_id' => $idempotencyKey, 'session' => $object]]);
        } elseif ($eventType === 'charge.refunded') {
            $states->markCancelled($payment, ['stripe' => ['event_id' => $idempotencyKey, 'session' => $object]]);
        }

        $events->markProcessed($event);

        return response()->json(['ok' => true]);
    }

    private function resolvePayment(array $object): ?SubscriptionPayment
    {
        $paymentId = Arr::get($object, 'metadata.payment_id') ?? Arr::get($object, 'client_reference_id');
        if ($paymentId) {
            $payment = SubscriptionPayment::query()->find($paymentId);
            if ($payment) {
                return $payment;
            }
        }

        $sessionId = Arr::get($object, 'id');
        if ($sessionId) {
            return SubscriptionPayment::query()
                ->where('provider', 'stripe')
                ->where('provider_ref', $sessionId)
                ->first();
        }

        return null;
    }
}

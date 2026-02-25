<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPayment;
use App\Services\Payments\PayPalGatewayService;
use App\Services\SubscriptionPaymentStateService;
use App\Services\WebhookEventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class PayPalPaymentWebhookController extends Controller
{
    public function handle(Request $request, WebhookEventService $events, PayPalGatewayService $paypal, SubscriptionPaymentStateService $states): JsonResponse
    {
        $settings = $paypal->settings();
        $payload = $request->all();
        $headers = array_change_key_case($request->headers->all(), CASE_LOWER);
        $flatHeaders = [];
        foreach ($headers as $key => $values) {
            $flatHeaders[$key] = is_array($values) ? (string) ($values[0] ?? '') : (string) $values;
        }

        if (!$paypal->verifyWebhook($settings, $flatHeaders, $payload)) {
            return response()->json(['ok' => false, 'error' => 'Invalid signature'], 401);
        }

        $eventType = (string) ($payload['event_type'] ?? 'unknown');
        $idempotencyKey = (string) ($payload['id'] ?? '');

        [$event, $isDuplicate] = $events->recordOrGet('paypal', $payload, $eventType, $idempotencyKey ?: null);
        if ($isDuplicate && $event->status === 'processed') {
            return response()->json(['ok' => true, 'duplicate' => true]);
        }

        $payment = $this->resolvePayment($payload);
        if (!$payment) {
            $events->markFailed($event, 'Subscription payment not found for PayPal webhook.');
            return response()->json(['ok' => false, 'error' => 'Payment not found'], 202);
        }

        if (in_array($eventType, ['CHECKOUT.ORDER.APPROVED', 'PAYMENT.CAPTURE.COMPLETED'], true)) {
            $states->markPaid($payment, ['paypal' => ['event_id' => $idempotencyKey, 'payload' => $payload]]);
        } elseif (in_array($eventType, ['PAYMENT.CAPTURE.DENIED', 'PAYMENT.CAPTURE.DECLINED'], true)) {
            $states->markFailed($payment, ['paypal' => ['event_id' => $idempotencyKey, 'payload' => $payload]]);
        } elseif (in_array($eventType, ['PAYMENT.CAPTURE.REFUNDED', 'PAYMENT.CAPTURE.REVERSED'], true)) {
            $states->markCancelled($payment, ['paypal' => ['event_id' => $idempotencyKey, 'payload' => $payload]]);
        }

        $events->markProcessed($event);

        return response()->json(['ok' => true]);
    }

    private function resolvePayment(array $payload): ?SubscriptionPayment
    {
        $customId = Arr::get($payload, 'resource.custom_id')
            ?? Arr::get($payload, 'resource.purchase_units.0.custom_id');
        if ($customId) {
            $payment = SubscriptionPayment::query()->find($customId);
            if ($payment) {
                return $payment;
            }
        }

        $orderId = Arr::get($payload, 'resource.id')
            ?? Arr::get($payload, 'resource.supplementary_data.related_ids.order_id')
            ?? Arr::get($payload, 'resource.supplementary_data.related_ids.capture_id');
        if ($orderId) {
            return SubscriptionPayment::query()
                ->where('provider', 'paypal')
                ->where('provider_ref', $orderId)
                ->first();
        }

        $invoiceNumber = Arr::get($payload, 'resource.invoice_id')
            ?? Arr::get($payload, 'resource.purchase_units.0.invoice_id');
        if (!$invoiceNumber) {
            return null;
        }

        return SubscriptionPayment::query()
            ->whereHas('invoice', function ($query) use ($invoiceNumber) {
                $query->where('invoice_number', $invoiceNumber);
            })
            ->latest('id')
            ->first();
    }
}

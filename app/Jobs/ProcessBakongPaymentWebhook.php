<?php

namespace App\Jobs;

use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPayment;
use App\Models\WebhookEvent;
use App\Services\SubscriptionPaymentStateService;
use App\Services\WebhookEventService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;

class ProcessBakongPaymentWebhook implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 5;
    public array $backoff = [30, 120, 300];

    public function __construct(public int $webhookEventId)
    {
    }

    public function handle(WebhookEventService $events, SubscriptionPaymentStateService $states): void
    {
        $event = WebhookEvent::query()->find($this->webhookEventId);
        if (!$event || $event->status === 'processed') {
            return;
        }

        $payload = is_array($event->payload) ? $event->payload : [];
        $providerRef = $this->resolveProviderRef($payload);
        $payment = $this->resolvePayment($payload, $providerRef);

        if (!$payment) {
            $events->markFailed($event, 'Subscription payment not found for Bakong webhook.');
            return;
        }

        $normalizedStatus = $this->normalizeStatus(
            (string) (
                Arr::get($payload, 'status')
                ?? Arr::get($payload, 'payment_status')
                ?? Arr::get($payload, 'state')
                ?? Arr::get($payload, 'result')
                ?? Arr::get($payload, 'event_type')
                ?? Arr::get($payload, 'type')
            )
        );

        if (!$normalizedStatus) {
            $events->markFailed($event, 'Unable to determine payment status from webhook payload.');
            return;
        }

        $metadata = [
            'bakong' => [
                'last_webhook_received_at' => now()->toIso8601String(),
                'last_payload' => $payload,
            ],
        ];

        if ($providerRef && !$payment->provider_ref) {
            $payment->update(['provider_ref' => $providerRef]);
        }

        if ($normalizedStatus === 'paid') {
            $states->markPaid($payment, $metadata);
        } elseif ($normalizedStatus === 'failed') {
            $states->markFailed($payment, $metadata);
        } elseif ($normalizedStatus === 'cancelled') {
            $states->markCancelled($payment, $metadata);
        } else {
            $payment->update([
                'status' => 'pending',
                'metadata' => array_replace_recursive((array) ($payment->metadata ?? []), $metadata),
            ]);
        }

        $events->markProcessed($event);
    }

    private function resolveProviderRef(array $payload): ?string
    {
        $ref = Arr::get($payload, 'provider_ref')
            ?? Arr::get($payload, 'transaction_id')
            ?? Arr::get($payload, 'payment_id')
            ?? Arr::get($payload, 'transaction.reference')
            ?? Arr::get($payload, 'data.transaction_id')
            ?? Arr::get($payload, 'data.reference');

        return $ref ? (string) $ref : null;
    }

    private function resolvePayment(array $payload, ?string $providerRef): ?SubscriptionPayment
    {
        $paymentId = Arr::get($payload, 'subscription_payment_id')
            ?? Arr::get($payload, 'payment.subscription_payment_id')
            ?? Arr::get($payload, 'data.subscription_payment_id');
        if ($paymentId) {
            $payment = SubscriptionPayment::query()->find($paymentId);
            if ($payment) {
                return $payment;
            }
        }

        if ($providerRef) {
            $payment = SubscriptionPayment::query()
                ->where('provider', 'bakong')
                ->where('provider_ref', $providerRef)
                ->first();
            if ($payment) {
                return $payment;
            }
        }

        $invoiceNumber = Arr::get($payload, 'invoice_number')
            ?? Arr::get($payload, 'data.invoice_number')
            ?? Arr::get($payload, 'payment.invoice_number');
        if (!$invoiceNumber) {
            return null;
        }

        $invoice = SubscriptionInvoice::query()->where('invoice_number', $invoiceNumber)->first();
        if (!$invoice) {
            return null;
        }

        return SubscriptionPayment::query()
            ->where('subscription_invoice_id', $invoice->id)
            ->orderByDesc('id')
            ->first();
    }

    private function normalizeStatus(string $value): ?string
    {
        $status = strtolower(trim($value));

        return match ($status) {
            'success', 'succeeded', 'paid', 'completed', 'complete' => 'paid',
            'failed', 'error', 'declined' => 'failed',
            'refunded', 'refund', 'cancelled', 'canceled' => 'cancelled',
            'pending', 'processing' => 'pending',
            default => null,
        };
    }

    
}

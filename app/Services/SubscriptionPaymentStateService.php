<?php

namespace App\Services;

use App\Events\SubscriptionPaymentFailed;
use App\Events\SubscriptionPaymentReceived;
use App\Events\SubscriptionPaymentRefunded;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPayment;
use Illuminate\Support\Facades\DB;

class SubscriptionPaymentStateService
{
    public function markPaid(SubscriptionPayment $payment, array $metadata = []): SubscriptionPayment
    {
        return $this->transition($payment, 'paid', $metadata);
    }

    public function markFailed(SubscriptionPayment $payment, array $metadata = []): SubscriptionPayment
    {
        return $this->transition($payment, 'failed', $metadata);
    }

    public function markCancelled(SubscriptionPayment $payment, array $metadata = []): SubscriptionPayment
    {
        return $this->transition($payment, 'cancelled', $metadata);
    }

    private function transition(SubscriptionPayment $payment, string $status, array $metadata = []): SubscriptionPayment
    {
        $before = $payment->status;
        if ($before === $status && empty($metadata)) {
            return $payment;
        }

        DB::transaction(function () use ($payment, $status, $metadata): void {
            $current = is_array($payment->metadata) ? $payment->metadata : [];
            $nextMetadata = array_replace_recursive($current, $metadata);

            $updates = [
                'status' => $status,
                'metadata' => $nextMetadata,
            ];

            if ($status === 'paid' && !$payment->paid_at) {
                $updates['paid_at'] = now();
            }

            $payment->update($updates);

            if ($payment->subscription_invoice_id && $status === 'paid') {
                SubscriptionInvoice::query()
                    ->where('id', $payment->subscription_invoice_id)
                    ->update([
                        'status' => 'paid',
                        'paid_at' => $payment->paid_at ?? now(),
                    ]);
            }
        });

        $payment->refresh();

        if ($before !== $payment->status) {
            if ($payment->status === 'paid') {
                event(new SubscriptionPaymentReceived($payment));
            } elseif ($payment->status === 'failed') {
                event(new SubscriptionPaymentFailed($payment));
            } elseif ($payment->status === 'cancelled') {
                event(new SubscriptionPaymentRefunded($payment));
            }
        }

        return $payment;
    }
}

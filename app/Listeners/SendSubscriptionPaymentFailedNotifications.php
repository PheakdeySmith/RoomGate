<?php

namespace App\Listeners;

use App\Events\SubscriptionPaymentFailed;
use App\Models\User;
use App\Services\NotificationService;

class SendSubscriptionPaymentFailedNotifications
{
    public function __construct(private readonly NotificationService $notifications)
    {
    }

    public function handle(SubscriptionPaymentFailed $event): void
    {
        $payment = $event->payment->loadMissing(['tenant.users', 'invoice.subscription.plan']);
        $tenant = $payment->tenant;

        if (! $tenant) {
            return;
        }

        $recipients = $this->resolveAdmins($tenant->users);
        $invoiceNumber = $payment->invoice?->invoice_number ?? '—';

        foreach ($recipients as $admin) {
            if (! $admin->email) {
                continue;
            }

            $this->notifications->queue(
                'subscription_payment_failed',
                $tenant,
                $admin,
                [
                    'recipient_name' => $admin->name,
                    'invoice_number' => $invoiceNumber,
                ],
                [
                    'dedupe_key' => 'subscription-payment-failed-'.$payment->id.'-user-'.$admin->id,
                    'metadata' => [
                        'subscription_payment_id' => $payment->id,
                        'subscription_invoice_id' => $payment->subscription_invoice_id,
                    ],
                ]
            );
        }
    }

    private function resolveAdmins($users)
    {
        return $users->filter(function (User $user) {
            return in_array($user->pivot?->role, ['owner', 'admin'], true);
        });
    }
}

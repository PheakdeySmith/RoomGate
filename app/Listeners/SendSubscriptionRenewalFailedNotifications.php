<?php

namespace App\Listeners;

use App\Events\SubscriptionRenewalFailed;
use App\Models\User;
use App\Services\NotificationService;

class SendSubscriptionRenewalFailedNotifications
{
    public function __construct(private readonly NotificationService $notifications)
    {
    }

    public function handle(SubscriptionRenewalFailed $event): void
    {
        $subscription = $event->subscription->loadMissing(['tenant.users', 'plan']);
        $tenant = $subscription->tenant;

        if (! $tenant) {
            return;
        }

        $recipients = $this->resolveOwners($tenant->users);
        foreach ($recipients as $owner) {
            if (! $owner->email) {
                continue;
            }

            $this->notifications->queue(
                'subscription_renewal_failed',
                $tenant,
                $owner,
                [
                    'recipient_name' => $owner->name,
                    'tenant_name' => $tenant->name,
                    'grace_end' => optional($subscription->grace_period_ends_at)->format('Y-m-d'),
                ],
                [
                    'dedupe_key' => 'subscription-renewal-failed-'.$subscription->id.'-user-'.$owner->id,
                    'metadata' => [
                        'subscription_id' => $subscription->id,
                    ],
                ]
            );
        }
    }

    private function resolveOwners($users)
    {
        $owners = $users->filter(function (User $user) {
            return $user->pivot?->role === 'owner';
        });

        if ($owners->isEmpty()) {
            return $users->filter(function (User $user) {
                return in_array($user->pivot?->role, ['admin', 'owner'], true);
            });
        }

        return $owners;
    }
}

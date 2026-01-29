<?php

namespace App\Listeners;

use App\Events\SubscriptionCancelled;
use App\Models\User;
use App\Services\NotificationService;

class SendSubscriptionCancelledNotifications
{
    public function __construct(private readonly NotificationService $notifications)
    {
    }

    public function handle(SubscriptionCancelled $event): void
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
                'subscription_cancelled',
                $tenant,
                $owner,
                [
                    'recipient_name' => $owner->name,
                    'tenant_name' => $tenant->name,
                ],
                [
                    'dedupe_key' => 'subscription-cancelled-'.$subscription->id.'-user-'.$owner->id,
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

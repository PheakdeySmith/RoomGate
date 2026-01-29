<?php

namespace App\Listeners;

use App\Events\SubscriptionCreated;
use App\Models\User;
use App\Services\NotificationService;

class SendSubscriptionCreatedNotifications
{
    public function __construct(private readonly NotificationService $notifications)
    {
    }

    public function handle(SubscriptionCreated $event): void
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
                'subscription_created',
                $tenant,
                $owner,
                [
                    'recipient_name' => $owner->name,
                    'tenant_name' => $tenant->name,
                    'plan_name' => $subscription->plan?->name ?? 'Subscription',
                    'period_end' => optional($subscription->current_period_end)->format('Y-m-d'),
                ],
                [
                    'dedupe_key' => 'subscription-created-'.$subscription->id.'-user-'.$owner->id,
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

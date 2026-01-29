<?php

namespace App\Listeners;

use App\Events\TenantMemberAccepted;
use App\Models\User;
use App\Services\NotificationService;

class SendTenantMemberAcceptedNotifications
{
    public function __construct(private readonly NotificationService $notifications)
    {
    }

    public function handle(TenantMemberAccepted $event): void
    {
        $tenant = $event->tenant->loadMissing('users');
        $member = $event->member;

        $admins = $tenant->users->filter(function (User $user) {
            return in_array($user->pivot?->role, ['owner', 'admin'], true);
        });

        foreach ($admins as $admin) {
            if (! $admin->email) {
                continue;
            }

            $this->notifications->queue(
                'tenant_invitation_accepted',
                $tenant,
                $admin,
                [
                    'owner_name' => $admin->name,
                    'recipient_name' => $member->name,
                    'tenant_name' => $tenant->name,
                    'role' => ucfirst($event->role),
                ],
                [
                    'dedupe_key' => 'tenant-invite-accepted-'.$tenant->id.'-user-'.$member->id.'-admin-'.$admin->id,
                    'metadata' => [
                        'tenant_id' => $tenant->id,
                        'user_id' => $member->id,
                    ],
                ]
            );
        }
    }
}

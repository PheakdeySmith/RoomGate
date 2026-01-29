<?php

namespace App\Listeners;

use App\Events\TenantMemberDisabled;
use App\Models\User;
use App\Services\NotificationService;

class SendTenantMemberDisabledNotifications
{
    public function __construct(private readonly NotificationService $notifications)
    {
    }

    public function handle(TenantMemberDisabled $event): void
    {
        $tenant = $event->tenant->loadMissing('users');
        $member = $event->member;

        if ($member->email) {
            $this->notifications->queue(
                'tenant_user_disabled',
                $tenant,
                $member,
                [
                    'recipient_name' => $member->name,
                    'tenant_name' => $tenant->name,
                ],
                [
                    'dedupe_key' => 'tenant-user-disabled-'.$tenant->id.'-user-'.$member->id,
                    'metadata' => [
                        'tenant_id' => $tenant->id,
                        'user_id' => $member->id,
                    ],
                ]
            );
        }

        $admins = $tenant->users->filter(function (User $user) {
            return in_array($user->pivot?->role, ['owner', 'admin'], true);
        });

        foreach ($admins as $admin) {
            if (! $admin->email) {
                continue;
            }

            $this->notifications->queue(
                'tenant_user_disabled_admin',
                $tenant,
                $admin,
                [
                    'recipient_name' => $admin->name,
                    'user_name' => $member->name,
                    'tenant_name' => $tenant->name,
                ],
                [
                    'dedupe_key' => 'tenant-user-disabled-'.$tenant->id.'-user-'.$member->id.'-admin-'.$admin->id,
                    'metadata' => [
                        'tenant_id' => $tenant->id,
                        'user_id' => $member->id,
                    ],
                ]
            );
        }
    }
}

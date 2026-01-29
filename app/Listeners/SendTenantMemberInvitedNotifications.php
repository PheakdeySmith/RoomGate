<?php

namespace App\Listeners;

use App\Events\TenantMemberInvited;
use App\Services\NotificationService;

class SendTenantMemberInvitedNotifications
{
    public function __construct(private readonly NotificationService $notifications)
    {
    }

    public function handle(TenantMemberInvited $event): void
    {
        $tenant = $event->tenant;
        $member = $event->member;

        if (! $member->email) {
            return;
        }

        $this->notifications->queue(
            'tenant_invitation_sent',
            $tenant,
            $member,
            [
                'recipient_name' => $member->name,
                'tenant_name' => $tenant->name,
                'role' => ucfirst($event->role),
            ],
            [
                'dedupe_key' => 'tenant-invite-'.$tenant->id.'-user-'.$member->id,
                'metadata' => [
                    'tenant_id' => $tenant->id,
                    'user_id' => $member->id,
                ],
            ]
        );
    }
}

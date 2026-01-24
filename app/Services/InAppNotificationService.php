<?php

namespace App\Services;

use App\Models\InAppNotification;
use App\Models\Tenant;
use App\Models\User;

class InAppNotificationService
{
    public function create(
        User $user,
        string $title,
        ?string $body = null,
        array $options = []
    ): InAppNotification {
        return InAppNotification::create([
            'tenant_id' => $options['tenant_id'] ?? null,
            'user_id' => $user->id,
            'type' => $options['type'] ?? 'info',
            'title' => $title,
            'body' => $body,
            'icon' => $options['icon'] ?? 'tabler-bell',
            'link_url' => $options['link_url'] ?? null,
        ]);
    }
}

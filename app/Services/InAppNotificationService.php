<?php

namespace App\Services;

use App\Models\InAppNotification;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\QueryException;

class InAppNotificationService
{
    public function create(
        User $user,
        string $title,
        ?string $body = null,
        array $options = []
    ): InAppNotification {
        $attributes = [
            'tenant_id' => $options['tenant_id'] ?? null,
            'user_id' => $user->id,
            'dedupe_key' => $options['dedupe_key'] ?? null,
        ];

        $values = [
            'type' => $options['type'] ?? 'info',
            'title' => $title,
            'body' => $body,
            'icon' => $options['icon'] ?? 'tabler-bell',
            'link_url' => $options['link_url'] ?? null,
        ];

        if (! empty($attributes['dedupe_key'])) {
            try {
                return InAppNotification::firstOrCreate($attributes, $values);
            } catch (QueryException $exception) {
                if ($this->isDedupeConflict($exception)) {
                    return InAppNotification::where($attributes)->firstOrFail();
                }
                throw $exception;
            }
        }

        return InAppNotification::create(array_merge($attributes, $values));
    }

    private function isDedupeConflict(QueryException $exception): bool
    {
        $sqlState = $exception->errorInfo[0] ?? null;
        $driverCode = $exception->errorInfo[1] ?? null;

        return $sqlState === '23000' && in_array($driverCode, [1062, 23505], true);
    }
}

<?php

namespace App\Policies;

use App\Models\User;

class TenantOwnedPolicy
{
    public function viewAny(User $user, int $tenantId): bool
    {
        return $this->isTenantMember($user, $tenantId);
    }

    public function view(User $user, object $model): bool
    {
        return $this->isTenantMember($user, (int) ($model->tenant_id ?? 0));
    }

    public function create(User $user, int $tenantId): bool
    {
        return $this->isTenantMember($user, $tenantId);
    }

    public function update(User $user, object $model): bool
    {
        return $this->isTenantMember($user, (int) ($model->tenant_id ?? 0));
    }

    public function delete(User $user, object $model): bool
    {
        return $this->isTenantMember($user, (int) ($model->tenant_id ?? 0));
    }

    private function isTenantMember(User $user, int $tenantId): bool
    {
        if ($tenantId <= 0) {
            return false;
        }

        return $user->tenants()
            ->where('tenant_id', $tenantId)
            ->wherePivot('status', 'active')
            ->exists();
    }
}

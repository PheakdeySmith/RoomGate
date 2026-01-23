<?php

namespace App\Services;

use App\Models\PlanLimit;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Support\Facades\Cache;

class PlanGate
{
    public function getActiveSubscription(Tenant $tenant): ?Subscription
    {
        return $tenant->subscriptions()
            ->whereIn('status', ['active', 'trialing'])
            ->where('current_period_end', '>=', now())
            ->orderByDesc('current_period_end')
            ->first();
    }

    public function getPlanLimits(?Subscription $subscription): array
    {
        if (! $subscription) {
            return [];
        }

        $planId = $subscription->plan_id;

        return Cache::remember("plan_limits:{$planId}", now()->addMinutes(15), function () use ($planId) {
            return PlanLimit::query()
                ->where('plan_id', $planId)
                ->pluck('limit_value', 'limit_key')
                ->toArray();
        });
    }

    public function tenantHasFeature(Tenant $tenant, string $featureKey): bool
    {
        $subscription = $this->getActiveSubscription($tenant);
        $limits = $this->getPlanLimits($subscription);

        if (! array_key_exists($featureKey, $limits)) {
            return false;
        }

        return $limits[$featureKey] !== '0' && $limits[$featureKey] !== 'false';
    }

    public function tenantLimit(Tenant $tenant, string $limitKey): ?string
    {
        $subscription = $this->getActiveSubscription($tenant);
        $limits = $this->getPlanLimits($subscription);

        return $limits[$limitKey] ?? null;
    }
}

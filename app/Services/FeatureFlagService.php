<?php

namespace App\Services;

use App\Models\FeatureFlag;
use Illuminate\Support\Facades\Cache;

class FeatureFlagService
{
    public function isEnabled(string $flagKey): bool
    {
        $flags = $this->all();

        return (bool) ($flags[$flagKey] ?? false);
    }

    public function all(): array
    {
        return Cache::remember('feature_flags:all', 300, function () {
            return FeatureFlag::query()
                ->pluck('is_enabled', 'flag_key')
                ->toArray();
        });
    }

    public function clearCache(): void
    {
        Cache::forget('feature_flags:all');
    }
}

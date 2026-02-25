<?php

namespace Modules\Admin\App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\FeatureFlag;
use App\Support\EnforcesOptionalPermission;
use App\Services\AuditLogger;
use App\Services\FeatureFlagService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminFeatureFlagController extends Controller
{
    use EnforcesOptionalPermission;

    public function index(Request $request)
    {
        $this->enforceOptionalPermission($request, 'feature_flags.manage');

        $flags = FeatureFlag::query()->orderBy('flag_key')->get();

        return view('admin::dashboard.feature-flags', compact('flags'));
    }

    public function store(Request $request, FeatureFlagService $flags, AuditLogger $auditLogger): RedirectResponse
    {
        $this->enforceOptionalPermission($request, 'feature_flags.manage');

        $validated = $request->validate([
            'flag_key' => ['required', 'string', 'max:120', 'alpha_dash', 'unique:feature_flags,flag_key'],
            'name' => ['required', 'string', 'max:191'],
            'description' => ['nullable', 'string', 'max:500'],
            'owner' => ['nullable', 'string', 'max:191'],
            'sunset_date' => ['nullable', 'date'],
            'is_enabled' => ['nullable', 'boolean'],
        ]);

        $flag = FeatureFlag::create([
            'flag_key' => $validated['flag_key'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'owner' => $validated['owner'] ?? null,
            'sunset_date' => $validated['sunset_date'] ?? null,
            'is_enabled' => $request->boolean('is_enabled'),
        ]);

        $flags->clearCache();
        $auditLogger->log('created', FeatureFlag::class, (string) $flag->id, null, $flag->toArray(), $request);

        return back()->with('status', 'Feature flag created.');
    }

    public function update(Request $request, FeatureFlag $featureFlag, FeatureFlagService $flags, AuditLogger $auditLogger): RedirectResponse
    {
        $this->enforceOptionalPermission($request, 'feature_flags.manage');

        $validated = $request->validate([
            'flag_key' => ['required', 'string', 'max:120', 'alpha_dash', Rule::unique('feature_flags', 'flag_key')->ignore($featureFlag->id)],
            'name' => ['required', 'string', 'max:191'],
            'description' => ['nullable', 'string', 'max:500'],
            'owner' => ['nullable', 'string', 'max:191'],
            'sunset_date' => ['nullable', 'date'],
            'is_enabled' => ['nullable', 'boolean'],
        ]);

        $before = $featureFlag->toArray();
        $featureFlag->update([
            'flag_key' => $validated['flag_key'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'owner' => $validated['owner'] ?? null,
            'sunset_date' => $validated['sunset_date'] ?? null,
            'is_enabled' => $request->boolean('is_enabled'),
        ]);

        $flags->clearCache();
        $auditLogger->log('updated', FeatureFlag::class, (string) $featureFlag->id, $before, $featureFlag->toArray(), $request);

        return back()->with('status', 'Feature flag updated.');
    }

    public function toggle(Request $request, FeatureFlag $featureFlag, FeatureFlagService $flags, AuditLogger $auditLogger): RedirectResponse
    {
        $this->enforceOptionalPermission($request, 'feature_flags.manage');

        $before = $featureFlag->toArray();
        $featureFlag->update(['is_enabled' => !$featureFlag->is_enabled]);

        $flags->clearCache();
        $auditLogger->log('updated', FeatureFlag::class, (string) $featureFlag->id, $before, $featureFlag->toArray(), $request);

        return back()->with('status', 'Feature flag toggled.');
    }
}

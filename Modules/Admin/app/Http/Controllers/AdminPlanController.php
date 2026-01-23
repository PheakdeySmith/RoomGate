<?php

namespace Modules\Admin\App\Http\Controllers;

use App\Models\Plan;
use App\Models\PlanLimit;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AdminPlanController extends Controller
{
    public function index()
    {
        $plans = Plan::query()
            ->with('limits')
            ->orderBy('name')
            ->get();

        return view('admin::dashboard.plans', compact('plans'));
    }

    public function store(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:50', 'unique:plans,code'],
            'price_cents' => ['required', 'integer', 'min:0'],
            'currency_code' => ['required', 'string', 'size:3'],
            'interval' => ['required', 'string', 'in:monthly,yearly'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = (bool) ($validated['is_active'] ?? false);

        $plan = DB::transaction(function () use ($validated) {
            return Plan::create($validated);
        });

        $auditLogger->log('created', Plan::class, (string) $plan->id, null, $plan->toArray());

        return back()->with('status', 'Plan created.');
    }

    public function update(Request $request, Plan $plan, AuditLogger $auditLogger): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:50', 'unique:plans,code,' . $plan->id],
            'price_cents' => ['required', 'integer', 'min:0'],
            'currency_code' => ['required', 'string', 'size:3'],
            'interval' => ['required', 'string', 'in:monthly,yearly'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = (bool) ($validated['is_active'] ?? false);

        $before = $plan->toArray();

        DB::transaction(function () use ($plan, $validated) {
            $plan->update($validated);
        });

        Cache::forget("plan_limits:{$plan->id}");

        $auditLogger->log('updated', Plan::class, (string) $plan->id, $before, $plan->toArray());

        return back()->with('status', 'Plan updated.');
    }

    public function destroy(Plan $plan, AuditLogger $auditLogger): RedirectResponse
    {
        $before = $plan->toArray();
        $plan->delete();

        Cache::forget("plan_limits:{$plan->id}");

        $auditLogger->log('deleted', Plan::class, (string) $plan->id, $before, null);

        return back()->with('status', 'Plan deleted.');
    }

    public function storeLimit(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $validated = $request->validate([
            'plan_id' => ['required', 'exists:plans,id'],
            'limit_key' => ['required', 'string', 'max:64'],
            'limit_value' => ['required', 'string', 'max:64'],
        ]);

        $limit = DB::transaction(function () use ($validated) {
            return PlanLimit::create($validated);
        });

        Cache::forget("plan_limits:{$validated['plan_id']}");

        $auditLogger->log('created', PlanLimit::class, (string) $limit->id, null, $limit->toArray());

        return back()->with('status', 'Plan limit added.');
    }

    public function updateLimit(Request $request, PlanLimit $planLimit, AuditLogger $auditLogger): RedirectResponse
    {
        $validated = $request->validate([
            'limit_key' => ['required', 'string', 'max:64'],
            'limit_value' => ['required', 'string', 'max:64'],
        ]);

        $before = $planLimit->toArray();
        $planLimit->update($validated);

        Cache::forget("plan_limits:{$planLimit->plan_id}");

        $auditLogger->log('updated', PlanLimit::class, (string) $planLimit->id, $before, $planLimit->toArray());

        return back()->with('status', 'Plan limit updated.');
    }

    public function destroyLimit(PlanLimit $planLimit, AuditLogger $auditLogger): RedirectResponse
    {
        $before = $planLimit->toArray();
        $planId = $planLimit->plan_id;
        $planLimit->delete();

        Cache::forget("plan_limits:{$planId}");

        $auditLogger->log('deleted', PlanLimit::class, (string) $planLimit->id, $before, null);

        return back()->with('status', 'Plan limit deleted.');
    }
}

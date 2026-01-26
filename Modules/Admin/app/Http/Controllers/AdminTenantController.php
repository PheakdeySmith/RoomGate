<?php

namespace Modules\Admin\App\Http\Controllers;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\PlanGate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminTenantController extends Controller
{
    public function index()
    {
        $plans = Plan::query()->orderBy('name')->get();

        return view('admin::dashboard.app-tenant-list', [
            'plans' => $plans,
        ]);
    }

    public function show(Tenant $tenant)
    {
        $tenant->load([
            'users' => function ($query) {
                $query->orderBy('name');
            },
            'subscriptions.plan',
        ]);
        $plans = Plan::query()->orderBy('name')->get();

        return view('admin::dashboard.tenant-view', [
            'tenant' => $tenant,
            'plans' => $plans,
        ]);
    }

    public function data(): JsonResponse
    {
        if (!Schema::hasTable('tenants')) {
            return response()->json(['data' => []]);
        }

        $tenants = Tenant::query()
            ->with([
                'users' => function ($query) {
                    $query->wherePivot('role', 'owner');
                },
                'subscriptions.plan',
            ])
            ->orderBy('id')
            ->get()
            ->map(function (Tenant $tenant) {
                $statusMap = [
                    'active' => 2,
                    'suspended' => 3,
                    'closed' => 3,
                ];
                $owner = $tenant->users->first();
                $subscription = $tenant->subscriptions->sortByDesc('current_period_end')->first();
                $planName = $subscription?->plan?->name ?? '—';
                $planId = $subscription?->plan_id;

                return [
                    'id' => $tenant->id,
                    'full_name' => $tenant->name,
                    'email' => $owner?->email ?? ($tenant->slug ? $tenant->slug . '@roomgate.test' : '-'),
                    'avatar' => null,
                    'role' => 'Owner',
                    'current_plan' => $planName,
                    'current_plan_id' => $planId,
                    'billing' => $subscription?->provider ? ucfirst($subscription->provider) : 'Manual',
                    'status' => $statusMap[$tenant->status] ?? 3,
                    'status_raw' => $tenant->status,
                    'action' => '',
                ];
            })
            ->values();

        return response()->json(['data' => $tenants]);
    }

    public function store(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'owner_email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'owner_password' => ['required', 'string', 'min:8'],
            'plan_id' => ['required', 'exists:plans,id'],
        ]);

        return DB::transaction(function () use ($validated, $request, $auditLogger) {
            $baseSlug = Str::slug($validated['name']);
            $slug = $baseSlug ?: Str::random(8);
            $suffix = 1;
            while (Tenant::query()->where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $suffix;
                $suffix++;
            }

            $plan = Plan::query()->findOrFail($validated['plan_id']);
            $now = now();
            $periodEnd = $plan->interval === 'yearly' ? $now->copy()->addYear() : $now->copy()->addMonth();

            $tenant = Tenant::create([
                'name' => $validated['name'],
                'slug' => $slug,
                'status' => 'active',
                'default_currency' => $plan->currency_code,
                'timezone' => 'UTC',
            ]);

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['owner_email'],
                'password' => $validated['owner_password'],
                'status' => 'active',
                'platform_role' => 'tenant',
            ]);

            if (method_exists($user, 'assignRole')) {
                $user->assignRole('owner');
            }

            DB::table('tenant_users')->insert([
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'role' => 'owner',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Subscription::create([
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'status' => 'active',
                'auto_renew' => true,
                'current_period_start' => $now,
                'current_period_end' => $periodEnd,
                'provider' => 'manual',
            ]);

            $auditLogger->log('created', Tenant::class, (string) $tenant->id, null, $tenant->toArray(), $request);

            return back()->with('status', 'Tenant created.');
        });
    }

    public function update(Request $request, Tenant $tenant, AuditLogger $auditLogger): RedirectResponse
    {
        $owner = $tenant->users()->wherePivot('role', 'owner')->first();
        $ownerId = $owner?->id;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:active,suspended,closed'],
            'plan_id' => ['required', 'exists:plans,id'],
            'owner_email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($ownerId)],
            'owner_password' => ['nullable', 'string', 'min:8'],
        ]);

        return DB::transaction(function () use ($validated, $tenant, $request, $auditLogger) {
            $before = $tenant->toArray();

            $tenant->fill([
                'name' => $validated['name'],
                'status' => $validated['status'],
            ]);
            $tenant->save();

            if ($owner) {
                if (!empty($validated['owner_email'])) {
                    $owner->email = $validated['owner_email'];
                }
                if (!empty($validated['owner_password'])) {
                    $owner->password = $validated['owner_password'];
                }
                $owner->save();
            }

            $plan = Plan::query()->findOrFail($validated['plan_id']);
            $now = now();
            $periodEnd = $plan->interval === 'yearly' ? $now->copy()->addYear() : $now->copy()->addMonth();
            $subscription = $tenant->subscriptions()->orderByDesc('current_period_end')->first();

            if ($subscription) {
                $subscription->update([
                    'plan_id' => $plan->id,
                    'status' => 'active',
                    'current_period_start' => $now,
                    'current_period_end' => $periodEnd,
                ]);
            } else {
                Subscription::create([
                    'tenant_id' => $tenant->id,
                    'plan_id' => $plan->id,
                    'status' => 'active',
                    'auto_renew' => true,
                    'current_period_start' => $now,
                    'current_period_end' => $periodEnd,
                    'provider' => 'manual',
                ]);
            }

            $auditLogger->log('updated', Tenant::class, (string) $tenant->id, $before, $tenant->toArray(), $request);

            return back()->with('status', 'Tenant updated.');
        });
    }

    public function destroy(Request $request, Tenant $tenant, AuditLogger $auditLogger): RedirectResponse
    {
        return DB::transaction(function () use ($tenant, $request, $auditLogger) {
            $before = $tenant->toArray();

            $tenant->users()->detach();
            $tenant->subscriptions()->update(['deleted_at' => now()]);
            $tenant->delete();

            $auditLogger->log('deleted', Tenant::class, (string) $tenant->id, $before, null, $request);

            return back()->with('status', 'Tenant deleted.');
        });
    }

    public function storeMember(Request $request, Tenant $tenant, AuditLogger $auditLogger, PlanGate $planGate): RedirectResponse
    {
        $currentCount = $tenant->users()->count();
        if (! $planGate->canCreate($tenant, 'tenant_users_max', $currentCount)) {
            return back()->withErrors(['plan' => 'This tenant has reached the user limit for their plan.']);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'in:owner,admin,staff,tenant'],
            'status' => ['required', 'in:active,invited,disabled'],
        ]);

        return DB::transaction(function () use ($validated, $tenant, $request, $auditLogger) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'status' => 'active',
                'platform_role' => 'tenant',
            ]);

            $tenant->users()->attach($user->id, [
                'role' => $validated['role'],
                'status' => $validated['status'],
            ]);

            $auditLogger->log('created', 'tenant_users', (string) $tenant->id . ':' . $user->id, null, [
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'role' => $validated['role'],
                'status' => $validated['status'],
            ], $request);

            return back()->with('status', 'Tenant member added.');
        });
    }

    public function updateMember(Request $request, Tenant $tenant, User $user, AuditLogger $auditLogger): RedirectResponse
    {
        $ownerId = $user->id;
        $pivot = DB::table('tenant_users')
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->first();

        if (!$pivot) {
            return back()->withErrors(['member' => 'Member not found.']);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($ownerId)],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['required', 'in:owner,admin,staff,tenant'],
            'status' => ['required', 'in:active,invited,disabled'],
        ]);

        return DB::transaction(function () use ($validated, $tenant, $user, $request, $auditLogger, $pivot) {
            $before = [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $pivot->role,
                'status' => $pivot->status,
            ];

            if ($pivot->role === 'owner' && $validated['role'] !== 'owner') {
                $ownerCount = DB::table('tenant_users')
                    ->where('tenant_id', $tenant->id)
                    ->where('role', 'owner')
                    ->count();
                if ($ownerCount <= 1) {
                    return back()->withErrors(['role' => 'At least one owner is required.']);
                }
            }

            $user->name = $validated['name'];
            $user->email = $validated['email'];
            if (!empty($validated['password'])) {
                $user->password = $validated['password'];
            }
            $user->save();

            DB::table('tenant_users')
                ->where('tenant_id', $tenant->id)
                ->where('user_id', $user->id)
                ->update([
                    'role' => $validated['role'],
                    'status' => $validated['status'],
                    'updated_at' => now(),
                ]);

            $auditLogger->log('updated', 'tenant_users', (string) $tenant->id . ':' . $user->id, $before, [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $validated['role'],
                'status' => $validated['status'],
            ], $request);

            return back()->with('status', 'Tenant member updated.');
        });
    }

    public function destroyMember(Request $request, Tenant $tenant, User $user, AuditLogger $auditLogger): RedirectResponse
    {
        $pivot = DB::table('tenant_users')
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->first();

        if (!$pivot) {
            return back()->withErrors(['member' => 'Member not found.']);
        }

        if ($pivot->role === 'owner') {
            $ownerCount = DB::table('tenant_users')
                ->where('tenant_id', $tenant->id)
                ->where('role', 'owner')
                ->count();
            if ($ownerCount <= 1) {
                return back()->withErrors(['owner' => 'At least one owner is required.']);
            }
        }

        return DB::transaction(function () use ($tenant, $user, $request, $auditLogger, $pivot) {
            DB::table('tenant_users')
                ->where('tenant_id', $tenant->id)
                ->where('user_id', $user->id)
                ->delete();

            $auditLogger->log('deleted', 'tenant_users', (string) $tenant->id . ':' . $user->id, [
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'role' => $pivot->role,
                'status' => $pivot->status,
            ], null, $request);

            return back()->with('status', 'Tenant member removed.');
        });
    }
}

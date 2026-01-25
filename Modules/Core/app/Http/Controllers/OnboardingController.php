<?php

namespace Modules\Core\App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Property;
use App\Models\PropertyType;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OnboardingController extends Controller
{
    public function __construct()
    {
        // auth handled at route level
    }

    public function show(Request $request)
    {
        $user = $request->user();
        if ($user?->tenants()->exists()) {
            return redirect()->route('Core.crm');
        }

        return view('core::onboarding.wizard');
    }

    public function store(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $user = $request->user();
        if ($user?->tenants()->exists()) {
            return redirect()->route('Core.crm');
        }

        $validated = $request->validate([
            'tenant_name' => ['nullable', 'string', 'max:255'],
            'property_name' => ['required', 'string', 'max:255'],
            'property_type' => ['nullable', 'string', 'max:120'],
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'state_province' => ['nullable', 'string', 'max:120'],
            'postal_code' => ['nullable', 'string', 'max:30'],
            'country' => ['nullable', 'string', 'max:120'],
            'extra' => ['nullable', 'array'],
        ]);
        if (($validated['extra']['account_type'] ?? null) === 'organization' && empty($validated['tenant_name'])) {
            return back()->withErrors(['tenant_name' => 'Organization name is required.'])->withInput();
        }

        return DB::transaction(function () use ($validated, $user, $request, $auditLogger) {
            $tenantName = $validated['tenant_name'] ?: ($user?->name ?? 'Tenant');
            $baseSlug = Str::slug($tenantName);
            $slug = $baseSlug ?: Str::random(8);
            $suffix = 1;
            while (Tenant::query()->where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $suffix;
                $suffix++;
            }

            $plan = Plan::query()->where('code', 'starter')->first()
                ?? Plan::query()->orderBy('price_cents')->first();

            if (! $plan) {
                return back()->withErrors(['plan' => 'No plans available. Please contact support.']);
            }

            $tenant = Tenant::create([
                'name' => $tenantName,
                'slug' => $slug,
                'status' => 'active',
                'default_currency' => $plan->currency_code,
                'timezone' => 'UTC',
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

            $propertyTypeId = null;
            if (!empty($validated['property_type'])) {
                $propertyType = PropertyType::query()->firstOrCreate(
                    ['tenant_id' => $tenant->id, 'name' => $validated['property_type']],
                    ['status' => 'active']
                );
                $propertyTypeId = $propertyType->id;
            }

            $property = Property::create([
                'tenant_id' => $tenant->id,
                'name' => $validated['property_name'],
                'property_type_id' => $propertyTypeId,
                'address_line_1' => $validated['address_line_1'] ?? null,
                'address_line_2' => $validated['address_line_2'] ?? null,
                'city' => $validated['city'] ?? null,
                'state_province' => $validated['state_province'] ?? null,
                'postal_code' => $validated['postal_code'] ?? null,
                'country' => $validated['country'] ?? null,
                'extra_metadata' => $validated['extra'] ?? null,
                'status' => 'active',
            ]);

            $now = now();
            $periodEnd = $plan->interval === 'yearly' ? $now->copy()->addYear() : $now->copy()->addMonth();

            $subscription = Subscription::create([
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'status' => 'active',
                'auto_renew' => true,
                'current_period_start' => $now,
                'current_period_end' => $periodEnd,
                'provider' => 'manual',
            ]);

            $auditLogger->log('created', Tenant::class, (string) $tenant->id, null, $tenant->toArray(), $request);
            $auditLogger->log('created', Property::class, (string) $property->id, null, $property->toArray(), $request);
            $auditLogger->log('created', Subscription::class, (string) $subscription->id, null, $subscription->toArray(), $request);

            return redirect()->route('core.onboarding.plan');
        });
    }

    public function plan(Request $request)
    {
        $user = $request->user();
        $tenant = $user?->tenants()->first();

        if (! $tenant) {
            return redirect()->route('core.onboarding');
        }

        $plans = Plan::query()->with('limits')->where('is_active', true)->orderBy('price_cents')->get();
        $subscription = $tenant->subscriptions()->orderByDesc('current_period_end')->first();

        return view('core::onboarding.plan', compact('plans', 'subscription'));
    }

    public function selectPlan(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $user = $request->user();
        $tenant = $user?->tenants()->first();

        if (! $tenant) {
            return redirect()->route('core.onboarding');
        }

        $validated = $request->validate([
            'plan_id' => ['required', 'exists:plans,id'],
        ]);

        $plan = Plan::query()->findOrFail($validated['plan_id']);
        $subscription = $tenant->subscriptions()->orderByDesc('current_period_end')->first();

        $now = now();
        $periodEnd = $plan->interval === 'yearly' ? $now->copy()->addYear() : $now->copy()->addMonth();

        if ($subscription) {
            $before = $subscription->toArray();
            $subscription->update([
                'plan_id' => $plan->id,
                'status' => 'active',
                'current_period_start' => $now,
                'current_period_end' => $periodEnd,
            ]);
            $auditLogger->log('updated', Subscription::class, (string) $subscription->id, $before, $subscription->toArray(), $request);
        } else {
            $subscription = Subscription::create([
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'status' => 'active',
                'auto_renew' => true,
                'current_period_start' => $now,
                'current_period_end' => $periodEnd,
                'provider' => 'manual',
            ]);
            $auditLogger->log('created', Subscription::class, (string) $subscription->id, null, $subscription->toArray(), $request);
        }

        return redirect()->route('Core.crm')->with('status', 'Plan selected.');
    }
}

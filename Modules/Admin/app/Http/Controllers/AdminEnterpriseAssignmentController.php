<?php

namespace Modules\Admin\App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\PropertyUser;
use App\Models\Tenant;
use App\Support\EnforcesOptionalPermission;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\PlanGate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminEnterpriseAssignmentController extends Controller
{
    use EnforcesOptionalPermission;

    public function index(Request $request, PlanGate $planGate)
    {
        $this->enforceOptionalPermission($request, 'enterprise_assignments.manage');

        $tenants = Tenant::query()->orderBy('name')->get();

        $eligibleTenantIds = $tenants->filter(function (Tenant $tenant) use ($planGate) {
            return $this->tenantCanUseAssignments($tenant, $planGate);
        })->pluck('id')->all();

        $properties = Property::query()
            ->whereIn('tenant_id', $eligibleTenantIds)
            ->orderBy('name')
            ->get();

        $staff = User::query()
            ->whereHas('tenants', function ($query) use ($eligibleTenantIds) {
                $query->whereIn('tenants.id', $eligibleTenantIds)
                    ->where('tenant_users.role', 'staff')
                    ->where('tenant_users.status', 'active');
            })
            ->orderBy('name')
            ->get();

        $assignments = PropertyUser::query()
            ->with(['tenant', 'property', 'user', 'assignedBy'])
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        return view('admin::dashboard.enterprise-assignments', compact('tenants', 'properties', 'staff', 'assignments'));
    }

    public function store(Request $request, PlanGate $planGate, AuditLogger $auditLogger): RedirectResponse
    {
        $this->enforceOptionalPermission($request, 'enterprise_assignments.manage');

        $validated = $request->validate([
            'tenant_id' => ['required', 'exists:tenants,id'],
            'property_id' => ['required', 'exists:properties,id'],
            'user_id' => ['required', 'exists:users,id'],
            'status' => ['nullable', 'in:active,disabled'],
        ]);

        $tenant = Tenant::query()->findOrFail((int) $validated['tenant_id']);
        if (!$this->tenantCanUseAssignments($tenant, $planGate)) {
            return back()->withErrors(['tenant_id' => 'Selected tenant plan does not allow enterprise assignments.']);
        }

        $property = Property::query()
            ->where('tenant_id', $tenant->id)
            ->findOrFail((int) $validated['property_id']);

        $staffExists = DB::table('tenant_users')
            ->where('tenant_id', $tenant->id)
            ->where('user_id', (int) $validated['user_id'])
            ->where('role', 'staff')
            ->where('status', 'active')
            ->exists();

        if (!$staffExists) {
            return back()->withErrors(['user_id' => 'Selected user is not active staff in this tenant.']);
        }

        $existing = PropertyUser::query()
            ->where('tenant_id', $tenant->id)
            ->where('property_id', $property->id)
            ->where('user_id', (int) $validated['user_id'])
            ->first();
        if ($existing) {
            return back()->withErrors(['assignment' => 'Staff is already assigned to this property.']);
        }

        $assignment = PropertyUser::create([
            'tenant_id' => $tenant->id,
            'property_id' => $property->id,
            'user_id' => (int) $validated['user_id'],
            'assigned_by_user_id' => auth()->id(),
            'status' => $validated['status'] ?? 'active',
        ]);

        $auditLogger->log('created', PropertyUser::class, (string) $assignment->id, null, $assignment->toArray(), $request, $tenant->id);

        return back()->with('status', 'Staff assigned to property.');
    }

    public function destroy(Request $request, PropertyUser $assignment, AuditLogger $auditLogger): RedirectResponse
    {
        $this->enforceOptionalPermission($request, 'enterprise_assignments.manage');

        $before = $assignment->toArray();
        $tenantId = $assignment->tenant_id;
        $assignment->delete();

        $auditLogger->log('deleted', PropertyUser::class, (string) $assignment->id, $before, null, $request, $tenantId);

        return back()->with('status', 'Assignment removed.');
    }

    private function tenantCanUseAssignments(Tenant $tenant, PlanGate $planGate): bool
    {
        if ($planGate->tenantHasFeature($tenant, 'staff_property_assignment')) {
            return true;
        }

        $subscription = $planGate->getActiveSubscription($tenant);
        $planCode = strtolower((string) ($subscription?->plan?->code ?? ''));
        $planName = strtolower((string) ($subscription?->plan?->name ?? ''));

        return str_contains($planCode, 'enterprise') || str_contains($planName, 'enterprise');
    }
}

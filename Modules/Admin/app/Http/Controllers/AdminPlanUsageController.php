<?php

namespace Modules\Admin\App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Amenity;
use App\Models\Property;
use App\Models\Room;
use App\Models\Tenant;
use App\Support\EnforcesOptionalPermission;
use App\Services\PlanGate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminPlanUsageController extends Controller
{
    use EnforcesOptionalPermission;

    public function index(Request $request, PlanGate $planGate)
    {
        $this->enforceOptionalPermission($request, 'plan_usage.view');

        $tenants = Tenant::query()->orderBy('name')->get();

        $rows = $tenants->map(function (Tenant $tenant) use ($planGate) {
            $subscription = $planGate->getActiveSubscription($tenant);
            $limits = $planGate->getPlanLimits($subscription);

            $usage = [
                'properties_max' => Property::query()->where('tenant_id', $tenant->id)->count(),
                'rooms_max' => Room::query()->where('tenant_id', $tenant->id)->count(),
                'amenities_max' => Amenity::query()->where('tenant_id', $tenant->id)->count(),
                'tenant_users_max' => (int) DB::table('tenant_users')->where('tenant_id', $tenant->id)->count(),
                'staff_max' => (int) DB::table('tenant_users')->where('tenant_id', $tenant->id)->where('role', 'staff')->count(),
            ];

            return [
                'tenant' => $tenant,
                'plan' => $subscription?->plan,
                'limits' => $limits,
                'usage' => $usage,
            ];
        });

        return view('admin::dashboard.plan-usage', ['rows' => $rows]);
    }
}

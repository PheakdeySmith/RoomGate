<?php

namespace Modules\Admin\App\Http\Controllers;

use App\Support\EnforcesOptionalPermission;
use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\MaintenanceRequest;
use App\Models\Room;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminReportsController extends Controller
{
    use EnforcesOptionalPermission;

    public function index(Request $request)
    {
        $this->enforceOptionalPermission($request, 'reports.analytics.view');

        $tenantId = $request->integer('tenant_id') ?: null;

        $roomsQuery = Room::query();
        $contractsQuery = Contract::query()->where('status', 'active');
        $invoiceQuery = Invoice::query();
        $maintenanceQuery = MaintenanceRequest::query();

        if ($tenantId) {
            $roomsQuery->where('tenant_id', $tenantId);
            $contractsQuery->where('tenant_id', $tenantId);
            $invoiceQuery->where('tenant_id', $tenantId);
            $maintenanceQuery->where('tenant_id', $tenantId);
        }

        $totalRooms = (int) $roomsQuery->count();
        $occupiedRooms = (int) $contractsQuery->distinct('room_id')->count('room_id');
        $vacantRooms = max(0, $totalRooms - $occupiedRooms);
        $occupancyRate = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100, 2) : 0.0;

        $invoiceTotals = (clone $invoiceQuery)
            ->selectRaw('COALESCE(SUM(total_cents),0) as total_cents, COALESCE(SUM(paid_cents),0) as paid_cents')
            ->first();
        $invoicedCents = (int) ($invoiceTotals->total_cents ?? 0);
        $paidCents = (int) ($invoiceTotals->paid_cents ?? 0);
        $outstandingCents = max(0, $invoicedCents - $paidCents);
        $rentCollectionRate = $invoicedCents > 0 ? round(($paidCents / $invoicedCents) * 100, 2) : 0.0;

        $overdueCents = (int) (clone $invoiceQuery)
            ->where('status', 'overdue')
            ->selectRaw('COALESCE(SUM(COALESCE(total_cents,0) - COALESCE(paid_cents,0)),0) as overdue_cents')
            ->value('overdue_cents');

        $openMaintenance = (int) (clone $maintenanceQuery)
            ->whereIn('status', ['open', 'in_progress'])
            ->count();
        $resolvedMaintenance = (int) (clone $maintenanceQuery)
            ->whereIn('status', ['resolved', 'closed'])
            ->count();
        $driver = DB::connection()->getDriverName();
        $avgResolutionExpression = match ($driver) {
            'pgsql' => 'COALESCE(AVG(EXTRACT(EPOCH FROM (resolved_at - requested_at)) / 3600),0)',
            'sqlite' => 'COALESCE(AVG((julianday(resolved_at) - julianday(requested_at)) * 24),0)',
            'sqlsrv' => 'COALESCE(AVG(DATEDIFF(second, requested_at, resolved_at) / 3600.0),0)',
            default => 'COALESCE(AVG(TIMESTAMPDIFF(SECOND, requested_at, resolved_at)) / 3600,0)',
        };
        $avgResolutionHours = (float) (clone $maintenanceQuery)
            ->whereNotNull('resolved_at')
            ->whereNotNull('requested_at')
            ->selectRaw($avgResolutionExpression . ' as avg_hours')
            ->value('avg_hours');

        $maintenanceSlaRate = ($openMaintenance + $resolvedMaintenance) > 0
            ? round(($resolvedMaintenance / ($openMaintenance + $resolvedMaintenance)) * 100, 2)
            : 0.0;

        $tenants = Tenant::query()->orderBy('name')->get(['id', 'name']);

        return view('admin::dashboard.reports-analytics', [
            'tenants' => $tenants,
            'selectedTenantId' => $tenantId,
            'stats' => [
                'rooms_total' => $totalRooms,
                'rooms_occupied' => $occupiedRooms,
                'rooms_vacant' => $vacantRooms,
                'occupancy_rate' => $occupancyRate,
                'rent_invoiced_cents' => $invoicedCents,
                'rent_paid_cents' => $paidCents,
                'rent_outstanding_cents' => $outstandingCents,
                'rent_collection_rate' => $rentCollectionRate,
                'delinquency_cents' => $overdueCents,
                'maintenance_open' => $openMaintenance,
                'maintenance_resolved' => $resolvedMaintenance,
                'maintenance_avg_resolution_hours' => round($avgResolutionHours, 2),
                'maintenance_sla_rate' => $maintenanceSlaRate,
            ],
        ]);
    }
}

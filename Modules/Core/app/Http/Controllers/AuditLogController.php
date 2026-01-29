<?php

namespace Modules\Core\App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Core\App\Services\CurrentTenant;

class AuditLogController extends Controller
{
    public function index(Request $request, CurrentTenant $currentTenant)
    {
        $tenant = $this->requireAuditAccess($currentTenant);

        $query = AuditLog::query()
            ->where('tenant_id', $tenant->id)
            ->with('user')
            ->orderByDesc('created_at');

        $query = $this->applyFilters($request, $query);

        $logs = $query->get();
        $users = User::query()
            ->whereHas('tenants', function ($sub) use ($tenant) {
                $sub->where('tenants.id', $tenant->id)
                    ->where('tenant_users.status', 'active');
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
        $models = AuditLog::query()
            ->where('tenant_id', $tenant->id)
            ->distinct()
            ->orderBy('model_type')
            ->pluck('model_type');

        return view('core::dashboard.audit-logs', compact('logs', 'users', 'models', 'tenant'));
    }

    public function export(Request $request, CurrentTenant $currentTenant)
    {
        $tenant = $this->requireAuditAccess($currentTenant);
        $query = AuditLog::query()
            ->where('tenant_id', $tenant->id)
            ->with('user')
            ->orderByDesc('created_at');

        $query = $this->applyFilters($request, $query);
        $logs = $query->get();

        $filename = 'audit_logs_'.$tenant->slug.'_'.now()->format('Ymd_His').'.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
        ];

        $callback = function () use ($logs) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['created_at', 'action', 'model_type', 'model_id', 'user', 'ip', 'url', 'method']);
            foreach ($logs as $log) {
                fputcsv($handle, [
                    optional($log->created_at)->format('Y-m-d H:i:s'),
                    $log->action,
                    $log->model_type,
                    $log->model_id,
                    $log->user?->email ?? 'System',
                    $log->ip_address,
                    $log->url,
                    $log->method,
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function applyFilters(Request $request, $query)
    {
        if ($request->filled('action')) {
            $query->where('action', $request->string('action'));
        }

        if ($request->filled('model_type')) {
            $query->where('model_type', $request->string('model_type'));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->date('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->date('to'));
        }

        if ($request->filled('q')) {
            $search = '%'.$request->string('q').'%';
            $query->where(function ($sub) use ($search) {
                $sub->where('model_type', 'like', $search)
                    ->orWhere('model_id', 'like', $search)
                    ->orWhere('action', 'like', $search);
            });
        }

        return $query;
    }

    private function requireAuditAccess(CurrentTenant $currentTenant)
    {
        $tenant = $currentTenant->getOrFail();
        $role = DB::table('tenant_users')
            ->where('tenant_id', $tenant->id)
            ->where('user_id', auth()->id())
            ->value('role');

        if (! in_array($role, ['owner', 'admin'], true)) {
            abort(403);
        }

        return $tenant;
    }
}

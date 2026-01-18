<?php

namespace Modules\Admin\App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdminAuditLogController extends Controller
{
    private const RESTORABLE_MODELS = [
        User::class,
        Role::class,
        Permission::class,
    ];

    public function index(Request $request)
    {
        $query = AuditLog::query()->with('user')->orderByDesc('created_at');

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

        $logs = $query->get();
        $users = User::orderBy('name')->get(['id', 'name', 'email']);
        $models = AuditLog::query()->distinct()->orderBy('model_type')->pluck('model_type');

        $trashedUserIds = User::onlyTrashed()->pluck('id')->map(fn ($id) => (string) $id)->all();
        $trashedRoleIds = Role::onlyTrashed()->pluck('id')->map(fn ($id) => (string) $id)->all();
        $trashedPermissionIds = Permission::onlyTrashed()->pluck('id')->map(fn ($id) => (string) $id)->all();
        $trashedUserIds = array_flip($trashedUserIds);
        $trashedRoleIds = array_flip($trashedRoleIds);
        $trashedPermissionIds = array_flip($trashedPermissionIds);
        $seenLatest = [];

        foreach ($logs as $log) {
            $log->can_restore = false;
            if ($log->action !== 'deleted') {
                continue;
            }
            if (!in_array($log->model_type, self::RESTORABLE_MODELS, true)) {
                continue;
            }

            $key = $log->model_type.':'.$log->model_id;
            if (isset($seenLatest[$key])) {
                continue;
            }
            $seenLatest[$key] = true;

            if ($log->model_type === User::class && isset($trashedUserIds[(string) $log->model_id])) {
                $log->can_restore = true;
            }
            if ($log->model_type === Role::class && isset($trashedRoleIds[(string) $log->model_id])) {
                $log->can_restore = true;
            }
            if ($log->model_type === Permission::class && isset($trashedPermissionIds[(string) $log->model_id])) {
                $log->can_restore = true;
            }
        }

        return view('admin::dashboard.audit-logs', compact('logs', 'users', 'models'));
    }

    public function restore(AuditLog $auditLog)
    {
        if ($auditLog->action !== 'deleted') {
            return back()->with('warning', 'Only deleted records can be restored.');
        }

        if (!in_array($auditLog->model_type, self::RESTORABLE_MODELS, true)) {
            return back()->with('warning', 'Restore is not available for this record.');
        }

        $modelClass = $auditLog->model_type;
        if (!class_exists($modelClass) || !in_array(SoftDeletes::class, class_uses_recursive($modelClass), true)) {
            return back()->with('error', 'Restore is not supported for this record.');
        }

        $model = $modelClass::withTrashed()->find($auditLog->model_id);
        if (!$model) {
            return back()->with('error', 'Record not found.');
        }

        if (method_exists($model, 'trashed') && $model->trashed()) {
            $model->restore();
            return back()->with('status', 'Record restored.');
        }

        return back()->with('info', 'Record is already active.');
    }
}

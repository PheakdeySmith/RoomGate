<?php

namespace Modules\Core\App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\PlanGate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;
use Modules\Core\App\Services\CurrentTenant;

class TenantMemberController extends Controller
{
    public function index(CurrentTenant $currentTenant, PlanGate $planGate)
    {
        $tenant = $this->requireManager($currentTenant);
        $subscription = $planGate->getActiveSubscription($tenant);
        $planLimits = $planGate->getPlanLimits($subscription);

        return view('core::dashboard.tenant-members', [
            'tenant' => $tenant,
            'planLimits' => $planLimits,
        ]);
    }

    public function membersData(Request $request, CurrentTenant $currentTenant): JsonResponse
    {
        $tenant = $this->requireManager($currentTenant);

        $query = $tenant->users();
        $total = $query->count();

        $search = trim((string) data_get($request->all(), 'search.value', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('users.name', 'like', "%{$search}%")
                    ->orWhere('users.email', 'like', "%{$search}%")
                    ->orWhere('tenant_users.role', 'like', "%{$search}%")
                    ->orWhere('tenant_users.status', 'like', "%{$search}%");
            });
        }

        $filtered = $query->count();

        $orderColumn = (int) data_get($request->all(), 'order.0.column', 1);
        $orderDir = data_get($request->all(), 'order.0.dir', 'asc') === 'desc' ? 'desc' : 'asc';
        $orderMap = [
            1 => 'users.name',
            2 => 'tenant_users.role',
            3 => 'tenant_users.status',
        ];
        if (isset($orderMap[$orderColumn])) {
            $query->orderBy($orderMap[$orderColumn], $orderDir);
        } else {
            $query->orderBy('users.name');
        }

        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        $members = $query->skip($start)->take($length)->get();

        $statusMap = [
            'active' => 'bg-label-success',
            'invited' => 'bg-label-warning',
            'disabled' => 'bg-label-secondary',
        ];

        $data = $members->map(function (User $member) use ($tenant, $statusMap) {
            $statusClass = $statusMap[$member->pivot->status] ?? 'bg-label-secondary';
            $isOwner = $member->pivot->role === 'owner';
            $actions = '';

            if (! $isOwner) {
                $toggleLabel = $member->pivot->status === 'disabled' ? 'Enable' : 'Disable';
                $toggleIcon = $member->pivot->status === 'disabled' ? 'tabler-player-play' : 'tabler-player-stop';
                $toggleConfirm = $member->pivot->status === 'disabled'
                    ? 'Enable this member?'
                    : 'Disable this member?';

                $toggleForm = '<form method="POST" action="' . route('core.tenant-members.toggle-status', $member) . '" data-confirm="' . e($toggleConfirm) . '" class="d-inline">'
                    . csrf_field()
                    . '<button type="submit" class="btn btn-icon btn-text-warning rounded-pill waves-effect" title="' . $toggleLabel . '">'
                    . '<i class="icon-base ti ' . $toggleIcon . ' icon-md"></i>'
                    . '</button></form>';

                $resetForm = '<form method="POST" action="' . route('core.tenant-members.reset-password', $member) . '" data-confirm="Send password reset email?" class="d-inline">'
                    . csrf_field()
                    . '<button type="submit" class="btn btn-icon btn-text-secondary rounded-pill waves-effect" title="Reset Password">'
                    . '<i class="icon-base ti tabler-key icon-md"></i>'
                    . '</button></form>';

                $editButton = '<button class="btn btn-icon btn-text-secondary rounded-pill waves-effect" data-bs-toggle="modal" data-bs-target="#editMemberModal" data-member-id="' . $member->id . '" data-member-name="' . e($member->name) . '" data-member-email="' . e($member->email) . '" data-member-role="' . e($member->pivot->role) . '" data-member-status="' . e($member->pivot->status) . '">'
                    . '<i class="icon-base ti tabler-edit icon-md"></i>'
                    . '</button>';

                $deleteForm = '<form method="POST" action="' . route('core.tenant-members.destroy', $member) . '" data-confirm="Remove this member?" class="d-inline">'
                    . csrf_field()
                    . method_field('DELETE')
                    . '<button type="submit" class="btn btn-icon btn-text-danger rounded-pill waves-effect" title="Remove">'
                    . '<i class="icon-base ti tabler-trash icon-md"></i>'
                    . '</button></form>';

                $actions = '<div class="d-flex align-items-center">' . $editButton . $toggleForm . $resetForm . $deleteForm . '</div>';
            }

            return [
                '',
                '<div class="d-flex flex-column"><span class="text-heading">' . e($member->name) . '</span><small class="text-body-secondary">' . e($member->email) . '</small></div>',
                ucfirst($member->pivot->role),
                '<span class="badge ' . $statusClass . '">' . ucfirst($member->pivot->status) . '</span>',
                $actions,
            ];
        });

        return response()->json([
            'draw' => (int) $request->input('draw'),
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $data,
        ]);
    }

    public function store(Request $request, AuditLogger $auditLogger, PlanGate $planGate, CurrentTenant $currentTenant): RedirectResponse
    {
        $tenant = $this->requireManager($currentTenant);
        $currentCount = $tenant->users()->count();
        if (! $planGate->canCreate($tenant, 'tenant_users_max', $currentCount)) {
            return back()->withErrors(['plan' => 'You have reached the user limit for your plan.']);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'in:admin,staff,tenant'],
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
            ], $request, $tenant->id);

            return back()->with('status', 'Member added.');
        });
    }

    public function update(Request $request, User $user, AuditLogger $auditLogger, CurrentTenant $currentTenant): RedirectResponse
    {
        $tenant = $this->requireManager($currentTenant);
        $pivot = DB::table('tenant_users')
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->first();

        if (! $pivot) {
            return back()->withErrors(['member' => 'Member not found.']);
        }

        if ($pivot->role === 'owner') {
            return back()->withErrors(['member' => 'Owner cannot be edited here.']);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['required', 'in:admin,staff,tenant'],
            'status' => ['required', 'in:active,invited,disabled'],
        ]);

        return DB::transaction(function () use ($validated, $tenant, $user, $request, $auditLogger, $pivot) {
            $before = [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $pivot->role,
                'status' => $pivot->status,
            ];

            $user->name = $validated['name'];
            $user->email = $validated['email'];
            if (! empty($validated['password'])) {
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
            ], $request, $tenant->id);

            return back()->with('status', 'Member updated.');
        });
    }

    public function destroy(Request $request, User $user, AuditLogger $auditLogger, CurrentTenant $currentTenant): RedirectResponse
    {
        $tenant = $this->requireManager($currentTenant);
        $pivot = DB::table('tenant_users')
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->first();

        if (! $pivot) {
            return back()->withErrors(['member' => 'Member not found.']);
        }

        if ($pivot->role === 'owner') {
            return back()->withErrors(['owner' => 'At least one owner is required.']);
        }

        if ((int) $user->id === (int) Auth::id()) {
            return back()->withErrors(['member' => 'You cannot remove your own account.']);
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
            ], null, $request, $tenant->id);

            return back()->with('status', 'Member removed.');
        });
    }

    public function toggleStatus(Request $request, User $user, AuditLogger $auditLogger, CurrentTenant $currentTenant): RedirectResponse
    {
        $tenant = $this->requireManager($currentTenant);
        $pivot = DB::table('tenant_users')
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->first();

        if (! $pivot) {
            return back()->withErrors(['member' => 'Member not found.']);
        }

        if ($pivot->role === 'owner') {
            return back()->withErrors(['owner' => 'At least one active owner is required.']);
        }

        $newStatus = $pivot->status === 'disabled' ? 'active' : 'disabled';

        return DB::transaction(function () use ($tenant, $user, $request, $auditLogger, $pivot, $newStatus) {
            DB::table('tenant_users')
                ->where('tenant_id', $tenant->id)
                ->where('user_id', $user->id)
                ->update([
                    'status' => $newStatus,
                    'updated_at' => now(),
                ]);

            $auditLogger->log('updated', 'tenant_users', (string) $tenant->id . ':' . $user->id, [
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'role' => $pivot->role,
                'status' => $pivot->status,
            ], [
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'role' => $pivot->role,
                'status' => $newStatus,
            ], $request, $tenant->id);

            return back()->with('status', 'Member status updated.');
        });
    }

    public function resetPassword(Request $request, User $user, AuditLogger $auditLogger, CurrentTenant $currentTenant): RedirectResponse
    {
        $tenant = $this->requireManager($currentTenant);
        $pivot = DB::table('tenant_users')
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->first();

        if (! $pivot) {
            return back()->withErrors(['member' => 'Member not found.']);
        }

        if ($pivot->role === 'owner') {
            return back()->withErrors(['member' => 'Owner cannot be reset here.']);
        }

        $status = Password::sendResetLink(['email' => $user->email]);

        $auditLogger->log('updated', 'tenant_users', (string) $tenant->id . ':' . $user->id, [
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'action' => 'password_reset_requested',
        ], null, $request, $tenant->id);

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('status', 'Password reset link sent.');
        }

        return back()->withErrors(['email' => __($status)]);
    }

    private function requireManager(CurrentTenant $currentTenant)
    {
        $tenant = $currentTenant->getOrFail();
        $role = DB::table('tenant_users')
            ->where('tenant_id', $tenant->id)
            ->where('user_id', Auth::id())
            ->value('role');

        if (! in_array($role, ['owner', 'admin'], true)) {
            abort(403);
        }

        return $tenant;
    }
}

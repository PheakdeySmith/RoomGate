<?php

namespace Modules\Admin\App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use App\Models\Permission;
use App\Models\Role;

class AdminRoleController extends Controller
{
    public function index()
    {
        $roles = Role::withCount('users')
            ->with(['permissions', 'users'])
            ->orderBy('name')
            ->get();

        $users = User::with('roles')->orderBy('name')->get();
        $permissions = Permission::orderBy('name')->get();

        return view('admin::dashboard.roles', compact('roles', 'users', 'permissions'));
    }

    public function store(Request $request)
    {
        $guardName = config('auth.defaults.guard', 'web');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')],
            'permissions' => ['array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')],
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'guard_name' => $guardName,
            'status' => 'active',
            'is_system' => false,
        ]);

        $role->syncPermissions($validated['permissions'] ?? []);

        return back()->with('status', 'Role created.');
    }

    public function update(Request $request, Role $role)
    {
        $guardName = config('auth.defaults.guard', 'web');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')->ignore($role->id)],
            'permissions' => ['array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')],
        ]);

        $role->update([
            'name' => $validated['name'],
            'guard_name' => $guardName,
        ]);

        $role->syncPermissions($validated['permissions'] ?? []);

        return back()->with('status', 'Role updated.');
    }

    public function destroy(Role $role)
    {
        if ($role->is_system) {
            return back()->with('warning', 'System roles cannot be deleted.');
        }

        if ($role->users()->exists()) {
            return back()->with('warning', 'Remove users from this role before deleting it.');
        }

        $role->delete();

        return back()->with('status', 'Role deleted.');
    }

    public function storeUser(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'string', Rule::exists('roles', 'name')],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'status' => 'active',
            'platform_role' => 'none',
            'email_verified_at' => now(),
        ]);

        $user->assignRole($validated['role']);

        return back()->with('status', 'User created.');
    }

    public function updateUser(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', 'string', Rule::exists('roles', 'name')],
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        $user->syncRoles([$validated['role']]);

        return back()->with('status', 'User updated.');
    }

    public function toggleUser(User $user)
    {
        $user->update([
            'status' => $user->status === 'suspended' ? 'active' : 'suspended',
        ]);

        return back()->with('status', 'User status updated.');
    }

    public function destroyUser(User $user)
    {
        $user->delete();

        return back()->with('status', 'User deleted.');
    }
}

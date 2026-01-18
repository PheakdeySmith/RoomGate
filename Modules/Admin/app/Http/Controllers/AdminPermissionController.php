<?php

namespace Modules\Admin\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Permission;
use App\Models\Role;

class AdminPermissionController extends Controller
{
    public function index()
    {
        $permissions = Permission::with('roles')->orderBy('name')->get();
        $roles = Role::orderBy('name')->get();

        return view('admin::dashboard.permissions', compact('permissions', 'roles'));
    }

    public function store(Request $request)
    {
        $guardName = config('auth.defaults.guard', 'web');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('permissions', 'name')],
            'roles' => ['array'],
            'roles.*' => ['string', Rule::exists('roles', 'name')],
            'is_system' => ['nullable', 'boolean'],
        ]);

        $permission = Permission::create([
            'name' => $validated['name'],
            'guard_name' => $guardName,
            'status' => 'active',
            'is_system' => (bool) ($validated['is_system'] ?? false),
        ]);

        if (! empty($validated['roles'])) {
            $permission->syncRoles($validated['roles']);
        }

        return back()->with('status', 'Permission created.');
    }

    public function update(Request $request, Permission $permission)
    {
        $guardName = config('auth.defaults.guard', 'web');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('permissions', 'name')->ignore($permission->id)],
            'roles' => ['array'],
            'roles.*' => ['string', Rule::exists('roles', 'name')],
            'is_system' => ['nullable', 'boolean'],
        ]);

        $permission->update([
            'name' => $validated['name'],
            'guard_name' => $guardName,
            'is_system' => (bool) ($validated['is_system'] ?? false),
        ]);

        $permission->syncRoles($validated['roles'] ?? []);

        return back()->with('status', 'Permission updated.');
    }

    public function toggle(Permission $permission)
    {
        $permission->update([
            'status' => $permission->status === 'suspended' ? 'active' : 'suspended',
        ]);

        return back()->with('status', 'Permission status updated.');
    }

    public function destroy(Permission $permission)
    {
        if ($permission->is_system) {
            return back()->withErrors(['permission' => 'This permission is protected and cannot be deleted.']);
        }

        $permission->delete();

        return back()->with('status', 'Permission deleted.');
    }
}

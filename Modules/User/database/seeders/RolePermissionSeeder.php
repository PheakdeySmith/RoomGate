<?php

namespace Modules\User\Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $guardName = config('auth.defaults.guard', 'web');

        $permissions = [
            'users.view',
            'users.create',
            'users.update',
            'users.delete',
            'roles.manage',
            'permissions.manage',
            'reports.analytics.view',
            'feature_flags.manage',
            'plan_usage.view',
            'ops_tooling.manage',
            'enterprise_assignments.manage',
            'system_setup.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission, 'guard_name' => $guardName],
                ['status' => 'active', 'is_system' => true]
            );
        }

        $rolePermissions = [
            'platform_admin' => $permissions,
            'support' => ['users.view'],
            'billing_admin' => ['users.view', 'reports.analytics.view', 'plan_usage.view'],
            'owner' => [],
            'admin' => ['system_setup.manage'],
            'staff' => [],
            'tenant' => [],
        ];

        foreach ($rolePermissions as $roleName => $rolePermissionNames) {
            $role = Role::updateOrCreate(
                ['name' => $roleName, 'guard_name' => $guardName],
                ['status' => 'active', 'is_system' => true]
            );

            $role->syncPermissions($rolePermissionNames);
        }
    }
}

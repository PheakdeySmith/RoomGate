<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminAccessRolesPermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_roles_and_permissions(): void
    {
        $admin = $this->createPlatformAdmin();

        $this->actingAs($admin)
            ->get(route('admin.roles'))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('admin.permissions'))
            ->assertOk();

        $permission = Permission::create([
            'name' => 'manage-test-'.Str::random(6),
            'guard_name' => 'web',
            'status' => 'active',
            'is_system' => false,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.roles.store'), [
                'name' => 'custom-role-'.Str::random(6),
                'permissions' => [$permission->name],
            ])
            ->assertRedirect();

        $role = Role::query()->where('name', 'like', 'custom-role-%')->firstOrFail();

        $this->actingAs($admin)
            ->patch(route('admin.roles.update', ['role' => $role->id]), [
                'name' => $role->name.'-updated',
                'permissions' => [$permission->name],
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->delete(route('admin.roles.destroy', ['role' => $role->id]))
            ->assertRedirect();

        $this->actingAs($admin)
            ->post(route('admin.permissions.store'), [
                'name' => 'perm-'.Str::random(6),
                'roles' => [],
            ])
            ->assertRedirect();

        $perm = Permission::query()->where('name', 'like', 'perm-%')->firstOrFail();

        $this->actingAs($admin)
            ->patch(route('admin.permissions.update', ['permission' => $perm->id]), [
                'name' => $perm->name.'-updated',
                'roles' => [],
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->delete(route('admin.permissions.destroy', ['permission' => $perm->id]))
            ->assertRedirect();
    }

    private function createPlatformAdmin(): User
    {
        Role::create([
            'name' => 'platform_admin',
            'guard_name' => 'web',
        ]);

        $user = User::factory()->create([
            'status' => 'active',
        ]);

        $user->assignRole('platform_admin');

        return $user;
    }
}

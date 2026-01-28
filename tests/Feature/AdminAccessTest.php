<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_can_access_admin_tenant_pages(): void
    {
        $admin = $this->createPlatformAdmin();
        $tenant = Tenant::create([
            'name' => 'Admin Tenant',
            'slug' => Str::slug('admin-tenant-'.Str::random(6)),
            'status' => 'active',
            'default_currency' => 'USD',
            'timezone' => 'UTC',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('admin.tenants.index'))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('admin.tenants.account', ['tenant' => $tenant->id]))
            ->assertOk();
    }

    public function test_non_admin_cannot_access_admin_routes(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
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

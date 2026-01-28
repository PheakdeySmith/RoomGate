<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\PlanLimit;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TenantAccessPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_can_access_access_roles_pages(): void
    {
        [$tenant, $user] = $this->createTenantWithActiveSubscription();

        $this->actingAs($user)
            ->get(route('Core.access-roles', ['tenant' => $tenant->slug]))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('Core.access-permission', ['tenant' => $tenant->slug]))
            ->assertOk();
    }

    private function createTenantWithActiveSubscription(): array
    {
        $tenant = Tenant::create([
            'name' => 'Tenant Access',
            'slug' => Str::slug('tenant-access-'.Str::random(6)),
            'status' => 'active',
            'default_currency' => 'USD',
            'timezone' => 'UTC',
        ]);

        $user = User::factory()->create([
            'status' => 'active',
        ]);

        $tenant->users()->attach($user->id, ['role' => 'owner', 'status' => 'active']);

        $plan = Plan::create([
            'name' => 'Test Plan',
            'code' => 'test-plan-'.Str::random(6),
            'price_cents' => 0,
            'currency_code' => 'USD',
            'interval' => 'monthly',
            'is_active' => true,
        ]);

        foreach (['properties_max', 'rooms_max', 'amenities_max'] as $limitKey) {
            PlanLimit::create([
                'plan_id' => $plan->id,
                'limit_key' => $limitKey,
                'limit_value' => 'unlimited',
            ]);
        }

        Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'auto_renew' => true,
            'current_period_start' => Carbon::now()->subDay(),
            'current_period_end' => Carbon::now()->addMonth(),
            'provider' => 'manual',
        ]);

        return [$tenant, $user];
    }
}

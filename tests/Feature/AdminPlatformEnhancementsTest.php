<?php

namespace Tests\Feature;

use App\Jobs\ProcessBakongPaymentWebhook;
use App\Models\Contract;
use App\Models\FeatureFlag;
use App\Models\Invoice;
use App\Models\MaintenanceRequest;
use App\Models\Plan;
use App\Models\PlanLimit;
use App\Models\Property;
use App\Models\PropertyUser;
use App\Models\Role;
use App\Models\Room;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WebhookEvent;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminPlatformEnhancementsTest extends TestCase
{
    use RefreshDatabase;

    public function test_reporting_analytics_dashboard_loads_with_metrics(): void
    {
        $admin = $this->createPlatformAdmin();

        $tenant = $this->createTenantWithSubscription('Metrics Tenant', 'starter');
        $occupant = User::factory()->create(['status' => 'active']);

        $property = Property::create([
            'tenant_id' => $tenant->id,
            'name' => 'Property A',
            'status' => 'active',
        ]);

        $room = Room::create([
            'tenant_id' => $tenant->id,
            'property_id' => $property->id,
            'room_number' => 'A-101',
            'max_occupants' => 2,
            'monthly_rent_cents' => 120000,
            'status' => 'available',
        ]);

        $contract = Contract::create([
            'tenant_id' => $tenant->id,
            'occupant_user_id' => $occupant->id,
            'room_id' => $room->id,
            'start_date' => Carbon::now()->subMonth()->toDateString(),
            'end_date' => Carbon::now()->addMonth()->toDateString(),
            'monthly_rent_cents' => 120000,
            'currency_code' => 'USD',
            'billing_cycle' => 'monthly',
            'payment_due_day' => 1,
            'status' => 'active',
        ]);

        Invoice::create([
            'tenant_id' => $tenant->id,
            'contract_id' => $contract->id,
            'invoice_number' => 'INV-' . Str::upper(Str::random(6)),
            'issue_date' => Carbon::now()->subDays(10)->toDateString(),
            'due_date' => Carbon::now()->subDays(2)->toDateString(),
            'currency_code' => 'USD',
            'subtotal_cents' => 120000,
            'discount_cents' => 0,
            'total_cents' => 120000,
            'paid_cents' => 20000,
            'status' => 'overdue',
        ]);

        MaintenanceRequest::create([
            'tenant_id' => $tenant->id,
            'created_by_user_id' => $occupant->id,
            'property_id' => $property->id,
            'room_id' => $room->id,
            'title' => 'AC issue',
            'status' => 'resolved',
            'priority' => 'high',
            'requested_at' => Carbon::now()->subHours(8),
            'resolved_at' => Carbon::now()->subHours(1),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.reports-analytics.index'))
            ->assertOk()
            ->assertSee('Occupancy Rate')
            ->assertSee('Rent Collection');
    }

    public function test_feature_flags_crud_and_toggle_flow(): void
    {
        $admin = $this->createPlatformAdmin();

        $this->actingAs($admin)
            ->get(route('admin.feature-flags.index'))
            ->assertOk();

        $this->actingAs($admin)
            ->post(route('admin.feature-flags.store'), [
                'flag_key' => 'new_ops_console',
                'name' => 'New Ops Console',
                'description' => 'Ops UI toggle',
                'owner' => 'Platform Team',
                'sunset_date' => Carbon::now()->addMonth()->toDateString(),
                'is_enabled' => '1',
            ])
            ->assertRedirect();

        $flag = FeatureFlag::query()->where('flag_key', 'new_ops_console')->firstOrFail();

        $this->actingAs($admin)
            ->patch(route('admin.feature-flags.update', $flag), [
                'flag_key' => 'new_ops_console',
                'name' => 'New Ops Console v2',
                'description' => 'Updated',
                'owner' => 'SRE',
                'is_enabled' => '0',
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->post(route('admin.feature-flags.toggle', $flag))
            ->assertRedirect();

        $this->assertDatabaseHas('feature_flags', [
            'id' => $flag->id,
            'name' => 'New Ops Console v2',
            'owner' => 'SRE',
            'is_enabled' => true,
        ]);
    }

    public function test_plan_usage_dashboard_shows_limits_against_usage(): void
    {
        $admin = $this->createPlatformAdmin();

        $tenant = $this->createTenantWithSubscription('Usage Tenant', 'growth');
        $plan = Plan::query()->where('code', 'growth')->firstOrFail();

        PlanLimit::create(['plan_id' => $plan->id, 'limit_key' => 'properties_max', 'limit_value' => '5']);
        PlanLimit::create(['plan_id' => $plan->id, 'limit_key' => 'rooms_max', 'limit_value' => '20']);
        PlanLimit::create(['plan_id' => $plan->id, 'limit_key' => 'amenities_max', 'limit_value' => '10']);
        PlanLimit::create(['plan_id' => $plan->id, 'limit_key' => 'tenant_users_max', 'limit_value' => '25']);
        PlanLimit::create(['plan_id' => $plan->id, 'limit_key' => 'staff_max', 'limit_value' => '8']);

        $property = Property::create([
            'tenant_id' => $tenant->id,
            'name' => 'Usage Property',
            'status' => 'active',
        ]);

        Room::create([
            'tenant_id' => $tenant->id,
            'property_id' => $property->id,
            'room_number' => 'U-100',
            'max_occupants' => 2,
            'monthly_rent_cents' => 100000,
            'status' => 'available',
        ]);

        $staff = User::factory()->create(['status' => 'active']);
        DB::table('tenant_users')->insert([
            'tenant_id' => $tenant->id,
            'user_id' => $staff->id,
            'role' => 'staff',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.plan-usage.index'))
            ->assertOk()
            ->assertSee('Usage Tenant')
            ->assertSee('1 / 5')
            ->assertSee('1 / 20');
    }

    public function test_ops_tooling_dashboard_and_actions_work(): void
    {
        $admin = $this->createPlatformAdmin();

        Bus::fake();

        $event = WebhookEvent::create([
            'provider' => 'bakong',
            'event_type' => 'payment.completed',
            'idempotency_key' => 'bk_' . Str::random(8),
            'payload' => ['data' => ['id' => 'bk-1']],
            'status' => 'failed',
            'received_at' => now(),
            'last_error' => 'temp fail',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.ops-tooling.index'))
            ->assertOk()
            ->assertSee('System Health')
            ->assertSee('Recent Webhooks');

        $this->actingAs($admin)
            ->post(route('admin.ops-tooling.webhooks.replay', $event))
            ->assertRedirect();

        Bus::assertDispatched(ProcessBakongPaymentWebhook::class);

        $this->actingAs($admin)
            ->post(route('admin.ops-tooling.failed-jobs.retry'))
            ->assertRedirect();
    }

    public function test_enterprise_assignment_flow_is_gated_and_can_assign_staff(): void
    {
        $admin = $this->createPlatformAdmin();

        $tenant = $this->createTenantWithSubscription('Enterprise Tenant', 'enterprise-plan', 'Enterprise Plan');

        $property = Property::create([
            'tenant_id' => $tenant->id,
            'name' => 'Enterprise Property',
            'status' => 'active',
        ]);

        $staff = User::factory()->create(['status' => 'active']);
        DB::table('tenant_users')->insert([
            'tenant_id' => $tenant->id,
            'user_id' => $staff->id,
            'role' => 'staff',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.enterprise-assignments.index'))
            ->assertOk()
            ->assertSee('Assign Staff to Property');

        $this->actingAs($admin)
            ->post(route('admin.enterprise-assignments.store'), [
                'tenant_id' => $tenant->id,
                'property_id' => $property->id,
                'user_id' => $staff->id,
                'status' => 'active',
            ])
            ->assertRedirect();

        $assignment = PropertyUser::query()->where('tenant_id', $tenant->id)->firstOrFail();

        $this->actingAs($admin)
            ->delete(route('admin.enterprise-assignments.destroy', $assignment))
            ->assertRedirect();

        $this->assertDatabaseMissing('property_users', [
            'id' => $assignment->id,
        ]);
    }

    private function createPlatformAdmin(): User
    {
        Role::query()->firstOrCreate([
            'name' => 'platform_admin',
            'guard_name' => 'web',
        ]);

        $user = User::factory()->create([
            'status' => 'active',
        ]);

        $user->assignRole('platform_admin');

        return $user;
    }

    private function createTenantWithSubscription(string $tenantName, string $planCode, ?string $planName = null): Tenant
    {
        $tenant = Tenant::create([
            'name' => $tenantName,
            'slug' => Str::slug($tenantName . '-' . Str::random(5)),
            'status' => 'active',
            'default_currency' => 'USD',
            'timezone' => 'UTC',
        ]);

        $plan = Plan::firstOrCreate(
            ['code' => $planCode],
            [
                'name' => $planName ?: Str::headline($planCode),
                'price_cents' => 9900,
                'currency_code' => 'USD',
                'interval' => 'monthly',
                'is_active' => true,
            ]
        );

        Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'auto_renew' => true,
            'current_period_start' => now()->subDay(),
            'current_period_end' => now()->addMonth(),
            'provider' => 'manual',
        ]);

        return $tenant;
    }
}

<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Property;
use App\Models\Room;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_tenant_property_room_contract_and_invoice(): void
    {
        $admin = $this->createPlatformAdmin();

        $plan = Plan::create([
            'name' => 'Admin Plan',
            'code' => 'admin-plan-'.Str::random(6),
            'price_cents' => 0,
            'currency_code' => 'USD',
            'interval' => 'monthly',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.tenants.store'), [
                'name' => 'Admin Tenant',
                'owner_email' => 'owner-'.Str::random(6).'@example.com',
                'owner_password' => 'password123',
                'plan_id' => $plan->id,
            ])
            ->assertRedirect();

        $tenant = Tenant::query()->firstOrFail();
        $subscription = Subscription::query()->where('tenant_id', $tenant->id)->first();
        $this->assertNotNull($subscription);

        $this->actingAs($admin)
            ->post(route('admin.properties.store'), [
                'tenant_id' => $tenant->id,
                'name' => 'Admin Property',
                'status' => 'active',
            ])
            ->assertRedirect();

        $property = Property::query()->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.rooms.store'), [
                'tenant_id' => $tenant->id,
                'property_id' => $property->id,
                'room_number' => 'A-100',
                'max_occupants' => 2,
                'monthly_rent' => 100,
                'status' => 'available',
            ])
            ->assertRedirect();

        $room = Room::query()->firstOrFail();
        $occupant = User::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.contracts.store'), [
                'tenant_id' => $tenant->id,
                'occupant_user_id' => $occupant->id,
                'room_id' => $room->id,
                'start_date' => Carbon::now()->toDateString(),
                'end_date' => Carbon::now()->addMonth()->toDateString(),
                'monthly_rent' => 100,
                'deposit' => 0,
                'billing_cycle' => 'monthly',
                'payment_due_day' => 1,
                'status' => 'active',
            ])
            ->assertRedirect();

        $contract = Contract::query()->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.contracts.generate-invoice', ['contract' => $contract->id]))
            ->assertRedirect();

        $invoice = Invoice::query()->where('contract_id', $contract->id)->firstOrFail();

        $this->actingAs($admin)
            ->patch(route('admin.invoices.update-status', ['invoice' => $invoice->id]), [
                'status' => 'paid',
            ])
            ->assertRedirect();
    }

    private function createPlatformAdmin(): User
    {
        Role::create([
            'name' => 'platform_admin',
            'guard_name' => 'web',
        ]);
        Role::create([
            'name' => 'owner',
            'guard_name' => 'web',
        ]);

        $user = User::factory()->create([
            'status' => 'active',
        ]);

        $user->assignRole('platform_admin');

        return $user;
    }
}

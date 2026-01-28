<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\Plan;
use App\Models\PlanLimit;
use App\Models\Property;
use App\Models\Room;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UtilityType;
use App\Models\UtilityProvider;
use App\Models\UtilityMeter;
use App\Models\UtilityRate;
use App\Models\UtilityBill;
use App\Models\UtilityMeterReading;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TenantUtilitiesTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_can_manage_utilities_flow(): void
    {
        [$tenant, $user] = $this->createTenantWithActiveSubscription();

        $property = Property::create([
            'tenant_id' => $tenant->id,
            'name' => 'Utility Property',
            'status' => 'active',
        ]);

        $room = Room::create([
            'tenant_id' => $tenant->id,
            'property_id' => $property->id,
            'room_number' => 'U-101',
            'max_occupants' => 2,
            'monthly_rent_cents' => 10000,
            'currency_code' => 'USD',
            'status' => 'available',
        ]);

        $utilityType = UtilityType::create([
            'tenant_id' => $tenant->id,
            'code' => 'water',
            'name' => 'Water',
            'unit_of_measure' => 'm3',
            'billing_type' => 'metered',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->post(route('core.utility-providers.store', ['tenant' => $tenant->slug]), [
                'utility_type_id' => $utilityType->id,
                'name' => 'Water Co',
                'status' => 'active',
            ])
            ->assertRedirect();

        $provider = UtilityProvider::query()->where('tenant_id', $tenant->id)->firstOrFail();

        $this->actingAs($user)
            ->post(route('core.utility-meters.store', ['tenant' => $tenant->slug]), [
                'property_id' => $property->id,
                'room_id' => $room->id,
                'utility_type_id' => $utilityType->id,
                'provider_id' => $provider->id,
                'meter_code' => 'WM-1001',
                'status' => 'active',
            ])
            ->assertRedirect();

        $meter = UtilityMeter::query()->where('tenant_id', $tenant->id)->firstOrFail();

        $this->actingAs($user)
            ->post(route('core.utility-readings.store', ['tenant' => $tenant->slug]), [
                'meter_id' => $meter->id,
                'reading_value' => 120.5,
                'reading_at' => now()->toDateString(),
            ])
            ->assertRedirect();

        $this->actingAs($user)
            ->post(route('core.utility-rates.store', ['tenant' => $tenant->slug]), [
                'property_id' => $property->id,
                'utility_type_id' => $utilityType->id,
                'rate' => 1.25,
                'effective_from' => now()->toDateString(),
            ])
            ->assertRedirect();

        $contract = Contract::create([
            'tenant_id' => $tenant->id,
            'occupant_user_id' => $user->id,
            'room_id' => $room->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'monthly_rent_cents' => 10000,
            'deposit_cents' => 0,
            'currency_code' => 'USD',
            'billing_cycle' => 'monthly',
            'payment_due_day' => 1,
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->post(route('core.utility-bills.store', ['tenant' => $tenant->slug]), [
                'contract_id' => $contract->id,
                'utility_type_id' => $utilityType->id,
                'meter_id' => $meter->id,
                'provider_id' => $provider->id,
                'billing_period_start' => now()->subMonth()->toDateString(),
                'billing_period_end' => now()->toDateString(),
                'amount' => 15.50,
                'tax' => 1.25,
                'status' => 'sent',
            ])
            ->assertRedirect();

        $rate = UtilityRate::query()->where('tenant_id', $tenant->id)->firstOrFail();
        $bill = UtilityBill::query()->where('tenant_id', $tenant->id)->firstOrFail();
        $reading = UtilityMeterReading::query()->where('tenant_id', $tenant->id)->firstOrFail();

        $this->actingAs($user)
            ->patch(route('core.utility-providers.update', ['tenant' => $tenant->slug, 'provider' => $provider->id]), [
                'utility_type_id' => $utilityType->id,
                'name' => 'Water Co Updated',
                'status' => 'inactive',
            ])
            ->assertRedirect();

        $this->actingAs($user)
            ->patch(route('core.utility-meters.update', ['tenant' => $tenant->slug, 'meter' => $meter->id]), [
                'property_id' => $property->id,
                'room_id' => $room->id,
                'utility_type_id' => $utilityType->id,
                'provider_id' => $provider->id,
                'meter_code' => 'WM-1002',
                'status' => 'inactive',
            ])
            ->assertRedirect();

        $this->actingAs($user)
            ->patch(route('core.utility-readings.update', ['tenant' => $tenant->slug, 'reading' => $reading->id]), [
                'meter_id' => $meter->id,
                'reading_value' => 130.0,
                'reading_at' => now()->toDateString(),
            ])
            ->assertRedirect();

        $this->actingAs($user)
            ->patch(route('core.utility-rates.update', ['tenant' => $tenant->slug, 'rate' => $rate->id]), [
                'property_id' => $property->id,
                'utility_type_id' => $utilityType->id,
                'rate' => 2.00,
                'effective_from' => now()->toDateString(),
            ])
            ->assertRedirect();

        $this->actingAs($user)
            ->patch(route('core.utility-bills.update', ['tenant' => $tenant->slug, 'bill' => $bill->id]), [
                'contract_id' => $contract->id,
                'utility_type_id' => $utilityType->id,
                'meter_id' => $meter->id,
                'provider_id' => $provider->id,
                'billing_period_start' => now()->subMonth()->toDateString(),
                'billing_period_end' => now()->toDateString(),
                'amount' => 20.00,
                'tax' => 2.50,
                'status' => 'paid',
            ])
            ->assertRedirect();

        $this->actingAs($user)
            ->delete(route('core.utility-bills.destroy', ['tenant' => $tenant->slug, 'bill' => $bill->id]))
            ->assertRedirect();

        $this->actingAs($user)
            ->delete(route('core.utility-readings.destroy', ['tenant' => $tenant->slug, 'reading' => $reading->id]))
            ->assertRedirect();

        $this->actingAs($user)
            ->delete(route('core.utility-meters.destroy', ['tenant' => $tenant->slug, 'meter' => $meter->id]))
            ->assertRedirect();

        $this->actingAs($user)
            ->delete(route('core.utility-providers.destroy', ['tenant' => $tenant->slug, 'provider' => $provider->id]))
            ->assertRedirect();
    }

    private function createTenantWithActiveSubscription(): array
    {
        $tenant = Tenant::create([
            'name' => 'Tenant Utilities',
            'slug' => Str::slug('tenant-utilities-'.Str::random(6)),
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

<?php

namespace Tests\Feature;

use App\Models\Amenity;
use App\Models\Contract;
use App\Models\Plan;
use App\Models\PlanLimit;
use App\Models\Property;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TenantOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_can_create_property_room_and_contract(): void
    {
        [$tenant, $user] = $this->createTenantWithActiveSubscription();

        $this->actingAs($user)
            ->post(route('core.properties.store', ['tenant' => $tenant->slug]), [
                'name' => 'Test Property',
                'status' => 'active',
            ])
            ->assertRedirect();

        $property = Property::query()->where('tenant_id', $tenant->id)->firstOrFail();

        $this->actingAs($user)
            ->post(route('core.room-types.store', ['tenant' => $tenant->slug]), [
                'name' => 'Studio',
                'description' => 'Small studio',
                'capacity' => 2,
                'status' => 'active',
            ])
            ->assertRedirect();

        $roomType = RoomType::query()->where('tenant_id', $tenant->id)->firstOrFail();

        $this->actingAs($user)
            ->post(route('core.rooms.store', ['tenant' => $tenant->slug]), [
                'room_number' => 'A-101',
                'property_id' => $property->id,
                'room_type_id' => $roomType->id,
                'max_occupants' => 2,
                'monthly_rent' => 150,
                'status' => 'available',
            ])
            ->assertRedirect();

        $room = Room::query()->where('tenant_id', $tenant->id)->firstOrFail();

        $this->actingAs($user)
            ->post(route('core.contracts.store', ['tenant' => $tenant->slug]), [
                'occupant_user_id' => $user->id,
                'room_id' => $room->id,
                'start_date' => now()->toDateString(),
                'end_date' => now()->addMonth()->toDateString(),
                'monthly_rent' => 150,
                'deposit' => 300,
                'billing_cycle' => 'monthly',
                'payment_due_day' => 1,
                'status' => 'active',
                'auto_renew' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('contracts', [
            'tenant_id' => $tenant->id,
            'room_id' => $room->id,
            'occupant_user_id' => $user->id,
        ]);
    }

    public function test_tenant_can_update_and_delete_property(): void
    {
        [$tenant, $user] = $this->createTenantWithActiveSubscription();

        $property = Property::create([
            'tenant_id' => $tenant->id,
            'name' => 'Original',
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->patch(route('core.properties.update', ['tenant' => $tenant->slug, 'property' => $property->id]), [
                'name' => 'Updated Property',
                'status' => 'inactive',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'tenant_id' => $tenant->id,
            'name' => 'Updated Property',
            'status' => 'inactive',
        ]);

        $this->actingAs($user)
            ->delete(route('core.properties.destroy', ['tenant' => $tenant->slug, 'property' => $property->id]))
            ->assertRedirect();

        $this->assertSoftDeleted('properties', ['id' => $property->id]);
    }

    public function test_tenant_can_manage_amenities(): void
    {
        [$tenant, $user] = $this->createTenantWithActiveSubscription();

        $this->actingAs($user)
            ->post(route('core.amenities.store', ['tenant' => $tenant->slug]), [
                'name' => 'WiFi',
                'description' => 'Fast internet',
                'price' => 10,
                'status' => 'active',
            ])
            ->assertRedirect();

        $amenity = Amenity::query()->where('tenant_id', $tenant->id)->firstOrFail();

        $this->actingAs($user)
            ->patch(route('core.amenities.update', ['tenant' => $tenant->slug, 'amenity' => $amenity->id]), [
                'name' => 'Premium WiFi',
                'description' => 'Upgraded',
                'price' => 15,
                'status' => 'inactive',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('amenities', [
            'id' => $amenity->id,
            'tenant_id' => $tenant->id,
            'name' => 'Premium WiFi',
            'status' => 'inactive',
        ]);
    }

    public function test_tenant_cannot_access_other_tenant_property(): void
    {
        [$tenantA, $userA] = $this->createTenantWithActiveSubscription();
        [$tenantB] = $this->createTenantWithActiveSubscription('tenant-b');

        $property = Property::create([
            'tenant_id' => $tenantB->id,
            'name' => 'Other Property',
            'status' => 'active',
        ]);

        $this->actingAs($userA)
            ->get(route('core.properties.show', ['tenant' => $tenantA->slug, 'property' => $property->id]))
            ->assertStatus(403);
    }

    private function createTenantWithActiveSubscription(string $slugSeed = 'tenant'): array
    {
        $tenant = Tenant::create([
            'name' => 'Tenant '.Str::studly($slugSeed),
            'slug' => Str::slug($slugSeed.'-'.Str::random(6)),
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

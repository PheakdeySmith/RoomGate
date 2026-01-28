<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\PlanLimit;
use App\Models\Property;
use App\Models\Room;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TenantInvoicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_can_generate_invoice_from_contract(): void
    {
        [$tenant, $user] = $this->createTenantWithActiveSubscription();

        $property = Property::create([
            'tenant_id' => $tenant->id,
            'name' => 'Invoice Property',
            'status' => 'active',
        ]);

        $room = Room::create([
            'tenant_id' => $tenant->id,
            'property_id' => $property->id,
            'room_number' => 'I-101',
            'max_occupants' => 1,
            'monthly_rent_cents' => 10000,
            'currency_code' => 'USD',
            'status' => 'available',
        ]);

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
            ->post(route('core.contracts.generate-invoice', ['tenant' => $tenant->slug, 'contract' => $contract->id]))
            ->assertRedirect();

        $this->assertDatabaseHas('invoices', [
            'tenant_id' => $tenant->id,
            'contract_id' => $contract->id,
        ]);

        $invoice = Invoice::query()->where('contract_id', $contract->id)->first();
        $this->assertNotNull($invoice);
    }

    private function createTenantWithActiveSubscription(): array
    {
        $tenant = Tenant::create([
            'name' => 'Tenant Invoices',
            'slug' => Str::slug('tenant-invoices-'.Str::random(6)),
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

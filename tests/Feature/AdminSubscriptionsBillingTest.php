<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\SubscriptionInvoice;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminSubscriptionsBillingTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_subscriptions_invoices_and_payments(): void
    {
        $admin = $this->createPlatformAdmin();

        $tenant = Tenant::create([
            'name' => 'Billing Tenant',
            'slug' => Str::slug('billing-tenant-'.Str::random(6)),
            'status' => 'active',
            'default_currency' => 'USD',
            'timezone' => 'UTC',
        ]);

        $plan = Plan::create([
            'name' => 'Billing Plan',
            'code' => 'billing-plan-'.Str::random(6),
            'price_cents' => 9900,
            'currency_code' => 'USD',
            'interval' => 'monthly',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.subscriptions.index'))
            ->assertOk();

        $this->actingAs($admin)
            ->post(route('admin.subscriptions.store'), [
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'status' => 'active',
                'auto_renew' => true,
                'current_period_start' => Carbon::now()->subDay()->toDateString(),
                'current_period_end' => Carbon::now()->addMonth()->toDateString(),
                'provider' => 'manual',
            ])
            ->assertRedirect();

        $subscription = Subscription::query()->where('tenant_id', $tenant->id)->firstOrFail();

        $this->actingAs($admin)
            ->patch(route('admin.subscriptions.update', ['subscription' => $subscription->id]), [
                'status' => 'past_due',
                'auto_renew' => false,
                'current_period_start' => Carbon::now()->subDay()->toDateString(),
                'current_period_end' => Carbon::now()->addMonth()->toDateString(),
                'provider' => 'manual',
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->get(route('admin.subscriptions.invoices'))
            ->assertOk();

        $invoiceNumber = 'SUB-'.Str::upper(Str::random(6));
        $this->actingAs($admin)
            ->post(route('admin.subscription-invoices.store'), [
                'tenant_id' => $tenant->id,
                'subscription_id' => $subscription->id,
                'invoice_number' => $invoiceNumber,
                'amount' => 99.00,
                'status' => 'unpaid',
                'billing_period_start' => Carbon::now()->toDateString(),
                'billing_period_end' => Carbon::now()->addMonth()->toDateString(),
                'due_date' => Carbon::now()->addDays(7)->toDateString(),
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->get(route('admin.subscriptions.payments'))
            ->assertOk();

        $subscriptionInvoice = SubscriptionInvoice::query()->where('tenant_id', $tenant->id)->firstOrFail();
        $this->actingAs($admin)
            ->post(route('admin.subscription-payments.store'), [
                'tenant_id' => $tenant->id,
                'subscription_invoice_id' => $subscriptionInvoice->id,
                'amount_cents' => 9900,
                'currency_code' => 'USD',
                'provider' => 'manual',
                'status' => 'paid',
                'paid_at' => Carbon::now()->toDateString(),
            ])
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

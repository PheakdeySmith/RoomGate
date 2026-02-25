<?php

namespace Tests\Feature;

use App\Models\PaymentGatewaySetting;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionInvoice;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class TenantBillingGatewayCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_can_start_stripe_checkout_for_subscription_invoice(): void
    {
        [$tenant, $user, $invoice] = $this->createTenantWithInvoice();

        PaymentGatewaySetting::query()->updateOrCreate(['gateway_name' => 'stripe'], [
            'gateway_name' => 'stripe',
            'is_active' => true,
            'gateway_mode' => 'sandbox',
            'gateway_secret_key' => 'sk_test_123',
            'gateway_publisher_key' => 'pk_test_123',
        ]);
        PaymentGatewaySetting::query()->updateOrCreate(['gateway_name' => 'paypal'], ['gateway_name' => 'paypal', 'is_active' => false]);
        PaymentGatewaySetting::query()->updateOrCreate(['gateway_name' => 'bakong'], ['gateway_name' => 'bakong', 'is_active' => false]);

        Http::fake([
            'https://api.stripe.com/v1/checkout/sessions' => Http::response([
                'id' => 'cs_test_1001',
                'url' => 'https://checkout.stripe.com/pay/cs_test_1001',
            ], 200),
        ]);

        $this->actingAs($user)
            ->post(route('core.billing.gateway.checkout', ['tenant' => $tenant->slug]), [
                'subscription_invoice_id' => $invoice->id,
                'provider' => 'stripe',
                'amount_cents' => $invoice->amount_cents,
            ])
            ->assertRedirect('https://checkout.stripe.com/pay/cs_test_1001');

        $this->assertDatabaseHas('subscription_payments', [
            'tenant_id' => $tenant->id,
            'subscription_invoice_id' => $invoice->id,
            'provider' => 'stripe',
            'provider_ref' => 'cs_test_1001',
            'status' => 'pending',
        ]);
    }

    public function test_tenant_can_start_bakong_pending_payment_flow(): void
    {
        [$tenant, $user, $invoice] = $this->createTenantWithInvoice();

        PaymentGatewaySetting::query()->updateOrCreate(['gateway_name' => 'stripe'], ['gateway_name' => 'stripe', 'is_active' => false]);
        PaymentGatewaySetting::query()->updateOrCreate(['gateway_name' => 'paypal'], ['gateway_name' => 'paypal', 'is_active' => false]);
        PaymentGatewaySetting::query()->updateOrCreate(['gateway_name' => 'bakong'], [
            'gateway_name' => 'bakong',
            'is_active' => true,
            'gateway_mode' => 'sandbox',
            'merchant_id' => 'merchant-123',
            'gateway_secret_key' => 'secret-123',
        ]);

        $this->actingAs($user)
            ->post(route('core.billing.gateway.checkout', ['tenant' => $tenant->slug]), [
                'subscription_invoice_id' => $invoice->id,
                'provider' => 'bakong',
                'amount_cents' => $invoice->amount_cents,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('subscription_payments', [
            'tenant_id' => $tenant->id,
            'subscription_invoice_id' => $invoice->id,
            'provider' => 'bakong',
            'status' => 'pending',
        ]);
    }

    public function test_repeated_stripe_checkout_reuses_existing_pending_payment(): void
    {
        [$tenant, $user, $invoice] = $this->createTenantWithInvoice();

        PaymentGatewaySetting::query()->updateOrCreate(['gateway_name' => 'stripe'], [
            'gateway_name' => 'stripe',
            'is_active' => true,
            'gateway_mode' => 'sandbox',
            'gateway_secret_key' => 'sk_test_123',
            'gateway_publisher_key' => 'pk_test_123',
        ]);
        PaymentGatewaySetting::query()->updateOrCreate(['gateway_name' => 'paypal'], ['gateway_name' => 'paypal', 'is_active' => false]);
        PaymentGatewaySetting::query()->updateOrCreate(['gateway_name' => 'bakong'], ['gateway_name' => 'bakong', 'is_active' => false]);

        Http::fake([
            'https://api.stripe.com/v1/checkout/sessions' => Http::response([
                'id' => 'cs_test_2002',
                'url' => 'https://checkout.stripe.com/pay/cs_test_2002',
            ], 200),
        ]);

        $payload = [
            'subscription_invoice_id' => $invoice->id,
            'provider' => 'stripe',
            'amount_cents' => $invoice->amount_cents,
        ];

        $this->actingAs($user)
            ->post(route('core.billing.gateway.checkout', ['tenant' => $tenant->slug]), $payload)
            ->assertRedirect('https://checkout.stripe.com/pay/cs_test_2002');

        $this->actingAs($user)
            ->post(route('core.billing.gateway.checkout', ['tenant' => $tenant->slug]), $payload)
            ->assertRedirect('https://checkout.stripe.com/pay/cs_test_2002');

        $count = SubscriptionInvoice::query()
            ->where('tenant_id', $tenant->id)
            ->where('id', $invoice->id)
            ->firstOrFail()
            ->payments()
            ->where('provider', 'stripe')
            ->where('status', 'pending')
            ->count();

        $this->assertSame(1, $count);
    }

    private function createTenantWithInvoice(): array
    {
        $tenant = Tenant::create([
            'name' => 'Tenant '.Str::random(6),
            'slug' => Str::slug('tenant-'.Str::random(8)),
            'status' => 'active',
            'default_currency' => 'USD',
            'timezone' => 'UTC',
        ]);

        $user = User::factory()->create(['status' => 'active']);
        $tenant->users()->attach($user->id, ['role' => 'owner', 'status' => 'active']);

        $plan = Plan::create([
            'name' => 'Starter',
            'code' => 'starter-'.Str::random(6),
            'price_cents' => 9900,
            'currency_code' => 'USD',
            'interval' => 'monthly',
            'is_active' => true,
        ]);

        $subscription = Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'auto_renew' => true,
            'current_period_start' => Carbon::now()->subDay(),
            'current_period_end' => Carbon::now()->addMonth(),
            'provider' => 'manual',
        ]);

        $invoice = SubscriptionInvoice::create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $subscription->id,
            'invoice_number' => 'SUB-INV-'.Str::upper(Str::random(6)),
            'amount_cents' => 9900,
            'currency_code' => 'USD',
            'status' => 'unpaid',
            'billing_period_start' => now()->startOfMonth()->toDateString(),
            'billing_period_end' => now()->endOfMonth()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
        ]);

        return [$tenant, $user, $invoice];
    }
}

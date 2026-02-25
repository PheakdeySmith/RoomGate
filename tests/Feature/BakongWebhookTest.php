<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPayment;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class BakongWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_bakong_webhook_marks_subscription_payment_paid(): void
    {
        config(['services.webhooks.bakong_secret' => 'test-secret']);

        $tenant = Tenant::create([
            'name' => 'Tenant '.Str::random(6),
            'slug' => Str::slug('tenant-'.Str::random(8)),
            'status' => 'active',
            'default_currency' => 'USD',
            'timezone' => 'UTC',
        ]);

        $plan = Plan::create([
            'name' => 'Starter',
            'code' => 'starter-'.Str::random(6),
            'price_cents' => 1000,
            'currency_code' => 'USD',
            'interval' => 'monthly',
            'is_active' => true,
        ]);

        $subscription = Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'auto_renew' => true,
            'current_period_start' => now()->subDay(),
            'current_period_end' => now()->addMonth(),
            'provider' => 'bakong',
            'provider_ref' => 'SUB-REF-1',
        ]);

        $invoice = SubscriptionInvoice::create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $subscription->id,
            'invoice_number' => 'SUB-INV-1001',
            'amount_cents' => 1000,
            'currency_code' => 'USD',
            'status' => 'unpaid',
            'billing_period_start' => now()->startOfMonth()->toDateString(),
            'billing_period_end' => now()->endOfMonth()->toDateString(),
            'due_date' => now()->addWeek()->toDateString(),
        ]);

        $payment = SubscriptionPayment::create([
            'tenant_id' => $tenant->id,
            'subscription_invoice_id' => $invoice->id,
            'amount_cents' => 1000,
            'currency_code' => 'USD',
            'provider' => 'bakong',
            'provider_ref' => 'BK-REF-1001',
            'status' => 'pending',
        ]);

        $this->postJson(
            route('webhooks.payments.bakong'),
            [
                'event' => 'payment.completed',
                'status' => 'success',
                'transaction_id' => 'BK-REF-1001',
                'invoice_number' => 'SUB-INV-1001',
            ],
            [
                'X-Webhook-Secret' => 'test-secret',
                'Idempotency-Key' => 'evt-bk-1001',
            ]
        )->assertStatus(202);

        $this->assertDatabaseHas('subscription_payments', [
            'id' => $payment->id,
            'status' => 'paid',
            'provider_ref' => 'BK-REF-1001',
        ]);

        $this->assertDatabaseHas('subscription_invoices', [
            'id' => $invoice->id,
            'status' => 'paid',
        ]);

        $this->assertDatabaseHas('webhook_events', [
            'provider' => 'bakong',
            'idempotency_key' => 'evt-bk-1001',
            'status' => 'processed',
        ]);
    }
}

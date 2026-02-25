<?php

namespace Tests\Feature;

use App\Models\PaymentGatewaySetting;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPayment;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProviderPaymentWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_stripe_webhook_marks_payment_paid(): void
    {
        $payment = $this->createPendingPayment('stripe', 'cs_test_2001');

        PaymentGatewaySetting::query()->updateOrCreate(['gateway_name' => 'stripe'], [
            'gateway_name' => 'stripe',
            'is_active' => true,
            'gateway_mode' => 'sandbox',
            'gateway_secret_key' => 'sk_test',
            'webhook_secret' => 'whsec_test',
        ]);

        $payload = json_encode([
            'id' => 'evt_123',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_2001',
                    'payment_status' => 'paid',
                    'metadata' => [
                        'payment_id' => (string) $payment->id,
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $timestamp = (string) time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$payload, 'whsec_test');

        $this->call(
            'POST',
            '/api/webhooks/payments/stripe',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_STRIPE_SIGNATURE' => "t={$timestamp},v1={$signature}",
            ],
            $payload
        )->assertStatus(200);

        $this->assertDatabaseHas('subscription_payments', [
            'id' => $payment->id,
            'status' => 'paid',
        ]);
    }

    public function test_paypal_webhook_marks_payment_paid(): void
    {
        $payment = $this->createPendingPayment('paypal', 'ORDER-1001');

        PaymentGatewaySetting::query()->updateOrCreate(['gateway_name' => 'paypal'], [
            'gateway_name' => 'paypal',
            'is_active' => true,
            'gateway_mode' => 'sandbox',
            'gateway_client_id' => 'client_123',
            'gateway_secret_key' => 'secret_123',
            'webhook_secret' => 'webhook-id-123',
        ]);

        Http::fake([
            'https://api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response(['access_token' => 'token_123'], 200),
            'https://api-m.sandbox.paypal.com/v1/notifications/verify-webhook-signature' => Http::response(['verification_status' => 'SUCCESS'], 200),
        ]);

        $this->withHeaders([
            'PayPal-Transmission-Id' => 'transmission-1',
            'PayPal-Transmission-Time' => now()->toIso8601String(),
            'PayPal-Cert-Url' => 'https://api-m.paypal.com/certs/test',
            'PayPal-Auth-Algo' => 'SHA256withRSA',
            'PayPal-Transmission-Sig' => 'sig-1',
        ])->postJson('/api/webhooks/payments/paypal', [
            'id' => 'WH-1001',
            'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
            'resource' => [
                'supplementary_data' => [
                    'related_ids' => [
                        'order_id' => 'ORDER-1001',
                    ],
                ],
            ],
        ])->assertStatus(200);

        $this->assertDatabaseHas('subscription_payments', [
            'id' => $payment->id,
            'status' => 'paid',
        ]);
    }

    private function createPendingPayment(string $provider, string $providerRef): SubscriptionPayment
    {
        $tenant = Tenant::create([
            'name' => 'Tenant '.Str::random(6),
            'slug' => Str::slug('tenant-'.Str::random(8)),
            'status' => 'active',
            'default_currency' => 'USD',
            'timezone' => 'UTC',
        ]);

        $plan = Plan::create([
            'name' => 'Starter',
            'code' => 'starter-'.Str::random(5),
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
            'provider' => 'manual',
        ]);

        $invoice = SubscriptionInvoice::create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $subscription->id,
            'invoice_number' => 'SUB-INV-'.Str::upper(Str::random(6)),
            'amount_cents' => 1000,
            'currency_code' => 'USD',
            'status' => 'unpaid',
            'billing_period_start' => now()->startOfMonth()->toDateString(),
            'billing_period_end' => now()->endOfMonth()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
        ]);

        return SubscriptionPayment::create([
            'tenant_id' => $tenant->id,
            'subscription_invoice_id' => $invoice->id,
            'amount_cents' => 1000,
            'currency_code' => 'USD',
            'provider' => $provider,
            'provider_ref' => $providerRef,
            'status' => 'pending',
        ]);
    }
}

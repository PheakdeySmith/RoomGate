<?php

namespace App\Services\Payments;

use App\Models\PaymentGatewaySetting;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPayment;
use App\Models\Tenant;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class StripeGatewayService
{
    public function createCheckoutSession(SubscriptionPayment $payment, SubscriptionInvoice $invoice, Tenant $tenant): array
    {
        $settings = $this->settings();
        $secretKey = (string) ($settings->gateway_secret_key ?? '');
        if ($secretKey === '') {
            throw new RuntimeException('Stripe secret key is missing.');
        }

        $successUrl = route('core.billing.gateway.return', ['tenant' => $tenant->slug, 'provider' => 'stripe'])
            .'?payment='.$payment->id.'&session_id={CHECKOUT_SESSION_ID}';
        $cancelUrl = route('core.billing.gateway.cancel', ['tenant' => $tenant->slug, 'provider' => 'stripe'])
            .'?payment='.$payment->id;

        $response = Http::timeout(20)
            ->asForm()
            ->withBasicAuth($secretKey, '')
            ->withHeaders([
                'Idempotency-Key' => 'rg-stripe-checkout-'.$payment->id,
            ])
            ->post('https://api.stripe.com/v1/checkout/sessions', [
                'mode' => 'payment',
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'client_reference_id' => (string) $payment->id,
                'payment_method_types[0]' => 'card',
                'line_items[0][price_data][currency]' => strtolower((string) ($invoice->currency_code ?? 'USD')),
                'line_items[0][price_data][unit_amount]' => (int) $payment->amount_cents,
                'line_items[0][price_data][product_data][name]' => 'Subscription '.$invoice->invoice_number,
                'line_items[0][quantity]' => 1,
                'metadata[payment_id]' => (string) $payment->id,
                'metadata[invoice_id]' => (string) $invoice->id,
                'metadata[tenant_id]' => (string) $tenant->id,
            ]);

        if (!$response->successful()) {
            throw new RuntimeException('Stripe checkout session creation failed: '.$response->body());
        }

        $payload = $response->json();
        $checkoutUrl = (string) ($payload['url'] ?? '');
        $sessionId = (string) ($payload['id'] ?? '');

        if ($checkoutUrl === '' || $sessionId === '') {
            throw new RuntimeException('Stripe checkout response missing url or session id.');
        }

        return [
            'checkout_url' => $checkoutUrl,
            'reference' => $sessionId,
            'payload' => $payload,
        ];
    }

    public function verifyWebhook(string $payload, string $signatureHeader, string $endpointSecret): bool
    {
        if ($signatureHeader === '' || $endpointSecret === '') {
            return false;
        }

        $pairs = [];
        foreach (explode(',', $signatureHeader) as $item) {
            [$k, $v] = array_pad(explode('=', trim($item), 2), 2, null);
            if ($k && $v) {
                $pairs[$k][] = $v;
            }
        }

        $timestamp = $pairs['t'][0] ?? null;
        $signatures = $pairs['v1'] ?? [];
        if (!$timestamp || empty($signatures)) {
            return false;
        }

        $signedPayload = $timestamp.'.'.$payload;
        $expected = hash_hmac('sha256', $signedPayload, $endpointSecret);

        foreach ($signatures as $candidate) {
            if (hash_equals($expected, $candidate)) {
                return true;
            }
        }

        return false;
    }

    public function settings(): PaymentGatewaySetting
    {
        $settings = PaymentGatewaySetting::query()->where('gateway_name', 'stripe')->first();
        if (!$settings) {
            throw new RuntimeException('Stripe gateway settings not found.');
        }

        return $settings;
    }
}

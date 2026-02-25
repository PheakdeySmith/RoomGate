<?php

namespace App\Services\Payments;

use App\Models\PaymentGatewaySetting;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPayment;
use App\Models\Tenant;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PayPalGatewayService
{
    public function createOrder(SubscriptionPayment $payment, SubscriptionInvoice $invoice, Tenant $tenant): array
    {
        $settings = $this->settings();
        $accessToken = $this->accessToken($settings);
        $baseUrl = $this->baseUrl($settings);

        $amount = number_format(((int) $payment->amount_cents) / 100, 2, '.', '');
        $returnUrl = route('core.billing.gateway.return', ['tenant' => $tenant->slug, 'provider' => 'paypal']).'?payment='.$payment->id;
        $cancelUrl = route('core.billing.gateway.cancel', ['tenant' => $tenant->slug, 'provider' => 'paypal']).'?payment='.$payment->id;

        $response = Http::timeout(20)
            ->withToken($accessToken)
            ->acceptJson()
            ->withHeaders([
                'PayPal-Request-Id' => 'rg-paypal-order-'.$payment->id,
            ])
            ->post($baseUrl.'/v2/checkout/orders', [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'custom_id' => (string) $payment->id,
                    'invoice_id' => (string) $invoice->invoice_number,
                    'amount' => [
                        'currency_code' => strtoupper((string) ($invoice->currency_code ?? 'USD')),
                        'value' => $amount,
                    ],
                ]],
                'application_context' => [
                    'return_url' => $returnUrl,
                    'cancel_url' => $cancelUrl,
                    'brand_name' => 'RoomGate',
                    'shipping_preference' => 'NO_SHIPPING',
                ],
            ]);

        if (!$response->successful()) {
            throw new RuntimeException('PayPal order creation failed: '.$response->body());
        }

        $payload = $response->json();
        $orderId = (string) ($payload['id'] ?? '');
        $approveUrl = '';
        foreach (($payload['links'] ?? []) as $link) {
            if (($link['rel'] ?? null) === 'approve') {
                $approveUrl = (string) ($link['href'] ?? '');
                break;
            }
        }

        if ($orderId === '' || $approveUrl === '') {
            throw new RuntimeException('PayPal create order response missing id/approve link.');
        }

        return [
            'checkout_url' => $approveUrl,
            'reference' => $orderId,
            'payload' => $payload,
        ];
    }

    public function captureOrder(string $orderId): array
    {
        $settings = $this->settings();
        $accessToken = $this->accessToken($settings);
        $baseUrl = $this->baseUrl($settings);

        $response = Http::timeout(20)
            ->withToken($accessToken)
            ->acceptJson()
            ->withHeaders([
                'PayPal-Request-Id' => 'rg-paypal-capture-'.$orderId,
            ])
            ->post($baseUrl.'/v2/checkout/orders/'.$orderId.'/capture');

        if (!$response->successful()) {
            throw new RuntimeException('PayPal order capture failed: '.$response->body());
        }

        return (array) $response->json();
    }

    public function verifyWebhook(PaymentGatewaySetting $settings, array $headers, array $eventPayload): bool
    {
        $webhookId = (string) ($settings->webhook_secret ?? '');
        if ($webhookId === '') {
            return false;
        }

        $accessToken = $this->accessToken($settings);
        $baseUrl = $this->baseUrl($settings);

        $payload = [
            'transmission_id' => $headers['paypal-transmission-id'] ?? '',
            'transmission_time' => $headers['paypal-transmission-time'] ?? '',
            'cert_url' => $headers['paypal-cert-url'] ?? '',
            'auth_algo' => $headers['paypal-auth-algo'] ?? '',
            'transmission_sig' => $headers['paypal-transmission-sig'] ?? '',
            'webhook_id' => $webhookId,
            'webhook_event' => $eventPayload,
        ];

        $response = Http::timeout(20)
            ->withToken($accessToken)
            ->acceptJson()
            ->post($baseUrl.'/v1/notifications/verify-webhook-signature', $payload);

        if (!$response->successful()) {
            return false;
        }

        return strtoupper((string) $response->json('verification_status')) === 'SUCCESS';
    }

    public function settings(): PaymentGatewaySetting
    {
        $settings = PaymentGatewaySetting::query()->where('gateway_name', 'paypal')->first();
        if (!$settings) {
            throw new RuntimeException('PayPal gateway settings not found.');
        }

        return $settings;
    }

    private function accessToken(PaymentGatewaySetting $settings): string
    {
        $clientId = (string) ($settings->gateway_client_id ?? '');
        $secret = (string) ($settings->gateway_secret_key ?? '');
        if ($clientId === '' || $secret === '') {
            throw new RuntimeException('PayPal client credentials are missing.');
        }

        $response = Http::timeout(20)
            ->asForm()
            ->withBasicAuth($clientId, $secret)
            ->post($this->baseUrl($settings).'/v1/oauth2/token', [
                'grant_type' => 'client_credentials',
            ]);

        if (!$response->successful()) {
            throw new RuntimeException('PayPal auth failed: '.$response->body());
        }

        $token = (string) $response->json('access_token');
        if ($token === '') {
            throw new RuntimeException('PayPal access token missing from response.');
        }

        return $token;
    }

    private function baseUrl(PaymentGatewaySetting $settings): string
    {
        return $settings->gateway_mode === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }
}

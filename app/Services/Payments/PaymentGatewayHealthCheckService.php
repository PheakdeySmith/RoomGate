<?php

namespace App\Services\Payments;

use App\Models\PaymentGatewaySetting;
use Illuminate\Support\Facades\Http;
use Throwable;

class PaymentGatewayHealthCheckService
{
    public function check(PaymentGatewaySetting $gateway): array
    {
        $name = strtolower((string) $gateway->gateway_name);

        return match ($name) {
            'stripe' => $this->checkStripe($gateway),
            'paypal' => $this->checkPayPal($gateway),
            'bakong' => $this->checkBakong($gateway),
            default => [
                'status' => 'failed',
                'message' => 'Unsupported gateway',
            ],
        };
    }

    private function checkStripe(PaymentGatewaySetting $gateway): array
    {
        if (empty($gateway->gateway_secret_key) || empty($gateway->gateway_publisher_key)) {
            return ['status' => 'failed', 'message' => 'Missing Stripe keys'];
        }

        try {
            $response = Http::timeout(15)
                ->withBasicAuth((string) $gateway->gateway_secret_key, '')
                ->get('https://api.stripe.com/v1/account');

            if (!$response->successful()) {
                return ['status' => 'failed', 'message' => 'Stripe API rejected credentials'];
            }

            return ['status' => 'ok', 'message' => 'Stripe credentials valid'];
        } catch (Throwable $e) {
            return ['status' => 'failed', 'message' => 'Stripe check error: '.$e->getMessage()];
        }
    }

    private function checkPayPal(PaymentGatewaySetting $gateway): array
    {
        if (empty($gateway->gateway_client_id) || empty($gateway->gateway_secret_key)) {
            return ['status' => 'failed', 'message' => 'Missing PayPal client credentials'];
        }

        $baseUrl = $gateway->gateway_mode === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';

        try {
            $response = Http::timeout(15)
                ->asForm()
                ->withBasicAuth((string) $gateway->gateway_client_id, (string) $gateway->gateway_secret_key)
                ->post($baseUrl.'/v1/oauth2/token', ['grant_type' => 'client_credentials']);

            if (!$response->successful() || empty($response->json('access_token'))) {
                return ['status' => 'failed', 'message' => 'PayPal auth failed'];
            }

            if (empty($gateway->webhook_secret)) {
                return ['status' => 'warning', 'message' => 'Credentials valid, but Webhook ID is missing'];
            }

            return ['status' => 'ok', 'message' => 'PayPal credentials valid'];
        } catch (Throwable $e) {
            return ['status' => 'failed', 'message' => 'PayPal check error: '.$e->getMessage()];
        }
    }

    private function checkBakong(PaymentGatewaySetting $gateway): array
    {
        if (empty($gateway->merchant_id) || empty($gateway->gateway_secret_key)) {
            return ['status' => 'failed', 'message' => 'Missing Bakong merchant/secret'];
        }

        if (empty($gateway->webhook_secret)) {
            return ['status' => 'warning', 'message' => 'Bakong keys set, webhook secret missing'];
        }

        return ['status' => 'ok', 'message' => 'Bakong configuration looks complete'];
    }
}

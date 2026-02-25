<?php

namespace Modules\Admin\App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PaymentGatewaySetting;
use App\Services\Payments\PaymentGatewayHealthCheckService;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminPaymentMethodSettingsController extends Controller
{
    public function index()
    {
        PaymentGatewaySetting::ensureDefaults();

        $gateways = PaymentGatewaySetting::query()
            ->whereIn('gateway_name', ['paypal', 'stripe', 'bakong'])
            ->orderByRaw("CASE gateway_name WHEN 'paypal' THEN 1 WHEN 'stripe' THEN 2 WHEN 'bakong' THEN 3 ELSE 99 END")
            ->get();

        return view('admin::dashboard.payment-method-settings', compact('gateways'));
    }

    public function updateActive(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        PaymentGatewaySetting::ensureDefaults();

        $validated = $request->validate([
            'gateways' => ['nullable', 'array'],
            'gateways.*' => ['integer', Rule::exists('payment_gateway_settings', 'id')],
        ]);

        $selected = collect($validated['gateways'] ?? [])->map(fn ($id) => (int) $id)->all();

        DB::transaction(function () use ($selected) {
            PaymentGatewaySetting::query()
                ->whereIn('gateway_name', ['paypal', 'stripe', 'bakong'])
                ->update(['is_active' => false]);

            if (!empty($selected)) {
                PaymentGatewaySetting::query()->whereIn('id', $selected)->update(['is_active' => true]);
            }
        });

        $auditLogger->log(
            'updated',
            'PaymentGatewaySettings',
            'active',
            null,
            ['active_gateway_ids' => $selected],
            $request
        );

        return back()->with('status', 'Payment gateway activation updated.');
    }

    public function update(Request $request, PaymentGatewaySetting $gatewaySetting, AuditLogger $auditLogger): RedirectResponse
    {
        $gateway = strtolower((string) $gatewaySetting->gateway_name);
        abort_unless(in_array($gateway, ['paypal', 'stripe', 'bakong'], true), 404);

        $rules = [
            'gateway_mode' => ['required', 'in:sandbox,live'],
            'gateway_username' => ['nullable', 'string', 'max:191'],
            'gateway_password' => ['nullable', 'string', 'max:191'],
            'gateway_signature' => ['nullable', 'string', 'max:191'],
            'gateway_client_id' => ['nullable', 'string', 'max:191'],
            'gateway_secret_key' => ['nullable', 'string', 'max:191'],
            'gateway_publisher_key' => ['nullable', 'string', 'max:191'],
            'gateway_private_key' => ['nullable', 'string', 'max:191'],
            'merchant_id' => ['nullable', 'string', 'max:191'],
            'webhook_secret' => ['nullable', 'string', 'max:191'],
            'service_charge' => ['nullable', 'boolean'],
            'charge_type' => ['nullable', 'in:P,F'],
            'charge' => ['nullable', 'numeric', 'min:0'],
        ];

        if ($gatewaySetting->is_active) {
            if ($gateway === 'paypal') {
                $rules['gateway_client_id'][0] = 'required';
                $rules['gateway_secret_key'][0] = 'required';
            } elseif ($gateway === 'stripe') {
                $rules['gateway_secret_key'][0] = 'required';
                $rules['gateway_publisher_key'][0] = 'required';
            } elseif ($gateway === 'bakong') {
                $rules['merchant_id'][0] = 'required';
                $rules['gateway_secret_key'][0] = 'required';
            }
        }

        $validated = $request->validate($rules);
        $validated['service_charge'] = $request->boolean('service_charge');

        if (!$validated['service_charge']) {
            $validated['charge_type'] = null;
            $validated['charge'] = 0;
        } else {
            $request->validate([
                'charge_type' => ['required', 'in:P,F'],
                'charge' => ['required', 'numeric', 'min:0'],
            ]);
            if (($validated['charge_type'] ?? null) === 'P' && (float) ($validated['charge'] ?? 0) > 100) {
                return back()->withErrors(['charge' => 'Percentage charge cannot exceed 100.'])->withInput();
            }
        }

        $before = $gatewaySetting->toArray();
        $gatewaySetting->update($validated);

        $auditLogger->log(
            'updated',
            PaymentGatewaySetting::class,
            (string) $gatewaySetting->id,
            $this->sanitizeForAudit($before),
            $this->sanitizeForAudit($gatewaySetting->toArray()),
            $request
        );

        return back()->with('status', strtoupper($gateway).' settings updated.');
    }

    public function healthCheck(
        Request $request,
        PaymentGatewaySetting $gatewaySetting,
        PaymentGatewayHealthCheckService $health,
        AuditLogger $auditLogger
    ): RedirectResponse {
        $gateway = strtolower((string) $gatewaySetting->gateway_name);
        abort_unless(in_array($gateway, ['paypal', 'stripe', 'bakong'], true), 404);

        $result = $health->check($gatewaySetting);
        $gatewaySetting->update([
            'health_status' => $result['status'] ?? 'failed',
            'health_message' => $result['message'] ?? 'Unknown check result',
            'health_checked_at' => now(),
        ]);

        $auditLogger->log(
            'updated',
            PaymentGatewaySetting::class,
            (string) $gatewaySetting->id,
            null,
            [
                'health_status' => $gatewaySetting->health_status,
                'health_message' => $gatewaySetting->health_message,
                'health_checked_at' => optional($gatewaySetting->health_checked_at)?->toIso8601String(),
            ],
            $request
        );

        $message = strtoupper($gateway).' health check: '.$gatewaySetting->health_message;
        if ($gatewaySetting->health_status === 'ok') {
            return back()->with('status', $message);
        }

        return back()->with('warning', $message);
    }

    private function sanitizeForAudit(array $payload): array
    {
        foreach ([
            'gateway_password',
            'gateway_secret_key',
            'gateway_private_key',
            'webhook_secret',
            'gateway_signature',
        ] as $secretField) {
            if (!empty($payload[$secretField])) {
                $payload[$secretField] = '[REDACTED]';
            }
        }

        return $payload;
    }
}

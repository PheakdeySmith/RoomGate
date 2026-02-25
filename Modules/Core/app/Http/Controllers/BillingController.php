<?php

namespace Modules\Core\App\Http\Controllers;

use App\Events\SubscriptionCancelled;
use App\Events\SubscriptionCreated;
use App\Http\Controllers\Controller;
use App\Models\PaymentGatewaySetting;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPayment;
use App\Services\AuditLogger;
use App\Services\Payments\PayPalGatewayService;
use App\Services\Payments\StripeGatewayService;
use App\Services\SubscriptionPaymentStateService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Modules\Core\App\Services\CurrentTenant;

class BillingController extends Controller
{
    public function index(CurrentTenant $currentTenant)
    {
        $tenant = $this->requireBillingAccess($currentTenant);

        $subscription = $tenant->subscriptions()
            ->with('plan')
            ->orderByDesc('current_period_end')
            ->first();

        $plans = Plan::query()
            ->where('is_active', true)
            ->orderBy('price_cents')
            ->get();

        $invoices = SubscriptionInvoice::query()
            ->where('tenant_id', $tenant->id)
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        $payments = SubscriptionPayment::query()
            ->where('tenant_id', $tenant->id)
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        $gatewaySettings = PaymentGatewaySetting::query()
            ->whereIn('gateway_name', ['paypal', 'stripe', 'bakong'])
            ->where('is_active', true)
            ->orderBy('gateway_name')
            ->get();

        return view('core::dashboard.billing', compact('subscription', 'plans', 'invoices', 'payments', 'tenant', 'gatewaySettings'));
    }

    public function changePlan(Request $request, AuditLogger $auditLogger, CurrentTenant $currentTenant): RedirectResponse
    {
        $tenant = $this->requireBillingAccess($currentTenant);

        $validated = $request->validate([
            'plan_id' => ['required', Rule::exists('plans', 'id')->where('is_active', true)],
        ]);

        $plan = Plan::query()->findOrFail($validated['plan_id']);
        $now = Carbon::now();

        $subscription = $tenant->subscriptions()->orderByDesc('current_period_end')->first();
        if ($subscription) {
            $before = $subscription->toArray();
            $subscription->update([
                'plan_id' => $plan->id,
                'status' => 'active',
                'auto_renew' => true,
                'current_period_start' => $now,
                'current_period_end' => $plan->interval === 'yearly' ? $now->copy()->addYear() : $now->copy()->addMonth(),
                'cancelled_at' => null,
            ]);

            $auditLogger->log('updated', Subscription::class, (string) $subscription->id, $before, $subscription->toArray(), $request, $tenant->id);
            $this->createSubscriptionInvoice($tenant, $subscription, $plan, $now);
            return back()->with('status', 'Plan updated.');
        }

        $subscription = Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'auto_renew' => true,
            'current_period_start' => $now,
            'current_period_end' => $plan->interval === 'yearly' ? $now->copy()->addYear() : $now->copy()->addMonth(),
            'provider' => 'manual',
        ]);

        $auditLogger->log('created', Subscription::class, (string) $subscription->id, null, $subscription->toArray(), $request, $tenant->id);
        event(new SubscriptionCreated($subscription));
        $this->createSubscriptionInvoice($tenant, $subscription, $plan, $now);

        return back()->with('status', 'Plan selected.');
    }

    public function cancel(Request $request, AuditLogger $auditLogger, CurrentTenant $currentTenant): RedirectResponse
    {
        $tenant = $this->requireBillingAccess($currentTenant);
        $subscription = $tenant->subscriptions()->orderByDesc('current_period_end')->first();
        if (!$subscription) {
            return back()->with('warning', 'No active subscription found.');
        }

        $before = $subscription->toArray();
        $subscription->update([
            'status' => 'cancelled',
            'auto_renew' => false,
            'cancelled_at' => now(),
        ]);

        $auditLogger->log('updated', Subscription::class, (string) $subscription->id, $before, $subscription->toArray(), $request, $tenant->id);
        event(new SubscriptionCancelled($subscription));

        return back()->with('status', 'Subscription cancelled.');
    }

    public function startGatewayCheckout(
        Request $request,
        AuditLogger $auditLogger,
        CurrentTenant $currentTenant,
        StripeGatewayService $stripe,
        PayPalGatewayService $paypal
    ): RedirectResponse
    {
        $tenant = $this->requireBillingAccess($currentTenant);

        $validated = $request->validate([
            'subscription_invoice_id' => [
                'required',
                Rule::exists('subscription_invoices', 'id')->where('tenant_id', $tenant->id),
            ],
            'provider' => ['required', 'in:stripe,paypal,bakong'],
            'amount_cents' => ['nullable', 'integer', 'min:1'],
        ]);

        $invoice = SubscriptionInvoice::query()->where('tenant_id', $tenant->id)->findOrFail($validated['subscription_invoice_id']);
        if ($invoice->status === 'paid') {
            return back()->with('warning', 'Invoice is already paid.');
        }

        $provider = strtolower((string) $validated['provider']);
        $gateway = PaymentGatewaySetting::query()
            ->where('gateway_name', $provider)
            ->where('is_active', true)
            ->first();
        if (!$gateway) {
            return back()->with('warning', ucfirst($provider).' is not active.');
        }

        $amountCents = (int) ($validated['amount_cents'] ?? $invoice->amount_cents);
        $existingPending = SubscriptionPayment::query()
            ->where('tenant_id', $tenant->id)
            ->where('subscription_invoice_id', $invoice->id)
            ->where('provider', $provider)
            ->where('status', 'pending')
            ->latest('id')
            ->first();

        if ($existingPending) {
            $existingMeta = (array) ($existingPending->metadata ?? []);
            $checkoutUrl = (string) (($existingMeta[$provider]['checkout_url'] ?? ''));
            if ($checkoutUrl !== '') {
                return redirect()->away($checkoutUrl);
            }

            if ($provider === 'bakong') {
                return back()->with('status', 'Bakong payment already initialized. Await provider callback.');
            }
        }

        $payment = SubscriptionPayment::create([
            'tenant_id' => $tenant->id,
            'subscription_invoice_id' => $invoice->id,
            'amount_cents' => $amountCents,
            'currency_code' => $invoice->currency_code ?? 'USD',
            'provider' => $provider,
            'status' => 'pending',
            'metadata' => ['initiated_at' => now()->toIso8601String()],
        ]);

        try {
            if ($provider === 'stripe') {
                $checkout = $stripe->createCheckoutSession($payment, $invoice, $tenant);
            } elseif ($provider === 'paypal') {
                $checkout = $paypal->createOrder($payment, $invoice, $tenant);
            } else {
                $payment->update([
                    'provider_ref' => 'RG-BK-'.$payment->id,
                    'metadata' => array_merge($payment->metadata ?? [], [
                        'bakong' => [
                            'instruction' => 'Awaiting Bakong callback.',
                        ],
                    ]),
                ]);

                return back()->with('status', 'Bakong payment initialized. Await provider callback.');
            }

            $payment->update([
                'provider_ref' => (string) $checkout['reference'],
                'metadata' => array_replace_recursive($payment->metadata ?? [], [
                    $provider => [
                        'checkout' => $checkout['payload'],
                        'checkout_url' => (string) $checkout['checkout_url'],
                    ],
                ]),
            ]);

            $auditLogger->log('created', SubscriptionPayment::class, (string) $payment->id, null, $payment->toArray(), $request, $tenant->id);

            return redirect()->away((string) $checkout['checkout_url']);
        } catch (\Throwable $e) {
            $payment->update([
                'status' => 'failed',
                'metadata' => array_merge($payment->metadata ?? [], ['error' => $e->getMessage()]),
            ]);

            return back()->with('warning', 'Unable to start checkout: '.$e->getMessage());
        }
    }

    public function gatewayReturn(
        Request $request,
        string $tenant,
        string $provider,
        CurrentTenant $currentTenant,
        PayPalGatewayService $paypal,
        SubscriptionPaymentStateService $states
    ): RedirectResponse
    {
        $tenantModel = $this->requireBillingAccess($currentTenant);
        $payment = SubscriptionPayment::query()
            ->where('tenant_id', $tenantModel->id)
            ->findOrFail((int) $request->query('payment'));

        if (strtolower($provider) === 'paypal') {
            $orderId = (string) ($request->query('token') ?: $payment->provider_ref);
            if ($orderId !== '') {
                $capture = $paypal->captureOrder($orderId);
                $status = strtoupper((string) ($capture['status'] ?? ''));
                if ($status === 'COMPLETED') {
                    $states->markPaid($payment, ['paypal' => ['capture' => $capture]]);
                    return redirect()->route('core.billing.index', ['tenant' => $tenantModel->slug])->with('status', 'PayPal payment captured.');
                }
                $states->markFailed($payment, ['paypal' => ['capture' => $capture]]);
                return redirect()->route('core.billing.index', ['tenant' => $tenantModel->slug])->with('warning', 'PayPal capture did not complete.');
            }
        }

        return redirect()->route('core.billing.index', ['tenant' => $tenantModel->slug])
            ->with('status', ucfirst($provider).' checkout completed. Waiting for confirmation.');
    }

    public function gatewayCancel(Request $request, string $tenant, string $provider, CurrentTenant $currentTenant, SubscriptionPaymentStateService $states): RedirectResponse
    {
        $tenantModel = $this->requireBillingAccess($currentTenant);
        $paymentId = (int) $request->query('payment');
        if ($paymentId > 0) {
            $payment = SubscriptionPayment::query()
                ->where('tenant_id', $tenantModel->id)
                ->find($paymentId);
            if ($payment && $payment->status === 'pending') {
                $states->markCancelled($payment, ['cancelled_by_user' => true, 'provider' => $provider]);
            }
        }

        return redirect()->route('core.billing.index', ['tenant' => $tenantModel->slug])
            ->with('warning', ucfirst($provider).' checkout was cancelled.');
    }

    public function storePayment(Request $request, AuditLogger $auditLogger, CurrentTenant $currentTenant): RedirectResponse
    {
        $tenant = $this->requireBillingAccess($currentTenant);

        $validated = $request->validate([
            'subscription_invoice_id' => [
                'required',
                Rule::exists('subscription_invoices', 'id')->where('tenant_id', $tenant->id),
            ],
            'amount_cents' => ['required', 'integer', 'min:1'],
            'provider_ref' => ['nullable', 'string', 'max:255'],
        ]);

        $invoice = SubscriptionInvoice::query()->where('tenant_id', $tenant->id)->findOrFail($validated['subscription_invoice_id']);

        $payment = DB::transaction(function () use ($validated, $invoice, $tenant, $auditLogger, $request) {
            $payment = SubscriptionPayment::create([
                'tenant_id' => $tenant->id,
                'subscription_invoice_id' => $invoice->id,
                'amount_cents' => $validated['amount_cents'],
                'currency_code' => $invoice->currency_code ?? 'USD',
                'provider' => 'manual',
                'provider_ref' => $validated['provider_ref'] ?? null,
                'status' => 'pending',
            ]);

            if ($validated['amount_cents'] >= (int) $invoice->amount_cents) {
                $payment->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                ]);
                $invoice->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                ]);

                $subscription = $invoice->subscription;
                if ($subscription && $subscription->status === 'past_due') {
                    $subscription->update(['status' => 'active']);
                }

                event(new \App\Events\SubscriptionPaymentReceived($payment));
            }

            $auditLogger->log('created', SubscriptionPayment::class, (string) $payment->id, null, $payment->toArray(), $request, $tenant->id);

            return $payment;
        });

        return back()->with('status', 'Payment recorded.');
    }

    public function exportInvoices(CurrentTenant $currentTenant)
    {
        $tenant = $this->requireBillingAccess($currentTenant);
        $invoices = SubscriptionInvoice::query()
            ->where('tenant_id', $tenant->id)
            ->orderByDesc('id')
            ->get();

        $filename = 'subscription_invoices_'.now()->format('Ymd_His').'.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
        ];

        $callback = function () use ($invoices) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['invoice_number', 'status', 'amount', 'currency', 'due_date', 'paid_at']);
            foreach ($invoices as $invoice) {
                fputcsv($handle, [
                    $invoice->invoice_number,
                    $invoice->status,
                    number_format($invoice->amount_cents / 100, 2),
                    $invoice->currency_code,
                    $invoice->due_date,
                    optional($invoice->paid_at)->format('Y-m-d H:i:s'),
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportPayments(CurrentTenant $currentTenant)
    {
        $tenant = $this->requireBillingAccess($currentTenant);
        $payments = SubscriptionPayment::query()
            ->where('tenant_id', $tenant->id)
            ->orderByDesc('id')
            ->get();

        $filename = 'subscription_payments_'.now()->format('Ymd_His').'.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
        ];

        $callback = function () use ($payments) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['invoice_id', 'status', 'amount', 'currency', 'paid_at', 'provider_ref']);
            foreach ($payments as $payment) {
                fputcsv($handle, [
                    $payment->subscription_invoice_id,
                    $payment->status,
                    number_format($payment->amount_cents / 100, 2),
                    $payment->currency_code,
                    optional($payment->paid_at)->format('Y-m-d H:i:s'),
                    $payment->provider_ref,
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function createSubscriptionInvoice($tenant, Subscription $subscription, Plan $plan, Carbon $now): SubscriptionInvoice
    {
        $sequence = SubscriptionInvoice::query()
            ->where('tenant_id', $tenant->id)
            ->whereYear('billing_period_start', $now->year)
            ->count() + 1;

        $periodEnd = $plan->interval === 'yearly'
            ? $now->copy()->addYear()
            : $now->copy()->addMonth();

        return SubscriptionInvoice::create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $subscription->id,
            'invoice_number' => sprintf('SUB-%s-%04d', $now->format('Y'), $sequence),
            'amount_cents' => $plan->price_cents,
            'currency_code' => $plan->currency_code ?? 'USD',
            'status' => 'unpaid',
            'billing_period_start' => $now->toDateString(),
            'billing_period_end' => $periodEnd->toDateString(),
            'due_date' => $now->copy()->addDays(7)->toDateString(),
        ]);
    }

    private function requireBillingAccess(CurrentTenant $currentTenant)
    {
        $tenant = $currentTenant->getOrFail();
        $role = DB::table('tenant_users')
            ->where('tenant_id', $tenant->id)
            ->where('user_id', auth()->id())
            ->value('role');

        if (! in_array($role, ['owner', 'admin', 'staff'], true)) {
            abort(403);
        }

        return $tenant;
    }
}

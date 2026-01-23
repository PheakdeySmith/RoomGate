<?php

namespace Modules\Admin\App\Http\Controllers;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPayment;
use App\Models\Tenant;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminSubscriptionController extends Controller
{
    public function index()
    {
        $subscriptions = Subscription::query()
            ->with(['tenant', 'plan'])
            ->orderByDesc('current_period_end')
            ->get();

        $plans = Plan::query()->orderBy('name')->get();
        $tenants = Tenant::query()->orderBy('name')->get();

        return view('admin::dashboard.subscriptions', compact('subscriptions', 'plans', 'tenants'));
    }

    public function invoices()
    {
        $invoices = SubscriptionInvoice::query()
            ->with(['tenant', 'subscription.plan'])
            ->orderByDesc('created_at')
            ->get();

        $subscriptions = Subscription::query()
            ->with(['tenant', 'plan'])
            ->orderByDesc('current_period_end')
            ->get();

        $tenants = Tenant::query()->orderBy('name')->get();

        $invoiceStats = [
            'client_count' => $invoices->pluck('tenant_id')->unique()->count(),
            'invoice_count' => $invoices->count(),
            'paid_total_cents' => $invoices->where('status', 'paid')->sum('amount_cents'),
            'unpaid_total_cents' => $invoices->where('status', '!=', 'paid')->sum('amount_cents'),
        ];

        $invoiceData = $invoices->map(function (SubscriptionInvoice $invoice) {
            $statusLabel = match ($invoice->status) {
                'paid' => 'Paid',
                'void' => 'Draft',
                default => 'Sent',
            };

            return [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'status' => $statusLabel,
                'status_raw' => $invoice->status,
                'tenant_name' => $invoice->tenant?->name ?? 'Unknown',
                'plan_name' => $invoice->subscription?->plan?->name ?? 'Subscription',
                'amount_cents' => $invoice->amount_cents,
                'currency' => $invoice->currency_code ?? 'USD',
                'issued_date' => optional($invoice->created_at)->toDateString(),
                'due_date' => optional($invoice->due_date)->toDateString(),
            ];
        });

        return view('admin::dashboard.subscription-invoices', compact('invoices', 'subscriptions', 'tenants', 'invoiceStats', 'invoiceData'));
    }

    public function createInvoice()
    {
        $subscriptions = Subscription::query()
            ->with(['tenant', 'plan'])
            ->orderByDesc('current_period_end')
            ->get();

        $tenants = Tenant::query()->orderBy('name')->get();

        return view('admin::dashboard.subscription-invoice-add', compact('subscriptions', 'tenants'));
    }

    public function showInvoice(SubscriptionInvoice $subscriptionInvoice)
    {
        $subscriptionInvoice->load(['tenant', 'subscription.plan']);

        return view('admin::dashboard.subscription-invoice-preview', compact('subscriptionInvoice'));
    }

    public function editInvoice(SubscriptionInvoice $subscriptionInvoice)
    {
        $subscriptionInvoice->load(['tenant', 'subscription.plan']);

        $subscriptions = Subscription::query()
            ->with(['tenant', 'plan'])
            ->orderByDesc('current_period_end')
            ->get();

        $tenants = Tenant::query()->orderBy('name')->get();

        return view('admin::dashboard.subscription-invoice-edit', compact('subscriptionInvoice', 'subscriptions', 'tenants'));
    }

    public function payments()
    {
        $payments = SubscriptionPayment::query()
            ->with(['tenant', 'invoice.subscription.plan'])
            ->orderByDesc('created_at')
            ->get();

        $invoices = SubscriptionInvoice::query()
            ->orderByDesc('created_at')
            ->get();

        $tenants = Tenant::query()->orderBy('name')->get();

        return view('admin::dashboard.subscription-payments', compact('payments', 'invoices', 'tenants'));
    }

    public function store(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'exists:tenants,id'],
            'plan_id' => ['required', 'exists:plans,id'],
            'status' => ['required', 'in:active,trialing,past_due,cancelled,expired'],
            'auto_renew' => ['nullable', 'boolean'],
            'current_period_start' => ['required', 'date'],
            'current_period_end' => ['required', 'date', 'after_or_equal:current_period_start'],
            'trial_ends_at' => ['nullable', 'date'],
            'provider' => ['required', 'string', 'max:50'],
            'provider_ref' => ['nullable', 'string', 'max:255'],
        ]);

        $validated['auto_renew'] = (bool) ($validated['auto_renew'] ?? false);

        $subscription = DB::transaction(function () use ($validated) {
            return Subscription::create($validated);
        });

        $auditLogger->log('created', Subscription::class, (string) $subscription->id, null, $subscription->toArray(), $request);

        return back()->with('status', 'Subscription created.');
    }

    public function update(Request $request, Subscription $subscription, AuditLogger $auditLogger): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:active,trialing,past_due,cancelled,expired'],
            'auto_renew' => ['nullable', 'boolean'],
            'current_period_start' => ['required', 'date'],
            'current_period_end' => ['required', 'date', 'after_or_equal:current_period_start'],
            'trial_ends_at' => ['nullable', 'date'],
            'cancelled_at' => ['nullable', 'date'],
            'provider' => ['required', 'string', 'max:50'],
            'provider_ref' => ['nullable', 'string', 'max:255'],
        ]);

        $validated['auto_renew'] = (bool) ($validated['auto_renew'] ?? false);

        $before = $subscription->toArray();

        DB::transaction(function () use ($subscription, $validated) {
            $subscription->update($validated);
        });

        $auditLogger->log('updated', Subscription::class, (string) $subscription->id, $before, $subscription->toArray(), $request);

        return back()->with('status', 'Subscription updated.');
    }

    public function destroy(Subscription $subscription, AuditLogger $auditLogger): RedirectResponse
    {
        $before = $subscription->toArray();
        $subscription->delete();

        $auditLogger->log('deleted', Subscription::class, (string) $subscription->id, $before, null, $request);

        return back()->with('status', 'Subscription deleted.');
    }

    public function storeInvoice(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'exists:tenants,id'],
            'subscription_id' => ['required', 'exists:subscriptions,id'],
            'invoice_number' => ['required', 'string', 'max:50', 'unique:subscription_invoices,invoice_number'],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency_code' => ['nullable', 'string', 'size:3'],
            'status' => ['required', 'in:unpaid,paid,void'],
            'billing_period_start' => ['required', 'date'],
            'billing_period_end' => ['required', 'date', 'after_or_equal:billing_period_start'],
            'due_date' => ['required', 'date'],
            'paid_at' => ['nullable', 'date'],
        ]);

        $validated['currency_code'] = 'USD';
        $validated['amount_cents'] = (int) round(((float) $validated['amount']) * 100);
        unset($validated['amount']);

        $invoice = DB::transaction(function () use ($validated) {
            return SubscriptionInvoice::create($validated);
        });

        $auditLogger->log('created', SubscriptionInvoice::class, (string) $invoice->id, null, $invoice->toArray(), $request);

        return back()->with('status', 'Subscription invoice created.');
    }

    public function updateInvoice(Request $request, SubscriptionInvoice $subscriptionInvoice, AuditLogger $auditLogger): RedirectResponse
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'exists:tenants,id'],
            'subscription_id' => ['required', 'exists:subscriptions,id'],
            'invoice_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('subscription_invoices', 'invoice_number')->ignore($subscriptionInvoice->id),
            ],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency_code' => ['nullable', 'string', 'size:3'],
            'status' => ['required', 'in:unpaid,paid,void'],
            'billing_period_start' => ['required', 'date'],
            'billing_period_end' => ['required', 'date', 'after_or_equal:billing_period_start'],
            'due_date' => ['required', 'date'],
            'paid_at' => ['nullable', 'date'],
        ]);

        $validated['currency_code'] = 'USD';
        $validated['amount_cents'] = (int) round(((float) $validated['amount']) * 100);
        unset($validated['amount']);

        $before = $subscriptionInvoice->toArray();
        $subscriptionInvoice->update($validated);

        $auditLogger->log('updated', SubscriptionInvoice::class, (string) $subscriptionInvoice->id, $before, $subscriptionInvoice->toArray(), $request);

        return back()->with('status', 'Subscription invoice updated.');
    }

    public function storePayment(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'exists:tenants,id'],
            'subscription_invoice_id' => ['required', 'exists:subscription_invoices,id'],
            'amount_cents' => ['required', 'integer', 'min:0'],
            'currency_code' => ['required', 'string', 'size:3'],
            'provider' => ['required', 'string', 'max:50'],
            'provider_ref' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:pending,paid,failed,cancelled'],
            'paid_at' => ['nullable', 'date'],
        ]);

        $payment = DB::transaction(function () use ($validated) {
            return SubscriptionPayment::create($validated);
        });

        $auditLogger->log('created', SubscriptionPayment::class, (string) $payment->id, null, $payment->toArray(), $request);

        return back()->with('status', 'Subscription payment created.');
    }

    public function updatePayment(Request $request, SubscriptionPayment $subscriptionPayment, AuditLogger $auditLogger): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:pending,paid,failed,cancelled'],
            'paid_at' => ['nullable', 'date'],
        ]);

        $before = $subscriptionPayment->toArray();
        $subscriptionPayment->update($validated);

        $auditLogger->log('updated', SubscriptionPayment::class, (string) $subscriptionPayment->id, $before, $subscriptionPayment->toArray(), $request);

        return back()->with('status', 'Subscription payment updated.');
    }
}

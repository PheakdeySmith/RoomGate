<?php

namespace Modules\Admin\App\Http\Controllers;

use App\Models\Contract;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AuditLogger;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminContractController extends Controller
{
    public function index()
    {
        $contracts = Contract::query()
            ->with(['tenant', 'room', 'occupant'])
            ->orderByDesc('created_at')
            ->get();

        $tenants = Tenant::query()->orderBy('name')->get();
        $rooms = Room::query()->with('property')->orderBy('room_number')->get();
        $users = User::query()->orderBy('name')->get();

        return view('admin::dashboard.contracts', compact('contracts', 'tenants', 'rooms', 'users'));
    }

    public function store(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'exists:tenants,id'],
            'occupant_user_id' => ['required', 'exists:users,id'],
            'room_id' => [
                'required',
                Rule::exists('rooms', 'id')->where(function ($query) use ($request) {
                    $query->where('tenant_id', $request->input('tenant_id'));
                }),
            ],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'monthly_rent' => ['required', 'numeric', 'min:0'],
            'billing_cycle' => ['required', 'in:monthly,weekly,daily,custom'],
            'payment_due_day' => ['required', 'integer', 'min:1', 'max:31'],
            'status' => ['required', 'in:active,pending,terminated,expired,cancelled'],
            'auto_renew' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        $rentCents = (int) round(((float) $validated['monthly_rent']) * 100);
        unset($validated['monthly_rent']);

        $contract = DB::transaction(function () use ($validated, $rentCents) {
            $validated['monthly_rent_cents'] = $rentCents;
            $validated['currency_code'] = 'USD';
            $validated['next_invoice_date'] = $validated['start_date'];

            return Contract::create($validated);
        });

        $auditLogger->log('created', Contract::class, (string) $contract->id, null, $contract->toArray(), $request);

        return back()->with('status', 'Contract created.');
    }

    public function update(Request $request, Contract $contract, AuditLogger $auditLogger): RedirectResponse
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'exists:tenants,id'],
            'occupant_user_id' => ['required', 'exists:users,id'],
            'room_id' => [
                'required',
                Rule::exists('rooms', 'id')->where(function ($query) use ($request) {
                    $query->where('tenant_id', $request->input('tenant_id'));
                }),
            ],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'monthly_rent' => ['required', 'numeric', 'min:0'],
            'billing_cycle' => ['required', 'in:monthly,weekly,daily,custom'],
            'payment_due_day' => ['required', 'integer', 'min:1', 'max:31'],
            'status' => ['required', 'in:active,pending,terminated,expired,cancelled'],
            'auto_renew' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        $before = $contract->toArray();
        $rentCents = (int) round(((float) $validated['monthly_rent']) * 100);
        unset($validated['monthly_rent']);

        DB::transaction(function () use ($contract, $validated, $rentCents) {
            $validated['monthly_rent_cents'] = $rentCents;
            $validated['currency_code'] = 'USD';

            $contract->update($validated);
        });

        $auditLogger->log('updated', Contract::class, (string) $contract->id, $before, $contract->toArray(), $request);

        return back()->with('status', 'Contract updated.');
    }

    public function destroy(Contract $contract, AuditLogger $auditLogger): RedirectResponse
    {
        $before = $contract->toArray();
        $contract->delete();

        $auditLogger->log('deleted', Contract::class, (string) $contract->id, $before, null, request());

        return back()->with('status', 'Contract deleted.');
    }

    public function generateInvoice(Request $request, Contract $contract, AuditLogger $auditLogger): RedirectResponse
    {
        $issueDate = Carbon::now()->startOfDay();
        $dueDate = $issueDate->copy()->day(min($contract->payment_due_day, $issueDate->daysInMonth));
        if ($dueDate->lt($issueDate)) {
            $dueDate = $dueDate->addMonthNoOverflow();
        }

        $invoice = DB::transaction(function () use ($contract, $issueDate, $dueDate) {
            $sequence = Invoice::where('tenant_id', $contract->tenant_id)->count() + 1;
            $invoiceNumber = sprintf('INV-%s-%04d', $issueDate->format('Y'), $sequence);

            $invoice = Invoice::create([
                'tenant_id' => $contract->tenant_id,
                'contract_id' => $contract->id,
                'invoice_number' => $invoiceNumber,
                'issue_date' => $issueDate->toDateString(),
                'due_date' => $dueDate->toDateString(),
                'currency_code' => $contract->currency_code ?? 'USD',
                'subtotal_cents' => $contract->monthly_rent_cents,
                'discount_cents' => 0,
                'total_cents' => $contract->monthly_rent_cents,
                'paid_cents' => 0,
                'status' => 'sent',
                'sent_at' => $issueDate,
            ]);

            InvoiceItem::create([
                'tenant_id' => $contract->tenant_id,
                'invoice_id' => $invoice->id,
                'description' => 'Monthly rent',
                'amount_cents' => $contract->monthly_rent_cents,
                'item_type' => 'rent',
            ]);

            $contract->update([
                'last_invoiced_through' => $issueDate->toDateString(),
                'next_invoice_date' => $issueDate->copy()->addMonthNoOverflow()->toDateString(),
            ]);

            return $invoice;
        });

        $auditLogger->log('created', Invoice::class, (string) $invoice->id, null, $invoice->toArray(), $request);

        return back()->with('status', 'Invoice generated.');
    }
}

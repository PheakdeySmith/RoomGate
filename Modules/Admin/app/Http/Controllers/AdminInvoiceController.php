<?php

namespace Modules\Admin\App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Property;
use App\Models\Room;
use App\Models\Tenant;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class AdminInvoiceController extends Controller
{
    public function index(Request $request)
    {
        $invoicesQuery = Invoice::query()
            ->with(['contract.room.property', 'contract.occupant', 'tenant'])
            ->orderByDesc('issue_date');

        if ($request->filled('invoice_id')) {
            $invoicesQuery->where('id', $request->input('invoice_id'));
        }

        if ($request->filled('tenant_id')) {
            $invoicesQuery->where('tenant_id', $request->input('tenant_id'));
        }

        if ($request->filled('property_id')) {
            $invoicesQuery->whereHas('contract.room', function ($query) use ($request) {
                $query->where('property_id', $request->input('property_id'));
            });
        }

        if ($request->filled('room_id')) {
            $invoicesQuery->whereHas('contract.room', function ($query) use ($request) {
                $query->where('id', $request->input('room_id'));
            });
        }

        if ($request->filled('status')) {
            $invoicesQuery->where('status', $request->input('status'));
        }

        $invoices = $invoicesQuery->get();

        $tenants = Tenant::query()->orderBy('name')->get();
        $properties = Property::query()->orderBy('name')->get();
        $rooms = Room::query()->orderBy('room_number')->get();

        return view('admin::dashboard.invoices', compact('invoices', 'tenants', 'properties', 'rooms'));
    }

    public function updateStatus(Request $request, Invoice $invoice, AuditLogger $auditLogger): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:draft,sent,paid,partial,overdue,void'],
        ]);

        return DB::transaction(function () use ($validated, $invoice, $auditLogger, $request) {
            $before = $invoice->toArray();
            $invoice->status = $validated['status'];

            if ($validated['status'] === 'paid') {
                $invoice->paid_cents = $invoice->total_cents ?? $invoice->paid_cents;
                if (!$invoice->sent_at) {
                    $invoice->sent_at = now();
                }
            }

            $invoice->save();

            $auditLogger->log(
                'updated',
                Invoice::class,
                (string) $invoice->id,
                $before,
                $invoice->toArray(),
                $request,
                $invoice->tenant_id
            );

            return back()->with('status', 'Invoice status updated.');
        });
    }

    public function print(Invoice $invoice)
    {
        $invoice->load(['tenant', 'contract.room.property', 'contract.occupant', 'items']);

        return view('admin::dashboard.invoice-print', compact('invoice'));
    }
}

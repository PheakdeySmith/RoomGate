<?php

namespace Modules\Core\App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Contract;
use App\Models\UtilityBill;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Modules\Core\App\Services\CurrentTenant;

class CoreInvoiceController extends Controller
{
    public function index(CurrentTenant $currentTenant)
    {
        $tenant = $currentTenant->getOrFail();
        $this->authorize('viewAny', [Invoice::class, $tenant->id]);

        return view('core::dashboard.invoices', [
            'tenant' => $tenant,
        ]);
    }

    public function create(CurrentTenant $currentTenant)
    {
        $tenant = $this->requireInvoiceManager($currentTenant);

        $contracts = Contract::query()
            ->with(['room.property', 'occupant'])
            ->where('tenant_id', $tenant->id)
            ->orderByDesc('created_at')
            ->get();

        return view('core::dashboard.invoices-add', [
            'tenant' => $tenant,
            'contracts' => $contracts,
            'invoice' => null,
            'selectedUtilityIds' => [],
            'manualItems' => [],
        ]);
    }

    public function store(Request $request, CurrentTenant $currentTenant, AuditLogger $auditLogger): RedirectResponse
    {
        $tenant = $this->requireInvoiceManager($currentTenant);

        $validated = $request->validate([
            'contract_id' => [
                'required',
                Rule::exists('contracts', 'id')->where('tenant_id', $tenant->id),
            ],
            'issue_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:issue_date'],
            'notes' => ['nullable', 'string'],
            'status' => ['nullable', 'in:draft,sent,paid,partial,overdue,void'],
            'utility_bill_ids' => ['nullable', 'array'],
            'utility_bill_ids.*' => [
                'integer',
                Rule::exists('utility_bills', 'id')->where('tenant_id', $tenant->id),
            ],
            'manual_items' => ['nullable', 'array'],
            'manual_items.*.description' => ['required_with:manual_items', 'string', 'max:255'],
            'manual_items.*.amount' => ['required_with:manual_items', 'numeric', 'min:0.01'],
        ]);

        $contract = Contract::query()
            ->where('tenant_id', $tenant->id)
            ->with(['room.property', 'occupant'])
            ->findOrFail($validated['contract_id']);

        $invoice = DB::transaction(function () use ($validated, $tenant, $contract, $auditLogger, $request) {
            $year = now()->format('Y');
            $sequence = Invoice::query()
                ->where('tenant_id', $tenant->id)
                ->where('invoice_number', 'like', "INV-{$year}-%")
                ->count() + 1;
            $invoiceNumber = sprintf('INV-%s-%04d', $year, $sequence);

            $invoice = Invoice::create([
                'tenant_id' => $tenant->id,
                'contract_id' => $contract->id,
                'invoice_number' => $invoiceNumber,
                'issue_date' => $validated['issue_date'],
                'due_date' => $validated['due_date'],
                'currency_code' => $tenant->default_currency ?? 'USD',
                'subtotal_cents' => 0,
                'discount_cents' => 0,
                'total_cents' => 0,
                'paid_cents' => 0,
                'status' => $validated['status'] ?? 'draft',
                'notes' => $validated['notes'] ?? null,
            ]);

            $items = $this->buildItemsPayload($tenant, $contract, $validated);
            $totals = $this->persistItems($invoice, $items);

            $invoice->update($totals);

            $auditLogger->log('created', Invoice::class, (string) $invoice->id, null, $invoice->toArray(), $request, $tenant->id);

            return $invoice;
        });

        return redirect()->route('Core.invoices.preview', [
            'tenant' => $tenant->slug,
            'invoice' => $invoice->id,
        ])->with('status', 'Invoice created.');
    }

    public function edit(string $tenant, CurrentTenant $currentTenant, string $invoice)
    {
        $tenant = $this->requireInvoiceManager($currentTenant);
        if (!ctype_digit($invoice)) {
            abort(404);
        }
        $invoice = Invoice::query()
            ->where('tenant_id', $tenant->id)
            ->findOrFail($invoice);
        $this->authorize('view', $invoice);

        $contracts = Contract::query()
            ->with(['room.property', 'occupant'])
            ->where('tenant_id', $tenant->id)
            ->orderByDesc('created_at')
            ->get();

        $invoice->load(['items', 'contract.room.property', 'contract.occupant']);

        $selectedUtilityIds = $invoice->items()
            ->where('item_type', 'utility')
            ->pluck('ref_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $manualItems = $invoice->items()
            ->where('item_type', 'other')
            ->get()
            ->map(fn (InvoiceItem $item) => [
                'description' => $item->description,
                'amount' => number_format(($item->amount_cents ?? 0) / 100, 2, '.', ''),
            ])
            ->values()
            ->all();

        return view('core::dashboard.invoices-edit', [
            'tenant' => $tenant,
            'contracts' => $contracts,
            'invoice' => $invoice,
            'selectedUtilityIds' => $selectedUtilityIds,
            'manualItems' => $manualItems,
        ]);
    }

    public function update(Request $request, string $tenant, CurrentTenant $currentTenant, string $invoice, AuditLogger $auditLogger): RedirectResponse
    {
        $tenant = $this->requireInvoiceManager($currentTenant);
        if (!ctype_digit($invoice)) {
            abort(404);
        }
        $invoice = Invoice::query()
            ->where('tenant_id', $tenant->id)
            ->findOrFail($invoice);
        $this->authorize('update', $invoice);

        $validated = $request->validate([
            'contract_id' => [
                'required',
                Rule::exists('contracts', 'id')->where('tenant_id', $tenant->id),
            ],
            'issue_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:issue_date'],
            'notes' => ['nullable', 'string'],
            'status' => ['nullable', 'in:draft,sent,paid,partial,overdue,void'],
            'utility_bill_ids' => ['nullable', 'array'],
            'utility_bill_ids.*' => [
                'integer',
                Rule::exists('utility_bills', 'id')->where('tenant_id', $tenant->id),
            ],
            'manual_items' => ['nullable', 'array'],
            'manual_items.*.description' => ['required_with:manual_items', 'string', 'max:255'],
            'manual_items.*.amount' => ['required_with:manual_items', 'numeric', 'min:0.01'],
        ]);

        $contract = Contract::query()
            ->where('tenant_id', $tenant->id)
            ->with(['room.property', 'occupant'])
            ->findOrFail($validated['contract_id']);

        $before = $invoice->toArray();

        DB::transaction(function () use ($invoice, $tenant, $contract, $validated, $auditLogger, $request, $before) {
            $invoice->update([
                'contract_id' => $contract->id,
                'issue_date' => $validated['issue_date'],
                'due_date' => $validated['due_date'],
                'status' => $validated['status'] ?? $invoice->status,
                'notes' => $validated['notes'] ?? null,
            ]);

            $invoice->items()->delete();

            $items = $this->buildItemsPayload($tenant, $contract, $validated);
            $totals = $this->persistItems($invoice, $items);

            $invoice->update($totals);

            $auditLogger->log('updated', Invoice::class, (string) $invoice->id, $before, $invoice->toArray(), $request, $tenant->id);
        });

        return redirect()->route('Core.invoices.preview', [
            'tenant' => $tenant->slug,
            'invoice' => $invoice->id,
        ])->with('status', 'Invoice updated.');
    }

    public function preview(string $tenant, CurrentTenant $currentTenant, string $invoice)
    {
        $tenant = $currentTenant->getOrFail();
        if (!ctype_digit($invoice)) {
            abort(404);
        }
        $invoice = Invoice::query()
            ->where('tenant_id', $tenant->id)
            ->findOrFail($invoice);
        $this->authorize('view', $invoice);

        $invoice->load(['items', 'contract.room.property', 'contract.occupant']);

        return view('core::dashboard.invoices-preview', [
            'tenant' => $tenant,
            'invoice' => $invoice,
        ]);
    }

    public function utilities(Request $request, CurrentTenant $currentTenant): JsonResponse
    {
        $tenant = $currentTenant->getOrFail();
        $contractId = (int) $request->input('contract_id');

        $bills = UtilityBill::query()
            ->with('utilityType')
            ->where('tenant_id', $tenant->id)
            ->where('contract_id', $contractId)
            ->orderByDesc('billing_period_end')
            ->get()
            ->map(function (UtilityBill $bill) {
                $label = $bill->utilityType?->name ?? 'Utility';
                $period = $bill->billing_period_start && $bill->billing_period_end
                    ? $bill->billing_period_start->format('Y-m-d') . ' to ' . $bill->billing_period_end->format('Y-m-d')
                    : null;
                return [
                    'id' => $bill->id,
                    'label' => $period ? $label . ' (' . $period . ')' : $label,
                    'amount_cents' => (int) ($bill->total_cents ?? 0),
                    'currency_code' => $bill->currency_code ?? 'USD',
                ];
            });

        return response()->json([
            'data' => $bills,
        ]);
    }

    public function data(Request $request, CurrentTenant $currentTenant): JsonResponse
    {
        $tenant = $currentTenant->getOrFail();
        $this->authorize('viewAny', [Invoice::class, $tenant->id]);

        $query = Invoice::query()
            ->where('tenant_id', $tenant->id)
            ->with(['contract.room.property', 'contract.occupant']);

        $total = $query->count();
        $search = trim((string) data_get($request->all(), 'search.value', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%")
                    ->orWhereHas('contract.occupant', function ($sub) use ($search) {
                        $sub->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhereHas('contract.room', function ($sub) use ($search) {
                        $sub->where('room_number', 'like', "%{$search}%")
                            ->orWhereHas('property', function ($nested) use ($search) {
                                $nested->where('name', 'like', "%{$search}%");
                            });
                    });
            });
        }

        $filtered = $query->count();

        $orderColumn = (int) data_get($request->all(), 'order.0.column', 1);
        $orderDir = data_get($request->all(), 'order.0.dir', 'desc') === 'asc' ? 'asc' : 'desc';
        $orderMap = [
            1 => 'invoice_number',
            4 => 'status',
            5 => 'total_cents',
            6 => 'due_date',
            7 => 'paid_cents',
        ];
        if (isset($orderMap[$orderColumn])) {
            $query->orderBy($orderMap[$orderColumn], $orderDir);
        } else {
            $query->orderByDesc('created_at');
        }

        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        $invoices = $query->skip($start)->take($length)->get();

        $statusMap = [
            'draft' => 'bg-label-secondary',
            'sent' => 'bg-label-info',
            'paid' => 'bg-label-success',
            'partial' => 'bg-label-warning',
            'overdue' => 'bg-label-danger',
            'void' => 'bg-label-secondary',
        ];

        $data = $invoices->map(function (Invoice $invoice) use ($statusMap, $tenant) {
            $currency = $invoice->currency_code ?: ($tenant->default_currency ?? 'USD');
            $totalCents = (int) ($invoice->total_cents ?? 0);
            $paidCents = (int) ($invoice->paid_cents ?? 0);
            $balanceCents = $totalCents - $paidCents;
            $statusClass = $statusMap[$invoice->status] ?? 'bg-label-secondary';

            $occupantName = $invoice->contract?->occupant?->name ?? '-';
            $roomLabel = $invoice->contract?->room?->room_number ?? '-';
            $propertyLabel = $invoice->contract?->room?->property?->name ?? 'Property';

            $viewUrl = route('Core.invoices.preview', [
                'tenant' => $tenant->slug,
                'invoice' => $invoice->id,
            ]);
            $editUrl = route('Core.invoices.edit', [
                'tenant' => $tenant->slug,
                'invoice' => $invoice->id,
            ]);

            $actions = '<div class="d-flex align-items-center">'
                . '<a href="' . $viewUrl . '" class="btn btn-icon btn-text-secondary rounded-pill waves-effect" title="View">'
                . '<i class="icon-base ti tabler-eye icon-md"></i>'
                . '</a>'
                . '<a href="' . $editUrl . '" class="btn btn-icon btn-text-secondary rounded-pill waves-effect" title="Edit">'
                . '<i class="icon-base ti tabler-edit icon-md"></i>'
                . '</a>'
                . '</div>';

            return [
                '',
                e($invoice->invoice_number ?? '-'),
                e($occupantName),
                e($roomLabel . ' (' . $propertyLabel . ')'),
                '<span class="badge ' . $statusClass . '">' . ucfirst($invoice->status) . '</span>',
                number_format($totalCents / 100, 2) . ' ' . e($currency),
                optional($invoice->due_date)->format('Y-m-d') ?? '-',
                number_format($paidCents / 100, 2) . ' ' . e($currency),
                number_format($balanceCents / 100, 2) . ' ' . e($currency),
                $actions,
            ];
        });

        return response()->json([
            'draw' => (int) $request->input('draw'),
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $data,
        ]);
    }

    private function requireInvoiceManager(CurrentTenant $currentTenant)
    {
        $tenant = $currentTenant->getOrFail();
        $role = DB::table('tenant_users')
            ->where('tenant_id', $tenant->id)
            ->where('user_id', Auth::id())
            ->value('role');

        if (! in_array($role, ['owner', 'admin', 'staff'], true)) {
            abort(403);
        }

        return $tenant;
    }

    private function buildItemsPayload($tenant, Contract $contract, array $validated): array
    {
        $items = [];

        $rentCents = (int) ($contract->monthly_rent_cents ?? 0);
        if ($rentCents > 0) {
            $items[] = [
                'description' => 'Monthly rent',
                'amount_cents' => $rentCents,
                'item_type' => 'rent',
                'ref_table' => 'contracts',
                'ref_id' => $contract->id,
            ];
        }

        $utilityIds = array_map('intval', $validated['utility_bill_ids'] ?? []);
        if (!empty($utilityIds)) {
            $bills = UtilityBill::query()
                ->with('utilityType')
                ->where('tenant_id', $tenant->id)
                ->where('contract_id', $contract->id)
                ->whereIn('id', $utilityIds)
                ->get();

            foreach ($bills as $bill) {
                $label = $bill->utilityType?->name ?? 'Utility';
                $period = $bill->billing_period_start && $bill->billing_period_end
                    ? $bill->billing_period_start->format('Y-m-d') . ' to ' . $bill->billing_period_end->format('Y-m-d')
                    : null;
                $description = $period ? $label . ' (' . $period . ')' : $label;
                $items[] = [
                    'description' => $description,
                    'amount_cents' => (int) ($bill->total_cents ?? 0),
                    'item_type' => 'utility',
                    'ref_table' => 'utility_bills',
                    'ref_id' => $bill->id,
                ];
            }
        }

        $manualItems = $validated['manual_items'] ?? [];
        foreach ($manualItems as $manual) {
            $amountCents = (int) round(((float) $manual['amount']) * 100);
            if ($amountCents <= 0) {
                continue;
            }
            $items[] = [
                'description' => $manual['description'],
                'amount_cents' => $amountCents,
                'item_type' => 'other',
                'ref_table' => null,
                'ref_id' => null,
            ];
        }

        return $items;
    }

    private function persistItems(Invoice $invoice, array $items): array
    {
        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += (int) ($item['amount_cents'] ?? 0);
            InvoiceItem::create(array_merge($item, [
                'tenant_id' => $invoice->tenant_id,
                'invoice_id' => $invoice->id,
            ]));
        }

        return [
            'subtotal_cents' => $subtotal,
            'discount_cents' => 0,
            'total_cents' => $subtotal,
        ];
    }
}

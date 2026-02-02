<?php

namespace Modules\Core\App\Http\Controllers;

use App\Models\Contract;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Room;
use App\Models\User;
use App\Services\AuditLogger;
use App\Events\RentInvoiceCreated;
use App\Events\ContractCreated;
use App\Events\ContractStatusChanged;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\Core\App\Services\CurrentTenant;
use Modules\Core\App\Services\UtilityInvoiceService;

class ContractController extends Controller
{
    public function index(CurrentTenant $currentTenant)
    {
        $tenant = $currentTenant->getOrFail();
        $this->authorize('viewAny', [Contract::class, $tenant->id]);

        $contracts = Contract::query()
            ->with(['room', 'occupant'])
            ->where('tenant_id', $tenant->id)
            ->orderByDesc('created_at')
            ->get();

        $rooms = Room::query()
            ->with('property')
            ->where('tenant_id', $tenant->id)
            ->orderBy('room_number')
            ->get();

        $users = User::query()
            ->whereHas('tenants', function ($query) use ($tenant) {
                $query->where('tenants.id', $tenant->id)
                    ->where('tenant_users.role', 'tenant')
                    ->where('tenant_users.status', 'active');
            })
            ->orderBy('name')
            ->get();

        return view('core::dashboard.contracts', compact('contracts', 'rooms', 'users'));
    }

    public function store(Request $request, AuditLogger $auditLogger, CurrentTenant $currentTenant): RedirectResponse
    {
        $tenant = $currentTenant->getOrFail();
        $this->authorize('create', [Contract::class, $tenant->id]);

        $createNewOccupant = $request->boolean('create_new_occupant');
        $rules = [
            'room_id' => [
                'required',
                Rule::exists('rooms', 'id')->where('tenant_id', $tenant->id),
            ],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'monthly_rent' => ['required', 'numeric', 'min:0'],
            'billing_cycle' => ['required', 'in:monthly,weekly,daily,custom'],
            'payment_due_day' => ['required', 'integer', 'min:1', 'max:31'],
            'status' => ['required', 'in:active,pending,terminated,expired,cancelled'],
            'auto_renew' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ];

        if ($createNewOccupant) {
            $rules = array_merge($rules, [
                'occupant_name' => ['required', 'string', 'max:255'],
                'occupant_email' => ['required', 'email', 'max:255', 'unique:users,email'],
                'occupant_password' => ['required', 'string', 'min:8'],
            ]);
        } else {
            $rules['occupant_user_id'] = [
                'required',
                Rule::exists('tenant_users', 'user_id')->where(function ($query) use ($tenant) {
                    $query->where('tenant_id', $tenant->id)
                        ->where('role', 'tenant')
                        ->where('status', 'active');
                }),
            ];
        }

        $validated = $request->validate($rules);
        $this->ensureNoOverlap($validated, $tenant->id);

        $rentCents = (int) round(((float) $validated['monthly_rent']) * 100);
        unset($validated['monthly_rent']);

        $contract = DB::transaction(function () use ($validated, $rentCents, $tenant, $createNewOccupant, $auditLogger, $request) {
            if ($createNewOccupant) {
                $user = User::create([
                    'name' => $validated['occupant_name'],
                    'email' => $validated['occupant_email'],
                    'password' => $validated['occupant_password'],
                    'status' => 'active',
                    'platform_role' => 'tenant',
                ]);

                $tenant->users()->attach($user->id, [
                    'role' => 'tenant',
                    'status' => 'active',
                ]);

                $auditLogger->log('created', 'tenant_users', (string) $tenant->id . ':' . $user->id, null, [
                    'tenant_id' => $tenant->id,
                    'user_id' => $user->id,
                    'role' => 'tenant',
                    'status' => 'active',
                ], $request, $tenant->id);

                $validated['occupant_user_id'] = $user->id;
            }

            $payload = array_merge($validated, [
                'tenant_id' => $tenant->id,
                'monthly_rent_cents' => $rentCents,
                'currency_code' => 'USD',
                'next_invoice_date' => $validated['start_date'],
            ]);

            unset($payload['occupant_name'], $payload['occupant_email'], $payload['occupant_password']);

            return Contract::create($payload);
        });

        $auditLogger->log('created', Contract::class, (string) $contract->id, null, $contract->toArray(), $request);
        event(new ContractCreated($contract));
        $this->syncRoomStatus($contract->room_id, $tenant->id);

        return back()->with('status', 'Contract created.');
    }

    public function update(Request $request, string $tenant, Contract $contract, AuditLogger $auditLogger, CurrentTenant $currentTenant): RedirectResponse
    {
        $tenant = $currentTenant->getOrFail();
        $this->authorize('update', $contract);

        $validated = $request->validate([
            'occupant_user_id' => [
                'required',
                Rule::exists('tenant_users', 'user_id')->where(function ($query) use ($tenant) {
                    $query->where('tenant_id', $tenant->id)
                        ->where('role', 'tenant')
                        ->where('status', 'active');
                }),
            ],
            'room_id' => [
                'required',
                Rule::exists('rooms', 'id')->where('tenant_id', $tenant->id),
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
        $this->ensureNoOverlap($validated, $tenant->id, $contract->id);
        $rentCents = (int) round(((float) $validated['monthly_rent']) * 100);
        unset($validated['monthly_rent']);
        $previousRoomId = $contract->room_id;

        DB::transaction(function () use ($contract, $validated, $rentCents) {
            $contract->update(array_merge($validated, [
                'monthly_rent_cents' => $rentCents,
                'currency_code' => 'USD',
            ]));
        });

        $auditLogger->log('updated', Contract::class, (string) $contract->id, $before, $contract->toArray(), $request);
        event(new ContractStatusChanged($contract, $before['status'] ?? null));
        $this->syncRoomStatus($previousRoomId, $tenant->id);
        if ((int) $previousRoomId !== (int) $contract->room_id) {
            $this->syncRoomStatus($contract->room_id, $tenant->id);
        }

        return back()->with('status', 'Contract updated.');
    }

    public function destroy(string $tenant, Contract $contract, AuditLogger $auditLogger, CurrentTenant $currentTenant): RedirectResponse
    {
        $tenant = $currentTenant->getOrFail();
        $this->authorize('delete', $contract);

        $before = $contract->toArray();
        $roomId = $contract->room_id;
        $contract->delete();

        $auditLogger->log('deleted', Contract::class, (string) $contract->id, $before, null, request());
        $this->syncRoomStatus($roomId, $tenant->id);

        return back()->with('status', 'Contract deleted.');
    }

    public function generateInvoice(
        Request $request,
        string $tenant,
        Contract $contract,
        AuditLogger $auditLogger,
        CurrentTenant $currentTenant,
        UtilityInvoiceService $utilityInvoiceService
    ): RedirectResponse
    {
        $tenant = $currentTenant->getOrFail();
        $this->authorize('update', $contract);

        $issueDate = Carbon::now()->startOfDay();
        $dueDate = $issueDate->copy()->day(min($contract->payment_due_day, $issueDate->daysInMonth));
        if ($dueDate->lt($issueDate)) {
            $dueDate = $dueDate->addMonthNoOverflow();
        }

        $invoice = DB::transaction(function () use ($contract, $issueDate, $dueDate, $utilityInvoiceService) {
            $sequence = Invoice::where('tenant_id', $contract->tenant_id)->count() + 1;
            $invoiceNumber = sprintf('INV-%s-%04d', $issueDate->format('Y'), $sequence);

            $periodStart = $utilityInvoiceService->resolvePeriodStart($contract);
            $utilityItems = $utilityInvoiceService->buildUtilityItems($contract, $issueDate, $periodStart);
            $rentCents = $utilityInvoiceService->calculateRentCents($contract, $issueDate, $periodStart, null, false);
            $utilityTotal = array_sum(array_column($utilityItems, 'amount_cents'));
            $subtotal = $rentCents + $utilityTotal;

            $invoice = Invoice::create([
                'tenant_id' => $contract->tenant_id,
                'contract_id' => $contract->id,
                'invoice_number' => $invoiceNumber,
                'issue_date' => $issueDate->toDateString(),
                'due_date' => $dueDate->toDateString(),
                'currency_code' => $contract->currency_code ?? 'USD',
                'subtotal_cents' => $subtotal,
                'discount_cents' => 0,
                'total_cents' => $subtotal,
                'paid_cents' => 0,
                'status' => 'sent',
                'sent_at' => $issueDate,
            ]);

            InvoiceItem::create([
                'tenant_id' => $contract->tenant_id,
                'invoice_id' => $invoice->id,
                'description' => 'Rent',
                'amount_cents' => $rentCents,
                'item_type' => 'rent',
                'ref_table' => 'contracts',
                'ref_id' => $contract->id,
            ]);

            foreach ($utilityItems as $item) {
                InvoiceItem::create(array_merge($item, [
                    'tenant_id' => $contract->tenant_id,
                    'invoice_id' => $invoice->id,
                ]));
            }

            $contract->update([
                'last_invoiced_through' => $issueDate->toDateString(),
                'next_invoice_date' => $issueDate->copy()->addMonthNoOverflow()->toDateString(),
            ]);

            return $invoice;
        });

        $auditLogger->log('created', Invoice::class, (string) $invoice->id, null, $invoice->toArray(), $request);
        event(new RentInvoiceCreated($invoice));

        return back()->with('status', 'Invoice generated.');
    }

    private function ensureNoOverlap(array $validated, int $tenantId, ?int $ignoreId = null): void
    {
        if (($validated['status'] ?? null) !== 'active') {
            return;
        }

        $start = Carbon::parse($validated['start_date']);
        $end = Carbon::parse($validated['end_date']);

        $query = Contract::query()
            ->where('tenant_id', $tenantId)
            ->where('room_id', $validated['room_id'])
            ->where('status', 'active')
            ->whereDate('start_date', '<=', $end->toDateString())
            ->whereDate('end_date', '>=', $start->toDateString());

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'room_id' => 'Room already has an active contract for that period.',
            ]);
        }
    }

    private function syncRoomStatus(int $roomId, int $tenantId): void
    {
        $room = Room::query()
            ->where('tenant_id', $tenantId)
            ->find($roomId);

        if (! $room) {
            return;
        }

        $hasActive = Contract::query()
            ->where('tenant_id', $tenantId)
            ->where('room_id', $roomId)
            ->where('status', 'active')
            ->exists();

        if ($hasActive) {
            if ($room->status !== 'occupied') {
                $room->update(['status' => 'occupied']);
            }
            return;
        }

        if ($room->status === 'occupied') {
            $room->update(['status' => 'available']);
        }
    }
}

<?php

namespace Modules\Core\App\Http\Controllers;

use App\Models\Contract;
use App\Models\UtilityBill;
use App\Models\UtilityMeter;
use App\Models\UtilityMeterReading;
use App\Models\UtilityProvider;
use App\Models\UtilityType;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Modules\Core\App\Services\CurrentTenant;

class UtilityBillController extends Controller
{
    public function index(CurrentTenant $currentTenant)
    {
        $tenant = $currentTenant->getOrFail();
        $this->authorize('viewAny', [UtilityBill::class, $tenant->id]);

        $bills = UtilityBill::query()
            ->with(['contract.occupant', 'room', 'property', 'utilityType', 'meter', 'provider'])
            ->where('tenant_id', $tenant->id)
            ->orderByDesc('billing_period_end')
            ->get();

        $contracts = Contract::query()
            ->with(['room.property', 'occupant'])
            ->where('tenant_id', $tenant->id)
            ->orderByDesc('created_at')
            ->get();

        $meters = UtilityMeter::query()
            ->with(['property', 'room', 'utilityType'])
            ->where('tenant_id', $tenant->id)
            ->orderBy('meter_code')
            ->get();

        $providers = UtilityProvider::query()
            ->where('tenant_id', $tenant->id)
            ->orderBy('name')
            ->get();

        $utilityTypes = UtilityType::query()
            ->where(function ($query) use ($tenant) {
                $query->whereNull('tenant_id')
                    ->orWhere('tenant_id', $tenant->id);
            })
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $readings = UtilityMeterReading::query()
            ->with('meter')
            ->where('tenant_id', $tenant->id)
            ->orderByDesc('reading_at')
            ->get();

        return view('core::dashboard.utilities.bills', compact('bills', 'contracts', 'meters', 'providers', 'utilityTypes', 'readings'));
    }

    public function store(Request $request, AuditLogger $auditLogger, CurrentTenant $currentTenant): RedirectResponse
    {
        $tenant = $currentTenant->getOrFail();
        $this->authorize('create', [UtilityBill::class, $tenant->id]);

        $validated = $request->validate([
            'contract_id' => [
                'required',
                Rule::exists('contracts', 'id')->where('tenant_id', $tenant->id),
            ],
            'utility_type_id' => [
                'required',
                Rule::exists('utility_types', 'id')->where(function ($query) use ($tenant) {
                    $query->whereNull('tenant_id')
                        ->orWhere('tenant_id', $tenant->id);
                }),
            ],
            'meter_id' => [
                'nullable',
                Rule::exists('utility_meters', 'id')->where('tenant_id', $tenant->id),
            ],
            'provider_id' => [
                'nullable',
                Rule::exists('utility_providers', 'id')->where('tenant_id', $tenant->id),
            ],
            'billing_period_start' => ['required', 'date'],
            'billing_period_end' => ['required', 'date', 'after_or_equal:billing_period_start'],
            'start_reading_id' => [
                'nullable',
                Rule::exists('utility_meter_readings', 'id')->where('tenant_id', $tenant->id),
            ],
            'end_reading_id' => [
                'nullable',
                Rule::exists('utility_meter_readings', 'id')->where('tenant_id', $tenant->id),
            ],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'tax' => ['nullable', 'numeric', 'min:0'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:draft,sent,paid,overdue,void'],
            'issued_at' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'paid_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $contract = Contract::query()->where('tenant_id', $tenant->id)->findOrFail($validated['contract_id']);
        $room = $contract->room;
        $property = $room?->property;

        $meter = null;
        if (!empty($validated['meter_id'])) {
            $meter = UtilityMeter::query()->where('tenant_id', $tenant->id)->findOrFail($validated['meter_id']);
            if ($property && (int) $meter->property_id !== (int) $property->id) {
                return back()->withErrors(['meter_id' => 'Selected meter does not belong to the contract property.']);
            }
            if ($room && $meter->room_id && (int) $meter->room_id !== (int) $room->id) {
                return back()->withErrors(['meter_id' => 'Selected meter does not belong to the contract room.']);
            }
        }

        $startReading = null;
        $endReading = null;
        if (!empty($validated['start_reading_id'])) {
            $startReading = UtilityMeterReading::query()->where('tenant_id', $tenant->id)->findOrFail($validated['start_reading_id']);
        }
        if (!empty($validated['end_reading_id'])) {
            $endReading = UtilityMeterReading::query()->where('tenant_id', $tenant->id)->findOrFail($validated['end_reading_id']);
        }
        if ($startReading && $endReading && $endReading->reading_value < $startReading->reading_value) {
            return back()->withErrors(['end_reading_id' => 'End reading must be greater than start reading.']);
        }
        if (($startReading || $endReading) && !$meter) {
            return back()->withErrors(['meter_id' => 'Select a meter when using readings.']);
        }
        if ($startReading && $meter && (int) $startReading->meter_id !== (int) $meter->id) {
            return back()->withErrors(['start_reading_id' => 'Start reading does not match the selected meter.']);
        }
        if ($endReading && $meter && (int) $endReading->meter_id !== (int) $meter->id) {
            return back()->withErrors(['end_reading_id' => 'End reading does not match the selected meter.']);
        }

        $usage = null;
        $unitCostCents = 0;
        $subtotalCents = 0;
        $taxCents = (int) round(((float) ($validated['tax'] ?? 0)) * 100);
        $totalCents = 0;

        if ($startReading && $endReading) {
            $usage = (float) $endReading->reading_value - (float) $startReading->reading_value;
            if (empty($validated['unit_cost'])) {
                return back()->withErrors(['unit_cost' => 'Unit cost is required when using meter readings.']);
            }
            $unitCostCents = (int) round(((float) $validated['unit_cost']) * 100);
            $subtotalCents = (int) round($usage * ((float) $validated['unit_cost']) * 100);
            $totalCents = $subtotalCents + $taxCents;
        } else {
            if (empty($validated['amount'])) {
                return back()->withErrors(['amount' => 'Amount is required when no readings are provided.']);
            }
            $subtotalCents = (int) round(((float) $validated['amount']) * 100);
            $totalCents = $subtotalCents + $taxCents;
            if (!empty($validated['unit_cost'])) {
                $unitCostCents = (int) round(((float) $validated['unit_cost']) * 100);
            }
        }

        $bill = DB::transaction(function () use ($validated, $tenant, $contract, $room, $property, $meter, $startReading, $endReading, $usage, $unitCostCents, $subtotalCents, $taxCents, $totalCents) {
            return UtilityBill::create([
                'tenant_id' => $tenant->id,
                'contract_id' => $contract->id,
                'property_id' => $property?->id,
                'room_id' => $room?->id,
                'utility_type_id' => $validated['utility_type_id'],
                'meter_id' => $meter?->id,
                'provider_id' => $validated['provider_id'] ?? $meter?->provider_id,
                'billing_period_start' => $validated['billing_period_start'],
                'billing_period_end' => $validated['billing_period_end'],
                'start_reading_id' => $startReading?->id,
                'end_reading_id' => $endReading?->id,
                'usage_amount' => $usage,
                'unit_cost_cents' => $unitCostCents,
                'subtotal_cents' => $subtotalCents,
                'tax_cents' => $taxCents,
                'total_cents' => $totalCents,
                'currency_code' => 'USD',
                'status' => $validated['status'],
                'issued_at' => $validated['issued_at'] ?? null,
                'due_date' => $validated['due_date'] ?? null,
                'paid_at' => $validated['paid_at'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);
        });

        $auditLogger->log('created', UtilityBill::class, (string) $bill->id, null, $bill->toArray(), $request);

        return back()->with('status', 'Utility bill created.');
    }

    public function update(Request $request, string $tenant, UtilityBill $bill, AuditLogger $auditLogger, CurrentTenant $currentTenant): RedirectResponse
    {
        $tenant = $currentTenant->getOrFail();
        $this->authorize('update', $bill);

        $validated = $request->validate([
            'contract_id' => [
                'required',
                Rule::exists('contracts', 'id')->where('tenant_id', $tenant->id),
            ],
            'utility_type_id' => [
                'required',
                Rule::exists('utility_types', 'id')->where(function ($query) use ($tenant) {
                    $query->whereNull('tenant_id')
                        ->orWhere('tenant_id', $tenant->id);
                }),
            ],
            'meter_id' => [
                'nullable',
                Rule::exists('utility_meters', 'id')->where('tenant_id', $tenant->id),
            ],
            'provider_id' => [
                'nullable',
                Rule::exists('utility_providers', 'id')->where('tenant_id', $tenant->id),
            ],
            'billing_period_start' => ['required', 'date'],
            'billing_period_end' => ['required', 'date', 'after_or_equal:billing_period_start'],
            'start_reading_id' => [
                'nullable',
                Rule::exists('utility_meter_readings', 'id')->where('tenant_id', $tenant->id),
            ],
            'end_reading_id' => [
                'nullable',
                Rule::exists('utility_meter_readings', 'id')->where('tenant_id', $tenant->id),
            ],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'tax' => ['nullable', 'numeric', 'min:0'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:draft,sent,paid,overdue,void'],
            'issued_at' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'paid_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $contract = Contract::query()->where('tenant_id', $tenant->id)->findOrFail($validated['contract_id']);
        $room = $contract->room;
        $property = $room?->property;

        $meter = null;
        if (!empty($validated['meter_id'])) {
            $meter = UtilityMeter::query()->where('tenant_id', $tenant->id)->findOrFail($validated['meter_id']);
            if ($property && (int) $meter->property_id !== (int) $property->id) {
                return back()->withErrors(['meter_id' => 'Selected meter does not belong to the contract property.']);
            }
            if ($room && $meter->room_id && (int) $meter->room_id !== (int) $room->id) {
                return back()->withErrors(['meter_id' => 'Selected meter does not belong to the contract room.']);
            }
        }

        $startReading = null;
        $endReading = null;
        if (!empty($validated['start_reading_id'])) {
            $startReading = UtilityMeterReading::query()->where('tenant_id', $tenant->id)->findOrFail($validated['start_reading_id']);
        }
        if (!empty($validated['end_reading_id'])) {
            $endReading = UtilityMeterReading::query()->where('tenant_id', $tenant->id)->findOrFail($validated['end_reading_id']);
        }
        if ($startReading && $endReading && $endReading->reading_value < $startReading->reading_value) {
            return back()->withErrors(['end_reading_id' => 'End reading must be greater than start reading.']);
        }
        if (($startReading || $endReading) && !$meter) {
            return back()->withErrors(['meter_id' => 'Select a meter when using readings.']);
        }
        if ($startReading && $meter && (int) $startReading->meter_id !== (int) $meter->id) {
            return back()->withErrors(['start_reading_id' => 'Start reading does not match the selected meter.']);
        }
        if ($endReading && $meter && (int) $endReading->meter_id !== (int) $meter->id) {
            return back()->withErrors(['end_reading_id' => 'End reading does not match the selected meter.']);
        }

        $usage = null;
        $unitCostCents = 0;
        $subtotalCents = 0;
        $taxCents = (int) round(((float) ($validated['tax'] ?? 0)) * 100);
        $totalCents = 0;

        if ($startReading && $endReading) {
            $usage = (float) $endReading->reading_value - (float) $startReading->reading_value;
            if (empty($validated['unit_cost'])) {
                return back()->withErrors(['unit_cost' => 'Unit cost is required when using meter readings.']);
            }
            $unitCostCents = (int) round(((float) $validated['unit_cost']) * 100);
            $subtotalCents = (int) round($usage * ((float) $validated['unit_cost']) * 100);
            $totalCents = $subtotalCents + $taxCents;
        } else {
            if (empty($validated['amount'])) {
                return back()->withErrors(['amount' => 'Amount is required when no readings are provided.']);
            }
            $subtotalCents = (int) round(((float) $validated['amount']) * 100);
            $totalCents = $subtotalCents + $taxCents;
            if (!empty($validated['unit_cost'])) {
                $unitCostCents = (int) round(((float) $validated['unit_cost']) * 100);
            }
        }

        $before = $bill->toArray();
        $bill->update([
            'contract_id' => $contract->id,
            'property_id' => $property?->id,
            'room_id' => $room?->id,
            'utility_type_id' => $validated['utility_type_id'],
            'meter_id' => $meter?->id,
            'provider_id' => $validated['provider_id'] ?? $meter?->provider_id,
            'billing_period_start' => $validated['billing_period_start'],
            'billing_period_end' => $validated['billing_period_end'],
            'start_reading_id' => $startReading?->id,
            'end_reading_id' => $endReading?->id,
            'usage_amount' => $usage,
            'unit_cost_cents' => $unitCostCents,
            'subtotal_cents' => $subtotalCents,
            'tax_cents' => $taxCents,
            'total_cents' => $totalCents,
            'currency_code' => 'USD',
            'status' => $validated['status'],
            'issued_at' => $validated['issued_at'] ?? null,
            'due_date' => $validated['due_date'] ?? null,
            'paid_at' => $validated['paid_at'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        $auditLogger->log('updated', UtilityBill::class, (string) $bill->id, $before, $bill->toArray(), $request);

        return back()->with('status', 'Utility bill updated.');
    }

    public function destroy(string $tenant, UtilityBill $bill, AuditLogger $auditLogger, CurrentTenant $currentTenant): RedirectResponse
    {
        $tenant = $currentTenant->getOrFail();
        $this->authorize('delete', $bill);

        $before = $bill->toArray();
        $bill->delete();

        $auditLogger->log('deleted', UtilityBill::class, (string) $bill->id, $before, null, request());

        return back()->with('status', 'Utility bill deleted.');
    }
}

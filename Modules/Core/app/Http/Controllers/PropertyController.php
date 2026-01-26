<?php

namespace Modules\Core\App\Http\Controllers;

use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Property;
use App\Models\PropertyType;
use App\Models\Room;
use App\Services\AuditLogger;
use App\Services\PlanGate;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PropertyController extends Controller
{
    public function index(PlanGate $planGate)
    {
        $tenant = auth()->user()->tenants()->firstOrFail();

        $properties = Property::query()
            ->with(['propertyType'])
            ->where('tenant_id', $tenant->id)
            ->orderByDesc('created_at')
            ->get();

        $propertyLimit = $planGate->tenantLimit($tenant, 'properties_max');
        $canCreateProperty = $planGate->canCreate($tenant, 'properties_max', $properties->count());

        $propertyTypes = PropertyType::query()
            ->where('status', 'active')
            ->where(function ($query) use ($tenant) {
                $query->whereNull('tenant_id')->orWhere('tenant_id', $tenant->id);
            })
            ->orderBy('name')
            ->get();

        return view('core::dashboard.properties', compact('properties', 'propertyTypes', 'propertyLimit', 'canCreateProperty'));
    }

    public function show(Property $property)
    {
        $tenant = auth()->user()->tenants()->firstOrFail();
        if ($property->tenant_id !== $tenant->id) {
            abort(404);
        }

        $property->load(['tenant.users', 'propertyType', 'rooms.roomType']);

        $rooms = Room::query()
            ->with(['roomType'])
            ->where('tenant_id', $tenant->id)
            ->where('property_id', $property->id)
            ->orderBy('room_number')
            ->get();

        $contracts = Contract::query()
            ->with(['occupant', 'room'])
            ->where('tenant_id', $tenant->id)
            ->whereIn('room_id', $rooms->pluck('id'))
            ->orderByDesc('start_date')
            ->get();

        $occupants = $contracts
            ->pluck('occupant')
            ->filter()
            ->unique('id')
            ->values();

        $tenantUsers = $property->tenant?->users ?? collect();

        $contractIds = $contracts->pluck('id');
        $invoiceTotals = Invoice::query()
            ->whereIn('contract_id', $contractIds)
            ->selectRaw('COALESCE(SUM(total_cents), 0) as total_cents, COALESCE(SUM(paid_cents), 0) as paid_cents')
            ->first();

        $totalCents = (int) ($invoiceTotals->total_cents ?? 0);
        $paidCents = (int) ($invoiceTotals->paid_cents ?? 0);
        $unpaidCents = max($totalCents - $paidCents, 0);

        $currentStart = Carbon::now()->startOfDay()->subDays(6);
        $currentEnd = Carbon::now()->endOfDay();
        $previousStart = $currentStart->copy()->subDays(7);
        $previousEnd = $currentStart->copy()->subDay();

        $currentTotal = (int) Invoice::query()
            ->whereIn('contract_id', $contractIds)
            ->whereBetween('issue_date', [$currentStart->toDateString(), $currentEnd->toDateString()])
            ->sum('total_cents');

        $previousTotal = (int) Invoice::query()
            ->whereIn('contract_id', $contractIds)
            ->whereBetween('issue_date', [$previousStart->toDateString(), $previousEnd->toDateString()])
            ->sum('total_cents');

        $percentChange = $previousTotal > 0
            ? round((($currentTotal - $previousTotal) / $previousTotal) * 100, 1)
            : 0;

        return view('core::dashboard.property-detail', compact(
            'property',
            'rooms',
            'contracts',
            'occupants',
            'tenantUsers',
            'totalCents',
            'paidCents',
            'unpaidCents',
            'percentChange'
        ));
    }

    public function store(Request $request, AuditLogger $auditLogger, PlanGate $planGate): RedirectResponse
    {
        $tenant = auth()->user()->tenants()->firstOrFail();
        $currentCount = Property::query()->where('tenant_id', $tenant->id)->count();
        if (!$planGate->canCreate($tenant, 'properties_max', $currentCount)) {
            return back()->withErrors(['plan' => 'Your plan limit does not allow more properties.']);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'property_type_id' => [
                'nullable',
                Rule::exists('property_types', 'id')->where(function ($query) use ($tenant) {
                    $query->whereNull('tenant_id')->orWhere('tenant_id', $tenant->id);
                }),
            ],
            'description' => ['nullable', 'string'],
            'address_line_1' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:64'],
            'status' => ['required', 'in:active,inactive,archived'],
        ]);

        $property = DB::transaction(function () use ($validated, $tenant) {
            return Property::create(array_merge($validated, ['tenant_id' => $tenant->id]));
        });

        $auditLogger->log('created', Property::class, (string) $property->id, null, $property->toArray(), $request);

        return back()->with('status', 'Property created.');
    }

    public function update(Request $request, Property $property, AuditLogger $auditLogger): RedirectResponse
    {
        $tenant = auth()->user()->tenants()->firstOrFail();
        if ($property->tenant_id !== $tenant->id) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'property_type_id' => [
                'nullable',
                Rule::exists('property_types', 'id')->where(function ($query) use ($tenant) {
                    $query->whereNull('tenant_id')->orWhere('tenant_id', $tenant->id);
                }),
            ],
            'description' => ['nullable', 'string'],
            'address_line_1' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:64'],
            'status' => ['required', 'in:active,inactive,archived'],
        ]);

        $before = $property->toArray();

        DB::transaction(function () use ($property, $validated) {
            $property->update($validated);
        });

        $auditLogger->log('updated', Property::class, (string) $property->id, $before, $property->toArray(), $request);

        return back()->with('status', 'Property updated.');
    }

    public function destroy(Property $property, AuditLogger $auditLogger): RedirectResponse
    {
        $tenant = auth()->user()->tenants()->firstOrFail();
        if ($property->tenant_id !== $tenant->id) {
            abort(404);
        }

        $before = $property->toArray();
        $property->delete();

        $auditLogger->log('deleted', Property::class, (string) $property->id, $before, null, request());

        return back()->with('status', 'Property deleted.');
    }
}

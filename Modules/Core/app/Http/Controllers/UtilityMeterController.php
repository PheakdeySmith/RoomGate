<?php

namespace Modules\Core\App\Http\Controllers;

use App\Models\Property;
use App\Models\Room;
use App\Models\UtilityMeter;
use App\Models\UtilityProvider;
use App\Models\UtilityType;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Validation\Rule;
use Modules\Core\App\Services\CurrentTenant;

class UtilityMeterController extends Controller
{
    public function index(CurrentTenant $currentTenant)
    {
        $tenant = $currentTenant->getOrFail();
        $this->authorize('viewAny', [UtilityMeter::class, $tenant->id]);

        $meters = UtilityMeter::query()
            ->with(['property', 'room', 'utilityType', 'provider'])
            ->where('tenant_id', $tenant->id)
            ->orderByDesc('created_at')
            ->get();

        $properties = Property::query()
            ->where('tenant_id', $tenant->id)
            ->orderBy('name')
            ->get();

        $rooms = Room::query()
            ->with('property')
            ->where('tenant_id', $tenant->id)
            ->orderBy('room_number')
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

        return view('core::dashboard.utilities.meters', compact('meters', 'properties', 'rooms', 'providers', 'utilityTypes'));
    }

    public function store(Request $request, AuditLogger $auditLogger, CurrentTenant $currentTenant): RedirectResponse
    {
        $tenant = $currentTenant->getOrFail();
        $this->authorize('create', [UtilityMeter::class, $tenant->id]);

        $validated = $request->validate([
            'property_id' => [
                'required',
                Rule::exists('properties', 'id')->where('tenant_id', $tenant->id),
            ],
            'room_id' => [
                'nullable',
                Rule::exists('rooms', 'id')->where('tenant_id', $tenant->id),
            ],
            'utility_type_id' => [
                'required',
                Rule::exists('utility_types', 'id')->where(function ($query) use ($tenant) {
                    $query->whereNull('tenant_id')
                        ->orWhere('tenant_id', $tenant->id);
                }),
            ],
            'provider_id' => [
                'nullable',
                Rule::exists('utility_providers', 'id')->where('tenant_id', $tenant->id),
            ],
            'meter_code' => ['required', 'string', 'max:64'],
            'unit_of_measure' => ['nullable', 'string', 'max:32'],
            'status' => ['required', 'in:active,inactive'],
            'installed_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        if (!empty($validated['room_id'])) {
            $room = Room::query()->where('tenant_id', $tenant->id)->findOrFail($validated['room_id']);
            if ((int) $room->property_id !== (int) $validated['property_id']) {
                return back()->withErrors(['room_id' => 'Selected room does not belong to the chosen property.']);
            }
        }

        $type = UtilityType::query()->findOrFail($validated['utility_type_id']);
        $unit = ($validated['unit_of_measure'] ?? null) ?: $type->unit_of_measure;

        $meter = UtilityMeter::create([
            'tenant_id' => $tenant->id,
            'property_id' => $validated['property_id'],
            'room_id' => $validated['room_id'] ?? null,
            'utility_type_id' => $validated['utility_type_id'],
            'provider_id' => $validated['provider_id'] ?? null,
            'meter_code' => $validated['meter_code'],
            'unit_of_measure' => $unit,
            'status' => $validated['status'],
            'installed_at' => $validated['installed_at'] ?? null,
        ]);

        $auditLogger->log('created', UtilityMeter::class, (string) $meter->id, null, $meter->toArray(), $request);

        return back()->with('status', 'Utility meter created.');
    }

    public function update(Request $request, string $tenant, UtilityMeter $meter, AuditLogger $auditLogger, CurrentTenant $currentTenant): RedirectResponse
    {
        $tenant = $currentTenant->getOrFail();
        $this->authorize('update', $meter);

        $validated = $request->validate([
            'property_id' => [
                'required',
                Rule::exists('properties', 'id')->where('tenant_id', $tenant->id),
            ],
            'room_id' => [
                'nullable',
                Rule::exists('rooms', 'id')->where('tenant_id', $tenant->id),
            ],
            'utility_type_id' => [
                'required',
                Rule::exists('utility_types', 'id')->where(function ($query) use ($tenant) {
                    $query->whereNull('tenant_id')
                        ->orWhere('tenant_id', $tenant->id);
                }),
            ],
            'provider_id' => [
                'nullable',
                Rule::exists('utility_providers', 'id')->where('tenant_id', $tenant->id),
            ],
            'meter_code' => ['required', 'string', 'max:64'],
            'unit_of_measure' => ['nullable', 'string', 'max:32'],
            'status' => ['required', 'in:active,inactive'],
            'installed_at' => ['nullable', 'date'],
        ]);

        if (!empty($validated['room_id'])) {
            $room = Room::query()->where('tenant_id', $tenant->id)->findOrFail($validated['room_id']);
            if ((int) $room->property_id !== (int) $validated['property_id']) {
                return back()->withErrors(['room_id' => 'Selected room does not belong to the chosen property.']);
            }
        }

        $type = UtilityType::query()->findOrFail($validated['utility_type_id']);
        $unit = ($validated['unit_of_measure'] ?? null) ?: $type->unit_of_measure;

        $before = $meter->toArray();
        $meter->update([
            'property_id' => $validated['property_id'],
            'room_id' => $validated['room_id'] ?? null,
            'utility_type_id' => $validated['utility_type_id'],
            'provider_id' => $validated['provider_id'] ?? null,
            'meter_code' => $validated['meter_code'],
            'unit_of_measure' => $unit,
            'status' => $validated['status'],
            'installed_at' => $validated['installed_at'] ?? null,
        ]);

        $auditLogger->log('updated', UtilityMeter::class, (string) $meter->id, $before, $meter->toArray(), $request);

        return back()->with('status', 'Utility meter updated.');
    }

    public function destroy(string $tenant, UtilityMeter $meter, AuditLogger $auditLogger, CurrentTenant $currentTenant): RedirectResponse
    {
        $tenant = $currentTenant->getOrFail();
        $this->authorize('delete', $meter);

        $before = $meter->toArray();
        $meter->delete();

        $auditLogger->log('deleted', UtilityMeter::class, (string) $meter->id, $before, null, request());

        return back()->with('status', 'Utility meter deleted.');
    }
}

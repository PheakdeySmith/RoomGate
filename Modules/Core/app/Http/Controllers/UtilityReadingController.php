<?php

namespace Modules\Core\App\Http\Controllers;

use App\Models\UtilityMeter;
use App\Models\UtilityMeterReading;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;

class UtilityReadingController extends Controller
{
    public function index()
    {
        $tenant = auth()->user()->tenants()->firstOrFail();

        $readings = UtilityMeterReading::query()
            ->with(['meter.property', 'meter.room', 'meter.utilityType'])
            ->where('tenant_id', $tenant->id)
            ->orderByDesc('reading_at')
            ->get();

        $meters = UtilityMeter::query()
            ->with(['property', 'room', 'utilityType'])
            ->where('tenant_id', $tenant->id)
            ->orderBy('meter_code')
            ->get();

        return view('core::dashboard.utilities.readings', compact('readings', 'meters'));
    }

    public function store(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $tenant = auth()->user()->tenants()->firstOrFail();

        $validated = $request->validate([
            'meter_id' => [
                'required',
                Rule::exists('utility_meters', 'id')->where('tenant_id', $tenant->id),
            ],
            'reading_value' => ['required', 'numeric', 'min:0'],
            'reading_at' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $reading = UtilityMeterReading::create([
            'tenant_id' => $tenant->id,
            'meter_id' => $validated['meter_id'],
            'reading_value' => $validated['reading_value'],
            'reading_at' => $validated['reading_at'],
            'recorded_by_user_id' => auth()->id(),
            'notes' => $validated['notes'] ?? null,
        ]);

        $meter = UtilityMeter::query()->find($validated['meter_id']);
        if ($meter) {
            $shouldUpdate = !$meter->last_reading_at || $reading->reading_at >= $meter->last_reading_at;
            if ($shouldUpdate) {
                $meter->update([
                    'last_reading_value' => $reading->reading_value,
                    'last_reading_at' => $reading->reading_at,
                ]);
            }
        }

        $auditLogger->log('created', UtilityMeterReading::class, (string) $reading->id, null, $reading->toArray(), $request);

        return back()->with('status', 'Meter reading added.');
    }

    public function update(Request $request, UtilityMeterReading $reading, AuditLogger $auditLogger): RedirectResponse
    {
        $tenant = auth()->user()->tenants()->firstOrFail();
        if ($reading->tenant_id !== $tenant->id) {
            abort(404);
        }

        $validated = $request->validate([
            'meter_id' => [
                'required',
                Rule::exists('utility_meters', 'id')->where('tenant_id', $tenant->id),
            ],
            'reading_value' => ['required', 'numeric', 'min:0'],
            'reading_at' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $before = $reading->toArray();
        $reading->update([
            'meter_id' => $validated['meter_id'],
            'reading_value' => $validated['reading_value'],
            'reading_at' => $validated['reading_at'],
            'notes' => $validated['notes'] ?? null,
        ]);

        $meter = UtilityMeter::query()->find($validated['meter_id']);
        if ($meter) {
            $latest = UtilityMeterReading::query()
                ->where('meter_id', $meter->id)
                ->orderByDesc('reading_at')
                ->first();
            if ($latest) {
                $meter->update([
                    'last_reading_value' => $latest->reading_value,
                    'last_reading_at' => $latest->reading_at,
                ]);
            }
        }

        $auditLogger->log('updated', UtilityMeterReading::class, (string) $reading->id, $before, $reading->toArray(), $request);

        return back()->with('status', 'Meter reading updated.');
    }

    public function destroy(UtilityMeterReading $reading, AuditLogger $auditLogger): RedirectResponse
    {
        $tenant = auth()->user()->tenants()->firstOrFail();
        if ($reading->tenant_id !== $tenant->id) {
            abort(404);
        }

        $before = $reading->toArray();
        $reading->delete();

        $auditLogger->log('deleted', UtilityMeterReading::class, (string) $reading->id, $before, null, request());

        return back()->with('status', 'Meter reading deleted.');
    }
}

<?php

namespace Modules\Core\App\Http\Controllers;

use App\Models\UtilityMeter;
use App\Models\UtilityMeterReading;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Modules\Core\App\Services\CurrentTenant;

class UtilityReadingController extends Controller
{
    public function index(CurrentTenant $currentTenant)
    {
        $tenant = $currentTenant->getOrFail();
        $this->authorize('viewAny', [UtilityMeterReading::class, $tenant->id]);

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

    public function store(Request $request, AuditLogger $auditLogger, CurrentTenant $currentTenant): RedirectResponse
    {
        $tenant = $currentTenant->getOrFail();
        $this->authorize('create', [UtilityMeterReading::class, $tenant->id]);

        $validated = $request->validate([
            'meter_id' => [
                'required',
                Rule::exists('utility_meters', 'id')->where('tenant_id', $tenant->id),
            ],
            'reading_value' => ['required', 'numeric', 'min:0'],
            'reading_at' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $reading = DB::transaction(function () use ($validated, $tenant) {
            $reading = UtilityMeterReading::create([
                'tenant_id' => $tenant->id,
                'meter_id' => $validated['meter_id'],
                'reading_value' => $validated['reading_value'],
                'reading_at' => $validated['reading_at'],
                'recorded_by_user_id' => auth()->id(),
                'notes' => $validated['notes'] ?? null,
            ]);

            $previous = UtilityMeterReading::query()
                ->where('tenant_id', $tenant->id)
                ->where('meter_id', $validated['meter_id'])
                ->where('id', '!=', $reading->id)
                ->where('reading_at', '<', $validated['reading_at'])
                ->orderByDesc('reading_at')
                ->first();

            $usageValue = $previous ? (float) $validated['reading_value'] - (float) $previous->reading_value : null;
            $reading->update(['usage_value' => $usageValue]);

            $next = UtilityMeterReading::query()
                ->where('tenant_id', $tenant->id)
                ->where('meter_id', $validated['meter_id'])
                ->where('reading_at', '>', $validated['reading_at'])
                ->orderBy('reading_at')
                ->first();

            if ($next) {
                $nextUsage = (float) $next->reading_value - (float) $validated['reading_value'];
                $next->update(['usage_value' => $nextUsage]);
            }

            return $reading;
        });

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

    public function update(Request $request, string $tenant, UtilityMeterReading $reading, AuditLogger $auditLogger, CurrentTenant $currentTenant): RedirectResponse
    {
        $tenant = $currentTenant->getOrFail();
        $this->authorize('update', $reading);

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
        $reading = DB::transaction(function () use ($validated, $reading, $tenant) {
            $reading->update([
                'meter_id' => $validated['meter_id'],
                'reading_value' => $validated['reading_value'],
                'reading_at' => $validated['reading_at'],
                'notes' => $validated['notes'] ?? null,
            ]);

            $previous = UtilityMeterReading::query()
                ->where('tenant_id', $tenant->id)
                ->where('meter_id', $validated['meter_id'])
                ->where('id', '!=', $reading->id)
                ->where('reading_at', '<', $validated['reading_at'])
                ->orderByDesc('reading_at')
                ->first();

            $usageValue = $previous ? (float) $validated['reading_value'] - (float) $previous->reading_value : null;
            $reading->update(['usage_value' => $usageValue]);

            $next = UtilityMeterReading::query()
                ->where('tenant_id', $tenant->id)
                ->where('meter_id', $validated['meter_id'])
                ->where('reading_at', '>', $validated['reading_at'])
                ->orderBy('reading_at')
                ->first();

            if ($next) {
                $nextUsage = (float) $next->reading_value - (float) $validated['reading_value'];
                $next->update(['usage_value' => $nextUsage]);
            }

            return $reading;
        });

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

    public function destroy(string $tenant, UtilityMeterReading $reading, AuditLogger $auditLogger, CurrentTenant $currentTenant): RedirectResponse
    {
        $tenant = $currentTenant->getOrFail();
        $this->authorize('delete', $reading);

        $before = $reading->toArray();
        DB::transaction(function () use ($reading, $tenant) {
            $next = UtilityMeterReading::query()
                ->where('tenant_id', $tenant->id)
                ->where('meter_id', $reading->meter_id)
                ->where('reading_at', '>', $reading->reading_at)
                ->orderBy('reading_at')
                ->first();

            $reading->delete();

            if ($next) {
                $previous = UtilityMeterReading::query()
                    ->where('tenant_id', $tenant->id)
                    ->where('meter_id', $next->meter_id)
                    ->where('reading_at', '<', $next->reading_at)
                    ->orderByDesc('reading_at')
                    ->first();
                $nextUsage = $previous ? (float) $next->reading_value - (float) $previous->reading_value : null;
                $next->update(['usage_value' => $nextUsage]);
            }
        });

        $auditLogger->log('deleted', UtilityMeterReading::class, (string) $reading->id, $before, null, request());

        return back()->with('status', 'Meter reading deleted.');
    }
}

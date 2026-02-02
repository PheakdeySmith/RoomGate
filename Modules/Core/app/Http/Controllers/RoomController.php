<?php

namespace Modules\Core\App\Http\Controllers;

use App\Models\Contract;
use App\Models\Property;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\UtilityMeter;
use App\Models\UtilityMeterReading;
use App\Services\AuditLogger;
use App\Services\PlanGate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Modules\Core\App\Services\CurrentTenant;
use Modules\Core\App\Services\RoomUtilitySetup;

class RoomController extends Controller
{
    public function index(PlanGate $planGate, CurrentTenant $currentTenant)
    {
        $tenant = $currentTenant->getOrFail();
        $this->authorize('viewAny', [Room::class, $tenant->id]);

        $rooms = Room::query()
            ->with(['property', 'roomType'])
            ->where('tenant_id', $tenant->id)
            ->orderByDesc('created_at')
            ->get();

        $roomLimit = $planGate->tenantLimit($tenant, 'rooms_max');
        $canCreateRoom = $planGate->canCreate($tenant, 'rooms_max', $rooms->count());

        $properties = Property::query()
            ->where('tenant_id', $tenant->id)
            ->orderBy('name')
            ->get();

        $roomTypes = RoomType::query()
            ->where('tenant_id', $tenant->id)
            ->orderBy('name')
            ->get();

        return view('core::dashboard.rooms', compact('rooms', 'properties', 'roomTypes', 'roomLimit', 'canCreateRoom'));
    }

    public function show(string $tenant, Room $room, CurrentTenant $currentTenant, RoomUtilitySetup $roomUtilitySetup)
    {
        $tenant = $currentTenant->getOrFail();
        $this->authorize('view', $room);

        $room->load(['property', 'roomType']);
        $roomUtilitySetup->ensureDefaultRoomMeters($room, $tenant->id);
        $roomMeters = UtilityMeter::query()
            ->with('utilityType')
            ->where('tenant_id', $tenant->id)
            ->where('room_id', $room->id)
            ->orderBy('utility_type_id')
            ->get();
        $meterStats = collect();
        $meterTrendSeries = collect();
        if ($roomMeters->isNotEmpty()) {
            $meterIds = $roomMeters->pluck('id')->all();
            $latestReadings = UtilityMeterReading::query()
                ->where('tenant_id', $tenant->id)
                ->whereIn('meter_id', $meterIds)
                ->orderByDesc('reading_at')
                ->orderByDesc('id')
                ->get()
                ->groupBy('meter_id');

            $meterStats = $latestReadings->map(function ($group) {
                return [
                    'latest' => $group->first(),
                ];
            });

            foreach ($meterIds as $meterId) {
                $series = UtilityMeterReading::query()
                    ->where('tenant_id', $tenant->id)
                    ->where('meter_id', $meterId)
                    ->orderByDesc('reading_at')
                    ->orderByDesc('id')
                    ->take(6)
                    ->get()
                    ->reverse()
                    ->pluck('usage_value')
                    ->map(fn ($value) => $value !== null ? (float) $value : 0)
                    ->values();

                $meterTrendSeries->put($meterId, $series);
            }
        }
        $roomReadings = UtilityMeterReading::query()
            ->with(['meter.utilityType'])
            ->where('tenant_id', $tenant->id)
            ->whereHas('meter', function ($query) use ($room) {
                $query->where('room_id', $room->id);
            })
            ->orderByDesc('reading_at')
            ->take(10)
            ->get();
        $activeContract = Contract::query()
            ->with('occupant')
            ->where('tenant_id', $tenant->id)
            ->where('room_id', $room->id)
            ->where('status', 'active')
            ->orderByDesc('start_date')
            ->first();

        return view('core::dashboard.room-detail', compact('room', 'activeContract', 'roomMeters', 'roomReadings', 'meterStats', 'meterTrendSeries'));
    }

    public function store(Request $request, AuditLogger $auditLogger, PlanGate $planGate, CurrentTenant $currentTenant, RoomUtilitySetup $roomUtilitySetup): RedirectResponse
    {
        $tenant = $currentTenant->getOrFail();
        $this->authorize('create', [Room::class, $tenant->id]);
        $currentCount = Room::query()->where('tenant_id', $tenant->id)->count();
        if (!$planGate->canCreate($tenant, 'rooms_max', $currentCount)) {
            return back()->withErrors(['plan' => 'Your plan limit does not allow more rooms.']);
        }

        $validated = $request->validate([
            'room_number' => ['required', 'string', 'max:255'],
            'property_id' => [
                'required',
                Rule::exists('properties', 'id')->where('tenant_id', $tenant->id),
            ],
            'room_type_id' => [
                'nullable',
                Rule::exists('room_types', 'id')->where('tenant_id', $tenant->id),
            ],
            'description' => ['nullable', 'string'],
            'size' => ['nullable', 'string', 'max:255'],
            'floor' => ['nullable', 'integer'],
            'max_occupants' => ['required', 'integer', 'min:1'],
            'monthly_rent' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:available,occupied,maintenance,inactive'],
        ]);

        $rentCents = (int) round(((float) ($validated['monthly_rent'] ?? 0)) * 100);
        unset($validated['monthly_rent']);

        $room = DB::transaction(function () use ($validated, $rentCents, $tenant, $roomUtilitySetup) {
            $payload = array_merge($validated, [
                'tenant_id' => $tenant->id,
                'monthly_rent_cents' => $rentCents,
                'currency_code' => 'USD',
            ]);

            $room = Room::create($payload);
            $roomUtilitySetup->ensureDefaultRoomMeters($room, $tenant->id);

            return $room;
        });

        $auditLogger->log('created', Room::class, (string) $room->id, null, $room->toArray(), $request);

        return back()->with('status', 'Room created.');
    }

    public function update(Request $request, string $tenant, Room $room, AuditLogger $auditLogger, CurrentTenant $currentTenant): RedirectResponse
    {
        $tenant = $currentTenant->getOrFail();
        $this->authorize('update', $room);

        $validated = $request->validate([
            'room_number' => ['required', 'string', 'max:255'],
            'property_id' => [
                'required',
                Rule::exists('properties', 'id')->where('tenant_id', $tenant->id),
            ],
            'room_type_id' => [
                'nullable',
                Rule::exists('room_types', 'id')->where('tenant_id', $tenant->id),
            ],
            'description' => ['nullable', 'string'],
            'size' => ['nullable', 'string', 'max:255'],
            'floor' => ['nullable', 'integer'],
            'max_occupants' => ['required', 'integer', 'min:1'],
            'monthly_rent' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:available,occupied,maintenance,inactive'],
        ]);

        $before = $room->toArray();
        $rentCents = (int) round(((float) ($validated['monthly_rent'] ?? 0)) * 100);
        unset($validated['monthly_rent']);

        DB::transaction(function () use ($room, $validated, $rentCents) {
            $room->update(array_merge($validated, [
                'monthly_rent_cents' => $rentCents,
                'currency_code' => 'USD',
            ]));
        });

        $auditLogger->log('updated', Room::class, (string) $room->id, $before, $room->toArray(), $request);

        return back()->with('status', 'Room updated.');
    }

    public function destroy(string $tenant, Room $room, AuditLogger $auditLogger, CurrentTenant $currentTenant): RedirectResponse
    {
        $tenant = $currentTenant->getOrFail();
        $this->authorize('delete', $room);

        $before = $room->toArray();
        $room->delete();

        $auditLogger->log('deleted', Room::class, (string) $room->id, $before, null, request());

        return back()->with('status', 'Room deleted.');
    }
}

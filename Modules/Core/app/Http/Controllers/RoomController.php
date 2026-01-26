<?php

namespace Modules\Core\App\Http\Controllers;

use App\Models\Contract;
use App\Models\Property;
use App\Models\Room;
use App\Models\RoomType;
use App\Services\AuditLogger;
use App\Services\PlanGate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RoomController extends Controller
{
    public function index(PlanGate $planGate)
    {
        $tenant = auth()->user()->tenants()->firstOrFail();

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

    public function show(Room $room)
    {
        $tenant = auth()->user()->tenants()->firstOrFail();
        if ($room->tenant_id !== $tenant->id) {
            abort(404);
        }

        $room->load(['property', 'roomType']);
        $activeContract = Contract::query()
            ->with('occupant')
            ->where('tenant_id', $tenant->id)
            ->where('room_id', $room->id)
            ->where('status', 'active')
            ->orderByDesc('start_date')
            ->first();

        return view('core::dashboard.room-detail', compact('room', 'activeContract'));
    }

    public function store(Request $request, AuditLogger $auditLogger, PlanGate $planGate): RedirectResponse
    {
        $tenant = auth()->user()->tenants()->firstOrFail();
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

        $room = DB::transaction(function () use ($validated, $rentCents, $tenant) {
            $payload = array_merge($validated, [
                'tenant_id' => $tenant->id,
                'monthly_rent_cents' => $rentCents,
                'currency_code' => 'USD',
            ]);

            return Room::create($payload);
        });

        $auditLogger->log('created', Room::class, (string) $room->id, null, $room->toArray(), $request);

        return back()->with('status', 'Room created.');
    }

    public function update(Request $request, Room $room, AuditLogger $auditLogger): RedirectResponse
    {
        $tenant = auth()->user()->tenants()->firstOrFail();
        if ($room->tenant_id !== $tenant->id) {
            abort(404);
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

    public function destroy(Room $room, AuditLogger $auditLogger): RedirectResponse
    {
        $tenant = auth()->user()->tenants()->firstOrFail();
        if ($room->tenant_id !== $tenant->id) {
            abort(404);
        }

        $before = $room->toArray();
        $room->delete();

        $auditLogger->log('deleted', Room::class, (string) $room->id, $before, null, request());

        return back()->with('status', 'Room deleted.');
    }
}

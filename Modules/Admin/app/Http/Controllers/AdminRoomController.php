<?php

namespace Modules\Admin\App\Http\Controllers;

use App\Models\Contract;
use App\Models\Property;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Tenant;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminRoomController extends Controller
{
    public function index()
    {
        $rooms = Room::query()
            ->with(['tenant', 'property', 'roomType'])
            ->orderByDesc('created_at')
            ->get();

        $tenants = Tenant::query()->orderBy('name')->get();
        $properties = Property::query()->orderBy('name')->get();
        $roomTypes = RoomType::query()->orderBy('name')->get();

        return view('admin::dashboard.rooms', compact('rooms', 'tenants', 'properties', 'roomTypes'));
    }

    public function show(Room $room)
    {
        $room->load(['tenant', 'property', 'roomType']);

        $contracts = Contract::query()
            ->with(['occupant'])
            ->where('room_id', $room->id)
            ->orderByDesc('start_date')
            ->get();

        $activeContract = $contracts->firstWhere('status', 'active');

        return view('admin::dashboard.room-detail', compact('room', 'contracts', 'activeContract'));
    }

    public function store(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'exists:tenants,id'],
            'property_id' => ['required', 'exists:properties,id'],
            'room_type_id' => [
                'nullable',
                Rule::exists('room_types', 'id'),
            ],
            'room_number' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'size' => ['nullable', 'string', 'max:255'],
            'floor' => ['nullable', 'integer'],
            'max_occupants' => ['required', 'integer', 'min:1'],
            'monthly_rent' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:available,occupied,maintenance,inactive'],
        ]);

        $property = Property::find($validated['property_id']);
        if (!$property) {
            throw ValidationException::withMessages(['property_id' => 'Selected property is invalid.']);
        }
        if ((int) $property->tenant_id !== (int) $validated['tenant_id']) {
            throw ValidationException::withMessages(['property_id' => 'Property does not belong to the selected tenant.']);
        }

        if (!empty($validated['room_type_id'])) {
            $roomTypeMatch = RoomType::query()
                ->where('id', $validated['room_type_id'])
                ->where('tenant_id', $validated['tenant_id'])
                ->exists();
            if (!$roomTypeMatch) {
                throw ValidationException::withMessages(['room_type_id' => 'Room type does not belong to the property tenant.']);
            }
        }

        $rentCents = (int) round(((float) ($validated['monthly_rent'] ?? 0)) * 100);
        unset($validated['monthly_rent']);

        $room = DB::transaction(function () use ($validated, $rentCents) {
            $validated['monthly_rent_cents'] = $rentCents;
            $validated['currency_code'] = 'USD';

            return Room::create($validated);
        });

        $auditLogger->log('created', Room::class, (string) $room->id, null, $room->toArray(), $request);

        return back()->with('status', 'Room created.');
    }

    public function update(Request $request, Room $room, AuditLogger $auditLogger): RedirectResponse
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'exists:tenants,id'],
            'property_id' => ['required', 'exists:properties,id'],
            'room_type_id' => [
                'nullable',
                Rule::exists('room_types', 'id'),
            ],
            'room_number' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'size' => ['nullable', 'string', 'max:255'],
            'floor' => ['nullable', 'integer'],
            'max_occupants' => ['required', 'integer', 'min:1'],
            'monthly_rent' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:available,occupied,maintenance,inactive'],
        ]);

        $property = Property::find($validated['property_id']);
        if (!$property) {
            throw ValidationException::withMessages(['property_id' => 'Selected property is invalid.']);
        }
        if ((int) $property->tenant_id !== (int) $validated['tenant_id']) {
            throw ValidationException::withMessages(['property_id' => 'Property does not belong to the selected tenant.']);
        }

        if (!empty($validated['room_type_id'])) {
            $roomTypeMatch = RoomType::query()
                ->where('id', $validated['room_type_id'])
                ->where('tenant_id', $validated['tenant_id'])
                ->exists();
            if (!$roomTypeMatch) {
                throw ValidationException::withMessages(['room_type_id' => 'Room type does not belong to the property tenant.']);
            }
        }

        $before = $room->toArray();
        $rentCents = (int) round(((float) ($validated['monthly_rent'] ?? 0)) * 100);
        unset($validated['monthly_rent']);

        DB::transaction(function () use ($room, $validated, $rentCents) {
            $validated['monthly_rent_cents'] = $rentCents;
            $validated['currency_code'] = 'USD';

            $room->update($validated);
        });

        $auditLogger->log('updated', Room::class, (string) $room->id, $before, $room->toArray(), $request);

        return back()->with('status', 'Room updated.');
    }

    public function destroy(Room $room, AuditLogger $auditLogger): RedirectResponse
    {
        $before = $room->toArray();
        $room->delete();

        $auditLogger->log('deleted', Room::class, (string) $room->id, $before, null, request());

        return back()->with('status', 'Room deleted.');
    }
}

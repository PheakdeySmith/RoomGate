<?php

namespace Modules\Admin\App\Http\Controllers;

use App\Models\Amenity;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Tenant;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class AdminAmenityController extends Controller
{
    public function index()
    {
        $amenities = Amenity::query()
            ->with(['tenant', 'rooms', 'roomTypes'])
            ->orderByDesc('created_at')
            ->get();

        $tenants = Tenant::query()->orderBy('name')->get();
        $rooms = Room::query()->orderBy('room_number')->get();
        $roomTypes = RoomType::query()->orderBy('name')->get();

        return view('admin::dashboard.amenities', compact('amenities', 'tenants', 'rooms', 'roomTypes'));
    }

    public function store(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'exists:tenants,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:active,inactive'],
            'room_ids' => ['array'],
            'room_ids.*' => ['integer', 'exists:rooms,id'],
            'room_type_ids' => ['array'],
            'room_type_ids.*' => ['integer', 'exists:room_types,id'],
        ]);

        $priceCents = (int) round(((float) ($validated['price'] ?? 0)) * 100);
        unset($validated['price']);

        $roomIds = $validated['room_ids'] ?? [];
        $roomTypeIds = $validated['room_type_ids'] ?? [];
        unset($validated['room_ids'], $validated['room_type_ids']);

        $amenity = DB::transaction(function () use ($validated, $priceCents, $roomIds, $roomTypeIds) {
            $validated['price_cents'] = $priceCents;
            $validated['currency_code'] = 'USD';
            $amenity = Amenity::create($validated);

            if ($roomIds) {
                $sync = [];
                foreach ($roomIds as $roomId) {
                    $sync[$roomId] = ['tenant_id' => $validated['tenant_id']];
                }
                $amenity->rooms()->sync($sync);
            }

            if ($roomTypeIds) {
                $sync = [];
                foreach ($roomTypeIds as $roomTypeId) {
                    $sync[$roomTypeId] = ['tenant_id' => $validated['tenant_id']];
                }
                $amenity->roomTypes()->sync($sync);
            }

            return $amenity;
        });

        $auditLogger->log('created', Amenity::class, (string) $amenity->id, null, $amenity->toArray(), $request);

        return back()->with('status', 'Amenity created.');
    }

    public function update(Request $request, Amenity $amenity, AuditLogger $auditLogger): RedirectResponse
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'exists:tenants,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:active,inactive'],
            'room_ids' => ['array'],
            'room_ids.*' => ['integer', 'exists:rooms,id'],
            'room_type_ids' => ['array'],
            'room_type_ids.*' => ['integer', 'exists:room_types,id'],
        ]);

        $before = $amenity->toArray();
        $priceCents = (int) round(((float) ($validated['price'] ?? 0)) * 100);
        unset($validated['price']);

        $roomIds = $validated['room_ids'] ?? [];
        $roomTypeIds = $validated['room_type_ids'] ?? [];
        unset($validated['room_ids'], $validated['room_type_ids']);

        DB::transaction(function () use ($amenity, $validated, $priceCents, $roomIds, $roomTypeIds) {
            $validated['price_cents'] = $priceCents;
            $validated['currency_code'] = 'USD';
            $amenity->update($validated);

            $roomSync = [];
            foreach ($roomIds as $roomId) {
                $roomSync[$roomId] = ['tenant_id' => $validated['tenant_id']];
            }
            $amenity->rooms()->sync($roomSync);

            $roomTypeSync = [];
            foreach ($roomTypeIds as $roomTypeId) {
                $roomTypeSync[$roomTypeId] = ['tenant_id' => $validated['tenant_id']];
            }
            $amenity->roomTypes()->sync($roomTypeSync);
        });

        $auditLogger->log('updated', Amenity::class, (string) $amenity->id, $before, $amenity->toArray(), $request);

        return back()->with('status', 'Amenity updated.');
    }

    public function destroy(Amenity $amenity, AuditLogger $auditLogger): RedirectResponse
    {
        $before = $amenity->toArray();
        $amenity->delete();

        $auditLogger->log('deleted', Amenity::class, (string) $amenity->id, $before, null, request());

        return back()->with('status', 'Amenity deleted.');
    }
}

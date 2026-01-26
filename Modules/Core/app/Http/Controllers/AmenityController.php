<?php

namespace Modules\Core\App\Http\Controllers;

use App\Models\Amenity;
use App\Models\Room;
use App\Models\RoomType;
use App\Services\AuditLogger;
use App\Services\PlanGate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AmenityController extends Controller
{
    public function index(PlanGate $planGate)
    {
        $tenant = auth()->user()->tenants()->firstOrFail();

        $amenities = Amenity::query()
            ->with(['rooms', 'roomTypes'])
            ->where('tenant_id', $tenant->id)
            ->orderByDesc('created_at')
            ->get();

        $amenityLimit = $planGate->tenantLimit($tenant, 'amenities_max');
        $canCreateAmenity = $planGate->canCreate($tenant, 'amenities_max', $amenities->count());

        $rooms = Room::query()
            ->where('tenant_id', $tenant->id)
            ->orderBy('room_number')
            ->get();

        $roomTypes = RoomType::query()
            ->where('tenant_id', $tenant->id)
            ->orderBy('name')
            ->get();

        return view('core::dashboard.amenities', compact('amenities', 'rooms', 'roomTypes', 'amenityLimit', 'canCreateAmenity'));
    }

    public function store(Request $request, AuditLogger $auditLogger, PlanGate $planGate): RedirectResponse
    {
        $tenant = auth()->user()->tenants()->firstOrFail();
        $currentCount = Amenity::query()->where('tenant_id', $tenant->id)->count();
        if (!$planGate->canCreate($tenant, 'amenities_max', $currentCount)) {
            return back()->withErrors(['plan' => 'Your plan limit does not allow more amenities.']);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:active,inactive'],
            'room_ids' => ['array'],
            'room_ids.*' => [
                'integer',
                Rule::exists('rooms', 'id')->where('tenant_id', $tenant->id),
            ],
            'room_type_ids' => ['array'],
            'room_type_ids.*' => [
                'integer',
                Rule::exists('room_types', 'id')->where('tenant_id', $tenant->id),
            ],
        ]);

        $priceCents = (int) round(((float) ($validated['price'] ?? 0)) * 100);
        unset($validated['price']);

        $roomIds = $validated['room_ids'] ?? [];
        $roomTypeIds = $validated['room_type_ids'] ?? [];
        unset($validated['room_ids'], $validated['room_type_ids']);

        $amenity = DB::transaction(function () use ($validated, $priceCents, $roomIds, $roomTypeIds, $tenant) {
            $amenity = Amenity::create(array_merge($validated, [
                'tenant_id' => $tenant->id,
                'price_cents' => $priceCents,
                'currency_code' => 'USD',
            ]));

            if ($roomIds) {
                $sync = [];
                foreach ($roomIds as $roomId) {
                    $sync[$roomId] = ['tenant_id' => $tenant->id];
                }
                $amenity->rooms()->sync($sync);
            }

            if ($roomTypeIds) {
                $sync = [];
                foreach ($roomTypeIds as $roomTypeId) {
                    $sync[$roomTypeId] = ['tenant_id' => $tenant->id];
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
        $tenant = auth()->user()->tenants()->firstOrFail();
        if ($amenity->tenant_id !== $tenant->id) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:active,inactive'],
            'room_ids' => ['array'],
            'room_ids.*' => [
                'integer',
                Rule::exists('rooms', 'id')->where('tenant_id', $tenant->id),
            ],
            'room_type_ids' => ['array'],
            'room_type_ids.*' => [
                'integer',
                Rule::exists('room_types', 'id')->where('tenant_id', $tenant->id),
            ],
        ]);

        $before = $amenity->toArray();
        $priceCents = (int) round(((float) ($validated['price'] ?? 0)) * 100);
        unset($validated['price']);

        $roomIds = $validated['room_ids'] ?? [];
        $roomTypeIds = $validated['room_type_ids'] ?? [];
        unset($validated['room_ids'], $validated['room_type_ids']);

        DB::transaction(function () use ($amenity, $validated, $priceCents, $roomIds, $roomTypeIds, $tenant) {
            $amenity->update(array_merge($validated, [
                'price_cents' => $priceCents,
                'currency_code' => 'USD',
            ]));

            $roomSync = [];
            foreach ($roomIds as $roomId) {
                $roomSync[$roomId] = ['tenant_id' => $tenant->id];
            }
            $amenity->rooms()->sync($roomSync);

            $roomTypeSync = [];
            foreach ($roomTypeIds as $roomTypeId) {
                $roomTypeSync[$roomTypeId] = ['tenant_id' => $tenant->id];
            }
            $amenity->roomTypes()->sync($roomTypeSync);
        });

        $auditLogger->log('updated', Amenity::class, (string) $amenity->id, $before, $amenity->toArray(), $request);

        return back()->with('status', 'Amenity updated.');
    }

    public function destroy(Amenity $amenity, AuditLogger $auditLogger): RedirectResponse
    {
        $tenant = auth()->user()->tenants()->firstOrFail();
        if ($amenity->tenant_id !== $tenant->id) {
            abort(404);
        }

        $before = $amenity->toArray();
        $amenity->delete();

        $auditLogger->log('deleted', Amenity::class, (string) $amenity->id, $before, null, request());

        return back()->with('status', 'Amenity deleted.');
    }
}

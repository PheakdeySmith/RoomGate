<?php

namespace Modules\Admin\App\Http\Controllers;

use App\Models\RoomType;
use App\Models\Tenant;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class AdminRoomTypeController extends Controller
{
    public function index()
    {
        $roomTypes = RoomType::query()
            ->with('tenant')
            ->orderByDesc('created_at')
            ->get();

        $tenants = Tenant::query()->orderBy('name')->get();

        return view('admin::dashboard.room-types', compact('roomTypes', 'tenants'));
    }

    public function store(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'exists:tenants,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $roomType = DB::transaction(function () use ($validated) {
            return RoomType::create($validated);
        });

        $auditLogger->log('created', RoomType::class, (string) $roomType->id, null, $roomType->toArray(), $request);

        return back()->with('status', 'Room type created.');
    }

    public function update(Request $request, RoomType $roomType, AuditLogger $auditLogger): RedirectResponse
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'exists:tenants,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $before = $roomType->toArray();

        DB::transaction(function () use ($roomType, $validated) {
            $roomType->update($validated);
        });

        $auditLogger->log('updated', RoomType::class, (string) $roomType->id, $before, $roomType->toArray(), $request);

        return back()->with('status', 'Room type updated.');
    }

    public function destroy(RoomType $roomType, AuditLogger $auditLogger): RedirectResponse
    {
        $before = $roomType->toArray();
        $roomType->delete();

        $auditLogger->log('deleted', RoomType::class, (string) $roomType->id, $before, null, request());

        return back()->with('status', 'Room type deleted.');
    }
}

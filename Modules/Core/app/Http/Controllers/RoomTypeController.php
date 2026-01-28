<?php

namespace Modules\Core\App\Http\Controllers;

use App\Models\RoomType;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Core\App\Services\CurrentTenant;

class RoomTypeController extends Controller
{
    public function index(CurrentTenant $currentTenant)
    {
        $tenant = $currentTenant->getOrFail();
        $this->authorize('viewAny', [RoomType::class, $tenant->id]);

        $roomTypes = RoomType::query()
            ->where('tenant_id', $tenant->id)
            ->orderByDesc('created_at')
            ->get();

        return view('core::dashboard.room-types', compact('roomTypes'));
    }

    public function store(Request $request, AuditLogger $auditLogger, CurrentTenant $currentTenant): RedirectResponse
    {
        $tenant = $currentTenant->getOrFail();
        $this->authorize('create', [RoomType::class, $tenant->id]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $roomType = DB::transaction(function () use ($validated, $tenant) {
            return RoomType::create(array_merge($validated, ['tenant_id' => $tenant->id]));
        });

        $auditLogger->log('created', RoomType::class, (string) $roomType->id, null, $roomType->toArray(), $request);

        return back()->with('status', 'Room type created.');
    }

    public function update(Request $request, string $tenant, RoomType $roomType, AuditLogger $auditLogger, CurrentTenant $currentTenant): RedirectResponse
    {
        $tenant = $currentTenant->getOrFail();
        $this->authorize('update', $roomType);

        $validated = $request->validate([
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

    public function destroy(string $tenant, RoomType $roomType, AuditLogger $auditLogger, CurrentTenant $currentTenant): RedirectResponse
    {
        $tenant = $currentTenant->getOrFail();
        $this->authorize('delete', $roomType);

        $before = $roomType->toArray();
        $roomType->delete();

        $auditLogger->log('deleted', RoomType::class, (string) $roomType->id, $before, null, request());

        return back()->with('status', 'Room type deleted.');
    }
}

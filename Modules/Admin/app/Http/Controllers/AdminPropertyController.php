<?php

namespace Modules\Admin\App\Http\Controllers;

use App\Models\Property;
use App\Models\Tenant;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class AdminPropertyController extends Controller
{
    public function index()
    {
        $properties = Property::query()
            ->with('tenant')
            ->orderByDesc('created_at')
            ->get();

        $tenants = Tenant::query()->orderBy('name')->get();

        return view('admin::dashboard.properties', compact('properties', 'tenants'));
    }

    public function store(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'exists:tenants,id'],
            'name' => ['required', 'string', 'max:255'],
            'property_type' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'address_line_1' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:64'],
            'status' => ['required', 'in:active,inactive,archived'],
        ]);

        $property = DB::transaction(function () use ($validated) {
            return Property::create($validated);
        });

        $auditLogger->log('created', Property::class, (string) $property->id, null, $property->toArray(), $request);

        return back()->with('status', 'Property created.');
    }

    public function update(Request $request, Property $property, AuditLogger $auditLogger): RedirectResponse
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'exists:tenants,id'],
            'name' => ['required', 'string', 'max:255'],
            'property_type' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'address_line_1' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:64'],
            'status' => ['required', 'in:active,inactive,archived'],
        ]);

        $before = $property->toArray();

        DB::transaction(function () use ($property, $validated) {
            $property->update($validated);
        });

        $auditLogger->log('updated', Property::class, (string) $property->id, $before, $property->toArray(), $request);

        return back()->with('status', 'Property updated.');
    }

    public function destroy(Property $property, AuditLogger $auditLogger): RedirectResponse
    {
        $before = $property->toArray();
        $property->delete();

        $auditLogger->log('deleted', Property::class, (string) $property->id, $before, null, request());

        return back()->with('status', 'Property deleted.');
    }
}

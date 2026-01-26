<?php

namespace Modules\Core\App\Http\Controllers;

use App\Models\Property;
use App\Models\UtilityRate;
use App\Models\UtilityType;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;

class UtilityRateController extends Controller
{
    public function index()
    {
        $tenant = auth()->user()->tenants()->firstOrFail();

        $rates = UtilityRate::query()
            ->with(['property', 'utilityType'])
            ->where('tenant_id', $tenant->id)
            ->orderByDesc('effective_from')
            ->get();

        $properties = Property::query()
            ->where('tenant_id', $tenant->id)
            ->orderBy('name')
            ->get();

        $utilityTypes = UtilityType::query()
            ->where(function ($query) use ($tenant) {
                $query->whereNull('tenant_id')
                    ->orWhere('tenant_id', $tenant->id);
            })
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('core::dashboard.utilities.rates', compact('rates', 'properties', 'utilityTypes'));
    }

    public function store(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $tenant = auth()->user()->tenants()->firstOrFail();

        $validated = $request->validate([
            'property_id' => [
                'nullable',
                Rule::exists('properties', 'id')->where('tenant_id', $tenant->id),
            ],
            'utility_type_id' => [
                'required',
                Rule::exists('utility_types', 'id')->where(function ($query) use ($tenant) {
                    $query->whereNull('tenant_id')
                        ->orWhere('tenant_id', $tenant->id);
                }),
            ],
            'rate' => ['required', 'numeric', 'min:0'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
        ]);

        $rateCents = (int) round(((float) $validated['rate']) * 100);

        $rate = UtilityRate::create([
            'tenant_id' => $tenant->id,
            'property_id' => $validated['property_id'] ?? null,
            'utility_type_id' => $validated['utility_type_id'],
            'rate_cents' => $rateCents,
            'currency_code' => 'USD',
            'effective_from' => $validated['effective_from'],
            'effective_to' => $validated['effective_to'] ?? null,
        ]);

        $auditLogger->log('created', UtilityRate::class, (string) $rate->id, null, $rate->toArray(), $request);

        return back()->with('status', 'Utility rate created.');
    }

    public function update(Request $request, UtilityRate $rate, AuditLogger $auditLogger): RedirectResponse
    {
        $tenant = auth()->user()->tenants()->firstOrFail();
        if ($rate->tenant_id !== $tenant->id) {
            abort(404);
        }

        $validated = $request->validate([
            'property_id' => [
                'nullable',
                Rule::exists('properties', 'id')->where('tenant_id', $tenant->id),
            ],
            'utility_type_id' => [
                'required',
                Rule::exists('utility_types', 'id')->where(function ($query) use ($tenant) {
                    $query->whereNull('tenant_id')
                        ->orWhere('tenant_id', $tenant->id);
                }),
            ],
            'rate' => ['required', 'numeric', 'min:0'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
        ]);

        $before = $rate->toArray();
        $rate->update([
            'property_id' => $validated['property_id'] ?? null,
            'utility_type_id' => $validated['utility_type_id'],
            'rate_cents' => (int) round(((float) $validated['rate']) * 100),
            'currency_code' => 'USD',
            'effective_from' => $validated['effective_from'],
            'effective_to' => $validated['effective_to'] ?? null,
        ]);

        $auditLogger->log('updated', UtilityRate::class, (string) $rate->id, $before, $rate->toArray(), $request);

        return back()->with('status', 'Utility rate updated.');
    }

    public function destroy(UtilityRate $rate, AuditLogger $auditLogger): RedirectResponse
    {
        $tenant = auth()->user()->tenants()->firstOrFail();
        if ($rate->tenant_id !== $tenant->id) {
            abort(404);
        }

        $before = $rate->toArray();
        $rate->delete();

        $auditLogger->log('deleted', UtilityRate::class, (string) $rate->id, $before, null, request());

        return back()->with('status', 'Utility rate deleted.');
    }
}

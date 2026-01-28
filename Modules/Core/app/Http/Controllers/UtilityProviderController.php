<?php

namespace Modules\Core\App\Http\Controllers;

use App\Models\UtilityProvider;
use App\Models\UtilityType;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Validation\Rule;
use Modules\Core\App\Services\CurrentTenant;

class UtilityProviderController extends Controller
{
    public function index(CurrentTenant $currentTenant)
    {
        $tenant = $currentTenant->getOrFail();
        $this->authorize('viewAny', [UtilityProvider::class, $tenant->id]);

        $providers = UtilityProvider::query()
            ->with('utilityType')
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

        return view('core::dashboard.utilities.providers', compact('providers', 'utilityTypes'));
    }

    public function store(Request $request, AuditLogger $auditLogger, CurrentTenant $currentTenant): RedirectResponse
    {
        $tenant = $currentTenant->getOrFail();
        $this->authorize('create', [UtilityProvider::class, $tenant->id]);

        $validated = $request->validate([
            'utility_type_id' => [
                'required',
                Rule::exists('utility_types', 'id')->where(function ($query) use ($tenant) {
                    $query->whereNull('tenant_id')
                        ->orWhere('tenant_id', $tenant->id);
                }),
            ],
            'name' => ['required', 'string', 'max:255'],
            'account_number' => ['nullable', 'string', 'max:100'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'status' => ['required', 'in:active,inactive'],
            'notes' => ['nullable', 'string'],
        ]);

        $provider = UtilityProvider::create(array_merge($validated, [
            'tenant_id' => $tenant->id,
        ]));

        $auditLogger->log('created', UtilityProvider::class, (string) $provider->id, null, $provider->toArray(), $request);

        return back()->with('status', 'Utility provider created.');
    }

    public function update(Request $request, string $tenant, UtilityProvider $provider, AuditLogger $auditLogger, CurrentTenant $currentTenant): RedirectResponse
    {
        $tenant = $currentTenant->getOrFail();
        $this->authorize('update', $provider);

        $validated = $request->validate([
            'utility_type_id' => [
                'required',
                Rule::exists('utility_types', 'id')->where(function ($query) use ($tenant) {
                    $query->whereNull('tenant_id')
                        ->orWhere('tenant_id', $tenant->id);
                }),
            ],
            'name' => ['required', 'string', 'max:255'],
            'account_number' => ['nullable', 'string', 'max:100'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'status' => ['required', 'in:active,inactive'],
            'notes' => ['nullable', 'string'],
        ]);

        $before = $provider->toArray();
        $provider->update($validated);

        $auditLogger->log('updated', UtilityProvider::class, (string) $provider->id, $before, $provider->toArray(), $request);

        return back()->with('status', 'Utility provider updated.');
    }

    public function destroy(string $tenant, UtilityProvider $provider, AuditLogger $auditLogger, CurrentTenant $currentTenant): RedirectResponse
    {
        $tenant = $currentTenant->getOrFail();
        $this->authorize('delete', $provider);

        $before = $provider->toArray();
        $provider->delete();

        $auditLogger->log('deleted', UtilityProvider::class, (string) $provider->id, $before, null, request());

        return back()->with('status', 'Utility provider deleted.');
    }
}

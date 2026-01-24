<?php

namespace Modules\Admin\App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Property;
use App\Models\Room;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class AdminInvoiceController extends Controller
{
    public function index(Request $request)
    {
        $invoicesQuery = Invoice::query()
            ->with(['contract.room.property', 'contract.occupant', 'tenant'])
            ->orderByDesc('issue_date');

        if ($request->filled('tenant_id')) {
            $invoicesQuery->where('tenant_id', $request->input('tenant_id'));
        }

        if ($request->filled('property_id')) {
            $invoicesQuery->whereHas('contract.room', function ($query) use ($request) {
                $query->where('property_id', $request->input('property_id'));
            });
        }

        if ($request->filled('room_id')) {
            $invoicesQuery->whereHas('contract.room', function ($query) use ($request) {
                $query->where('id', $request->input('room_id'));
            });
        }

        if ($request->filled('status')) {
            $invoicesQuery->where('status', $request->input('status'));
        }

        $invoices = $invoicesQuery->get();

        $tenants = Tenant::query()->orderBy('name')->get();
        $properties = Property::query()->orderBy('name')->get();
        $rooms = Room::query()->orderBy('room_number')->get();

        return view('admin::dashboard.invoices', compact('invoices', 'tenants', 'properties', 'rooms'));
    }
}

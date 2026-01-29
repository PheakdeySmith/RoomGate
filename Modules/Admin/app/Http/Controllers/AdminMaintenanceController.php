<?php

namespace Modules\Admin\App\Http\Controllers;

use App\Models\MaintenanceRequest;
use Illuminate\Routing\Controller;

class AdminMaintenanceController extends Controller
{
    public function index()
    {
        $requests = MaintenanceRequest::query()
            ->with(['tenant', 'createdBy', 'assignedTo', 'property', 'room'])
            ->orderByDesc('requested_at')
            ->limit(500)
            ->get();

        return view('admin::dashboard.maintenance', compact('requests'));
    }
}

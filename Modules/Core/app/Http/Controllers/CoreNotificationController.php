<?php

namespace Modules\Core\App\Http\Controllers;

use App\Models\InAppNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\Core\App\Services\CurrentTenant;

class CoreNotificationController extends Controller
{
    public function index(Request $request, CurrentTenant $currentTenant)
    {
        $tenant = $currentTenant->getOrFail();
        $user = $request->user();

        $notifications = InAppNotification::query()
            ->where('user_id', $user->id)
            ->when($tenant, function ($query) use ($tenant) {
                $query->where(function ($sub) use ($tenant) {
                    $sub->whereNull('tenant_id')->orWhere('tenant_id', $tenant->id);
                });
            })
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('core::dashboard.notifications', compact('notifications'));
    }

    public function markAllRead(Request $request, CurrentTenant $currentTenant): RedirectResponse
    {
        $tenant = $currentTenant->getOrFail();
        $user = $request->user();

        InAppNotification::query()
            ->where('user_id', $user->id)
            ->when($tenant, function ($query) use ($tenant) {
                $query->where(function ($sub) use ($tenant) {
                    $sub->whereNull('tenant_id')->orWhere('tenant_id', $tenant->id);
                });
            })
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return back()->with('status', 'Notifications marked as read.');
    }

    public function markRead(Request $request, InAppNotification $notification, CurrentTenant $currentTenant): RedirectResponse
    {
        $tenant = $currentTenant->getOrFail();
        $user = $request->user();

        if ($notification->user_id !== $user->id) {
            abort(403);
        }

        if ($notification->tenant_id && (int) $notification->tenant_id !== (int) $tenant->id) {
            abort(403);
        }

        if (! $notification->read_at) {
            $notification->update(['read_at' => now()]);
        }

        return back();
    }
}

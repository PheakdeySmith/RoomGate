<?php

namespace Modules\Admin\App\Http\Controllers;

use App\Models\InAppNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class AdminNotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $notifications = InAppNotification::query()
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get();

        return view('admin::dashboard.notifications', compact('notifications'));
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $user = $request->user();

        InAppNotification::query()
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return back()->with('status', 'Notifications marked as read.');
    }

    public function markRead(Request $request, InAppNotification $notification): RedirectResponse
    {
        if ($notification->user_id !== $request->user()->id) {
            abort(403);
        }

        if (!$notification->read_at) {
            $notification->update(['read_at' => now()]);
        }

        return back();
    }
}

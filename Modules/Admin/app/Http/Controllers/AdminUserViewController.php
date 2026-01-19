<?php

namespace Modules\Admin\App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;

class AdminUserViewController extends Controller
{
    public function account(User $user)
    {
        return view('admin::dashboard.app-user-view-account', compact('user'));
    }

    public function security(User $user)
    {
        return view('admin::dashboard.app-user-view-security', compact('user'));
    }

    public function billing(User $user)
    {
        return view('admin::dashboard.app-user-view-billing', compact('user'));
    }

    public function notifications(User $user)
    {
        return view('admin::dashboard.app-user-view-notifications', compact('user'));
    }

    public function connections(User $user)
    {
        return view('admin::dashboard.app-user-view-connections', compact('user'));
    }
}

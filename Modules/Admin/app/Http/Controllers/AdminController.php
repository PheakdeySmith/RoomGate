<?php

namespace Modules\Admin\App\Http\Controllers;

use App\Http\Controllers\Controller;
class AdminController extends Controller
{
    public function dashboard()
    {
        $roleCount = \App\Models\Role::count();
        $permissionCount = \App\Models\Permission::count();

        return view('admin::dashboard.index', compact('roleCount', 'permissionCount'));
    }
}

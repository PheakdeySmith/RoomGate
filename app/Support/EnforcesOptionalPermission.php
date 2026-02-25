<?php

namespace App\Support;

use App\Models\Permission;
use Illuminate\Http\Request;

trait EnforcesOptionalPermission
{
    protected function enforceOptionalPermission(Request $request, string $permissionName): void
    {
        $permissionIsActive = Permission::query()
            ->where('name', $permissionName)
            ->where('status', 'active')
            ->exists();

        if ($permissionIsActive && ! $request->user()?->can($permissionName)) {
            abort(403);
        }
    }
}


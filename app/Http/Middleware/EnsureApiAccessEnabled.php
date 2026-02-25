<?php

namespace App\Http\Middleware;

use App\Models\BusinessSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiAccessEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        $settings = BusinessSetting::current();
        $apiEnabled = (bool) $settings->metaValue('api.enabled', true);
        if (!$apiEnabled) {
            abort(403, 'API access is disabled by platform settings.');
        }

        $allowedRoles = (array) $settings->metaValue('api.allowed_roles', []);
        $allowedRoles = array_values(array_filter(array_map('strval', $allowedRoles)));

        if (!empty($allowedRoles)) {
            $user = $request->user();
            if (!$user || !$user->hasAnyRole($allowedRoles)) {
                abort(403, 'Your role is not allowed to use the API.');
            }
        }

        return $next($request);
    }
}


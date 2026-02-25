<?php

namespace App\Http\Middleware;

use App\Models\BusinessSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPlatformMaintenanceMode
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->runningInConsole()) {
            return $next($request);
        }

        try {
            $settings = BusinessSetting::current();
        } catch (\Throwable) {
            return $next($request);
        }

        $enabled = (bool) $settings->metaValue('utility.maintenance.enabled', false);
        if (!$enabled) {
            return $next($request);
        }

        $applicableRoles = array_values((array) $settings->metaValue('utility.maintenance.applicable_roles', []));
        $frontendEnabled = (bool) $settings->metaValue('utility.maintenance.frontend', false);

        $apply = false;
        $user = $request->user();
        if ($user && method_exists($user, 'hasAnyRole') && !empty($applicableRoles)) {
            $apply = $user->hasAnyRole($applicableRoles);
        }

        if (!$user && $frontendEnabled && !$request->is('admin/*')) {
            $apply = true;
        }

        if (!$apply) {
            return $next($request);
        }

        return response()->view('errors.maintenance-custom', [
            'title' => (string) $settings->metaValue('utility.maintenance.title', 'We will be back soon!'),
            'subtitle' => (string) $settings->metaValue('utility.maintenance.subtitle', 'Sorry for the inconvenience but we are performing some maintenance at the moment.'),
            'imagePath' => $settings->metaValue('utility.maintenance.image_path'),
        ], 503);
    }
}


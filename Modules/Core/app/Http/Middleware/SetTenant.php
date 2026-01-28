<?php

namespace Modules\Core\App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Modules\Core\App\Services\CurrentTenant;
use Symfony\Component\HttpFoundation\Response;

class SetTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $slug = (string) $request->route('tenant');
        if ($slug === '') {
            abort(404);
        }

        $tenant = Tenant::query()->where('slug', $slug)->firstOrFail();
        $user = $request->user();
        if (! $user) {
            abort(401);
        }

        $isPlatform = method_exists($user, 'hasAnyRole')
            ? $user->hasAnyRole(['admin', 'platform_admin'])
            : false;

        $isMember = $tenant->users()
            ->where('users.id', $user->id)
            ->wherePivot('status', 'active')
            ->exists();

        if (! $isPlatform && ! $isMember) {
            abort(403);
        }

        app(CurrentTenant::class)->set($tenant);
        URL::defaults(['tenant' => $tenant->slug]);

        return $next($request);
    }
}

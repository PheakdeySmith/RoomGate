<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Sentry\Laravel\Integration;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\SecurityHeaders::class,
        ]);

        $middleware->alias([
            'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'tenant' => \Modules\Core\App\Http\Middleware\SetTenant::class,
            'tenant.onboarded' => \App\Http\Middleware\EnsureTenantOnboarded::class,
        ]);

        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        if (class_exists(Integration::class)) {
            Integration::handles($exceptions);
        }

        $exceptions->reportable(function (Throwable $exception): void {
            $request = app()->bound('request') ? request() : null;
            $context = [
                'exception' => $exception,
                'url' => optional($request)->fullUrl(),
                'method' => optional($request)->method(),
                'ip' => optional($request)->ip(),
                'user_id' => optional(optional($request)->user())->id,
                'tenant_id' => optional(app(\Modules\Core\App\Services\CurrentTenant::class)->get())->id,
            ];

            Log::error($exception->getMessage(), $context);

            if (class_exists(\Bugsnag\BugsnagLaravel\Facades\Bugsnag::class)) {
                \Bugsnag\BugsnagLaravel\Facades\Bugsnag::notifyException($exception);
            }
        });

        $exceptions->renderable(function (Throwable $exception, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            if ($exception instanceof ValidationException) {
                return response()->json([
                    'message' => 'Validation failed.',
                    'errors' => $exception->errors(),
                ], $exception->status);
            }

            $status = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : 500;
            $message = $exception instanceof HttpExceptionInterface
                ? $exception->getMessage()
                : 'Server error.';

            if (! app()->isProduction()) {
                $message = $exception->getMessage() ?: $message;
            }

            return response()->json([
                'message' => $message,
            ], $status);
        });
    })->create();

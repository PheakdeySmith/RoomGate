<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): Response
    {
        $request->authenticate();

        $request->session()->regenerate();

        if ($request->expectsJson()) {
            return response()->noContent();
        }

        $user = $request->user();
        if ($user && !$user->email_verified_at) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('verification.otp', ['email' => $user->email])
                ->withErrors(['email' => 'Please verify your email before logging in.']);
        }
        if ($user && $user->hasAnyRole(['platform_admin', 'admin'])) {
            return redirect()->route('admin.dashboard');
        }
        if ($user && $user->tenants()->exists()) {
            return redirect()->intended('/core/crm-dashboard');
        }

        return redirect()->route('core.onboarding');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): Response
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->noContent();
        }

        return redirect()->route('login');
    }
}

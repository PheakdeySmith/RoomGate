<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Mail\OtpCodeMail;
use App\Models\BusinessSetting;
use App\Models\OtpCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
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

        if ($user && $this->requiresTwoFactor($user)) {
            $settings = BusinessSetting::current();
            $ttlMinutes = max(1, (int) $settings->metaValue('two_factor.ttl_minutes', 10));
            $code = OtpCode::issue(
                $user->email,
                OtpCode::TYPE_LOGIN_2FA,
                $user->id,
                now()->addMinutes($ttlMinutes)
            );
            Mail::to($user->email)->send(new OtpCodeMail($code, OtpCode::TYPE_LOGIN_2FA));

            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('two-factor.challenge', ['email' => $user->email])
                ->with('status', 'Two-factor code sent to your email.');
        }
        if ($user && $user->hasAnyRole(['platform_admin', 'admin'])) {
            return redirect()->route('admin.dashboard');
        }
        if ($user) {
            $tenant = $user->tenants()->orderBy('name')->first();
            if ($tenant) {
                return redirect()->route('Core.crm', ['tenant' => $tenant->slug]);
            }
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

    private function requiresTwoFactor($user): bool
    {
        $settings = BusinessSetting::current();
        if (!(bool) $settings->metaValue('two_factor.enabled', false)) {
            return false;
        }

        $roles = (array) $settings->metaValue('two_factor.roles', ['platform_admin', 'admin', 'owner']);
        if (empty($roles)) {
            return false;
        }

        return $user->hasAnyRole($roles);
    }
}

<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\OtpCodeMail;
use App\Models\BusinessSetting;
use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class TwoFactorChallengeController extends Controller
{
    public function show(Request $request)
    {
        return view('auth.two-factor-challenge', [
            'email' => $request->query('email'),
        ]);
    }

    public function verify(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'digits:6'],
        ]);

        $record = OtpCode::verifyCode($validated['email'], OtpCode::TYPE_LOGIN_2FA, $validated['code']);
        if (!$record) {
            return back()->withErrors(['code' => 'Invalid or expired code.'])->withInput();
        }

        $record->forceFill(['used_at' => now()])->save();

        $user = User::query()->where('email', $validated['email'])->first();
        if (!$user) {
            return redirect()->route('login')->withErrors(['email' => 'User account not found.']);
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        if ($user->hasAnyRole(['platform_admin', 'admin'])) {
            return redirect()->route('admin.dashboard');
        }

        $tenant = $user->tenants()->orderBy('name')->first();
        if ($tenant) {
            return redirect()->route('Core.crm', ['tenant' => $tenant->slug]);
        }

        return redirect()->route('core.onboarding');
    }

    public function resend(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::query()->where('email', $validated['email'])->first();
        if (!$user) {
            return back()->withErrors(['email' => 'User account not found.']);
        }

        $settings = BusinessSetting::current();
        $ttlMinutes = max(1, (int) $settings->metaValue('two_factor.ttl_minutes', 10));
        $code = OtpCode::issue(
            $user->email,
            OtpCode::TYPE_LOGIN_2FA,
            $user->id,
            now()->addMinutes($ttlMinutes)
        );

        Mail::to($user->email)->send(new OtpCodeMail($code, OtpCode::TYPE_LOGIN_2FA));

        return back()->with('status', 'A new authentication code has been sent.');
    }
}


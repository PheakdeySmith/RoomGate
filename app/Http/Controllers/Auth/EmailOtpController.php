<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\OtpCodeMail;
use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class EmailOtpController extends Controller
{
    public function show(Request $request)
    {
        $email = $request->get('email') ?: $request->session()->get('otp_email');
        if ($email) {
            $request->session()->put('otp_email', $email);
        }

        return view('auth.verify-otp', [
            'email' => $email,
        ]);
    }

    public function verify(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'digits:6'],
        ]);

        $record = OtpCode::verifyCode($validated['email'], OtpCode::TYPE_EMAIL_VERIFY, $validated['code']);
        if (!$record) {
            return back()->withErrors(['code' => 'Invalid or expired code.'])->withInput();
        }

        $user = User::query()->where('email', $validated['email'])->first();
        if ($user && !$user->email_verified_at) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        $record->forceFill(['used_at' => now()])->save();

        if ($user) {
            Auth::login($user, true);
        }

        return redirect()->route('core.onboarding');
    }

    public function confirm(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'digits:6'],
        ]);

        $record = OtpCode::verifyCode($validated['email'], OtpCode::TYPE_EMAIL_VERIFY, $validated['code']);
        if (!$record) {
            return redirect()
                ->route('verification.otp', ['email' => $validated['email']])
                ->withErrors(['code' => 'Invalid or expired code.']);
        }

        $user = User::query()->where('email', $validated['email'])->first();
        if ($user && !$user->email_verified_at) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        $record->forceFill(['used_at' => now()])->save();

        if ($user) {
            Auth::login($user, true);
        }

        return redirect()->route('core.onboarding');
    }

    public function resend(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::query()->where('email', $validated['email'])->first();
        $code = OtpCode::issue(
            $validated['email'],
            OtpCode::TYPE_EMAIL_VERIFY,
            $user?->id,
            now()->addMinutes(10)
        );

        $verifyUrl = route('verification.otp.confirm', ['email' => $validated['email'], 'code' => $code]);
        Mail::to($validated['email'])->send(new OtpCodeMail($code, OtpCode::TYPE_EMAIL_VERIFY, $verifyUrl));

        return back()->with('status', 'A new verification code has been sent.');
    }

    public function changeEmail(Request $request)
    {
        $validated = $request->validate([
            'current_email' => ['required', 'email'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
        ]);

        $user = User::query()->where('email', $validated['current_email'])->first();
        if (!$user || $user->email_verified_at) {
            return back()->withErrors(['email' => 'Unable to update email. Please register again.']);
        }

        $user->forceFill(['email' => $validated['email']])->save();

        $code = OtpCode::issue(
            $user->email,
            OtpCode::TYPE_EMAIL_VERIFY,
            $user->id,
            now()->addMinutes(10)
        );

        $verifyUrl = route('verification.otp.confirm', ['email' => $user->email, 'code' => $code]);
        Mail::to($user->email)->send(new OtpCodeMail($code, OtpCode::TYPE_EMAIL_VERIFY, $verifyUrl));

        $request->session()->put('otp_email', $user->email);

        return redirect()->route('verification.otp', ['email' => $user->email])
            ->with('status', 'Verification code sent to your new email.');
    }
}

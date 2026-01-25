<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\OtpCodeMail;
use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules;

class PasswordOtpController extends Controller
{
    public function send(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::query()->where('email', $validated['email'])->first();
        if (!$user) {
            return back()->withErrors(['email' => 'We could not find that email address.']);
        }

        $code = OtpCode::issue(
            $validated['email'],
            OtpCode::TYPE_PASSWORD_RESET,
            $user->id,
            now()->addMinutes(10)
        );

        Mail::to($validated['email'])->send(new OtpCodeMail($code, OtpCode::TYPE_PASSWORD_RESET));

        return redirect()->route('password.otp', ['email' => $validated['email']])
            ->with('status', 'We sent a 6-digit code to your email.');
    }

    public function show(Request $request)
    {
        return view('auth.reset-otp', [
            'email' => $request->get('email'),
        ]);
    }

    public function reset(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'digits:6'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $record = OtpCode::verifyCode($validated['email'], OtpCode::TYPE_PASSWORD_RESET, $validated['code']);
        if (!$record) {
            return back()->withErrors(['code' => 'Invalid or expired code.'])->withInput();
        }

        $user = User::query()->where('email', $validated['email'])->first();
        if (!$user) {
            return back()->withErrors(['email' => 'We could not find that email address.']);
        }

        $user->forceFill(['password' => Hash::make($validated['password'])])->save();
        $record->forceFill(['used_at' => now()])->save();

        return redirect()->route('login')->with('status', 'Password reset. You can log in now.');
    }
}

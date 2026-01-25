<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\OtpCode;
use App\Mail\OtpCodeMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules;

class RegisteredUserController extends Controller
{
    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->string('password')),
        ]);

        $code = OtpCode::issue(
            $user->email,
            OtpCode::TYPE_EMAIL_VERIFY,
            $user->id,
            now()->addMinutes(10)
        );

        $verifyUrl = route('verification.otp.confirm', ['email' => $user->email, 'code' => $code]);
        Mail::to($user->email)->send(new OtpCodeMail($code, OtpCode::TYPE_EMAIL_VERIFY, $verifyUrl));

        if ($request->expectsJson()) {
            return response()->noContent();
        }

        return redirect()->route('verification.otp', ['email' => $user->email])
            ->with('status', 'We sent a verification code to your email.');
    }
}

<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailOtpController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\PasswordOtpController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::get('/login', function () {
    return view('auth.login');
})->middleware('guest')->name('login');

Route::get('/register', function () {
    return view('auth.register');
})->middleware('guest')->name('register');

Route::get('/forgot-password', function () {
    return view('auth.forgot-password');
})->middleware('guest')->name('password.request');

Route::get('/reset-password/{token}', function (string $token) {
    return view('auth.reset-password', ['token' => $token]);
})->middleware('guest')->name('password.reset');

Route::post('/register', [RegisteredUserController::class, 'store'])
    ->middleware(['guest', 'throttle:10,1'])
    ->name('register.store');

Route::post('/login', [AuthenticatedSessionController::class, 'store'])
    ->middleware(['guest', 'throttle:10,1'])
    ->name('login.store');

Route::post('/forgot-password', [PasswordOtpController::class, 'send'])
    ->middleware(['guest', 'throttle:5,1'])
    ->name('password.email');

Route::get('/reset-password/otp', [PasswordOtpController::class, 'show'])
    ->middleware(['guest', 'throttle:10,1'])
    ->name('password.otp');

Route::post('/reset-password/otp', [PasswordOtpController::class, 'reset'])
    ->middleware(['guest', 'throttle:5,1'])
    ->name('password.otp.reset');

Route::post('/reset-password', [NewPasswordController::class, 'store'])
    ->middleware(['guest', 'throttle:5,1'])
    ->name('password.store');

Route::get('/verify-email/{id}/{hash}', VerifyEmailController::class)
    ->middleware(['auth', 'signed', 'throttle:6,1'])
    ->name('verification.verify');

Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
    ->middleware(['auth', 'throttle:6,1'])
    ->name('verification.send');

Route::get('/verify-email-otp', [EmailOtpController::class, 'show'])
    ->middleware('guest')
    ->name('verification.otp');

Route::post('/verify-email-otp', [EmailOtpController::class, 'verify'])
    ->middleware(['guest', 'throttle:6,1'])
    ->name('verification.otp.verify');

Route::get('/verify-email-otp/confirm', [EmailOtpController::class, 'confirm'])
    ->middleware(['guest', 'throttle:6,1'])
    ->name('verification.otp.confirm');

Route::post('/verify-email-otp/resend', [EmailOtpController::class, 'resend'])
    ->middleware(['guest', 'throttle:3,1'])
    ->name('verification.otp.resend');

Route::post('/verify-email-otp/change', [EmailOtpController::class, 'changeEmail'])
    ->middleware(['guest', 'throttle:3,1'])
    ->name('verification.otp.change');

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::get('/auth/{provider}/redirect', [SocialAuthController::class, 'redirect'])
    ->middleware('guest')
    ->name('oauth.redirect');

Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])
    ->middleware('guest')
    ->name('oauth.callback');

Route::get('/auth/{provider}/email', [SocialAuthController::class, 'showEmailForm'])
    ->middleware('guest')
    ->name('oauth.email');

Route::post('/auth/{provider}/email', [SocialAuthController::class, 'storeEmail'])
    ->middleware(['guest', 'throttle:10,1'])
    ->name('oauth.email.store');

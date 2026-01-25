<?php

use App\Http\Controllers\PrivateFileController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (Auth::check()) {
        $user = Auth::user();
        if ($user && $user->hasAnyRole(['platform_admin', 'admin'])) {
            return redirect()->route('admin.dashboard');
        }

        return redirect()->intended('/core/crm-dashboard');
    }

    return view('welcome');
});

Route::get('/private-files/{path}', [PrivateFileController::class, 'show'])
    ->where('path', '.*')
    ->middleware('auth')
    ->name('private-files.show');

require __DIR__.'/auth.php';

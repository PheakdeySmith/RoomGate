<?php

use App\Http\Controllers\PrivateFileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/private-files/{path}', [PrivateFileController::class, 'show'])
    ->where('path', '.*')
    ->middleware('auth')
    ->name('private-files.show');

require __DIR__.'/auth.php';

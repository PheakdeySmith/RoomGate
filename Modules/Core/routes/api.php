<?php

use Illuminate\Support\Facades\Route;
use Modules\Core\App\Http\Controllers\CoreController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('core', CoreController::class)->names('core');
});

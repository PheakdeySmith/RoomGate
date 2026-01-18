<?php

use Illuminate\Support\Facades\Route;
use Modules\Admin\App\Http\Controllers\AdminController;
use Modules\Admin\App\Http\Controllers\AdminAuditLogController;
use Modules\Admin\App\Http\Controllers\AdminPermissionController;
use Modules\Admin\App\Http\Controllers\AdminRoleController;
use Modules\Admin\App\Http\Controllers\AdminSettingsController;
use Modules\Admin\App\Http\Controllers\AdminTranslationController;

Route::middleware(['auth', 'role:platform_admin|admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/audit-logs', [AdminAuditLogController::class, 'index'])->name('audit-logs');
        Route::post('/audit-logs/{auditLog}/restore', [AdminAuditLogController::class, 'restore'])->name('audit-logs.restore');
        Route::get('/roles', [AdminRoleController::class, 'index'])->name('roles');
        Route::post('/roles', [AdminRoleController::class, 'store'])->name('roles.store');
        Route::patch('/roles/{role}', [AdminRoleController::class, 'update'])->name('roles.update');
        Route::delete('/roles/{role}', [AdminRoleController::class, 'destroy'])->name('roles.destroy');
        Route::post('/users', [AdminRoleController::class, 'storeUser'])->name('users.store');
        Route::patch('/users/{user}', [AdminRoleController::class, 'updateUser'])->name('users.update');
        Route::patch('/users/{user}/toggle', [AdminRoleController::class, 'toggleUser'])->name('users.toggle');
        Route::delete('/users/{user}', [AdminRoleController::class, 'destroyUser'])->name('users.destroy');

        Route::get('/permissions', [AdminPermissionController::class, 'index'])->name('permissions');
        Route::post('/permissions', [AdminPermissionController::class, 'store'])->name('permissions.store');
        Route::patch('/permissions/{permission}', [AdminPermissionController::class, 'update'])->name('permissions.update');
        Route::patch('/permissions/{permission}/toggle', [AdminPermissionController::class, 'toggle'])->name('permissions.toggle');
        Route::delete('/permissions/{permission}', [AdminPermissionController::class, 'destroy'])->name('permissions.destroy');

        Route::get('/translations', [AdminTranslationController::class, 'index'])->name('translations');
        Route::post('/translations', [AdminTranslationController::class, 'store'])->name('translations.store');
        Route::patch('/translations/{key}', [AdminTranslationController::class, 'update'])->name('translations.update');
        Route::delete('/translations/{key}', [AdminTranslationController::class, 'destroy'])->name('translations.destroy');

        Route::get('/settings', [AdminSettingsController::class, 'edit'])->name('settings');
        Route::put('/settings', [AdminSettingsController::class, 'update'])->name('settings.update');
    });

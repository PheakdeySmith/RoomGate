<?php

use Illuminate\Support\Facades\Route;
use Modules\Admin\App\Http\Controllers\AdminController;
use Modules\Admin\App\Http\Controllers\AdminAuditLogController;
use Modules\Admin\App\Http\Controllers\AdminPermissionController;
use Modules\Admin\App\Http\Controllers\AdminRoleController;
use Modules\Admin\App\Http\Controllers\AdminSettingsController;
use Modules\Admin\App\Http\Controllers\AdminPlanController;
use Modules\Admin\App\Http\Controllers\AdminSubscriptionController;
use Modules\Admin\App\Http\Controllers\AdminTranslationController;
use Modules\Admin\App\Http\Controllers\AdminTenantController;
use Modules\Admin\App\Http\Controllers\AdminUserViewController;
use Modules\Admin\App\Http\Controllers\AdminPropertyController;
use Modules\Admin\App\Http\Controllers\AdminRoomTypeController;
use Modules\Admin\App\Http\Controllers\AdminRoomController;
use Modules\Admin\App\Http\Controllers\AdminAmenityController;
use Modules\Admin\App\Http\Controllers\AdminContractController;
use Modules\Admin\App\Http\Controllers\AdminInvoiceController;
use Modules\Admin\App\Http\Controllers\AdminNotificationController;
use Modules\Admin\App\Http\Controllers\AdminOutboundMessageController;
use Modules\Admin\App\Http\Controllers\AdminIoTController;

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
        Route::get('/tenants', [AdminTenantController::class, 'index'])->name('tenants.index');
        Route::get('/tenants/data', [AdminTenantController::class, 'data'])->name('tenants.data');
        Route::post('/tenants', [AdminTenantController::class, 'store'])->name('tenants.store');
        Route::patch('/tenants/{tenant}', [AdminTenantController::class, 'update'])->name('tenants.update');
        Route::delete('/tenants/{tenant}', [AdminTenantController::class, 'destroy'])->name('tenants.destroy');
        Route::get('/tenants/{tenant}', [AdminTenantController::class, 'show'])->name('tenants.show');
        Route::post('/tenants/{tenant}/members', [AdminTenantController::class, 'storeMember'])->name('tenants.members.store');
        Route::patch('/tenants/{tenant}/members/{user}', [AdminTenantController::class, 'updateMember'])->name('tenants.members.update');
        Route::delete('/tenants/{tenant}/members/{user}', [AdminTenantController::class, 'destroyMember'])->name('tenants.members.destroy');
        Route::get('/users/{user}/account', [AdminUserViewController::class, 'account'])->name('users.account');
        Route::get('/users/{user}/security', [AdminUserViewController::class, 'security'])->name('users.security');
        Route::get('/users/{user}/billing', [AdminUserViewController::class, 'billing'])->name('users.billing');
        Route::get('/users/{user}/notifications', [AdminUserViewController::class, 'notifications'])->name('users.notifications');
        Route::get('/users/{user}/connections', [AdminUserViewController::class, 'connections'])->name('users.connections');

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

        Route::get('/plans', [AdminPlanController::class, 'index'])->name('plans.index');
        Route::post('/plans', [AdminPlanController::class, 'store'])->name('plans.store');
        Route::patch('/plans/{plan}', [AdminPlanController::class, 'update'])->name('plans.update');
        Route::delete('/plans/{plan}', [AdminPlanController::class, 'destroy'])->name('plans.destroy');
        Route::post('/plan-limits', [AdminPlanController::class, 'storeLimit'])->name('plan-limits.store');
        Route::patch('/plan-limits/{planLimit}', [AdminPlanController::class, 'updateLimit'])->name('plan-limits.update');
        Route::delete('/plan-limits/{planLimit}', [AdminPlanController::class, 'destroyLimit'])->name('plan-limits.destroy');

        Route::get('/properties', [AdminPropertyController::class, 'index'])->name('properties.index');
        Route::get('/properties/{property}', [AdminPropertyController::class, 'show'])->name('properties.show');
        Route::post('/properties', [AdminPropertyController::class, 'store'])->name('properties.store');
        Route::patch('/properties/{property}', [AdminPropertyController::class, 'update'])->name('properties.update');
        Route::delete('/properties/{property}', [AdminPropertyController::class, 'destroy'])->name('properties.destroy');
        Route::get('/room-types', [AdminRoomTypeController::class, 'index'])->name('room-types.index');
        Route::post('/room-types', [AdminRoomTypeController::class, 'store'])->name('room-types.store');
        Route::patch('/room-types/{roomType}', [AdminRoomTypeController::class, 'update'])->name('room-types.update');
        Route::delete('/room-types/{roomType}', [AdminRoomTypeController::class, 'destroy'])->name('room-types.destroy');
        Route::get('/rooms', [AdminRoomController::class, 'index'])->name('rooms.index');
        Route::get('/rooms/{room}', [AdminRoomController::class, 'show'])->name('rooms.show');
        Route::post('/rooms', [AdminRoomController::class, 'store'])->name('rooms.store');
        Route::patch('/rooms/{room}', [AdminRoomController::class, 'update'])->name('rooms.update');
        Route::delete('/rooms/{room}', [AdminRoomController::class, 'destroy'])->name('rooms.destroy');
        Route::get('/amenities', [AdminAmenityController::class, 'index'])->name('amenities.index');
        Route::post('/amenities', [AdminAmenityController::class, 'store'])->name('amenities.store');
        Route::patch('/amenities/{amenity}', [AdminAmenityController::class, 'update'])->name('amenities.update');
        Route::delete('/amenities/{amenity}', [AdminAmenityController::class, 'destroy'])->name('amenities.destroy');
        Route::get('/contracts', [AdminContractController::class, 'index'])->name('contracts.index');
        Route::post('/contracts', [AdminContractController::class, 'store'])->name('contracts.store');
        Route::patch('/contracts/{contract}', [AdminContractController::class, 'update'])->name('contracts.update');
        Route::delete('/contracts/{contract}', [AdminContractController::class, 'destroy'])->name('contracts.destroy');
        Route::post('/contracts/{contract}/generate-invoice', [AdminContractController::class, 'generateInvoice'])->name('contracts.generate-invoice');
        Route::get('/invoices', [AdminInvoiceController::class, 'index'])->name('invoices.index');

        Route::get('/notifications', [AdminNotificationController::class, 'index'])->name('notifications.index');
        Route::post('/notifications/mark-read', [AdminNotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');
        Route::post('/notifications/{notification}/read', [AdminNotificationController::class, 'markRead'])->name('notifications.mark-read');
        Route::get('/outbound-messages', [AdminOutboundMessageController::class, 'index'])->name('outbound-messages.index');
        Route::get('/iot-control', [AdminIoTController::class, 'index'])->name('iot.index');
        Route::get('/iot-control/status', [AdminIoTController::class, 'status'])->name('iot.status');
        Route::post('/iot-control/led', [AdminIoTController::class, 'led'])->name('iot.led');

        Route::get('/subscriptions', [AdminSubscriptionController::class, 'index'])->name('subscriptions.index');
        Route::get('/subscriptions/invoices', [AdminSubscriptionController::class, 'invoices'])->name('subscriptions.invoices');
        Route::get('/subscriptions/payments', [AdminSubscriptionController::class, 'payments'])->name('subscriptions.payments');
        Route::get('/subscription-invoices', [AdminSubscriptionController::class, 'invoices'])->name('subscription-invoices.index');
        Route::get('/subscription-invoices/create', [AdminSubscriptionController::class, 'createInvoice'])->name('subscription-invoices.create');
        Route::get('/subscription-invoices/{subscriptionInvoice}', [AdminSubscriptionController::class, 'showInvoice'])->name('subscription-invoices.show');
        Route::get('/subscription-invoices/{subscriptionInvoice}/edit', [AdminSubscriptionController::class, 'editInvoice'])->name('subscription-invoices.edit');
        Route::post('/subscriptions', [AdminSubscriptionController::class, 'store'])->name('subscriptions.store');
        Route::patch('/subscriptions/{subscription}', [AdminSubscriptionController::class, 'update'])->name('subscriptions.update');
        Route::delete('/subscriptions/{subscription}', [AdminSubscriptionController::class, 'destroy'])->name('subscriptions.destroy');
        Route::post('/subscription-invoices', [AdminSubscriptionController::class, 'storeInvoice'])->name('subscription-invoices.store');
        Route::patch('/subscription-invoices/{subscriptionInvoice}', [AdminSubscriptionController::class, 'updateInvoice'])->name('subscription-invoices.update');
        Route::post('/subscription-payments', [AdminSubscriptionController::class, 'storePayment'])->name('subscription-payments.store');
        Route::patch('/subscription-payments/{subscriptionPayment}', [AdminSubscriptionController::class, 'updatePayment'])->name('subscription-payments.update');
    });

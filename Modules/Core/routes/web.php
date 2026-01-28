<?php

use Illuminate\Support\Facades\Route;
use Modules\Core\App\Http\Controllers\AmenityController;
use Modules\Core\App\Http\Controllers\CoreController;
use Modules\Core\App\Http\Controllers\CoreInvoiceController;
use Modules\Core\App\Http\Controllers\CoreNotificationController;
use Modules\Core\App\Http\Controllers\ContractController;
use Modules\Core\App\Http\Controllers\OnboardingController;
use Modules\Core\App\Http\Controllers\PropertyController;
use Modules\Core\App\Http\Controllers\RoomController;
use Modules\Core\App\Http\Controllers\RoomTypeController;
use Modules\Core\App\Http\Controllers\TenantMemberController;
use Modules\Core\App\Http\Controllers\UtilityBillController;
use Modules\Core\App\Http\Controllers\UtilityMeterController;
use Modules\Core\App\Http\Controllers\UtilityProviderController;
use Modules\Core\App\Http\Controllers\UtilityRateController;
use Modules\Core\App\Http\Controllers\UtilityReadingController;

Route::middleware('auth')->group(function () {
    Route::get('onboarding', [OnboardingController::class, 'show'])->name('core.onboarding');
    Route::post('onboarding', [OnboardingController::class, 'store'])->name('core.onboarding.store');
    Route::get('onboarding/plan', [OnboardingController::class, 'plan'])->name('core.onboarding.plan');
    Route::post('onboarding/plan', [OnboardingController::class, 'selectPlan'])->name('core.onboarding.plan.store');
});

Route::middleware(['auth', 'tenant', 'tenant.onboarded'])
    ->prefix('t/{tenant}')
    ->group(function () {
        Route::get('dashboard', [CoreController::class, 'index'])->name('core.dashboard');
        Route::get('core/crm-dashboard', [CoreController::class, 'crmDashboard'])->name('Core.crm');
        Route::get('core/access-roles', [CoreController::class, 'accessRoles'])->name('Core.access-roles');
        Route::get('core/access-permission', [CoreController::class, 'accessPermission'])->name('Core.access-permission');
        Route::get('core/users', [CoreController::class, 'userList'])->name('Core.users.index');
        Route::get('cores/users', [CoreController::class, 'userList']);
        Route::get('core/users/account', [CoreController::class, 'userViewAccount'])->name('Core.users.account');
        Route::get('core/users/billing', [CoreController::class, 'userViewBilling'])->name('Core.users.billing');
        Route::get('core/users/connections', [CoreController::class, 'userViewConnections'])->name('Core.users.connections');
        Route::get('core/users/notifications', [CoreController::class, 'userViewNotifications'])->name('Core.users.notifications');
        Route::get('core/users/security', [CoreController::class, 'userViewSecurity'])->name('Core.users.security');
        Route::get('core/notifications', [CoreNotificationController::class, 'index'])->name('core.notifications.index');
        Route::post('core/notifications/mark-all-read', [CoreNotificationController::class, 'markAllRead'])->name('core.notifications.mark-all-read');
        Route::post('core/notifications/{notification}/read', [CoreNotificationController::class, 'markRead'])->name('core.notifications.mark-read');
        Route::get('core/invoices', [CoreInvoiceController::class, 'index'])->name('Core.invoices.index');
        Route::get('core/invoices/data', [CoreInvoiceController::class, 'data'])->name('core.invoices.data');
        Route::get('core/invoices/add', [CoreInvoiceController::class, 'create'])->name('Core.invoices.add');
        Route::post('core/invoices', [CoreInvoiceController::class, 'store'])->name('core.invoices.store');
        Route::get('core/invoices/{invoice}/edit', [CoreInvoiceController::class, 'edit'])->name('Core.invoices.edit');
        Route::patch('core/invoices/{invoice}', [CoreInvoiceController::class, 'update'])->name('core.invoices.update');
        Route::get('core/invoices/{invoice}/preview', [CoreInvoiceController::class, 'preview'])->name('Core.invoices.preview');
        Route::get('core/invoices/utilities', [CoreInvoiceController::class, 'utilities'])->name('core.invoices.utilities');
        Route::get('core/invoices/print', [CoreController::class, 'invoicePrint'])->name('Core.invoices.print');
        Route::get('core/properties', [PropertyController::class, 'index'])->name('core.properties.index');
        Route::post('core/properties', [PropertyController::class, 'store'])->name('core.properties.store');
        Route::get('core/properties/{property}', [PropertyController::class, 'show'])->name('core.properties.show');
        Route::patch('core/properties/{property}', [PropertyController::class, 'update'])->name('core.properties.update');
        Route::delete('core/properties/{property}', [PropertyController::class, 'destroy'])->name('core.properties.destroy');

        Route::get('core/room-types', [RoomTypeController::class, 'index'])->name('core.room-types.index');
        Route::post('core/room-types', [RoomTypeController::class, 'store'])->name('core.room-types.store');
        Route::patch('core/room-types/{roomType}', [RoomTypeController::class, 'update'])->name('core.room-types.update');
        Route::delete('core/room-types/{roomType}', [RoomTypeController::class, 'destroy'])->name('core.room-types.destroy');

        Route::get('core/rooms', [RoomController::class, 'index'])->name('core.rooms.index');
        Route::post('core/rooms', [RoomController::class, 'store'])->name('core.rooms.store');
        Route::get('core/rooms/{room}', [RoomController::class, 'show'])->name('core.rooms.show');
        Route::patch('core/rooms/{room}', [RoomController::class, 'update'])->name('core.rooms.update');
        Route::delete('core/rooms/{room}', [RoomController::class, 'destroy'])->name('core.rooms.destroy');

        Route::get('core/amenities', [AmenityController::class, 'index'])->name('core.amenities.index');
        Route::post('core/amenities', [AmenityController::class, 'store'])->name('core.amenities.store');
        Route::patch('core/amenities/{amenity}', [AmenityController::class, 'update'])->name('core.amenities.update');
        Route::delete('core/amenities/{amenity}', [AmenityController::class, 'destroy'])->name('core.amenities.destroy');

        Route::get('core/contracts', [ContractController::class, 'index'])->name('core.contracts.index');
        Route::post('core/contracts', [ContractController::class, 'store'])->name('core.contracts.store');
        Route::patch('core/contracts/{contract}', [ContractController::class, 'update'])->name('core.contracts.update');
        Route::delete('core/contracts/{contract}', [ContractController::class, 'destroy'])->name('core.contracts.destroy');
        Route::post('core/contracts/{contract}/invoices', [ContractController::class, 'generateInvoice'])->name('core.contracts.generate-invoice');

        Route::get('core/tenant-members', [TenantMemberController::class, 'index'])->name('core.tenant-members.index');
        Route::get('core/tenant-members/data', [TenantMemberController::class, 'membersData'])->name('core.tenant-members.data');
        Route::post('core/tenant-members', [TenantMemberController::class, 'store'])->name('core.tenant-members.store');
        Route::patch('core/tenant-members/{user}', [TenantMemberController::class, 'update'])->name('core.tenant-members.update');
        Route::delete('core/tenant-members/{user}', [TenantMemberController::class, 'destroy'])->name('core.tenant-members.destroy');
        Route::post('core/tenant-members/{user}/toggle-status', [TenantMemberController::class, 'toggleStatus'])->name('core.tenant-members.toggle-status');
        Route::post('core/tenant-members/{user}/reset-password', [TenantMemberController::class, 'resetPassword'])->name('core.tenant-members.reset-password');

        Route::get('core/utility-providers', [UtilityProviderController::class, 'index'])->name('core.utility-providers.index');
        Route::post('core/utility-providers', [UtilityProviderController::class, 'store'])->name('core.utility-providers.store');
        Route::patch('core/utility-providers/{provider}', [UtilityProviderController::class, 'update'])->name('core.utility-providers.update');
        Route::delete('core/utility-providers/{provider}', [UtilityProviderController::class, 'destroy'])->name('core.utility-providers.destroy');

        Route::get('core/utility-meters', [UtilityMeterController::class, 'index'])->name('core.utility-meters.index');
        Route::post('core/utility-meters', [UtilityMeterController::class, 'store'])->name('core.utility-meters.store');
        Route::patch('core/utility-meters/{meter}', [UtilityMeterController::class, 'update'])->name('core.utility-meters.update');
        Route::delete('core/utility-meters/{meter}', [UtilityMeterController::class, 'destroy'])->name('core.utility-meters.destroy');

        Route::get('core/utility-readings', [UtilityReadingController::class, 'index'])->name('core.utility-readings.index');
        Route::post('core/utility-readings', [UtilityReadingController::class, 'store'])->name('core.utility-readings.store');
        Route::patch('core/utility-readings/{reading}', [UtilityReadingController::class, 'update'])->name('core.utility-readings.update');
        Route::delete('core/utility-readings/{reading}', [UtilityReadingController::class, 'destroy'])->name('core.utility-readings.destroy');

        Route::get('core/utility-rates', [UtilityRateController::class, 'index'])->name('core.utility-rates.index');
        Route::post('core/utility-rates', [UtilityRateController::class, 'store'])->name('core.utility-rates.store');
        Route::patch('core/utility-rates/{rate}', [UtilityRateController::class, 'update'])->name('core.utility-rates.update');
        Route::delete('core/utility-rates/{rate}', [UtilityRateController::class, 'destroy'])->name('core.utility-rates.destroy');

        Route::get('core/utility-bills', [UtilityBillController::class, 'index'])->name('core.utility-bills.index');
        Route::post('core/utility-bills', [UtilityBillController::class, 'store'])->name('core.utility-bills.store');
        Route::patch('core/utility-bills/{bill}', [UtilityBillController::class, 'update'])->name('core.utility-bills.update');
        Route::delete('core/utility-bills/{bill}', [UtilityBillController::class, 'destroy'])->name('core.utility-bills.destroy');
        Route::resource('core', CoreController::class)->names('Core');
    });

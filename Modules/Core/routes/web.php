<?php

use Illuminate\Support\Facades\Route;
use Modules\Core\App\Http\Controllers\CoreController;

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
Route::get('core/invoices', [CoreController::class, 'invoiceList'])->name('Core.invoices.index');
Route::get('core/invoices/add', [CoreController::class, 'invoiceAdd'])->name('Core.invoices.add');
Route::get('core/invoices/edit', [CoreController::class, 'invoiceEdit'])->name('Core.invoices.edit');
Route::get('core/invoices/preview', [CoreController::class, 'invoicePreview'])->name('Core.invoices.preview');
Route::get('core/invoices/print', [CoreController::class, 'invoicePrint'])->name('Core.invoices.print');
Route::resource('core', CoreController::class)->names('Core');

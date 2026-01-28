<?php

namespace Modules\Core\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CoreController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('core::dashboard.dashboard');
    }

    public function crmDashboard()
    {
        return view('core::dashboard.crm-dashboard');
    }

    public function accessRoles()
    {
        return view('core::dashboard.app-access-roles');
    }

    public function accessPermission()
    {
        return view('core::dashboard.app-access-permission');
    }

    public function userList()
    {
        return view('core::dashboard.app-user-list');
    }

    public function userViewAccount()
    {
        return view('core::dashboard.app-user-view-account');
    }

    public function userViewBilling()
    {
        return view('core::dashboard.app-user-view-billing');
    }

    public function userViewConnections()
    {
        return view('core::dashboard.app-user-view-connections');
    }

    public function userViewNotifications()
    {
        return redirect()->route('core.notifications.index');
    }

    public function userViewSecurity()
    {
        return view('core::dashboard.app-user-view-security');
    }

    public function invoiceList()
    {
        return view('core::dashboard.app-invoice-list');
    }

    public function invoiceAdd()
    {
        return view('core::dashboard.app-invoice-add');
    }

    public function invoiceEdit()
    {
        return view('core::dashboard.app-invoice-edit');
    }

    public function invoicePreview()
    {
        return view('core::dashboard.app-invoice-preview');
    }

    public function invoicePrint()
    {
        return view('core::dashboard.app-invoice-print');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('core::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {}

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('core::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('core::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id) {}
}

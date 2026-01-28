@extends('admin::components.layouts.master')
@section('title', 'Admin Dashboard | RoomGate')
@section('page-title', 'Admin Dashboard')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="row g-6">
        <div class="col-md-6 col-lg-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h5 class="mb-1">Roles</h5>
                            <p class="mb-0 text-body-secondary">Total roles</p>
                        </div>
                        <h3 class="mb-0">{{ $roleCount }}</h3>
                    </div>
                    <div class="mt-4">
                        <a href="{{ route('admin.roles') }}" class="btn btn-primary btn-sm">Manage roles</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h5 class="mb-1">Permissions</h5>
                            <p class="mb-0 text-body-secondary">Total permissions</p>
                        </div>
                        <h3 class="mb-0">{{ $permissionCount }}</h3>
                    </div>
                    <div class="mt-4">
                        <a href="{{ route('admin.permissions') }}" class="btn btn-outline-primary btn-sm">View permissions</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h5 class="mb-1">Tenant Console</h5>
                            <p class="mb-0 text-body-secondary">Switch to tenant UI</p>
                        </div>
                        <i class="icon-base ti tabler-building-community icon-lg text-primary"></i>
                    </div>
                    @php
                        $adminTenant = auth()->user()?->tenants()->orderBy('name')->first();
                    @endphp
                    <div class="mt-4">
                        @if ($adminTenant)
                            <a href="{{ route('Core.crm', ['tenant' => $adminTenant->slug]) }}" class="btn btn-outline-secondary btn-sm">
                                Open tenant dashboard
                            </a>
                        @else
                            <span class="text-body-secondary">No tenant assigned.</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

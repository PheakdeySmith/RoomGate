@extends('admin::components.layouts.master')
@section('title', 'Tenant Connections | RoomGate Admin')

@push('page-styles')
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/datatables-bs5/datatables.bootstrap5.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/css/pages/page-user-view.css" />
@endpush

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="row">
    @include('admin::dashboard.tenant-view.partials.sidebar')

    <div class="col-xl-8 col-lg-7 order-0 order-md-1" data-ajax-container="user-view">
      @include('admin::dashboard.tenant-view.partials.tabs')

      <div class="card mb-6">
        <div class="card-body">
          <h5 class="mb-2">Integrations</h5>
          <div class="row g-4">
            <div class="col-md-6">
              <div class="border rounded p-4 h-100">
                <div class="text-body-secondary">IoT Device IP</div>
                <div class="fw-semibold">{{ $appSettings->iot_device_ip ?? '—' }}</div>
                <div class="text-body-secondary mt-2">Tenant Slug</div>
                <div>{{ $tenant->slug }}</div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="border rounded p-4 h-100">
                <div class="text-body-secondary">Utility Meters</div>
                <div class="fw-semibold">{{ $utilityMeters->count() }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('page-scripts')
  <script src="{{ asset('assets/assets') }}/vendor/libs/datatables-bs5/datatables-bootstrap5.js"></script>
  <script src="{{ asset('assets/assets') }}/js/roomgate-ajax.js"></script>
  <script src="{{ asset('assets/assets') }}/js/admin-user-view-ajax.js"></script>
@endpush

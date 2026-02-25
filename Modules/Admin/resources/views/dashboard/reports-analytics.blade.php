@extends('admin::components.layouts.master')
@section('title', 'Reports & Analytics | RoomGate Admin')
@section('page-title', 'Reports & Analytics')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="card mb-6">
    <div class="card-body">
      <form method="GET" action="{{ route('admin.reports-analytics.index') }}" class="row g-3 align-items-end">
        <div class="col-md-4">
          <label class="form-label">Tenant</label>
          <select name="tenant_id" class="form-select">
            <option value="">All Tenants</option>
            @foreach($tenants as $tenant)
              <option value="{{ $tenant->id }}" {{ (int) $selectedTenantId === (int) $tenant->id ? 'selected' : '' }}>{{ $tenant->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-8 d-flex gap-2">
          <button type="submit" class="btn btn-primary">Apply Filter</button>
          <a href="{{ route('admin.reports-analytics.index') }}" class="btn btn-label-secondary">Reset</a>
        </div>
      </form>
    </div>
  </div>

  <div class="row g-6">
    <div class="col-md-6 col-xl-3">
      <div class="card h-100">
        <div class="card-body">
          <h6 class="mb-1">Occupancy Rate</h6>
          <h3 class="mb-1">{{ number_format($stats['occupancy_rate'], 2) }}%</h3>
          <small class="text-body-secondary">Occupied {{ $stats['rooms_occupied'] }} / {{ $stats['rooms_total'] }} rooms</small>
        </div>
      </div>
    </div>
    <div class="col-md-6 col-xl-3">
      <div class="card h-100">
        <div class="card-body">
          <h6 class="mb-1">Vacancy</h6>
          <h3 class="mb-1">{{ $stats['rooms_vacant'] }}</h3>
          <small class="text-body-secondary">Available units not under active contract</small>
        </div>
      </div>
    </div>
    <div class="col-md-6 col-xl-3">
      <div class="card h-100">
        <div class="card-body">
          <h6 class="mb-1">Rent Collection</h6>
          <h3 class="mb-1">{{ number_format($stats['rent_collection_rate'], 2) }}%</h3>
          <small class="text-body-secondary">Paid ${{ number_format($stats['rent_paid_cents'] / 100, 2) }} / ${{ number_format($stats['rent_invoiced_cents'] / 100, 2) }}</small>
        </div>
      </div>
    </div>
    <div class="col-md-6 col-xl-3">
      <div class="card h-100">
        <div class="card-body">
          <h6 class="mb-1">Delinquency</h6>
          <h3 class="mb-1">${{ number_format($stats['delinquency_cents'] / 100, 2) }}</h3>
          <small class="text-body-secondary">Outstanding ${{ number_format($stats['rent_outstanding_cents'] / 100, 2) }}</small>
        </div>
      </div>
    </div>

    <div class="col-md-6 col-xl-4">
      <div class="card h-100">
        <div class="card-body">
          <h6 class="mb-1">Maintenance Open</h6>
          <h3 class="mb-0">{{ $stats['maintenance_open'] }}</h3>
        </div>
      </div>
    </div>
    <div class="col-md-6 col-xl-4">
      <div class="card h-100">
        <div class="card-body">
          <h6 class="mb-1">Maintenance SLA Rate</h6>
          <h3 class="mb-0">{{ number_format($stats['maintenance_sla_rate'], 2) }}%</h3>
        </div>
      </div>
    </div>
    <div class="col-md-6 col-xl-4">
      <div class="card h-100">
        <div class="card-body">
          <h6 class="mb-1">Avg Resolution Time</h6>
          <h3 class="mb-0">{{ number_format($stats['maintenance_avg_resolution_hours'], 2) }}h</h3>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

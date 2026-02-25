@extends('admin::components.layouts.master')
@section('title', 'Plan Usage | RoomGate Admin')
@section('page-title', 'Plan Usage Visibility')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="card">
    <h5 class="card-header">Tenant Usage vs Plan Limits</h5>
    <div class="table-responsive text-nowrap">
      <table class="table">
        <thead>
          <tr>
            <th>Tenant</th>
            <th>Plan</th>
            <th>Properties</th>
            <th>Rooms</th>
            <th>Amenities</th>
            <th>Users</th>
            <th>Staff</th>
          </tr>
        </thead>
        <tbody>
          @forelse($rows as $row)
            @php
              $planName = $row['plan']?->name ?? 'No active plan';
              $limits = $row['limits'];
              $usage = $row['usage'];
              $fmt = function (string $key) use ($limits, $usage) {
                  $limit = $limits[$key] ?? 'unlimited';
                  return ($usage[$key] ?? 0) . ' / ' . $limit;
              };
            @endphp
            <tr>
              <td>{{ $row['tenant']->name }}</td>
              <td>{{ $planName }}</td>
              <td>{{ $fmt('properties_max') }}</td>
              <td>{{ $fmt('rooms_max') }}</td>
              <td>{{ $fmt('amenities_max') }}</td>
              <td>{{ $fmt('tenant_users_max') }}</td>
              <td>{{ $fmt('staff_max') }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="text-center text-body-secondary">No tenants found.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection

@extends('admin::components.layouts.master')
@section('title', 'Enterprise Assignments | RoomGate Admin')
@section('page-title', 'Enterprise Assignments')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="row g-6">
    <div class="col-lg-4">
      <div class="card">
        <h5 class="card-header">Assign Staff to Property</h5>
        <div class="card-body">
          <form method="POST" action="{{ route('admin.enterprise-assignments.store') }}" class="row g-3">
            @csrf
            <div class="col-12">
              <label class="form-label">Tenant</label>
              <select name="tenant_id" class="form-select" required>
                @foreach($tenants as $tenant)
                  <option value="{{ $tenant->id }}">{{ $tenant->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-12">
              <label class="form-label">Property</label>
              <select name="property_id" class="form-select" required>
                @foreach($properties as $property)
                  <option value="{{ $property->id }}">{{ $property->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-12">
              <label class="form-label">Staff User</label>
              <select name="user_id" class="form-select" required>
                @foreach($staff as $user)
                  <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                @endforeach
              </select>
            </div>
            <div class="col-12">
              <label class="form-label">Status</label>
              <select name="status" class="form-select">
                <option value="active">Active</option>
                <option value="disabled">Disabled</option>
              </select>
            </div>
            <div class="col-12">
              <button type="submit" class="btn btn-primary w-100">Create Assignment</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div class="col-lg-8">
      <div class="card">
        <h5 class="card-header">Assignments</h5>
        <div class="table-responsive text-nowrap">
          <table class="table">
            <thead>
              <tr>
                <th>Tenant</th>
                <th>Property</th>
                <th>Staff</th>
                <th>Status</th>
                <th>Assigned By</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              @forelse($assignments as $assignment)
                <tr>
                  <td>{{ $assignment->tenant?->name ?? '-' }}</td>
                  <td>{{ $assignment->property?->name ?? '-' }}</td>
                  <td>{{ $assignment->user?->name ?? '-' }}</td>
                  <td><span class="badge {{ $assignment->status === 'active' ? 'bg-label-success' : 'bg-label-secondary' }}">{{ ucfirst($assignment->status) }}</span></td>
                  <td>{{ $assignment->assignedBy?->name ?? 'System' }}</td>
                  <td>
                    <form method="POST" action="{{ route('admin.enterprise-assignments.destroy', $assignment) }}" data-confirm="Remove this assignment?">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-sm btn-label-danger">Remove</button>
                    </form>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="6" class="text-center text-body-secondary">No assignments found.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

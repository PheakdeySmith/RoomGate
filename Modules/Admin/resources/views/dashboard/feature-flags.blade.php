@extends('admin::components.layouts.master')
@section('title', 'Feature Flags | RoomGate Admin')
@section('page-title', 'Feature Flags')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="row g-6">
    <div class="col-lg-4">
      <div class="card">
        <h5 class="card-header">New Feature Flag</h5>
        <div class="card-body">
          <form method="POST" action="{{ route('admin.feature-flags.store') }}" class="row g-3">
            @csrf
            <div class="col-12">
              <label class="form-label">Flag Key</label>
              <input type="text" name="flag_key" class="form-control" placeholder="new_checkout_flow" required>
            </div>
            <div class="col-12">
              <label class="form-label">Name</label>
              <input type="text" name="name" class="form-control" placeholder="New Checkout Flow" required>
            </div>
            <div class="col-12">
              <label class="form-label">Owner</label>
              <input type="text" name="owner" class="form-control" placeholder="Billing Team">
            </div>
            <div class="col-12">
              <label class="form-label">Sunset Date</label>
              <input type="date" name="sunset_date" class="form-control">
            </div>
            <div class="col-12">
              <label class="form-label">Description</label>
              <textarea name="description" class="form-control" rows="3"></textarea>
            </div>
            <div class="col-12">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="is_enabled" value="1" id="newFlagEnabled">
                <label class="form-check-label" for="newFlagEnabled">Enabled</label>
              </div>
            </div>
            <div class="col-12">
              <button type="submit" class="btn btn-primary w-100">Create Flag</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div class="col-lg-8">
      <div class="card">
        <h5 class="card-header">Flag Registry</h5>
        <div class="table-responsive text-nowrap">
          <table class="table">
            <thead>
              <tr>
                <th>Flag</th>
                <th>Owner</th>
                <th>Sunset</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($flags as $flag)
                <tr>
                  <td>
                    <div class="d-flex flex-column">
                      <span class="fw-medium">{{ $flag->name }}</span>
                      <small class="text-body-secondary">{{ $flag->flag_key }}</small>
                    </div>
                  </td>
                  <td>{{ $flag->owner ?: 'Unassigned' }}</td>
                  <td>{{ $flag->sunset_date ? \Illuminate\Support\Carbon::parse($flag->sunset_date)->format('Y-m-d') : 'Not set' }}</td>
                  <td>
                    <span class="badge {{ $flag->is_enabled ? 'bg-label-success' : 'bg-label-secondary' }}">{{ $flag->is_enabled ? 'Enabled' : 'Disabled' }}</span>
                  </td>
                  <td>
                    <div class="d-flex gap-2">
                      <form method="POST" action="{{ route('admin.feature-flags.toggle', $flag) }}">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-label-primary">Toggle</button>
                      </form>
                    </div>
                  </td>
                </tr>
                <tr>
                  <td colspan="5">
                    <form method="POST" action="{{ route('admin.feature-flags.update', $flag) }}" class="row g-2">
                      @csrf
                      @method('PATCH')
                      <div class="col-md-2">
                        <input type="text" class="form-control" name="flag_key" value="{{ $flag->flag_key }}" required>
                      </div>
                      <div class="col-md-2">
                        <input type="text" class="form-control" name="name" value="{{ $flag->name }}" required>
                      </div>
                      <div class="col-md-2">
                        <input type="text" class="form-control" name="owner" value="{{ $flag->owner }}" placeholder="Owner">
                      </div>
                      <div class="col-md-2">
                        <input type="date" class="form-control" name="sunset_date" value="{{ $flag->sunset_date ? \Illuminate\Support\Carbon::parse($flag->sunset_date)->format('Y-m-d') : '' }}">
                      </div>
                      <div class="col-md-2">
                        <select name="is_enabled" class="form-select">
                          <option value="1" {{ $flag->is_enabled ? 'selected' : '' }}>Enabled</option>
                          <option value="0" {{ !$flag->is_enabled ? 'selected' : '' }}>Disabled</option>
                        </select>
                      </div>
                      <div class="col-md-2">
                        <button type="submit" class="btn btn-sm btn-primary w-100">Save</button>
                      </div>
                      <div class="col-12">
                        <input type="text" class="form-control" name="description" value="{{ $flag->description }}" placeholder="Description">
                      </div>
                    </form>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="5" class="text-center text-body-secondary">No feature flags yet.</td>
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

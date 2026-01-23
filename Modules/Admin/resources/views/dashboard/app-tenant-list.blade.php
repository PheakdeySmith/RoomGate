@extends('admin::components.layouts.master')
@section('title', 'Tenants | RoomGate Admin')
@section('page-title', 'Tenants')

@push('page-styles')
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/datatables-bs5/datatables.bootstrap5.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/select2/select2.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/@form-validation/form-validation.css" />
@endpush

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="row g-6 mb-6">
    <div class="col-sm-6 col-xl-3">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div class="content-left">
              <span class="text-heading">Total Tenants</span>
              <div class="d-flex align-items-center my-1">
                <h4 class="mb-0 me-2">128</h4>
                <p class="text-success mb-0">(+12%)</p>
              </div>
              <small class="mb-0">All tenants</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-primary">
                <i class="icon-base ti tabler-building-community icon-26px"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-3">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div class="content-left">
              <span class="text-heading">Active Tenants</span>
              <div class="d-flex align-items-center my-1">
                <h4 class="mb-0 me-2">112</h4>
                <p class="text-success mb-0">(+5%)</p>
              </div>
              <small class="mb-0">Last 30 days</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-success">
                <i class="icon-base ti tabler-building-skyscraper icon-26px"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-3">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div class="content-left">
              <span class="text-heading">Trialing</span>
              <div class="d-flex align-items-center my-1">
                <h4 class="mb-0 me-2">9</h4>
                <p class="text-danger mb-0">(-2%)</p>
              </div>
              <small class="mb-0">Current trials</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-warning">
                <i class="icon-base ti tabler-hourglass icon-26px"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-3">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div class="content-left">
              <span class="text-heading">Suspended</span>
              <div class="d-flex align-items-center my-1">
                <h4 class="mb-0 me-2">7</h4>
                <p class="text-success mb-0">(+1%)</p>
              </div>
              <small class="mb-0">Compliance</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-danger">
                <i class="icon-base ti tabler-shield-x icon-26px"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="card">
    <div class="card-header border-bottom">
      <h5 class="card-title mb-0">Filters</h5>
      <div class="d-flex justify-content-between align-items-center row pt-4 gap-4 gap-md-0">
        <div class="col-md-4">
          <select class="select2 form-select text-capitalize" id="TenantRoleFilter" data-placeholder="Select Role">
            <option value="">Select Role</option>
            <option value="Owner">Owner</option>
            <option value="Admin">Admin</option>
            <option value="Staff">Staff</option>
          </select>
        </div>
        <div class="col-md-4">
          <select class="select2 form-select text-capitalize" id="TenantPlanFilter" data-placeholder="Select Plan">
            <option value="">Select Plan</option>
            <option value="Basic">Basic</option>
            <option value="Enterprise">Enterprise</option>
            <option value="Company">Company</option>
            <option value="Team">Team</option>
          </select>
        </div>
        <div class="col-md-4">
          <select class="select2 form-select text-capitalize" id="TenantStatusFilter" data-placeholder="Select Status">
            <option value="">Select Status</option>
            <option value="Active">Active</option>
            <option value="Trialing">Trialing</option>
            <option value="Suspended">Suspended</option>
          </select>
        </div>
      </div>
    </div>
    <div class="card-datatable">
      <table class="datatables-users table">
        <thead class="border-top">
          <tr>
            <th></th>
            <th></th>
            <th>Tenant</th>
            <th>Role</th>
            <th>Plan</th>
            <th>Billing</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
      </table>
    </div>
  <div
      class="offcanvas offcanvas-end"
      tabindex="-1"
      id="offcanvasAddUser"
      aria-labelledby="offcanvasAddUserLabel">
      <div class="offcanvas-header border-bottom">
        <h5 id="offcanvasAddUserLabel" class="offcanvas-title">Add Tenant</h5>
        <button
          type="button"
          class="btn-close text-reset"
          data-bs-dismiss="offcanvas"
          aria-label="Close"></button>
      </div>
      <div class="offcanvas-body mx-0 flex-grow-0 p-6 h-100">
        <form class="add-new-user pt-0" id="addNewUserForm" method="POST" action="{{ route('admin.tenants.store') }}">
          @csrf
          <div class="mb-6 form-control-validation">
            <label class="form-label" for="add-user-fullname">Tenant Name</label>
            <input
              type="text"
              class="form-control"
              id="add-user-fullname"
              placeholder="Sunrise Apartments"
              name="name"
              aria-label="Sunrise Apartments" />
          </div>
          <div class="mb-6 form-control-validation">
            <label class="form-label" for="add-user-email">Owner Email</label>
            <input
              type="text"
              id="add-user-email"
              class="form-control"
              placeholder="owner@example.com"
              aria-label="owner@example.com"
              name="owner_email" />
          </div>
          <div class="mb-6 form-control-validation">
            <label class="form-label" for="add-user-password">Owner Password</label>
            <input
              type="password"
              id="add-user-password"
              class="form-control"
              placeholder="Minimum 8 characters"
              aria-label="Owner password"
              name="owner_password" />
          </div>
          <div class="mb-6 form-control-validation">
            <label class="form-label" for="user-plan">Select Plan</label>
            <select id="user-plan" name="plan_id" class="form-select">
              @foreach ($plans as $plan)
                <option value="{{ $plan->id }}">{{ $plan->name }}</option>
              @endforeach
            </select>
          </div>
          <button type="submit" class="btn btn-primary me-3 data-submit">Submit</button>
          <button type="reset" class="btn btn-label-danger" data-bs-dismiss="offcanvas">Cancel</button>
        </form>
      </div>
    </div>
    <div
      class="offcanvas offcanvas-end"
      tabindex="-1"
      id="offcanvasEditTenant"
      aria-labelledby="offcanvasEditTenantLabel">
      <div class="offcanvas-header border-bottom">
        <h5 id="offcanvasEditTenantLabel" class="offcanvas-title">Edit Tenant</h5>
        <button
          type="button"
          class="btn-close text-reset"
          data-bs-dismiss="offcanvas"
          aria-label="Close"></button>
      </div>
      <div class="offcanvas-body mx-0 flex-grow-0 p-6 h-100">
        <form class="add-new-user pt-0" id="editTenantForm" method="POST">
          @csrf
          @method('PATCH')
          <div class="mb-6 form-control-validation">
            <label class="form-label" for="edit-tenant-name">Tenant Name</label>
            <input
              type="text"
              class="form-control"
              id="edit-tenant-name"
              name="name"
              placeholder="Sunrise Apartments" />
          </div>
          <div class="mb-6 form-control-validation">
            <label class="form-label" for="edit-tenant-email">Owner Email</label>
            <input
              type="text"
              id="edit-tenant-email"
              class="form-control"
              placeholder="owner@example.com"
              name="owner_email" />
          </div>
          <div class="mb-6">
            <label class="form-label" for="edit-tenant-password">Owner Password</label>
            <input
              type="password"
              id="edit-tenant-password"
              class="form-control"
              placeholder="Leave blank to keep" 
              name="owner_password" />
          </div>
          <div class="mb-6 form-control-validation">
            <label class="form-label" for="edit-tenant-status">Status</label>
            <select id="edit-tenant-status" name="status" class="form-select">
              <option value="active">Active</option>
              <option value="suspended">Suspended</option>
              <option value="closed">Closed</option>
            </select>
          </div>
          <div class="mb-6 form-control-validation">
            <label class="form-label" for="edit-tenant-plan">Select Plan</label>
            <select id="edit-tenant-plan" name="plan_id" class="form-select">
              @foreach ($plans as $plan)
                <option value="{{ $plan->id }}">{{ $plan->name }}</option>
              @endforeach
            </select>
          </div>
          <button type="submit" class="btn btn-primary me-3 data-submit">Update</button>
          <button type="reset" class="btn btn-label-danger" data-bs-dismiss="offcanvas">Cancel</button>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

@push('page-scripts')
  <script src="{{ asset('assets/assets') }}/vendor/libs/moment/moment.js"></script>
  <script src="{{ asset('assets/assets') }}/vendor/libs/datatables-bs5/datatables-bootstrap5.js"></script>
  <script src="{{ asset('assets/assets') }}/vendor/libs/select2/select2.js"></script>
  <script src="{{ asset('assets/assets') }}/vendor/libs/@form-validation/popular.js"></script>
  <script src="{{ asset('assets/assets') }}/vendor/libs/@form-validation/bootstrap5.js"></script>
  <script src="{{ asset('assets/assets') }}/vendor/libs/@form-validation/auto-focus.js"></script>
  <script src="{{ asset('assets/assets') }}/vendor/libs/cleave-zen/cleave-zen.js"></script>
  <script>
    window.roomGateUserListDataUrl = "{{ route('admin.tenants.data') }}";
    window.roomGateUserViewFallback = "javascript:void(0);";
    window.roomGateUserListStaticFilters = true;
    window.roomGateUserListMode = "tenants";
  </script>
  <script src="{{ asset('assets/assets') }}/js/app-user-list.js"></script>
@endpush

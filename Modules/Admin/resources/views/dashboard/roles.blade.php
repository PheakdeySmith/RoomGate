@extends('admin::components.layouts.master')
@section('title', 'Roles | RoomGate Admin')
@section('page-title', 'Roles')

@push('page-styles')
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/datatables-bs5/datatables.bootstrap5.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/@form-validation/form-validation.css" />
@endpush

@section('content')
@php
  $statusClass = [
      'active' => 'bg-label-success',
      'inactive' => 'bg-label-secondary',
      'suspended' => 'bg-label-warning',
  ];
  $roleIconMap = [
      'owner' => 'tabler-crown',
      'admin' => 'tabler-shield',
      'staff' => 'tabler-user',
      'tenant' => 'tabler-home',
      'platform_admin' => 'tabler-lock-access',
      'support' => 'tabler-headset',
      'billing_admin' => 'tabler-credit-card',
  ];
@endphp

<div class="container-xxl flex-grow-1 container-p-y">
  <h4 class="mb-1">Roles List</h4>
  <p class="mb-6">
    A role provides access to predefined menus and features so that depending on assigned role an administrator can
    have access to what user needs.
  </p>

  <div class="row g-6">
    @foreach ($roles as $role)
      <div class="col-xl-4 col-lg-6 col-md-6">
        <div class="card">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-4">
              <h6 class="fw-normal mb-0 text-body">Total {{ $role->users_count }} users</h6>
              <ul class="list-unstyled d-flex align-items-center avatar-group mb-0">
                @php
                  $avatars = $role->users->take(4);
                  $extra = max($role->users_count - $avatars->count(), 0);
                @endphp
                @foreach ($avatars as $user)
                  @php $avatarIndex = ($user->id % 13) + 1; @endphp
                  <li class="avatar pull-up">
                    <img class="rounded-circle" src="{{ asset('assets/assets') }}/img/avatars/{{ $avatarIndex }}.png" alt="Avatar" />
                  </li>
                @endforeach
                @if ($extra > 0)
                  <li class="avatar">
                    <span class="avatar-initial rounded-circle pull-up" data-bs-toggle="tooltip" data-bs-placement="bottom" title="{{ $extra }} more">
                      +{{ $extra }}
                    </span>
                  </li>
                @endif
              </ul>
            </div>
            <div class="d-flex justify-content-between align-items-end">
              <div class="role-heading">
                <h5 class="mb-1 text-capitalize">{{ $role->name }}</h5>
                <div class="d-flex align-items-center gap-2">
                  <a
                    href="javascript:;"
                    data-bs-toggle="modal"
                    data-bs-target="#editRoleModal"
                    class="role-edit-modal"
                    data-role-id="{{ $role->id }}"
                    data-role-name="{{ $role->name }}"
                    data-role-permissions='@json($role->permissions->pluck("name"))'>
                    <span>Edit Role</span>
                  </a>
                  @if (! $role->is_system)
                    <form method="POST" action="{{ route('admin.roles.destroy', $role) }}" data-confirm="Delete this role?">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-link p-0 text-danger">
                        Delete Role
                      </button>
                    </form>
                  @endif
                </div>
              </div>
              <button
                type="button"
                class="btn btn-icon role-duplicate"
                data-bs-toggle="modal"
                data-bs-target="#addRoleModal"
                data-role-name="{{ $role->name }}"
                data-role-permissions='@json($role->permissions->pluck("name"))'>
                <i class="icon-base ti tabler-copy icon-md text-heading"></i>
              </button>
            </div>
          </div>
        </div>
      </div>
    @endforeach

    <div class="col-xl-4 col-lg-6 col-md-6">
      <div class="card h-100">
        <div class="row h-100">
          <div class="col-sm-5">
            <div class="d-flex align-items-end h-100 justify-content-center mt-sm-0 mt-4">
              <img
                src="{{ asset('assets/assets') }}/img/illustrations/add-new-roles.png"
                class="img-fluid"
                alt="Image"
                width="83" />
            </div>
          </div>
          <div class="col-sm-7">
            <div class="card-body text-sm-end text-center ps-sm-0">
              <button
                data-bs-target="#addRoleModal"
                data-bs-toggle="modal"
                class="btn btn-sm btn-primary mb-4 text-nowrap add-new-role">
                Add New Role
              </button>
              <p class="mb-0">
                Add new role,<br />
                if it doesn't exist.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12">
      <h4 class="mt-6 mb-1">Total users with their roles</h4>
      <p class="mb-0">Find all of your company accounts and their associate roles.</p>
    </div>
    <div class="col-12">
      <div class="card">
        <div class="card-datatable table-responsive">
          <table class="datatables-users table border-top">
            <thead>
              <tr>
                <th></th>
                <th>User</th>
                <th>Role</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($users as $user)
                @php
                  $roleName = $user->roles->pluck('name')->first();
                  $iconName = $roleIconMap[$roleName] ?? 'tabler-user';
                  $statusLabel = $statusClass[$user->status] ?? 'bg-label-secondary';
                  $avatarIndex = ($user->id % 13) + 1;
                @endphp
                <tr>
                  <td></td>
                  <td>
                    <div class="d-flex justify-content-left align-items-center role-name">
                      <div class="avatar-wrapper">
                        <div class="avatar avatar-sm me-3">
                          <img src="{{ asset('assets/assets') }}/img/avatars/{{ $avatarIndex }}.png" alt="Avatar" class="rounded-circle" />
                        </div>
                      </div>
                      <div class="d-flex flex-column">
                        <span class="text-heading text-truncate"><span class="fw-medium">{{ $user->name }}</span></span>
                        <small>{{ $user->email }}</small>
                      </div>
                    </div>
                  </td>
                  <td>
                    <span class="text-truncate d-flex align-items-center">
                      <span class="me-2">
                        <i class="icon-base ti {{ $iconName }} icon-22px text-primary"></i>
                      </span>
                      {{ $roleName ?? 'User' }}
                    </span>
                  </td>
                  <td><span class="badge {{ $statusLabel }}">{{ ucfirst($user->status) }}</span></td>
                  <td>
                    <div class="d-flex align-items-center">
                      <button
                        class="btn btn-icon btn-text-secondary rounded-pill waves-effect"
                        data-bs-toggle="modal"
                        data-bs-target="#editUserModal"
                        data-user-id="{{ $user->id }}"
                        data-user-name="{{ $user->name }}"
                        data-user-email="{{ $user->email }}"
                        data-user-role="{{ $roleName }}">
                        <i class="icon-base ti tabler-edit icon-md"></i>
                      </button>
                      <a href="javascript:;" class="btn btn-icon btn-text-secondary rounded-pill waves-effect dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                        <i class="icon-base ti tabler-dots-vertical icon-md"></i>
                      </a>
                      <div class="dropdown-menu dropdown-menu-end m-0">
                        <form method="POST" action="{{ route('admin.users.toggle', $user) }}" data-confirm="Suspend this user?">
                          @csrf
                          @method('PATCH')
                          <button class="dropdown-item" type="submit">
                            {{ $user->status === 'suspended' ? 'Activate' : 'Suspend' }}
                          </button>
                        </form>
                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}" data-confirm="Delete this user? This will soft delete.">
                          @csrf
                          @method('DELETE')
                          <button class="dropdown-item" type="submit">
                            Delete
                          </button>
                        </form>
                      </div>
                    </div>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="addRoleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-simple modal-dialog-centered modal-add-new-role">
      <div class="modal-content">
        <div class="modal-body">
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          <div class="text-center mb-6">
            <h4 class="role-title">Add New Role</h4>
            <p class="text-body-secondary">Set role permissions</p>
          </div>
          <form id="addRoleForm" class="row g-3" method="POST" action="{{ route('admin.roles.store') }}">
            @csrf
            <div class="col-12 form-control-validation mb-3">
              <label class="form-label" for="modalRoleName">Role Name</label>
              <input
                type="text"
                id="modalRoleName"
                name="name"
                class="form-control"
                placeholder="Enter a role name" />
            </div>
            <div class="col-12">
              <h5 class="mb-6">Role Permissions</h5>
              <div class="table-responsive">
                <table class="table table-flush-spacing">
                  <tbody>
                    <tr>
                      <td class="text-nowrap fw-medium">
                        Admin Access
                        <i
                          class="icon-base ti tabler-info-circle icon-xs"
                          data-bs-toggle="tooltip"
                          data-bs-placement="top"
                          title="Allows access to the admin console"></i>
                      </td>
                      <td>
                        <div class="d-flex justify-content-end">
                          <div class="form-check mb-0">
                            <input class="form-check-input" type="checkbox" id="selectAll" />
                            <label class="form-check-label" for="selectAll"> Select All </label>
                          </div>
                        </div>
                      </td>
                    </tr>
                    @foreach ($permissions as $permission)
                      <tr>
                        <td class="text-nowrap fw-medium text-heading">{{ $permission->name }}</td>
                        <td>
                          <div class="d-flex justify-content-end">
                            <div class="form-check mb-0">
                              <input class="form-check-input permission-checkbox" type="checkbox" name="permissions[]" value="{{ $permission->name }}" />
                              <label class="form-check-label"> Allow </label>
                            </div>
                          </div>
                        </td>
                      </tr>
                    @endforeach
                    @if ($permissions->isEmpty())
                      <tr>
                        <td colspan="2" class="text-center text-body-secondary">No permissions available.</td>
                      </tr>
                    @endif
                  </tbody>
                </table>
              </div>
            </div>
            <div class="col-12 text-center">
              <button type="submit" class="btn btn-primary me-sm-4 me-1">Submit</button>
              <button type="reset" class="btn btn-label-secondary" data-bs-dismiss="modal" aria-label="Close">Cancel</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="editRoleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-simple modal-dialog-centered modal-add-new-role">
      <div class="modal-content">
        <div class="modal-body">
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          <div class="text-center mb-6">
            <h4 class="role-title">Edit Role</h4>
            <p class="text-body-secondary">Update role permissions</p>
          </div>
          <form id="editRoleForm" class="row g-3" method="POST">
            @csrf
            @method('PATCH')
            <div class="col-12 form-control-validation mb-3">
              <label class="form-label" for="editRoleName">Role Name</label>
              <input
                type="text"
                id="editRoleName"
                name="name"
                class="form-control"
                placeholder="Enter a role name" />
            </div>
            <div class="col-12">
              <h5 class="mb-6">Role Permissions</h5>
              <div class="table-responsive">
                <table class="table table-flush-spacing">
                  <tbody>
                    @foreach ($permissions as $permission)
                      <tr>
                        <td class="text-nowrap fw-medium text-heading">{{ $permission->name }}</td>
                        <td>
                          <div class="d-flex justify-content-end">
                            <div class="form-check mb-0">
                              <input class="form-check-input edit-role-permission" type="checkbox" name="permissions[]" value="{{ $permission->name }}" />
                              <label class="form-check-label"> Allow </label>
                            </div>
                          </div>
                        </td>
                      </tr>
                    @endforeach
                    @if ($permissions->isEmpty())
                      <tr>
                        <td colspan="2" class="text-center text-body-secondary">No permissions available.</td>
                      </tr>
                    @endif
                  </tbody>
                </table>
              </div>
            </div>
            <div class="col-12 text-center">
              <button type="submit" class="btn btn-primary me-sm-4 me-1">Submit</button>
              <button type="reset" class="btn btn-label-secondary" data-bs-dismiss="modal" aria-label="Close">Cancel</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-simple modal-add-new-user">
      <div class="modal-content">
        <div class="modal-body">
          <button type="button" class="btn-close btn-pinned" data-bs-dismiss="modal" aria-label="Close"></button>
          <div class="text-center mb-6">
            <h3>Add New User</h3>
            <p class="text-body-secondary">Create a user and assign a role.</p>
          </div>
          <form class="row g-3" method="POST" action="{{ route('admin.users.store') }}">
            @csrf
            <div class="col-12">
              <label class="form-label" for="userName">Full Name</label>
              <input type="text" id="userName" name="name" class="form-control" placeholder="Enter name" />
            </div>
            <div class="col-12">
              <label class="form-label" for="userEmail">Email</label>
              <input type="email" id="userEmail" name="email" class="form-control" placeholder="Enter email" />
            </div>
            <div class="col-12">
              <label class="form-label" for="userPassword">Password</label>
              <input type="password" id="userPassword" name="password" class="form-control" placeholder="Create password" />
            </div>
            <div class="col-12">
              <label class="form-label" for="userRole">Role</label>
              <select id="userRole" name="role" class="form-select">
                @foreach ($roles as $role)
                  <option value="{{ $role->name }}">{{ $role->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-12 text-center mt-6">
              <button type="submit" class="btn btn-primary me-sm-4 me-1">Create User</button>
              <button type="reset" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-simple modal-add-new-user">
      <div class="modal-content">
        <div class="modal-body">
          <button type="button" class="btn-close btn-pinned" data-bs-dismiss="modal" aria-label="Close"></button>
          <div class="text-center mb-6">
            <h3>Edit User</h3>
            <p class="text-body-secondary">Update user info and role.</p>
          </div>
          <form class="row g-3" method="POST" id="editUserForm">
            @csrf
            @method('PATCH')
            <div class="col-12">
              <label class="form-label" for="editUserName">Full Name</label>
              <input type="text" id="editUserName" name="name" class="form-control" placeholder="Enter name" />
            </div>
            <div class="col-12">
              <label class="form-label" for="editUserEmail">Email</label>
              <input type="email" id="editUserEmail" name="email" class="form-control" placeholder="Enter email" />
            </div>
            <div class="col-12">
              <label class="form-label" for="editUserRole">Role</label>
              <select id="editUserRole" name="role" class="form-select">
                @foreach ($roles as $role)
                  <option value="{{ $role->name }}">{{ $role->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-12 text-center mt-6">
              <button type="submit" class="btn btn-primary me-sm-4 me-1">Update User</button>
              <button type="reset" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('page-scripts')
  <script src="{{ asset('assets/assets') }}/vendor/libs/datatables-bs5/datatables-bootstrap5.js"></script>
  <script src="{{ asset('assets/assets') }}/vendor/libs/select2/select2.js"></script>
  <script src="{{ asset('assets/assets') }}/vendor/libs/@form-validation/popular.js"></script>
  <script src="{{ asset('assets/assets') }}/vendor/libs/@form-validation/bootstrap5.js"></script>
  <script src="{{ asset('assets/assets') }}/vendor/libs/@form-validation/auto-focus.js"></script>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const table = document.querySelector('.datatables-users');
      if (table && window.DataTable) {
        new DataTable(table, {
          order: [[2, 'asc']],
          layout: {
            topStart: {
              rowClass: 'row my-md-0 me-3 ms-0 justify-content-between',
              features: [
                {
                  pageLength: {
                    menu: [10, 25, 50, 100],
                    text: '_MENU_'
                  }
                }
              ]
            },
            topEnd: {
              features: [
                {
                  search: {
                    placeholder: 'Search User',
                    text: '_INPUT_'
                  }
                },
                {
                  buttons: [
                    {
                      extend: 'collection',
                      className: 'btn btn-label-secondary dropdown-toggle me-4',
                      text: '<span class="d-flex align-items-center gap-1"><i class="icon-base ti tabler-upload icon-xs"></i> <span class="d-inline-block">Export</span></span>',
                      buttons: ['print', 'csv', 'excel', 'pdf', 'copy']
                    },
                    {
                      text: '<i class="icon-base ti tabler-plus me-0 me-sm-1 icon-16px"></i><span class="d-none d-sm-inline-block">Add New User</span>',
                      className: 'add-new btn btn-primary rounded-2 waves-effect waves-light',
                      attr: {
                        'data-bs-toggle': 'modal',
                        'data-bs-target': '#addUserModal'
                      }
                    }
                  ]
                }
              ]
            },
            bottomStart: {
              rowClass: 'row mx-3 justify-content-between',
              features: ['info']
            },
            bottomEnd: 'paging'
          },
          language: {
            paginate: {
              next: '<i class="icon-base ti tabler-chevron-right scaleX-n1-rtl icon-18px"></i>',
              previous: '<i class="icon-base ti tabler-chevron-left scaleX-n1-rtl icon-18px"></i>',
              first: '<i class="icon-base ti tabler-chevrons-left scaleX-n1-rtl icon-18px"></i>',
              last: '<i class="icon-base ti tabler-chevrons-right scaleX-n1-rtl icon-18px"></i>'
            }
          },
          responsive: {
            details: {
              display: DataTable.Responsive.display.modal({
                header: function () {
                  return 'User Details';
                }
              }),
              type: 'column'
            }
          }
        });
      }

      setTimeout(() => {
        const elementsToModify = [
          { selector: '.dt-buttons .btn', classToRemove: 'btn-secondary' },
          { selector: '.dt-buttons.btn-group .btn-group', classToRemove: 'btn-group' },
          { selector: '.dt-buttons.btn-group', classToRemove: 'btn-group', classToAdd: 'd-flex' },
          { selector: '.dt-search .form-control', classToRemove: 'form-control-sm' },
          { selector: '.dt-length .form-select', classToRemove: 'form-select-sm' },
          { selector: '.dt-length', classToAdd: 'mb-md-6 mb-0' },
          { selector: '.dt-layout-start', classToAdd: 'ps-3 mt-0' },
          {
            selector: '.dt-layout-end',
            classToRemove: 'justify-content-between',
            classToAdd: 'justify-content-md-between justify-content-center d-flex flex-wrap gap-4 mt-0 mb-md-0 mb-6'
          },
          { selector: '.dt-layout-table', classToRemove: 'row mt-2' },
          { selector: '.dt-layout-full', classToRemove: 'col-md col-12', classToAdd: 'table-responsive' }
        ];

        elementsToModify.forEach(({ selector, classToRemove, classToAdd }) => {
          document.querySelectorAll(selector).forEach(element => {
            if (classToRemove) {
              classToRemove.split(' ').forEach(className => element.classList.remove(className));
            }
            if (classToAdd) {
              classToAdd.split(' ').forEach(className => element.classList.add(className));
            }
          });
        });
      }, 100);

      const editRoleModal = document.getElementById('editRoleModal');
      if (editRoleModal) {
        editRoleModal.addEventListener('show.bs.modal', function (event) {
          const trigger = event.relatedTarget;
          const roleId = trigger.getAttribute('data-role-id');
          const roleName = trigger.getAttribute('data-role-name');
          const rolePermissions = trigger.getAttribute('data-role-permissions');
          const form = document.getElementById('editRoleForm');

          form.action = `{{ url('/admin/roles') }}/${roleId}`;
          document.getElementById('editRoleName').value = roleName;

          const permissionList = rolePermissions ? JSON.parse(rolePermissions) : [];
          const permissionSet = new Set(permissionList);
          form.querySelectorAll('.edit-role-permission').forEach((checkbox) => {
            checkbox.checked = permissionSet.has(checkbox.value);
          });
        });
      }

      const addRoleModal = document.getElementById('addRoleModal');
      if (addRoleModal) {
        addRoleModal.addEventListener('show.bs.modal', function (event) {
          const trigger = event.relatedTarget;
          if (!trigger || !trigger.classList.contains('role-duplicate')) {
            return;
          }
          const roleName = trigger.getAttribute('data-role-name');
          const rolePermissions = trigger.getAttribute('data-role-permissions');
          const nameInput = document.getElementById('modalRoleName');
          const permissionList = rolePermissions ? JSON.parse(rolePermissions) : [];
          const permissionSet = new Set(permissionList);

          nameInput.value = roleName ? `${roleName} Copy` : '';
          document.querySelectorAll('.permission-checkbox').forEach((checkbox) => {
            checkbox.checked = permissionSet.has(checkbox.value);
          });
        });
      }

      const selectAll = document.getElementById('selectAll');
      if (selectAll) {
        selectAll.addEventListener('change', function () {
          document.querySelectorAll('.permission-checkbox').forEach((checkbox) => {
            checkbox.checked = selectAll.checked;
          });
        });
      }

      const editUserModal = document.getElementById('editUserModal');
      if (editUserModal) {
        editUserModal.addEventListener('show.bs.modal', function (event) {
          const trigger = event.relatedTarget;
          const userId = trigger.getAttribute('data-user-id');
          const userName = trigger.getAttribute('data-user-name');
          const userEmail = trigger.getAttribute('data-user-email');
          const userRole = trigger.getAttribute('data-user-role');
          const form = document.getElementById('editUserForm');

          form.action = `{{ url('/admin/users') }}/${userId}`;
          document.getElementById('editUserName').value = userName || '';
          document.getElementById('editUserEmail').value = userEmail || '';
          document.querySelectorAll('#editUserRole option').forEach((option) => {
            option.selected = option.value === userRole;
          });
        });
      }

    });
  </script>
@endpush

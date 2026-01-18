@extends('admin::components.layouts.master')
@section('title', 'Permissions | RoomGate Admin')
@section('page-title', 'Permissions')

@push('page-styles')
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/datatables-bs5/datatables.bootstrap5.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/@form-validation/form-validation.css" />
@endpush

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="card">
    <div class="card-datatable table-responsive">
      <table class="datatables-permissions table border-top">
        <thead>
          <tr>
            <th></th>
            <th></th>
            <th>Name</th>
            <th>Assigned To</th>
            <th>Created Date</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($permissions as $permission)
            <tr>
              <td></td>
              <td></td>
              <td><span class="text-nowrap text-heading">{{ $permission->name }}</span></td>
              <td>
                @php
                  $roleBadge = [
                      'platform_admin' => 'bg-label-primary',
                      'admin' => 'bg-label-primary',
                      'owner' => 'bg-label-info',
                      'staff' => 'bg-label-success',
                      'tenant' => 'bg-label-secondary',
                      'support' => 'bg-label-info',
                      'billing_admin' => 'bg-label-warning',
                  ];
                  $statusClass = [
                      'active' => 'bg-label-success',
                      'inactive' => 'bg-label-secondary',
                      'suspended' => 'bg-label-warning',
                  ];
                @endphp
                <span class="text-nowrap">
                  @forelse ($permission->roles as $role)
                    @php $badgeClass = $roleBadge[$role->name] ?? 'bg-label-primary'; @endphp
                    <span class="badge {{ $badgeClass }} me-2">{{ $role->name }}</span>
                  @empty
                    <span class="text-body-secondary">None</span>
                  @endforelse
                </span>
              </td>
              <td><span class="text-nowrap">{{ $permission->created_at?->format('d M Y') }}</span></td>
              <td>
                <span class="badge {{ $statusClass[$permission->status] ?? 'bg-label-secondary' }}">
                  {{ ucfirst($permission->status) }}
                </span>
              </td>
              <td>
                <div class="d-flex align-items-center">
                  <button
                    class="btn btn-icon me-1"
                    data-bs-target="#editPermissionModal"
                    data-bs-toggle="modal"
                    data-permission-id="{{ $permission->id }}"
                    data-permission-name="{{ $permission->name }}"
                    data-permission-system="{{ $permission->is_system ? '1' : '0' }}">
                    <i class="icon-base ti tabler-edit icon-22px"></i>
                  </button>
                  <a href="javascript:;" class="btn btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                    <i class="icon-base ti tabler-dots-vertical icon-22px"></i>
                  </a>
                  <div class="dropdown-menu dropdown-menu-end m-0">
                    <form method="POST" action="{{ route('admin.permissions.toggle', $permission) }}" data-confirm="Are you sure you want to change this permission status?">
                      @csrf
                      @method('PATCH')
                      <button class="dropdown-item" type="submit">
                        {{ $permission->status === 'suspended' ? 'Activate' : 'Suspend' }}
                      </button>
                    </form>
                    <form method="POST" action="{{ route('admin.permissions.destroy', $permission) }}" data-confirm="Delete this permission? This cannot be undone.">
                      @csrf
                      @method('DELETE')
                      <button class="dropdown-item {{ $permission->is_system ? 'disabled' : '' }}" type="submit" {{ $permission->is_system ? 'disabled' : '' }}>
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

  <div class="modal fade" id="addPermissionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-simple">
      <div class="modal-content">
        <div class="modal-body">
          <button type="button" class="btn-close btn-pinned" data-bs-dismiss="modal" aria-label="Close"></button>
          <div class="text-center mb-6">
            <h3>Add New Permission</h3>
            <p class="text-body-secondary">Permissions you may use and assign to your users.</p>
          </div>
          <form id="addPermissionForm" class="row" method="POST" action="{{ route('admin.permissions.store') }}">
            @csrf
            <div class="col-12 form-control-validation mb-4">
              <label class="form-label" for="modalPermissionName">Permission Name</label>
              <input
                type="text"
                id="modalPermissionName"
                name="name"
                class="form-control"
                placeholder="Permission Name"
                autofocus />
            </div>
            <div class="col-12 mb-2">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="corePermission" name="is_system" value="1" />
                <label class="form-check-label" for="corePermission"> Set as core permission </label>
              </div>
            </div>
            <div class="col-12 text-center demo-vertical-spacing">
              <button type="submit" class="btn btn-primary me-sm-4 me-1">Create Permission</button>
              <button
                type="reset"
                class="btn btn-label-secondary"
                data-bs-dismiss="modal"
                aria-label="Close">
                Discard
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="editPermissionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-simple">
      <div class="modal-content">
        <div class="modal-body">
          <button type="button" class="btn-close btn-pinned" data-bs-dismiss="modal" aria-label="Close"></button>
          <div class="text-center mb-6">
            <h3>Edit Permission</h3>
            <p class="text-body-secondary">Edit permission as per your requirements.</p>
          </div>
          <div class="alert alert-warning" role="alert">
            <h6 class="alert-heading mb-2">Warning</h6>
            <p class="mb-0">
              By editing the permission name, you might break the system permissions functionality.
            </p>
          </div>
          <form id="editPermissionForm" class="row" method="POST">
            @csrf
            @method('PATCH')
            <div class="col-sm-9 form-control-validation">
              <label class="form-label" for="editPermissionName">Permission Name</label>
              <input
                type="text"
                id="editPermissionName"
                name="name"
                class="form-control"
                placeholder="Permission Name" />
            </div>
            <div class="col-sm-3 mb-4">
              <label class="form-label invisible d-none d-sm-inline-block">Button</label>
              <button type="submit" class="btn btn-primary mt-1 mt-sm-0">Update</button>
            </div>
            <div class="col-12">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="editCorePermission" name="is_system" value="1" />
                <label class="form-check-label" for="editCorePermission"> Set as core permission </label>
              </div>
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
  <script src="{{ asset('assets/assets') }}/vendor/libs/@form-validation/popular.js"></script>
  <script src="{{ asset('assets/assets') }}/vendor/libs/@form-validation/bootstrap5.js"></script>
  <script src="{{ asset('assets/assets') }}/vendor/libs/@form-validation/auto-focus.js"></script>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const table = document.querySelector('.datatables-permissions');
      if (table && window.DataTable) {
        new DataTable(table, {
          order: [[2, 'asc']],
          layout: {
            topStart: {
              rowClass: 'row m-3 my-0 justify-content-between',
              features: [
                {
                  pageLength: {
                    menu: [10, 25, 50, 100],
                    text: 'Show _MENU_'
                  }
                }
              ]
            },
            topEnd: {
              features: [
                {
                  search: {
                    placeholder: 'Search Permissions',
                    text: '_INPUT_'
                  }
                },
                {
                  buttons: [
                    {
                      text: '<i class="icon-base ti tabler-plus icon-xs me-0 me-sm-2"></i><span class="d-none d-sm-inline-block">Add Permission</span>',
                      className: 'add-new btn btn-primary',
                      attr: {
                        'data-bs-toggle': 'modal',
                        'data-bs-target': '#addPermissionModal'
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
                  return 'Permission Details';
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
          { selector: '.dt-search', classToAdd: 'me-4' },
          { selector: '.dt-search .form-control', classToRemove: 'form-control-sm' },
          { selector: '.dt-length', classToAdd: 'mb-0 mb-md-5' },
          { selector: '.dt-length .form-select', classToRemove: 'form-select-sm' },
          { selector: '.dt-buttons', classToAdd: 'mb-0 w-auto' },
          { selector: '.dt-layout-start', classToAdd: 'mt-0 px-5' },
          {
            selector: '.dt-layout-end',
            classToRemove: 'justify-content-between',
            classToAdd: 'justify-content-md-between justify-content-center d-flex flex-wrap gap-md-4 mb-sm-0 mb-6 mt-0'
          },
          { selector: '.dt-layout-start', classToAdd: 'mt-0' },
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

      const editPermissionModal = document.getElementById('editPermissionModal');
      if (editPermissionModal) {
        editPermissionModal.addEventListener('show.bs.modal', function (event) {
          const trigger = event.relatedTarget;
          const permissionId = trigger.getAttribute('data-permission-id');
          const permissionName = trigger.getAttribute('data-permission-name');
          const permissionSystem = trigger.getAttribute('data-permission-system');
          const form = document.getElementById('editPermissionForm');

          form.action = `{{ url('/admin/permissions') }}/${permissionId}`;
          document.getElementById('editPermissionName').value = permissionName;
          document.getElementById('editCorePermission').checked = permissionSystem === '1';

        });
      }

    });
  </script>
@endpush

@extends('core::components.layouts.master')
@section('title', 'Tenant Members | RoomGate')
@section('page-title', 'Tenant Members')

@push('page-styles')
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/datatables-bs5/datatables.bootstrap5.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/select2/select2.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/animate-css/animate.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/sweetalert2/sweetalert2.css" />
@endpush

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="card mb-6">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
      <div>
        <h5 class="mb-0">Tenant Members</h5>
        <small class="text-body-secondary">Create renters and staff for contracts.</small>
      </div>
      <div class="d-flex align-items-center gap-2">
        <span class="text-body-secondary small">Limit: {{ $planLimits['tenant_users_max'] ?? '—' }} users</span>
      </div>
    </div>
    <div class="card-datatable table-responsive">
      <table class="datatables-tenant-users table border-top">
        <thead>
          <tr>
            <th></th>
            <th>User</th>
            <th>Role</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>
</div>

<div class="modal fade" id="addMemberModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="mb-1">Add Member</h4>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form class="row g-3" method="POST" action="{{ route('core.tenant-members.store') }}">
          @csrf
          <div class="col-md-6">
            <label class="form-label" for="memberName">Full Name</label>
            <input type="text" id="memberName" name="name" class="form-control" required />
          </div>
          <div class="col-md-6">
            <label class="form-label" for="memberEmail">Email</label>
            <input type="email" id="memberEmail" name="email" class="form-control" required />
          </div>
          <div class="col-md-6">
            <label class="form-label" for="memberPassword">Password</label>
            <input type="password" id="memberPassword" name="password" class="form-control" required />
          </div>
          <div class="col-md-3">
            <label class="form-label" for="memberRole">Role</label>
            <select id="memberRole" name="role" class="form-select">
              <option value="tenant">Tenant</option>
              <option value="staff">Staff</option>
              <option value="admin">Admin</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label" for="memberStatus">Status</label>
            <select id="memberStatus" name="status" class="form-select">
              <option value="active">Active</option>
              <option value="invited">Invited</option>
              <option value="disabled">Disabled</option>
            </select>
          </div>
          <div class="col-12">
            <button type="submit" class="btn btn-primary me-sm-4 me-1">Add Member</button>
            <button type="reset" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="editMemberModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="mb-1">Edit Member</h4>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form class="row g-3" method="POST" id="editMemberForm">
          @csrf
          @method('PATCH')
          <div class="col-md-6">
            <label class="form-label" for="editMemberName">Full Name</label>
            <input type="text" id="editMemberName" name="name" class="form-control" />
          </div>
          <div class="col-md-6">
            <label class="form-label" for="editMemberEmail">Email</label>
            <input type="email" id="editMemberEmail" name="email" class="form-control" />
          </div>
          <div class="col-md-6">
            <label class="form-label" for="editMemberPassword">Password</label>
            <input type="password" id="editMemberPassword" name="password" class="form-control" placeholder="Leave blank to keep" />
          </div>
          <div class="col-md-3">
            <label class="form-label" for="editMemberRole">Role</label>
            <select id="editMemberRole" name="role" class="form-select">
              <option value="tenant">Tenant</option>
              <option value="staff">Staff</option>
              <option value="admin">Admin</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label" for="editMemberStatus">Status</label>
            <select id="editMemberStatus" name="status" class="form-select">
              <option value="active">Active</option>
              <option value="invited">Invited</option>
              <option value="disabled">Disabled</option>
            </select>
          </div>
          <div class="col-12">
            <button type="submit" class="btn btn-primary me-sm-4 me-1">Update Member</button>
            <button type="reset" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

@push('page-scripts')
  <script src="{{ asset('assets/assets') }}/vendor/libs/datatables-bs5/datatables-bootstrap5.js"></script>
  <script src="{{ asset('assets/assets') }}/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.js"></script>
  <script src="{{ asset('assets/assets') }}/vendor/libs/select2/select2.js"></script>
  <script>
    function bindConfirmForms(scope) {
      const confirmForms = (scope || document).querySelectorAll('form[data-confirm]');
      const swal = window.Swal
        ? window.Swal.mixin({
            buttonsStyling: false,
            customClass: {
              confirmButton: 'btn btn-primary',
              cancelButton: 'btn btn-label-danger',
              denyButton: 'btn btn-label-secondary'
            }
          })
        : null;

      confirmForms.forEach((form) => {
        if (form.dataset.confirmBound === '1') {
          return;
        }
        form.dataset.confirmBound = '1';
        form.addEventListener('submit', function (event) {
          const message = form.getAttribute('data-confirm');
          if (!message) {
            return;
          }
          event.preventDefault();
          if (!swal) {
            if (window.confirm(message)) {
              form.submit();
            }
            return;
          }
          swal
            .fire({
              title: 'Are you sure?',
              text: message,
              icon: 'warning',
              showCancelButton: true,
              confirmButtonText: 'Yes, continue',
              cancelButtonText: 'Cancel',
              reverseButtons: true
            })
            .then((result) => {
              if (result.isConfirmed) {
                form.submit();
              }
            });
        });
      });
    }

    function initTenantMembersTable() {
      if (window.$ && $.fn.select2) {
        $('.select2').each(function () {
          const placeholder = $(this).find('option[value=""]').first().text() || 'Select';
          $(this).select2({
            placeholder: placeholder,
            allowClear: true,
            width: '100%'
          });
        });
      }

      const table = document.querySelector('.datatables-tenant-users');
      if (table && window.DataTable && !table.dataset.bound) {
        table.dataset.bound = '1';
        const dataTable = new DataTable(table, RoomGateDataTables.buildOptions({
          processing: true,
          serverSide: true,
          ajax: '{{ route('core.tenant-members.data') }}',
          order: [[1, 'asc']],
          columnDefs: [
            {
              targets: 0,
              className: 'control',
              orderable: false,
              searchable: false,
              render: function () {
                return '';
              }
            },
            { targets: 4, orderable: false, searchable: false }
          ],
          layout: Object.assign({}, RoomGateDataTables.layout, {
            topEnd: {
              features: [
                {
                  search: {
                    placeholder: 'Search Member',
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
                      text: '<i class="icon-base ti tabler-plus me-0 me-sm-1 icon-16px"></i><span class="d-none d-sm-inline-block">Add Member</span>',
                      className: 'add-new btn btn-primary rounded-2 waves-effect waves-light',
                      attr: {
                        'data-bs-toggle': 'modal',
                        'data-bs-target': '#addMemberModal'
                      }
                    }
                  ]
                }
              ]
            }
          }),
          language: Object.assign({}, RoomGateDataTables.language, { emptyTable: 'No members yet.' }),
          responsive: {
            details: {
              display: DataTable.Responsive.display.modal({
                header: function () {
                  return 'Member Details';
                }
              }),
              type: 'column'
            }
          }
        }));

        dataTable.on('draw', function () {
          bindConfirmForms(table);
        });

        if (window.RoomGateDataTables && RoomGateDataTables.applyLayoutClasses) {
          setTimeout(() => {
            RoomGateDataTables.applyLayoutClasses();
          }, 100);
        }
      }

      const editModal = document.getElementById('editMemberModal');
      if (editModal && !editModal.dataset.bound) {
        editModal.dataset.bound = '1';
        editModal.addEventListener('show.bs.modal', function (event) {
          const trigger = event.relatedTarget;
          if (!trigger) {
            return;
          }
          const memberId = trigger.getAttribute('data-member-id');
          const form = document.getElementById('editMemberForm');
          form.action = `{{ url('/t') }}/{{ $tenant->slug }}/core/tenant-members/${memberId}`;
          document.getElementById('editMemberName').value = trigger.getAttribute('data-member-name') || '';
          document.getElementById('editMemberEmail').value = trigger.getAttribute('data-member-email') || '';
          document.getElementById('editMemberRole').value = trigger.getAttribute('data-member-role') || 'tenant';
          document.getElementById('editMemberStatus').value = trigger.getAttribute('data-member-status') || 'active';
          document.getElementById('editMemberPassword').value = '';
        });
      }
    }

    document.addEventListener('DOMContentLoaded', initTenantMembersTable);
  </script>
@endpush

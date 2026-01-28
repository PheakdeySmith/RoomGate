@extends('admin::components.layouts.master')
@section('title', 'Tenant Security | RoomGate Admin')

@push('page-styles')
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/datatables-bs5/datatables.bootstrap5.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/select2/select2.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/animate-css/animate.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/sweetalert2/sweetalert2.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/css/pages/page-user-view.css" />
@endpush

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="row">
    @include('admin::dashboard.tenant-view.partials.sidebar')

    <div class="col-xl-8 col-lg-7 order-0 order-md-1" data-ajax-container="user-view">
      @include('admin::dashboard.tenant-view.partials.tabs')

      <div class="card mb-6">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
          <h5 class="mb-0">Tenant Members</h5>
          <a href="{{ route('admin.tenants.members.export', $tenant) }}" class="btn btn-label-secondary btn-sm">
            <i class="icon-base ti tabler-download icon-16px me-1"></i>Export CSV
          </a>
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
  </div>
</div>

@include('admin::dashboard.tenant-view.partials.member-modals')
@endsection

@push('page-scripts')
  <script src="{{ asset('assets/assets') }}/vendor/libs/datatables-bs5/datatables-bootstrap5.js"></script>
  <script src="{{ asset('assets/assets') }}/vendor/libs/select2/select2.js"></script>
  <script src="{{ asset('assets/assets') }}/js/roomgate-ajax.js"></script>
  <script src="{{ asset('assets/assets') }}/js/admin-user-view-ajax.js"></script>

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
          ajax: '{{ route('admin.tenants.members.data', $tenant) }}',
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
      }

      const tenantId = @json($tenant->id);
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
          form.action = `{{ url('/admin/tenants') }}/${tenantId}/members/${memberId}`;
          document.getElementById('editMemberName').value = trigger.getAttribute('data-member-name') || '';
          document.getElementById('editMemberEmail').value = trigger.getAttribute('data-member-email') || '';
          document.getElementById('editMemberRole').value = trigger.getAttribute('data-member-role') || 'tenant';
          document.getElementById('editMemberStatus').value = trigger.getAttribute('data-member-status') || 'active';
          document.getElementById('editMemberPassword').value = '';
        });
      }
    }

    document.addEventListener('DOMContentLoaded', initTenantMembersTable);
    document.addEventListener('user-view:loaded', initTenantMembersTable);
  </script>
@endpush

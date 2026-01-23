@extends('admin::components.layouts.master')
@section('title', 'Tenant Details | RoomGate Admin')
@section('page-title', 'Tenant Details')

@push('page-styles')
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/datatables-bs5/datatables.bootstrap5.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/select2/select2.css" />
@endpush

@section('content')
@php
  $memberStatus = [
      'active' => 'bg-label-success',
      'invited' => 'bg-label-warning',
      'disabled' => 'bg-label-secondary',
  ];
@endphp

<div class="container-xxl flex-grow-1 container-p-y">
  <div class="card mb-6">
    <div class="card-body">
      <div class="d-flex flex-wrap align-items-start justify-content-between gap-4">
        <div>
          <h4 class="mb-1">{{ $tenant->name }}</h4>
          <div class="text-body-secondary">Slug: {{ $tenant->slug }}</div>
          <div class="text-body-secondary">Status: {{ ucfirst($tenant->status) }}</div>
        </div>
        <div class="d-flex flex-column align-items-end">
          <span class="text-body-secondary">Active Plan</span>
          <span class="text-heading fw-medium">
            {{ $tenant->subscriptions->sortByDesc('current_period_end')->first()?->plan?->name ?? '—' }}
          </span>
        </div>
      </div>
    </div>
  </div>

  <div class="card">
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
        <tbody>
          @foreach ($tenant->users as $user)
            @php
              $pivot = $user->pivot;
              $statusClass = $memberStatus[$pivot->status] ?? 'bg-label-secondary';
            @endphp
            <tr>
              <td></td>
              <td>
                <div class="d-flex flex-column">
                  <span class="text-heading">{{ $user->name }}</span>
                  <small class="text-body-secondary">{{ $user->email }}</small>
                </div>
              </td>
              <td class="text-capitalize">{{ $pivot->role }}</td>
              <td><span class="badge {{ $statusClass }}">{{ ucfirst($pivot->status) }}</span></td>
              <td>
                <div class="d-flex align-items-center">
                  <button
                    class="btn btn-icon btn-text-secondary rounded-pill waves-effect"
                    data-bs-toggle="modal"
                    data-bs-target="#editMemberModal"
                    data-member-id="{{ $user->id }}"
                    data-member-name="{{ $user->name }}"
                    data-member-email="{{ $user->email }}"
                    data-member-role="{{ $pivot->role }}"
                    data-member-status="{{ $pivot->status }}">
                    <i class="icon-base ti tabler-edit icon-md"></i>
                  </button>
                  <form method="POST" action="{{ route('admin.tenants.members.destroy', [$tenant, $user]) }}" data-confirm="Remove this member?">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-icon btn-text-danger rounded-pill waves-effect">
                      <i class="icon-base ti tabler-trash icon-md"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="modal fade" id="addMemberModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-simple">
    <div class="modal-content">
      <div class="modal-body">
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        <div class="text-center mb-6">
          <span class="badge bg-label-primary rounded-1 p-2 mb-3">
            <i class="icon-base ti tabler-user-plus icon-md"></i>
          </span>
          <h4 class="mb-1">Add Tenant Member</h4>
          <p class="text-body-secondary mb-0">Invite a new user to this tenant.</p>
        </div>
        <form class="row g-3" method="POST" action="{{ route('admin.tenants.members.store', $tenant) }}">
          @csrf
          <div class="col-12">
            <label class="form-label" for="memberName">Full Name</label>
            <input type="text" id="memberName" name="name" class="form-control" placeholder="Jane Doe" />
          </div>
          <div class="col-12">
            <label class="form-label" for="memberEmail">Email</label>
            <input type="email" id="memberEmail" name="email" class="form-control" placeholder="jane@example.com" />
          </div>
          <div class="col-12">
            <label class="form-label" for="memberPassword">Password</label>
            <input type="password" id="memberPassword" name="password" class="form-control" placeholder="Minimum 8 characters" />
          </div>
          <div class="col-6">
            <label class="form-label" for="memberRole">Role</label>
            <select id="memberRole" name="role" class="form-select">
              <option value="owner">Owner</option>
              <option value="admin">Admin</option>
              <option value="staff">Staff</option>
              <option value="tenant" selected>Tenant</option>
            </select>
          </div>
          <div class="col-6">
            <label class="form-label" for="memberStatus">Status</label>
            <select id="memberStatus" name="status" class="form-select">
              <option value="active">Active</option>
              <option value="invited">Invited</option>
              <option value="disabled">Disabled</option>
            </select>
          </div>
          <div class="col-12 text-center mt-6">
            <button type="submit" class="btn btn-primary me-sm-4 me-1">Add Member</button>
            <button type="reset" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="editMemberModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-simple">
    <div class="modal-content">
      <div class="modal-body">
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        <div class="text-center mb-6">
          <span class="badge bg-label-warning rounded-1 p-2 mb-3">
            <i class="icon-base ti tabler-edit icon-md"></i>
          </span>
          <h4 class="mb-1">Edit Tenant Member</h4>
          <p class="text-body-secondary mb-0">Update member role and status.</p>
        </div>
        <form class="row g-3" method="POST" id="editMemberForm">
          @csrf
          @method('PATCH')
          <div class="col-12">
            <label class="form-label" for="editMemberName">Full Name</label>
            <input type="text" id="editMemberName" name="name" class="form-control" />
          </div>
          <div class="col-12">
            <label class="form-label" for="editMemberEmail">Email</label>
            <input type="email" id="editMemberEmail" name="email" class="form-control" />
          </div>
          <div class="col-12">
            <label class="form-label" for="editMemberPassword">Password</label>
            <input type="password" id="editMemberPassword" name="password" class="form-control" placeholder="Leave blank to keep" />
          </div>
          <div class="col-6">
            <label class="form-label" for="editMemberRole">Role</label>
            <select id="editMemberRole" name="role" class="form-select">
              <option value="owner">Owner</option>
              <option value="admin">Admin</option>
              <option value="staff">Staff</option>
              <option value="tenant">Tenant</option>
            </select>
          </div>
          <div class="col-6">
            <label class="form-label" for="editMemberStatus">Status</label>
            <select id="editMemberStatus" name="status" class="form-select">
              <option value="active">Active</option>
              <option value="invited">Invited</option>
              <option value="disabled">Disabled</option>
            </select>
          </div>
          <div class="col-12 text-center mt-6">
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
  <script src="{{ asset('assets/assets') }}/vendor/libs/select2/select2.js"></script>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
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
      if (table && window.DataTable) {
        new DataTable(table, {
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
            }
          ],
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
                  return 'Member Details';
                }
              }),
              type: 'column'
            }
          }
        });

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
      }

      const tenantId = @json($tenant->id);
      const editModal = document.getElementById('editMemberModal');
      if (editModal) {
        editModal.addEventListener('show.bs.modal', function (event) {
          const trigger = event.relatedTarget;
          const memberId = trigger.getAttribute('data-member-id');
          const name = trigger.getAttribute('data-member-name');
          const email = trigger.getAttribute('data-member-email');
          const role = trigger.getAttribute('data-member-role');
          const status = trigger.getAttribute('data-member-status');

          const form = document.getElementById('editMemberForm');
          form.action = `{{ url('/admin/tenants') }}/${tenantId}/members/${memberId}`;
          document.getElementById('editMemberName').value = name || '';
          document.getElementById('editMemberEmail').value = email || '';
          document.getElementById('editMemberRole').value = role || 'tenant';
          document.getElementById('editMemberStatus').value = status || 'active';
          document.getElementById('editMemberPassword').value = '';
        });
      }
    });
  </script>
@endpush

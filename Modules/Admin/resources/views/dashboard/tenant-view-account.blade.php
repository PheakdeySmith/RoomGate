@extends('admin::components.layouts.master')
@section('title', 'Tenant Details | RoomGate Admin')

@push('page-styles')
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/datatables-bs5/datatables.bootstrap5.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/animate-css/animate.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/sweetalert2/sweetalert2.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/css/pages/page-user-view.css" />
@endpush

@section('content')
@php
  $currency = $tenant->default_currency ?? 'USD';
@endphp
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="row">
    @include('admin::dashboard.tenant-view.partials.sidebar')

    <div class="col-xl-8 col-lg-7 order-0 order-md-1" data-ajax-container="user-view">
      @include('admin::dashboard.tenant-view.partials.tabs')

      <div class="card mb-6">
        <div class="card-body">
          <h5 class="mb-2">Tenant Overview</h5>
          <p class="text-body-secondary mb-4">Core account details and live metrics.</p>

          <div class="row g-4">
            <div class="col-md-6">
              <div class="border rounded p-4 h-100">
                <div class="text-body-secondary">Active Subscription</div>
                <div class="fw-semibold">{{ $plan?->name ?? 'No plan' }}</div>
                <div class="text-body-secondary mt-2">Period</div>
                <div>
                  {{ $subscription?->current_period_start?->toDateString() ?? '—' }}
                  to
                  {{ $subscription?->current_period_end?->toDateString() ?? '—' }}
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="border rounded p-4 h-100">
                <div class="text-body-secondary">Owner</div>
                <div class="fw-semibold">{{ $owner?->name ?? '—' }}</div>
                <div class="text-body-secondary mt-2">Contact</div>
                <div>{{ $owner?->email ?? '—' }}</div>
              </div>
            </div>
          </div>

          <div class="row g-4 mt-2">
            <div class="col-md-3">
              <div class="border rounded p-3 text-center">
                <div class="text-body-secondary">Properties</div>
                <div class="fs-4 fw-semibold">{{ $stats['properties'] ?? 0 }}</div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="border rounded p-3 text-center">
                <div class="text-body-secondary">Rooms</div>
                <div class="fs-4 fw-semibold">{{ $stats['rooms'] ?? 0 }}</div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="border rounded p-3 text-center">
                <div class="text-body-secondary">Active Contracts</div>
                <div class="fs-4 fw-semibold">{{ $stats['contracts'] ?? 0 }}</div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="border rounded p-3 text-center">
                <div class="text-body-secondary">Open Invoices</div>
                <div class="fs-4 fw-semibold">{{ $stats['open_invoices'] ?? 0 }}</div>
              </div>
            </div>
          </div>

          <div class="row g-4 mt-2">
            <div class="col-md-3">
              <div class="border rounded p-3 text-center">
                <div class="text-body-secondary">Total Invoiced</div>
                <div class="fs-5 fw-semibold">{{ number_format($kpis['total_invoiced_cents'] / 100, 2) }} {{ $currency }}</div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="border rounded p-3 text-center">
                <div class="text-body-secondary">Total Paid</div>
                <div class="fs-5 fw-semibold">{{ number_format($kpis['total_paid_cents'] / 100, 2) }} {{ $currency }}</div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="border rounded p-3 text-center">
                <div class="text-body-secondary">Overdue Balance</div>
                <div class="fs-5 fw-semibold">{{ number_format($kpis['overdue_balance_cents'] / 100, 2) }} {{ $currency }}</div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="border rounded p-3 text-center">
                <div class="text-body-secondary">Occupancy</div>
                <div class="fs-5 fw-semibold">{{ $kpis['occupancy_rate'] }}%</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-body">
          <h5 class="mb-2">Plan Limits</h5>
          <div class="row g-3">
            @foreach ($planLimits as $key => $value)
              <div class="col-md-6">
                <div class="d-flex justify-content-between">
                  <span class="text-capitalize">{{ str_replace('_', ' ', $key) }}</span>
                  <span class="fw-semibold">{{ $value }}</span>
                </div>
              </div>
            @endforeach
            @if (empty($planLimits))
              <div class="col-12 text-body-secondary">No limits configured for this plan.</div>
            @endif
          </div>
        </div>
      </div>

      <div class="card mb-6">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
          <h5 class="mb-0">Tenant Members</h5>
          <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('admin.tenants.members.export', $tenant) }}" class="btn btn-label-secondary btn-sm">
              <i class="icon-base ti tabler-download icon-16px me-1"></i>Export CSV
            </a>
            <a href="{{ route('admin.tenants.security', $tenant) }}" class="btn btn-label-primary btn-sm">
              View all
            </a>
          </div>
        </div>
        <div class="card-datatable table-responsive">
          <table class="table datatables-tenant-members-preview">
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

      <div class="card mb-6">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
          <h5 class="mb-0">Recent Tenant Activity</h5>
          <a
            href="{{ route('admin.tenants.activity.export', $tenant) }}"
            class="btn btn-label-secondary btn-sm"
            id="tenant-activity-export">
            <i class="icon-base ti tabler-download icon-16px me-1"></i>Export CSV
          </a>
        </div>
        <div class="card-body border-bottom">
          <div class="row g-3 align-items-end">
            <div class="col-md-3">
              <label class="form-label" for="activityAction">Action</label>
              <select id="activityAction" class="form-select">
                <option value="">All</option>
                <option value="created">Created</option>
                <option value="updated">Updated</option>
                <option value="deleted">Deleted</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label" for="activityModel">Model</label>
              <select id="activityModel" class="form-select">
                <option value="">All</option>
                <option value="tenant">Tenant</option>
                <option value="tenant_users">Tenant Members</option>
                <option value="property">Property</option>
                <option value="room">Room</option>
                <option value="contract">Contract</option>
                <option value="invoice">Invoice</option>
                <option value="utility_provider">Utility Provider</option>
                <option value="utility_meter">Utility Meter</option>
              </select>
            </div>
            <div class="col-md-2">
              <label class="form-label" for="activityDateFrom">From</label>
              <input type="date" id="activityDateFrom" class="form-control" />
            </div>
            <div class="col-md-2">
              <label class="form-label" for="activityDateTo">To</label>
              <input type="date" id="activityDateTo" class="form-control" />
            </div>
            <div class="col-md-2 d-flex gap-2">
              <button type="button" class="btn btn-primary" id="activityApply">Apply</button>
              <button type="button" class="btn btn-label-secondary" id="activityReset">Reset</button>
            </div>
          </div>
        </div>
        <div class="card-datatable table-responsive">
          <table class="table datatables-tenant-activity">
            <thead>
              <tr>
                <th></th>
                <th>Action</th>
                <th>Model</th>
                <th>User</th>
                <th>URL</th>
                <th>When</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
          <h5 class="mb-0">Tenant Invoices</h5>
          <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('admin.tenants.invoices.export', $tenant) }}" class="btn btn-label-secondary btn-sm">
              <i class="icon-base ti tabler-download icon-16px me-1"></i>Export CSV
            </a>
            <a href="{{ route('admin.invoices.index', ['tenant_id' => $tenant->id]) }}" class="btn btn-label-primary btn-sm">
              View all
            </a>
          </div>
        </div>
        <div class="card-datatable table-responsive">
          <table class="table datatables-tenant-invoices-preview">
            <thead>
              <tr>
                <th></th>
                <th>Invoice #</th>
                <th>Status</th>
                <th>Total</th>
                <th>Due Date</th>
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
  <script src="{{ asset('assets/assets') }}/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.js"></script>
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

    function initTenantAccountTables() {
      if (!window.DataTable) {
        return;
      }

      const memberTable = document.querySelector('.datatables-tenant-members-preview');
      if (memberTable && !memberTable.dataset.bound) {
        memberTable.dataset.bound = '1';
        const dataTable = new DataTable(memberTable, RoomGateDataTables.buildOptions({
          processing: true,
          serverSide: true,
          pageLength: 5,
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
            topStart: {
              rowClass: 'row my-md-0 me-3 ms-0 justify-content-between',
              features: [
                {
                  pageLength: {
                    menu: [5, 10, 25],
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
          bindConfirmForms(memberTable);
        });
      }

      const invoiceTable = document.querySelector('.datatables-tenant-invoices-preview');
      if (invoiceTable && !invoiceTable.dataset.bound) {
        invoiceTable.dataset.bound = '1';
        const dataTable = new DataTable(invoiceTable, RoomGateDataTables.buildOptions({
          processing: true,
          serverSide: true,
          pageLength: 5,
          ajax: '{{ route('admin.tenants.invoices.data', $tenant) }}',
          order: [[1, 'desc']],
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
            { targets: 5, orderable: false, searchable: false }
          ],
          layout: Object.assign({}, RoomGateDataTables.layout, {
            topStart: {
              rowClass: 'row my-md-0 me-3 ms-0 justify-content-between',
              features: [
                {
                  pageLength: {
                    menu: [5, 10, 25],
                    text: '_MENU_'
                  }
                }
              ]
            },
            topEnd: {
              features: [
                {
                  search: {
                    placeholder: 'Search Invoice',
                    text: '_INPUT_'
                  }
                }
              ]
            }
          }),
          language: Object.assign({}, RoomGateDataTables.language, { emptyTable: 'No invoices yet.' }),
          responsive: {
            details: {
              display: DataTable.Responsive.display.modal({
                header: function () {
                  return 'Invoice Details';
                }
              }),
              type: 'column'
            }
          }
        }));

        dataTable.on('draw', function () {
          bindConfirmForms(invoiceTable);
        });
      }

      const activityTable = document.querySelector('.datatables-tenant-activity');
      if (activityTable && !activityTable.dataset.bound) {
        activityTable.dataset.bound = '1';
        const activityDataTable = new DataTable(activityTable, RoomGateDataTables.buildOptions({
          processing: true,
          serverSide: true,
          pageLength: 10,
          ajax: {
            url: '{{ route('admin.tenants.activity.data', $tenant) }}',
            data: function (d) {
              d.filter_action = document.getElementById('activityAction')?.value || '';
              d.filter_model = document.getElementById('activityModel')?.value || '';
              d.filter_date_from = document.getElementById('activityDateFrom')?.value || '';
              d.filter_date_to = document.getElementById('activityDateTo')?.value || '';
            }
          },
          order: [[5, 'desc']],
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
            { targets: 4, orderable: false }
          ],
          layout: Object.assign({}, RoomGateDataTables.layout, {
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
                    placeholder: 'Search Activity',
                    text: '_INPUT_'
                  }
                }
              ]
            }
          }),
          language: Object.assign({}, RoomGateDataTables.language, { emptyTable: 'No activity logged for this tenant yet.' }),
          responsive: {
            details: {
              display: DataTable.Responsive.display.modal({
                header: function () {
                  return 'Activity Details';
                }
              }),
              type: 'column'
            }
          }
        }));

        const applyBtn = document.getElementById('activityApply');
        const resetBtn = document.getElementById('activityReset');
        const exportBtn = document.getElementById('tenant-activity-export');
        const buildExportUrl = () => {
          const params = new URLSearchParams();
          const action = document.getElementById('activityAction')?.value || '';
          const model = document.getElementById('activityModel')?.value || '';
          const dateFrom = document.getElementById('activityDateFrom')?.value || '';
          const dateTo = document.getElementById('activityDateTo')?.value || '';
          if (action) params.set('action', action);
          if (model) params.set('model', model);
          if (dateFrom) params.set('date_from', dateFrom);
          if (dateTo) params.set('date_to', dateTo);
          const base = '{{ route('admin.tenants.activity.export', $tenant) }}';
          return params.toString() ? `${base}?${params.toString()}` : base;
        };

        if (applyBtn && !applyBtn.dataset.bound) {
          applyBtn.dataset.bound = '1';
          applyBtn.addEventListener('click', function () {
            activityDataTable.ajax.reload();
            if (exportBtn) {
              exportBtn.href = buildExportUrl();
            }
          });
        }

        if (resetBtn && !resetBtn.dataset.bound) {
          resetBtn.dataset.bound = '1';
          resetBtn.addEventListener('click', function () {
            const action = document.getElementById('activityAction');
            const model = document.getElementById('activityModel');
            const dateFrom = document.getElementById('activityDateFrom');
            const dateTo = document.getElementById('activityDateTo');
            if (action) action.value = '';
            if (model) model.value = '';
            if (dateFrom) dateFrom.value = '';
            if (dateTo) dateTo.value = '';
            activityDataTable.ajax.reload();
            if (exportBtn) {
              exportBtn.href = '{{ route('admin.tenants.activity.export', $tenant) }}';
            }
          });
        }

        if (exportBtn) {
          exportBtn.href = buildExportUrl();
        }
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

    document.addEventListener('DOMContentLoaded', initTenantAccountTables);
    document.addEventListener('user-view:loaded', initTenantAccountTables);
  </script>
@endpush

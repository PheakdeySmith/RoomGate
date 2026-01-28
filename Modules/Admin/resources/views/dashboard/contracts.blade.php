@extends('admin::components.layouts.master')
@section('title', 'Contracts | RoomGate Admin')
@section('page-title', 'Contracts')

@push('page-styles')
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/datatables-bs5/datatables.bootstrap5.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/select2/select2.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/@form-validation/form-validation.css" />
@endpush

@section('content')
@php
  $statusLabels = [
      'active' => 'bg-label-success',
      'pending' => 'bg-label-warning',
      'terminated' => 'bg-label-danger',
      'expired' => 'bg-label-secondary',
      'cancelled' => 'bg-label-secondary',
  ];
@endphp

<div class="container-xxl flex-grow-1 container-p-y">
  <div class="card">
    <div class="card-datatable table-responsive">
      <table class="datatables-contracts table border-top">
        <thead>
          <tr>
            <th></th>
            <th>Tenant</th>
            <th>Room</th>
            <th>Occupant</th>
            <th>Period</th>
            <th>Rent (USD)</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($contracts as $contract)
            <tr>
              <td></td>
              <td>{{ $contract->tenant?->name ?? 'Unknown' }}</td>
              <td>{{ $contract->room?->room_number ?? '—' }}</td>
              <td>{{ $contract->occupant?->name ?? '—' }}</td>
              <td>{{ optional($contract->start_date)->format('Y-m-d') }} - {{ optional($contract->end_date)->format('Y-m-d') }}</td>
              <td>${{ number_format(($contract->monthly_rent_cents ?? 0) / 100, 2) }}</td>
              <td>
                <span class="badge {{ $statusLabels[$contract->status] ?? 'bg-label-secondary' }}">
                  {{ ucfirst($contract->status) }}
                </span>
              </td>
              <td>
                <div class="d-flex align-items-center">
                  <form method="POST" action="{{ route('admin.contracts.generate-invoice', $contract) }}" class="me-1">
                    @csrf
                    <button type="submit" class="btn btn-icon btn-text-secondary rounded-pill waves-effect" data-confirm="Generate invoice for this contract?">
                      <i class="icon-base ti tabler-receipt-2 icon-22px"></i>
                    </button>
                  </form>
                  <a href="javascript:;" class="btn btn-icon btn-text-secondary rounded-pill waves-effect me-1" data-bs-toggle="modal" data-bs-target="#editContractModal"
                    data-contract-id="{{ $contract->id }}"
                    data-contract-tenant="{{ $contract->tenant_id }}"
                    data-contract-room="{{ $contract->room_id }}"
                    data-contract-occupant="{{ $contract->occupant_user_id }}"
                    data-contract-start="{{ optional($contract->start_date)->format('Y-m-d') }}"
                    data-contract-end="{{ optional($contract->end_date)->format('Y-m-d') }}"
                    data-contract-rent="{{ number_format(($contract->monthly_rent_cents ?? 0) / 100, 2, '.', '') }}"
                    data-contract-cycle="{{ $contract->billing_cycle }}"
                    data-contract-due-day="{{ $contract->payment_due_day }}"
                    data-contract-status="{{ $contract->status }}"
                    data-contract-notes="{{ $contract->notes }}"
                    data-contract-auto-renew="{{ $contract->auto_renew ? 1 : 0 }}">
                    <i class="icon-base ti tabler-edit icon-22px"></i>
                  </a>
                  <form method="POST" action="{{ route('admin.contracts.destroy', $contract) }}" data-confirm="Delete this contract?">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-icon btn-text-secondary rounded-pill waves-effect">
                      <i class="icon-base ti tabler-trash icon-22px"></i>
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

<div class="modal fade" id="addContractModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Contract</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form method="POST" action="{{ route('admin.contracts.store') }}" class="row g-3">
          @csrf
          <div class="col-md-6">
            <label class="form-label" for="contractTenant">Tenant</label>
            <select id="contractTenant" name="tenant_id" class="select2 form-select" required>
              <option value="">Select tenant</option>
              @foreach ($tenants as $tenant)
                <option value="{{ $tenant->id }}">{{ $tenant->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="contractRoom">Room</label>
            <select id="contractRoom" name="room_id" class="select2 form-select" required>
              <option value="">Select room</option>
              @foreach ($rooms as $room)
                <option value="{{ $room->id }}" data-tenant-id="{{ $room->tenant_id }}">{{ $room->room_number }} ({{ $room->property?->name ?? 'Property' }})</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="contractOccupant">Occupant</label>
            <select id="contractOccupant" name="occupant_user_id" class="select2 form-select" required>
              <option value="">Select user</option>
              @foreach ($users as $user)
                <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label" for="contractStart">Start Date</label>
            <input type="text" id="contractStart" name="start_date" class="form-control flatpickr" placeholder="YYYY-MM-DD" required />
          </div>
          <div class="col-md-3">
            <label class="form-label" for="contractEnd">End Date</label>
            <input type="text" id="contractEnd" name="end_date" class="form-control flatpickr" placeholder="YYYY-MM-DD" required />
          </div>
          <div class="col-md-4">
            <label class="form-label" for="contractRent">Monthly Rent (USD)</label>
            <input type="number" id="contractRent" name="monthly_rent" class="form-control" step="0.01" min="0" required />
          </div>
          <div class="col-md-4">
            <label class="form-label" for="contractDueDay">Payment Due Day</label>
            <input type="number" id="contractDueDay" name="payment_due_day" class="form-control" min="1" max="31" value="1" required />
          </div>
          <div class="col-md-6">
            <label class="form-label" for="contractCycle">Billing Cycle</label>
            <select id="contractCycle" name="billing_cycle" class="form-select" required>
              <option value="monthly">Monthly</option>
              <option value="weekly">Weekly</option>
              <option value="daily">Daily</option>
              <option value="custom">Custom</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="contractStatus">Status</label>
            <select id="contractStatus" name="status" class="form-select" required>
              <option value="active">Active</option>
              <option value="pending">Pending</option>
              <option value="terminated">Terminated</option>
              <option value="expired">Expired</option>
              <option value="cancelled">Cancelled</option>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label" for="contractNotes">Notes</label>
            <textarea id="contractNotes" name="notes" class="form-control" rows="2" placeholder="Optional notes"></textarea>
          </div>
          <div class="col-12">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="contractAutoRenew" name="auto_renew" value="1">
              <label class="form-check-label" for="contractAutoRenew">Auto renew</label>
            </div>
          </div>
          <div class="col-12 d-flex justify-content-end">
            <button type="submit" class="btn btn-primary">Create Contract</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="editContractModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Contract</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form method="POST" id="editContractForm" action="" class="row g-3">
          @csrf
          @method('PATCH')
          <div class="col-md-6">
            <label class="form-label" for="editContractTenant">Tenant</label>
            <select id="editContractTenant" name="tenant_id" class="select2 form-select" required>
              <option value="">Select tenant</option>
              @foreach ($tenants as $tenant)
                <option value="{{ $tenant->id }}">{{ $tenant->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="editContractRoom">Room</label>
            <select id="editContractRoom" name="room_id" class="select2 form-select" required>
              <option value="">Select room</option>
              @foreach ($rooms as $room)
                <option value="{{ $room->id }}" data-tenant-id="{{ $room->tenant_id }}">{{ $room->room_number }} ({{ $room->property?->name ?? 'Property' }})</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="editContractOccupant">Occupant</label>
            <select id="editContractOccupant" name="occupant_user_id" class="select2 form-select" required>
              <option value="">Select user</option>
              @foreach ($users as $user)
                <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label" for="editContractStart">Start Date</label>
            <input type="text" id="editContractStart" name="start_date" class="form-control flatpickr" placeholder="YYYY-MM-DD" required />
          </div>
          <div class="col-md-3">
            <label class="form-label" for="editContractEnd">End Date</label>
            <input type="text" id="editContractEnd" name="end_date" class="form-control flatpickr" placeholder="YYYY-MM-DD" required />
          </div>
          <div class="col-md-4">
            <label class="form-label" for="editContractRent">Monthly Rent (USD)</label>
            <input type="number" id="editContractRent" name="monthly_rent" class="form-control" step="0.01" min="0" required />
          </div>
          <div class="col-md-4">
            <label class="form-label" for="editContractDueDay">Payment Due Day</label>
            <input type="number" id="editContractDueDay" name="payment_due_day" class="form-control" min="1" max="31" required />
          </div>
          <div class="col-md-6">
            <label class="form-label" for="editContractCycle">Billing Cycle</label>
            <select id="editContractCycle" name="billing_cycle" class="form-select" required>
              <option value="monthly">Monthly</option>
              <option value="weekly">Weekly</option>
              <option value="daily">Daily</option>
              <option value="custom">Custom</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="editContractStatus">Status</label>
            <select id="editContractStatus" name="status" class="form-select" required>
              <option value="active">Active</option>
              <option value="pending">Pending</option>
              <option value="terminated">Terminated</option>
              <option value="expired">Expired</option>
              <option value="cancelled">Cancelled</option>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label" for="editContractNotes">Notes</label>
            <textarea id="editContractNotes" name="notes" class="form-control" rows="2"></textarea>
          </div>
          <div class="col-12">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="editContractAutoRenew" name="auto_renew" value="1">
              <label class="form-check-label" for="editContractAutoRenew">Auto renew</label>
            </div>
          </div>
          <div class="col-12 d-flex justify-content-end">
            <button type="submit" class="btn btn-primary">Save Changes</button>
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
  <script src="{{ asset('assets/assets') }}/vendor/libs/flatpickr/flatpickr.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      if (window.flatpickr) {
        document.querySelectorAll('.flatpickr').forEach((el) => {
          flatpickr(el, { dateFormat: 'Y-m-d' });
        });
      }



      const updateTenantFromRoom = (roomSelect, tenantSelect) => {
        if (!roomSelect) {
          return;
        }
        const selected = roomSelect.options[roomSelect.selectedIndex];
        const tenantId = selected ? selected.getAttribute('data-tenant-id') : '';
        if (!tenantSelect) {
          return;
        }
        if (window.$ && $.fn.select2) {
          $(tenantSelect).val(tenantId).trigger('change');
        } else {
          tenantSelect.value = tenantId;
        }
      };

      const filterRoomOptions = (tenantSelect, roomSelect) => {
        if (!tenantSelect || !roomSelect) {
          return;
        }
        const tenantId = tenantSelect.value;
        Array.from(roomSelect.options).forEach((option) => {
          if (!option.value) {
            option.hidden = false;
            return;
          }
          const optionTenant = option.getAttribute('data-tenant-id');
          option.hidden = tenantId && optionTenant !== tenantId;
        });
        if (roomSelect.value) {
          const selected = roomSelect.options[roomSelect.selectedIndex];
          if (selected && selected.hidden) {
            roomSelect.value = '';
          }
        }
      };

      if (window.$ && $.fn.select2) {
        $('.select2').each(function () {
          const placeholder = $(this).find('option[value=""]').first().text() || 'Select';
          const modal = $(this).closest('.modal');
          $(this).select2({
            placeholder: placeholder,
            allowClear: true,
            width: '100%',
            dropdownParent: modal.length ? modal : $(document.body)
          });
        });
      }

      const initTable = (selector, searchPlaceholder, addText, addTarget) => {
        const table = document.querySelector(selector);
        if (!table || !window.DataTable) {
          return;
        }
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
                    placeholder: searchPlaceholder,
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
                      text: addText,
                      className: 'add-new btn btn-primary rounded-2 waves-effect waves-light',
                      attr: {
                        'data-bs-toggle': 'modal',
                        'data-bs-target': addTarget
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
                  return 'Details';
                }
              }),
              type: 'column'
            }
          }
        });
      };

      const contractTenant = document.getElementById('contractTenant');
      const contractRoom = document.getElementById('contractRoom');
      if (contractTenant && contractRoom) {
        contractTenant.addEventListener('change', function () {
          filterRoomOptions(contractTenant, contractRoom);
        });
        filterRoomOptions(contractTenant, contractRoom);
      }
      if (contractRoom) {
        contractRoom.addEventListener('change', function () {
          updateTenantFromRoom(contractRoom, document.getElementById('contractTenant'));
        });
        updateTenantFromRoom(contractRoom, document.getElementById('contractTenant'));
      }

      const editContractTenant = document.getElementById('editContractTenant');
      const editContractRoom = document.getElementById('editContractRoom');
      if (editContractTenant && editContractRoom) {
        editContractTenant.addEventListener('change', function () {
          filterRoomOptions(editContractTenant, editContractRoom);
        });
        filterRoomOptions(editContractTenant, editContractRoom);
      }
      if (editContractRoom) {
        editContractRoom.addEventListener('change', function () {
          updateTenantFromRoom(editContractRoom, document.getElementById('editContractTenant'));
        });
      }

      initTable('.datatables-contracts', 'Search Contract', '<i class="icon-base ti tabler-plus me-0 me-sm-1 icon-16px"></i><span class="d-none d-sm-inline-block">Add Contract</span>', '#addContractModal');

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

      const editModal = document.getElementById('editContractModal');
      if (editModal) {
        editModal.addEventListener('show.bs.modal', function (event) {
          const trigger = event.relatedTarget;
          const form = document.getElementById('editContractForm');
          const contractId = trigger.getAttribute('data-contract-id');

          form.action = `{{ url('/admin/contracts') }}/${contractId}`;
          const tenantSelect = document.getElementById('editContractTenant');
          const roomSelect = document.getElementById('editContractRoom');
          if (tenantSelect) {
            tenantSelect.value = trigger.getAttribute('data-contract-tenant') || '';
          }
          if (tenantSelect && roomSelect) {
            filterRoomOptions(tenantSelect, roomSelect);
          }
          if (roomSelect) {
            roomSelect.value = trigger.getAttribute('data-contract-room') || '';
          }
          document.getElementById('editContractOccupant').value = trigger.getAttribute('data-contract-occupant') || '';
          document.getElementById('editContractStart').value = trigger.getAttribute('data-contract-start') || '';
          document.getElementById('editContractEnd').value = trigger.getAttribute('data-contract-end') || '';
          document.getElementById('editContractRent').value = trigger.getAttribute('data-contract-rent') || '0.00';
          document.getElementById('editContractCycle').value = trigger.getAttribute('data-contract-cycle') || 'monthly';
          document.getElementById('editContractDueDay').value = trigger.getAttribute('data-contract-due-day') || '1';
          document.getElementById('editContractStatus').value = trigger.getAttribute('data-contract-status') || 'active';
          document.getElementById('editContractNotes').value = trigger.getAttribute('data-contract-notes') || '';
          document.getElementById('editContractAutoRenew').checked = trigger.getAttribute('data-contract-auto-renew') === '1';

          if (window.$ && $.fn.select2) {
            if (tenantSelect) {
              $(tenantSelect).trigger('change');
            }
            if (roomSelect) {
              $(roomSelect).trigger('change');
            }
            $('#editContractOccupant').trigger('change');
          }
        });
      }
    });
  </script>
@endpush

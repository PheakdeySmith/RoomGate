@extends('core::components.layouts.master')
@section('title', 'Utility Bills | RoomGate')
@section('page-title', 'Utility Bills')

@push('page-styles')
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/datatables-bs5/datatables.bootstrap5.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/select2/select2.css" />
@endpush

@section('content')
@php
  $statusLabels = [
      'draft' => 'bg-label-secondary',
      'sent' => 'bg-label-info',
      'paid' => 'bg-label-success',
      'overdue' => 'bg-label-warning',
      'void' => 'bg-label-danger',
  ];
@endphp

<div class="container-xxl flex-grow-1 container-p-y">
  <div class="card">
    <div class="card-datatable table-responsive">
      <table class="datatables-utility-bills table border-top">
        <thead>
          <tr>
            <th></th>
            <th>Contract</th>
            <th>Room</th>
            <th>Type</th>
            <th>Period</th>
            <th>Amount (USD)</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($bills as $bill)
            <tr>
              <td></td>
              <td>{{ $bill->contract?->occupant?->name ?? 'Tenant' }}</td>
              <td>{{ $bill->room?->room_number ?? '-' }}</td>
              <td>{{ $bill->utilityType?->name ?? '-' }}</td>
              <td>{{ optional($bill->billing_period_start)->format('Y-m-d') }} - {{ optional($bill->billing_period_end)->format('Y-m-d') }}</td>
              <td>${{ number_format(($bill->total_cents ?? 0) / 100, 2) }}</td>
              <td>
                <span class="badge {{ $statusLabels[$bill->status] ?? 'bg-label-secondary' }}">
                  {{ ucfirst($bill->status) }}
                </span>
              </td>
              <td>
                <div class="d-flex align-items-center">
                  <a href="javascript:;" class="btn btn-icon btn-text-secondary rounded-pill waves-effect me-1"
                     data-bs-toggle="modal" data-bs-target="#editBillModal"
                     data-bill-id="{{ $bill->id }}"
                     data-bill-contract="{{ $bill->contract_id }}"
                     data-bill-type="{{ $bill->utility_type_id }}"
                     data-bill-meter="{{ $bill->meter_id }}"
                     data-bill-provider="{{ $bill->provider_id }}"
                     data-bill-start="{{ optional($bill->billing_period_start)->format('Y-m-d') }}"
                     data-bill-end="{{ optional($bill->billing_period_end)->format('Y-m-d') }}"
                     data-bill-start-reading="{{ $bill->start_reading_id }}"
                     data-bill-end-reading="{{ $bill->end_reading_id }}"
                     data-bill-unit-cost="{{ number_format(($bill->unit_cost_cents ?? 0) / 100, 4, '.', '') }}"
                     data-bill-tax="{{ number_format(($bill->tax_cents ?? 0) / 100, 2, '.', '') }}"
                     data-bill-amount="{{ number_format(($bill->subtotal_cents ?? 0) / 100, 2, '.', '') }}"
                     data-bill-status="{{ $bill->status }}"
                     data-bill-issued="{{ optional($bill->issued_at)->format('Y-m-d') }}"
                     data-bill-due="{{ optional($bill->due_date)->format('Y-m-d') }}"
                     data-bill-paid="{{ optional($bill->paid_at)->format('Y-m-d') }}"
                     data-bill-notes="{{ $bill->notes }}">
                    <i class="icon-base ti tabler-edit icon-22px"></i>
                  </a>
                  <form method="POST" action="{{ route('core.utility-bills.destroy', $bill) }}" data-confirm="Delete this bill?">
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

<div class="modal fade" id="addBillModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Utility Bill</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form method="POST" action="{{ route('core.utility-bills.store') }}" class="row g-3">
          @csrf
          <div class="col-md-6">
            <label class="form-label" for="billContract">Contract</label>
            <select id="billContract" name="contract_id" class="select2 form-select" required>
              <option value="">Select contract</option>
              @foreach ($contracts as $contract)
                <option value="{{ $contract->id }}">
                  {{ $contract->occupant?->name ?? 'Tenant' }} - {{ $contract->room?->room_number ?? 'Room' }} ({{ $contract->room?->property?->name ?? 'Property' }})
                </option>
              @endforeach
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="billType">Utility Type</label>
            <select id="billType" name="utility_type_id" class="select2 form-select" required>
              <option value="">Select type</option>
              @foreach ($utilityTypes as $type)
                <option value="{{ $type->id }}">{{ $type->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="billMeter">Meter (optional)</label>
            <select id="billMeter" name="meter_id" class="select2 form-select">
              <option value="">Select meter</option>
              @foreach ($meters as $meter)
                <option value="{{ $meter->id }}">{{ $meter->meter_code }} ({{ $meter->utilityType?->name }})</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="billProvider">Provider</label>
            <select id="billProvider" name="provider_id" class="select2 form-select">
              <option value="">Select provider</option>
              @foreach ($providers as $provider)
                <option value="{{ $provider->id }}">{{ $provider->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label" for="billStart">Period Start</label>
            <input type="text" id="billStart" name="billing_period_start" class="form-control flatpickr" placeholder="YYYY-MM-DD" required />
          </div>
          <div class="col-md-3">
            <label class="form-label" for="billEnd">Period End</label>
            <input type="text" id="billEnd" name="billing_period_end" class="form-control flatpickr" placeholder="YYYY-MM-DD" required />
          </div>
          <div class="col-md-3">
            <label class="form-label" for="billIssued">Issued At</label>
            <input type="text" id="billIssued" name="issued_at" class="form-control flatpickr" placeholder="YYYY-MM-DD" />
          </div>
          <div class="col-md-3">
            <label class="form-label" for="billDue">Due Date</label>
            <input type="text" id="billDue" name="due_date" class="form-control flatpickr" placeholder="YYYY-MM-DD" />
          </div>
          <div class="col-md-6">
            <label class="form-label" for="billStartReading">Start Reading</label>
            <select id="billStartReading" name="start_reading_id" class="select2 form-select">
              <option value="">Select reading</option>
              @foreach ($readings as $reading)
                <option value="{{ $reading->id }}">{{ $reading->meter?->meter_code }} - {{ $reading->reading_value }} ({{ optional($reading->reading_at)->format('Y-m-d') }})</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="billEndReading">End Reading</label>
            <select id="billEndReading" name="end_reading_id" class="select2 form-select">
              <option value="">Select reading</option>
              @foreach ($readings as $reading)
                <option value="{{ $reading->id }}">{{ $reading->meter?->meter_code }} - {{ $reading->reading_value }} ({{ optional($reading->reading_at)->format('Y-m-d') }})</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="billUnitCost">Unit Cost (USD)</label>
            <input type="number" id="billUnitCost" name="unit_cost" class="form-control" step="0.0001" min="0" />
          </div>
          <div class="col-md-4">
            <label class="form-label" for="billTax">Tax (USD)</label>
            <input type="number" id="billTax" name="tax" class="form-control" step="0.01" min="0" />
          </div>
          <div class="col-md-4">
            <label class="form-label" for="billAmount">Amount (USD)</label>
            <input type="number" id="billAmount" name="amount" class="form-control" step="0.01" min="0" />
          </div>
          <div class="col-md-4">
            <label class="form-label" for="billStatus">Status</label>
            <select id="billStatus" name="status" class="form-select" required>
              <option value="draft">Draft</option>
              <option value="sent">Sent</option>
              <option value="paid">Paid</option>
              <option value="overdue">Overdue</option>
              <option value="void">Void</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="billPaidAt">Paid At</label>
            <input type="text" id="billPaidAt" name="paid_at" class="form-control flatpickr" placeholder="YYYY-MM-DD" />
          </div>
          <div class="col-12">
            <label class="form-label" for="billNotes">Notes</label>
            <textarea id="billNotes" name="notes" class="form-control" rows="2"></textarea>
          </div>
          <div class="col-12 d-flex justify-content-end">
            <button type="submit" class="btn btn-primary">Create Bill</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="editBillModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Utility Bill</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form method="POST" id="editBillForm" action="" class="row g-3">
          @csrf
          @method('PATCH')
          <div class="col-md-6">
            <label class="form-label" for="editBillContract">Contract</label>
            <select id="editBillContract" name="contract_id" class="select2 form-select" required>
              <option value="">Select contract</option>
              @foreach ($contracts as $contract)
                <option value="{{ $contract->id }}">
                  {{ $contract->occupant?->name ?? 'Tenant' }} - {{ $contract->room?->room_number ?? 'Room' }} ({{ $contract->room?->property?->name ?? 'Property' }})
                </option>
              @endforeach
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="editBillType">Utility Type</label>
            <select id="editBillType" name="utility_type_id" class="select2 form-select" required>
              <option value="">Select type</option>
              @foreach ($utilityTypes as $type)
                <option value="{{ $type->id }}">{{ $type->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="editBillMeter">Meter (optional)</label>
            <select id="editBillMeter" name="meter_id" class="select2 form-select">
              <option value="">Select meter</option>
              @foreach ($meters as $meter)
                <option value="{{ $meter->id }}">{{ $meter->meter_code }} ({{ $meter->utilityType?->name }})</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="editBillProvider">Provider</label>
            <select id="editBillProvider" name="provider_id" class="select2 form-select">
              <option value="">Select provider</option>
              @foreach ($providers as $provider)
                <option value="{{ $provider->id }}">{{ $provider->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label" for="editBillStart">Period Start</label>
            <input type="text" id="editBillStart" name="billing_period_start" class="form-control flatpickr" placeholder="YYYY-MM-DD" required />
          </div>
          <div class="col-md-3">
            <label class="form-label" for="editBillEnd">Period End</label>
            <input type="text" id="editBillEnd" name="billing_period_end" class="form-control flatpickr" placeholder="YYYY-MM-DD" required />
          </div>
          <div class="col-md-3">
            <label class="form-label" for="editBillIssued">Issued At</label>
            <input type="text" id="editBillIssued" name="issued_at" class="form-control flatpickr" placeholder="YYYY-MM-DD" />
          </div>
          <div class="col-md-3">
            <label class="form-label" for="editBillDue">Due Date</label>
            <input type="text" id="editBillDue" name="due_date" class="form-control flatpickr" placeholder="YYYY-MM-DD" />
          </div>
          <div class="col-md-6">
            <label class="form-label" for="editBillStartReading">Start Reading</label>
            <select id="editBillStartReading" name="start_reading_id" class="select2 form-select">
              <option value="">Select reading</option>
              @foreach ($readings as $reading)
                <option value="{{ $reading->id }}">{{ $reading->meter?->meter_code }} - {{ $reading->reading_value }} ({{ optional($reading->reading_at)->format('Y-m-d') }})</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="editBillEndReading">End Reading</label>
            <select id="editBillEndReading" name="end_reading_id" class="select2 form-select">
              <option value="">Select reading</option>
              @foreach ($readings as $reading)
                <option value="{{ $reading->id }}">{{ $reading->meter?->meter_code }} - {{ $reading->reading_value }} ({{ optional($reading->reading_at)->format('Y-m-d') }})</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="editBillUnitCost">Unit Cost (USD)</label>
            <input type="number" id="editBillUnitCost" name="unit_cost" class="form-control" step="0.0001" min="0" />
          </div>
          <div class="col-md-4">
            <label class="form-label" for="editBillTax">Tax (USD)</label>
            <input type="number" id="editBillTax" name="tax" class="form-control" step="0.01" min="0" />
          </div>
          <div class="col-md-4">
            <label class="form-label" for="editBillAmount">Amount (USD)</label>
            <input type="number" id="editBillAmount" name="amount" class="form-control" step="0.01" min="0" />
          </div>
          <div class="col-md-4">
            <label class="form-label" for="editBillStatus">Status</label>
            <select id="editBillStatus" name="status" class="form-select" required>
              <option value="draft">Draft</option>
              <option value="sent">Sent</option>
              <option value="paid">Paid</option>
              <option value="overdue">Overdue</option>
              <option value="void">Void</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="editBillPaidAt">Paid At</label>
            <input type="text" id="editBillPaidAt" name="paid_at" class="form-control flatpickr" placeholder="YYYY-MM-DD" />
          </div>
          <div class="col-12">
            <label class="form-label" for="editBillNotes">Notes</label>
            <textarea id="editBillNotes" name="notes" class="form-control" rows="2"></textarea>
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

      const table = document.querySelector('.datatables-utility-bills');
      if (table && window.DataTable) {
        new DataTable(table, {
          order: [[4, 'desc']],
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
                    placeholder: 'Search Bill',
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
                      text: '<i class="icon-base ti tabler-plus me-0 me-sm-1 icon-16px"></i><span class="d-none d-sm-inline-block">Add Bill</span>',
                      className: 'add-new btn btn-primary rounded-2 waves-effect waves-light',
                      attr: {
                        'data-bs-toggle': 'modal',
                        'data-bs-target': '#addBillModal'
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
                  return 'Bill';
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

      const editModal = document.getElementById('editBillModal');
      if (editModal) {
        editModal.addEventListener('show.bs.modal', function (event) {
          const trigger = event.relatedTarget;
          const form = document.getElementById('editBillForm');
          const billId = trigger.getAttribute('data-bill-id');

          form.action = `{{ url('/core/utility-bills') }}/${billId}`;
          document.getElementById('editBillContract').value = trigger.getAttribute('data-bill-contract') || '';
          document.getElementById('editBillType').value = trigger.getAttribute('data-bill-type') || '';
          document.getElementById('editBillMeter').value = trigger.getAttribute('data-bill-meter') || '';
          document.getElementById('editBillProvider').value = trigger.getAttribute('data-bill-provider') || '';
          document.getElementById('editBillStart').value = trigger.getAttribute('data-bill-start') || '';
          document.getElementById('editBillEnd').value = trigger.getAttribute('data-bill-end') || '';
          document.getElementById('editBillStartReading').value = trigger.getAttribute('data-bill-start-reading') || '';
          document.getElementById('editBillEndReading').value = trigger.getAttribute('data-bill-end-reading') || '';
          document.getElementById('editBillUnitCost').value = trigger.getAttribute('data-bill-unit-cost') || '';
          document.getElementById('editBillTax').value = trigger.getAttribute('data-bill-tax') || '';
          document.getElementById('editBillAmount').value = trigger.getAttribute('data-bill-amount') || '';
          document.getElementById('editBillStatus').value = trigger.getAttribute('data-bill-status') || 'draft';
          document.getElementById('editBillIssued').value = trigger.getAttribute('data-bill-issued') || '';
          document.getElementById('editBillDue').value = trigger.getAttribute('data-bill-due') || '';
          document.getElementById('editBillPaidAt').value = trigger.getAttribute('data-bill-paid') || '';
          document.getElementById('editBillNotes').value = trigger.getAttribute('data-bill-notes') || '';

          if (window.$ && $.fn.select2) {
            $('#editBillContract').trigger('change');
            $('#editBillType').trigger('change');
            $('#editBillMeter').trigger('change');
            $('#editBillProvider').trigger('change');
            $('#editBillStartReading').trigger('change');
            $('#editBillEndReading').trigger('change');
          }
        });
      }
    });
  </script>
@endpush

@extends('admin::components.layouts.master')
@section('title', 'Subscription Payments | RoomGate Admin')
@section('page-title', 'Subscription Payments')

@push('page-styles')
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/flatpickr/flatpickr.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/datatables-bs5/datatables.bootstrap5.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/select2/select2.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/@form-validation/form-validation.css" />
@endpush

@section('content')
@php
  $paymentStatusLabels = [
      'paid' => 'bg-label-success',
      'pending' => 'bg-label-warning',
      'failed' => 'bg-label-danger',
      'cancelled' => 'bg-label-secondary',
  ];
@endphp

<div class="container-xxl flex-grow-1 container-p-y">
  <div class="card">
    <div class="card-datatable table-responsive">
      <table class="datatables-subscription-payments table border-top">
        <thead>
          <tr>
            <th></th>
            <th>Tenant</th>
            <th>Invoice</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Provider</th>
            <th>Paid At</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($payments as $payment)
            @php
              $paymentStatus = $paymentStatusLabels[$payment->status] ?? 'bg-label-secondary';
              $paidAt = optional($payment->paid_at)->format('Y-m-d');
              $amount = number_format($payment->amount_cents / 100, 2);
            @endphp
            <tr>
              <td></td>
              <td>{{ $payment->tenant?->name ?? 'Unknown' }}</td>
              <td>{{ $payment->invoice?->invoice_number ?? 'Unknown' }}</td>
              <td>{{ $payment->currency_code }} {{ $amount }}</td>
              <td><span class="badge {{ $paymentStatus }}">{{ ucfirst($payment->status) }}</span></td>
              <td>{{ $payment->provider }}</td>
              <td>{{ $paidAt ?: '-' }}</td>
              <td>
                <div class="d-flex align-items-center">
                  <button
                    class="btn btn-icon btn-text-secondary rounded-pill waves-effect"
                    data-bs-toggle="modal"
                    data-bs-target="#editPaymentModal"
                    data-payment-id="{{ $payment->id }}"
                    data-payment-status="{{ $payment->status }}"
                    data-payment-paid-at="{{ $paidAt }}">
                    <i class="icon-base ti tabler-edit icon-md"></i>
                  </button>
                </div>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>


<div class="modal fade" id="addPaymentModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-simple">
    <div class="modal-content">
      <div class="modal-body">
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        <div class="text-center mb-6">
          <span class="badge bg-label-primary rounded-1 p-2 mb-3">
            <i class="icon-base ti tabler-cash icon-md"></i>
          </span>
          <h4 class="mb-1">Create Payment</h4>
          <p class="text-body-secondary mb-0">Record provider reference and status.</p>
        </div>
        <form class="row g-3" method="POST" action="{{ route('admin.subscription-payments.store') }}">
          @csrf
          <div class="col-12">
            <label class="form-label" for="paymentTenant">Tenant</label>
            <select id="paymentTenant" name="tenant_id" class="select2 form-select">
              @foreach ($tenants as $tenant)
                <option value="{{ $tenant->id }}">{{ $tenant->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-12">
            <label class="form-label" for="paymentInvoice">Invoice</label>
            <select id="paymentInvoice" name="subscription_invoice_id" class="select2 form-select">
              @foreach ($invoices as $invoice)
                <option value="{{ $invoice->id }}">{{ $invoice->invoice_number }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-6">
            <label class="form-label" for="paymentAmount">Amount (cents)</label>
            <input type="number" id="paymentAmount" name="amount_cents" class="form-control" placeholder="9900" />
          </div>
          <div class="col-6">
            <label class="form-label" for="paymentCurrency">Currency Code</label>
            <input type="text" id="paymentCurrency" name="currency_code" class="form-control" placeholder="USD" />
          </div>
          <div class="col-12">
            <label class="form-label" for="paymentProvider">Provider</label>
            <input type="text" id="paymentProvider" name="provider" class="form-control" placeholder="bakong" />
          </div>
          <div class="col-12">
            <label class="form-label" for="paymentRef">Provider Reference</label>
            <input type="text" id="paymentRef" name="provider_ref" class="form-control" placeholder="BK-REF-1001" />
          </div>
          <div class="col-12">
            <label class="form-label" for="paymentStatus">Status</label>
            <select id="paymentStatus" name="status" class="form-select">
              <option value="pending">Pending</option>
              <option value="paid">Paid</option>
              <option value="failed">Failed</option>
              <option value="cancelled">Cancelled</option>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label" for="paymentPaidAt">Paid At</label>
            <input type="text" id="paymentPaidAt" name="paid_at" class="form-control dob-picker" placeholder="YYYY-MM-DD" />
          </div>
          <div class="col-12 text-center mt-6">
            <button type="submit" class="btn btn-primary me-sm-4 me-1">Create Payment</button>
            <button type="reset" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="editPaymentModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-simple">
    <div class="modal-content">
      <div class="modal-body">
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        <div class="text-center mb-6">
          <span class="badge bg-label-warning rounded-1 p-2 mb-3">
            <i class="icon-base ti tabler-edit icon-md"></i>
          </span>
          <h4 class="mb-1">Edit Payment</h4>
          <p class="text-body-secondary mb-0">Update status and payment date.</p>
        </div>
        <form class="row g-3" method="POST" id="editPaymentForm">
          @csrf
          @method('PATCH')
          <div class="col-12">
            <label class="form-label" for="editPaymentStatus">Status</label>
            <select id="editPaymentStatus" name="status" class="form-select">
              <option value="pending">Pending</option>
              <option value="paid">Paid</option>
              <option value="failed">Failed</option>
              <option value="cancelled">Cancelled</option>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label" for="editPaymentPaidAt">Paid At</label>
            <input type="text" id="editPaymentPaidAt" name="paid_at" class="form-control dob-picker" placeholder="YYYY-MM-DD" />
          </div>
          <div class="col-12 text-center mt-6">
            <button type="submit" class="btn btn-primary me-sm-4 me-1">Update Payment</button>
            <button type="reset" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

@push('page-scripts')
  <script src="{{ asset('assets/assets') }}/vendor/libs/flatpickr/flatpickr.js"></script>
  <script src="{{ asset('assets/assets') }}/vendor/libs/datatables-bs5/datatables-bootstrap5.js"></script>
  <script src="{{ asset('assets/assets') }}/vendor/libs/select2/select2.js"></script>
  <script src="{{ asset('assets/assets') }}/vendor/libs/@form-validation/popular.js"></script>
  <script src="{{ asset('assets/assets') }}/vendor/libs/@form-validation/bootstrap5.js"></script>
  <script src="{{ asset('assets/assets') }}/vendor/libs/@form-validation/auto-focus.js"></script>

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
      if (window.flatpickr) {
        flatpickr('.dob-picker', { dateFormat: 'Y-m-d' });
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

      initTable('.datatables-subscription-payments', 'Search Payment', '<i class="icon-base ti tabler-plus me-0 me-sm-1 icon-16px"></i><span class="d-none d-sm-inline-block">Add Payment</span>', '#addPaymentModal');

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

      const editPaymentModal = document.getElementById('editPaymentModal');
      if (editPaymentModal) {
        editPaymentModal.addEventListener('show.bs.modal', function (event) {
          const trigger = event.relatedTarget;
          const paymentId = trigger.getAttribute('data-payment-id');
          const status = trigger.getAttribute('data-payment-status');
          const paidAt = trigger.getAttribute('data-payment-paid-at');
          const form = document.getElementById('editPaymentForm');

          form.action = `{{ url('/admin/subscription-payments') }}/${paymentId}`;
          document.getElementById('editPaymentStatus').value = status || 'pending';
          document.getElementById('editPaymentPaidAt').value = paidAt || '';
        });
      }
    });
  </script>
@endpush

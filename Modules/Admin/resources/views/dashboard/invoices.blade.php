@extends('admin::components.layouts.master')
@section('title', 'Invoices | RoomGate Admin')
@section('page-title', 'Invoices')

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
      'partial' => 'bg-label-warning',
      'overdue' => 'bg-label-danger',
      'void' => 'bg-label-secondary',
  ];
@endphp

<div class="container-xxl flex-grow-1 container-p-y">
  <div class="card mb-6">
    <div class="card-body">
      <form method="GET" class="row g-3 align-items-end">
        <div class="col-md-3">
          <label class="form-label" for="invoiceTenant">Tenant</label>
          <select id="invoiceTenant" name="tenant_id" class="select2 form-select">
            <option value="">All</option>
            @foreach ($tenants as $tenant)
              <option value="{{ $tenant->id }}" @selected(request('tenant_id') == $tenant->id)>{{ $tenant->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label" for="invoiceProperty">Property</label>
          <select id="invoiceProperty" name="property_id" class="select2 form-select">
            <option value="">All</option>
            @foreach ($properties as $property)
              <option value="{{ $property->id }}" @selected(request('property_id') == $property->id)>{{ $property->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label" for="invoiceRoom">Room</label>
          <select id="invoiceRoom" name="room_id" class="select2 form-select">
            <option value="">All</option>
            @foreach ($rooms as $room)
              <option value="{{ $room->id }}" @selected(request('room_id') == $room->id)>{{ $room->room_number }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label" for="invoiceStatus">Status</label>
          <select id="invoiceStatus" name="status" class="select2 form-select">
            <option value="">All</option>
            @foreach (['draft','sent','paid','partial','overdue','void'] as $status)
              <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-12 d-flex gap-2">
          <button type="submit" class="btn btn-primary">Filter</button>
          <a href="{{ route('admin.invoices.index') }}" class="btn btn-label-secondary">Reset</a>
        </div>
      </form>
    </div>
  </div>
  <div class="card">
    <div class="card-datatable table-responsive">
      <table class="datatables-invoices table border-top">
        <thead>
          <tr>
            <th></th>
            <th>Invoice #</th>
            <th>Tenant</th>
            <th>Room</th>
            <th>Occupant</th>
            <th>Total (USD)</th>
            <th>Due Date</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($invoices as $invoice)
            <tr>
              <td></td>
              <td>{{ $invoice->invoice_number }}</td>
              <td>{{ $invoice->tenant?->name ?? 'Unknown' }}</td>
              <td>{{ $invoice->contract?->room?->room_number ?? '—' }}</td>
              <td>{{ $invoice->contract?->occupant?->name ?? '—' }}</td>
              <td>${{ number_format(($invoice->total_cents ?? 0) / 100, 2) }}</td>
              <td>{{ optional($invoice->due_date)->format('Y-m-d') }}</td>
              <td>
                <span class="badge {{ $statusLabels[$invoice->status] ?? 'bg-label-secondary' }}">
                  {{ ucfirst($invoice->status) }}
                </span>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection

@push('page-scripts')
  <script src="{{ asset('assets/assets') }}/vendor/libs/datatables-bs5/datatables-bootstrap5.js"></script>
  <script src="{{ asset('assets/assets') }}/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.js"></script>
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

      const table = document.querySelector('.datatables-invoices');
      if (!table || !window.DataTable) {
        return;
      }

      new DataTable(table, {
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
                  placeholder: 'Search Invoice',
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
    });
  </script>
@endpush

@extends('core::components.layouts.master')
@section('title', 'Invoices | RoomGate')
@section('page-title', 'Invoices')

@push('page-styles')
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/datatables-bs5/datatables.bootstrap5.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css" />
@endpush

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="card">
    <div class="card-datatable table-responsive">
      <table class="datatables-invoices table border-top">
        <thead>
          <tr>
            <th></th>
            <th>Invoice #</th>
            <th>Occupant</th>
            <th>Room</th>
            <th>Status</th>
            <th>Total</th>
            <th>Due Date</th>
            <th>Paid</th>
            <th>Balance</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>
</div>
@endsection

@push('page-scripts')
  <script src="{{ asset('assets/assets') }}/vendor/libs/datatables-bs5/datatables-bootstrap5.js"></script>
  <script src="{{ asset('assets/assets') }}/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const table = document.querySelector('.datatables-invoices');
      if (!table || !window.DataTable) {
        return;
      }

      const dataTable = new DataTable(table, RoomGateDataTables.buildOptions({
        processing: true,
        serverSide: true,
        ajax: '{{ route('core.invoices.data') }}',
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
          { targets: 9, orderable: false, searchable: false }
        ],
        layout: Object.assign({}, RoomGateDataTables.layout, {
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
                  },
                  {
                    text: '<i class="icon-base ti tabler-plus me-0 me-sm-1 icon-16px"></i><span class="d-none d-sm-inline-block">Add Invoice</span>',
                    className: 'add-new btn btn-primary rounded-2 waves-effect waves-light',
                    action: function () {
                      window.location.href = '{{ route('Core.invoices.add') }}';
                    }
                  }
                ]
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
        if (window.RoomGateDataTables && RoomGateDataTables.applyLayoutClasses) {
          setTimeout(() => {
            RoomGateDataTables.applyLayoutClasses();
          }, 100);
        }
      });
    });
  </script>
@endpush

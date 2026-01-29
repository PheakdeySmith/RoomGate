@extends('admin::components.layouts.master')
@section('title', 'Maintenance Requests | RoomGate Admin')
@section('page-title', 'Maintenance Requests')

@push('page-styles')
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/datatables-bs5/datatables.bootstrap5.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css" />
@endpush

@section('content')
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="card">
      <div class="card-datatable table-responsive">
        <table class="datatables-maintenance table border-top">
          <thead>
            <tr>
              <th></th>
              <th>ID</th>
              <th>Tenant</th>
              <th>Title</th>
              <th>Status</th>
              <th>Priority</th>
              <th>Assigned</th>
              <th>Requested</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($requests as $item)
              <tr>
                <td></td>
                <td>#{{ $item->id }}</td>
                <td>{{ $item->tenant?->name ?? '-' }}</td>
                <td>{{ $item->title }}</td>
                <td>{{ ucfirst(str_replace('_', ' ', $item->status)) }}</td>
                <td>{{ ucfirst($item->priority) }}</td>
                <td>{{ $item->assignedTo?->name ?? '-' }}</td>
                <td>{{ optional($item->requested_at)->format('Y-m-d H:i') }}</td>
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
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const table = document.querySelector('.datatables-maintenance');
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
        layout: window.RoomGateDataTables?.layout,
        language: window.RoomGateDataTables?.language,
        responsive: {
          details: {
            display: DataTable.Responsive.display.modal({
              header: function () {
                return 'Maintenance Details';
              }
            }),
            type: 'column'
          }
        }
      });
    });
  </script>
@endpush

@extends('admin::components.layouts.master')
@section('title', 'Outbound Messages | RoomGate Admin')
@section('page-title', 'Outbound Messages')

@push('page-styles')
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/datatables-bs5/datatables.bootstrap5.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css" />
@endpush

@section('content')
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="card">
      <div class="card-datatable table-responsive">
        <table class="datatables-outbound table border-top">
          <thead>
            <tr>
              <th></th>
              <th>ID</th>
              <th>Tenant</th>
              <th>User</th>
              <th>Channel</th>
              <th>To</th>
              <th>Status</th>
              <th>Attempts</th>
              <th>Scheduled</th>
              <th>Sent</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($messages as $message)
              <tr>
                <td></td>
                <td>{{ $message->id }}</td>
                <td>{{ $message->tenant?->name ?? '-' }}</td>
                <td>{{ $message->user?->email ?? '-' }}</td>
                <td>{{ strtoupper($message->channel) }}</td>
                <td>{{ $message->to_address ?? '-' }}</td>
                <td>{{ ucfirst($message->status) }}</td>
                <td>{{ $message->attempt_count }}</td>
                <td>{{ optional($message->scheduled_at)->format('Y-m-d H:i') ?? '-' }}</td>
                <td>{{ optional($message->sent_at)->format('Y-m-d H:i') ?? '-' }}</td>
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
      const table = document.querySelector('.datatables-outbound');
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
                  placeholder: 'Search Messages',
                  text: '_INPUT_'
                }
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
    });
  </script>
@endpush

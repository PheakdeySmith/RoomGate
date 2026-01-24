@extends('admin::components.layouts.master')
@section('title', 'Notifications | RoomGate Admin')
@section('page-title', 'Notifications')

@push('page-styles')
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/datatables-bs5/datatables.bootstrap5.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css" />
@endpush

@section('content')
@php
  $typeLabels = [
      'info' => 'bg-label-primary',
      'success' => 'bg-label-success',
      'warning' => 'bg-label-warning',
      'error' => 'bg-label-danger',
  ];
@endphp

<div class="container-xxl flex-grow-1 container-p-y">
  <div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
      <h5 class="mb-0">All Notifications</h5>
      <form method="POST" action="{{ route('admin.notifications.mark-all-read') }}">
        @csrf
        <button type="submit" class="btn btn-label-primary btn-sm">Mark all read</button>
      </form>
    </div>
    <div class="card-datatable table-responsive">
      <table class="datatables-notifications table border-top">
        <thead>
          <tr>
            <th></th>
            <th>Title</th>
            <th>Message</th>
            <th>Type</th>
            <th>Created</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($notifications as $notification)
            <tr>
              <td></td>
              <td>{{ $notification->title }}</td>
              <td>{{ $notification->body }}</td>
              <td>
                <span class="badge {{ $typeLabels[$notification->type] ?? 'bg-label-secondary' }}">
                  {{ ucfirst($notification->type) }}
                </span>
              </td>
              <td>{{ optional($notification->created_at)->format('Y-m-d H:i') }}</td>
              <td>
                @if ($notification->read_at)
                  <span class="badge bg-label-secondary">Read</span>
                @else
                  <span class="badge bg-label-primary">New</span>
                @endif
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
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const table = document.querySelector('.datatables-notifications');
      if (!table || !window.DataTable) {
        return;
      }

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
                  placeholder: 'Search Notification',
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

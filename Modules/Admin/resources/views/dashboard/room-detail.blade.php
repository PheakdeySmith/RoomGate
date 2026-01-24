@extends('admin::components.layouts.master')
@section('title', 'Room Details | RoomGate Admin')
@section('page-title', 'Room Details')

@push('page-styles')
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/datatables-bs5/datatables.bootstrap5.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css" />
@endpush

@section('content')
@php
  $roomStatusLabels = [
      'available' => 'bg-label-success',
      'occupied' => 'bg-label-warning',
      'maintenance' => 'bg-label-danger',
      'inactive' => 'bg-label-secondary',
  ];
  $contractStatusLabels = [
      'active' => 'bg-label-success',
      'pending' => 'bg-label-warning',
      'terminated' => 'bg-label-danger',
      'expired' => 'bg-label-secondary',
      'cancelled' => 'bg-label-secondary',
  ];
@endphp

<div class="container-xxl flex-grow-1 container-p-y">
  <div class="card mb-6">
    <div class="card-body">
      <div class="d-flex flex-wrap align-items-center justify-content-between gap-4">
        <div>
          <h4 class="mb-2">Room {{ $room->room_number }}</h4>
          <div class="d-flex flex-wrap gap-3 text-body-secondary">
            <span><strong>Tenant:</strong> {{ $room->tenant?->name ?? 'Unknown' }}</span>
            <span><strong>Property:</strong> {{ $room->property?->name ?? 'Unknown' }}</span>
            <span><strong>Type:</strong> {{ $room->roomType?->name ?? '—' }}</span>
            <span><strong>Status:</strong>
              <span class="badge {{ $roomStatusLabels[$room->status] ?? 'bg-label-secondary' }}">
                {{ ucfirst($room->status) }}
              </span>
            </span>
          </div>
        </div>
        <div class="text-end">
          <div class="text-body-secondary">Monthly Rent</div>
          <div class="fw-semibold">${{ number_format(($room->monthly_rent_cents ?? 0) / 100, 2) }}</div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-6 mb-6">
    <div class="col-lg-6">
      <div class="card h-100">
        <div class="card-header">
          <h5 class="mb-0">Current Occupant</h5>
        </div>
        <div class="card-body">
          @if ($activeContract && $activeContract->occupant)
            <div class="d-flex align-items-center gap-3">
              <div class="avatar avatar-lg">
                <img src="{{ asset('assets/assets') }}/img/avatars/1.png" alt="Avatar" class="rounded-circle">
              </div>
              <div>
                <h6 class="mb-1">{{ $activeContract->occupant->name }}</h6>
                <div class="text-body-secondary">{{ $activeContract->occupant->email }}</div>
                <div class="mt-2">
                  <span class="badge {{ $contractStatusLabels[$activeContract->status] ?? 'bg-label-secondary' }}">
                    {{ ucfirst($activeContract->status) }}
                  </span>
                </div>
              </div>
            </div>
            <div class="mt-4 text-body-secondary">
              <div><strong>Contract:</strong> {{ optional($activeContract->start_date)->format('Y-m-d') }} - {{ optional($activeContract->end_date)->format('Y-m-d') }}</div>
              <div><strong>Rent:</strong> ${{ number_format(($activeContract->monthly_rent_cents ?? 0) / 100, 2) }}</div>
              <div><strong>Due Day:</strong> {{ $activeContract->payment_due_day }}</div>
            </div>
          @else
            <div class="text-body-secondary">No active contract for this room.</div>
          @endif
        </div>
      </div>
    </div>
    <div class="col-lg-6">
      <div class="card h-100">
        <div class="card-header">
          <h5 class="mb-0">Room Details</h5>
        </div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-sm-6">
              <div class="text-body-secondary">Max Occupants</div>
              <div class="fw-semibold">{{ $room->max_occupants ?? '—' }}</div>
            </div>
            <div class="col-sm-6">
              <div class="text-body-secondary">Floor</div>
              <div class="fw-semibold">{{ $room->floor ?? '—' }}</div>
            </div>
            <div class="col-sm-6">
              <div class="text-body-secondary">Size</div>
              <div class="fw-semibold">{{ $room->size ?? '—' }}</div>
            </div>
            <div class="col-sm-6">
              <div class="text-body-secondary">Status</div>
              <div class="fw-semibold">{{ ucfirst($room->status) }}</div>
            </div>
            <div class="col-12">
              <div class="text-body-secondary">Description</div>
              <div class="fw-semibold">{{ $room->description ?? '—' }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <h5 class="mb-0">Contract History</h5>
    </div>
    <div class="card-datatable table-responsive">
      <table class="datatables-room-contracts table border-top">
        <thead>
          <tr>
            <th></th>
            <th>Occupant</th>
            <th>Period</th>
            <th>Status</th>
            <th>Rent (USD)</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($contracts as $contract)
            <tr>
              <td></td>
              <td>{{ $contract->occupant?->name ?? '—' }}</td>
              <td>{{ optional($contract->start_date)->format('Y-m-d') }} - {{ optional($contract->end_date)->format('Y-m-d') }}</td>
              <td>
                <span class="badge {{ $contractStatusLabels[$contract->status] ?? 'bg-label-secondary' }}">
                  {{ ucfirst($contract->status) }}
                </span>
              </td>
              <td>${{ number_format(($contract->monthly_rent_cents ?? 0) / 100, 2) }}</td>
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
      const table = document.querySelector('.datatables-room-contracts');
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
                  placeholder: 'Search Contract',
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

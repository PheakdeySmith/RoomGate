@extends('core::components.layouts.master')
@section('title', 'Property Details | RoomGate')
@section('page-title', 'Property Details')

@push('page-styles')
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/datatables-bs5/datatables.bootstrap5.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css" />
@endpush

@section('content')
@php
  $statusLabels = [
      'active' => 'bg-label-success',
      'inactive' => 'bg-label-secondary',
      'archived' => 'bg-label-danger',
  ];
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
  $roomCount = $rooms->count();
  $occupiedCount = $rooms->where('status', 'occupied')->count();
  $occupantsCount = $occupants->count();
  $activeContractsCount = $contracts->where('status', 'active')->count();
  $occupancyRate = $roomCount > 0 ? round(($occupiedCount / $roomCount) * 100, 1) : 0;
@endphp

<div class="container-xxl flex-grow-1 container-p-y">
  <div class="card mb-6">
    <div class="card-body">
      <div class="d-flex flex-wrap align-items-center justify-content-between gap-4">
        <div>
          <h4 class="mb-2">{{ $property->name }}</h4>
          <div class="d-flex flex-wrap gap-3 text-body-secondary">
            <span><strong>Tenant:</strong> {{ $property->tenant?->name ?? 'Unknown' }}</span>
            <span><strong>Type:</strong> {{ $property->propertyType?->name ?? '-' }}</span>
            <span><strong>Status:</strong>
              <span class="badge {{ $statusLabels[$property->status] ?? 'bg-label-secondary' }}">
                {{ ucfirst($property->status) }}
              </span>
            </span>
          </div>
        </div>
        <div class="text-end">
          <div class="text-body-secondary">Address</div>
          <div class="fw-semibold">
            {{ $property->address_line_1 ?? '-' }}
            @if (!empty($property->city) || !empty($property->country))
              <span class="d-block">{{ $property->city ?? '-' }}, {{ $property->country ?? '-' }}</span>
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-6 mb-6">
    <div class="col-lg-6 order-md-0 order-lg-0">
      <div class="card h-100">
        <div class="card-header pb-0 d-flex justify-content-between">
          <div class="card-title mb-0">
            <h5 class="mb-1">Earning Reports</h5>
            <p class="card-subtitle">Weekly Earnings Overview</p>
          </div>
          <div class="dropdown">
            <button
              class="btn btn-text-secondary rounded-pill text-body-secondary border-0 p-2 me-n1"
              type="button"
              id="earningReportsId"
              data-bs-toggle="dropdown"
              aria-haspopup="true"
              aria-expanded="false">
              <i class="icon-base ti tabler-dots-vertical icon-md text-body-secondary"></i>
            </button>
            <div class="dropdown-menu dropdown-menu-end" aria-labelledby="earningReportsId">
              <a class="dropdown-item" href="javascript:void(0);">View More</a>
              <a class="dropdown-item" href="javascript:void(0);">Delete</a>
            </div>
          </div>
        </div>
        <div class="card-body">
          <div class="row align-items-center g-md-8">
            <div class="col-12 col-md-5 d-flex flex-column">
              <div class="d-flex gap-2 align-items-center mb-3 flex-wrap">
                <h2 class="mb-0">${{ number_format($totalCents / 100, 0) }}</h2>
                <div class="badge rounded bg-label-success">+{{ $percentChange }}%</div>
              </div>
              <small class="text-body">You informed of this week compared to last week</small>
            </div>
            <div class="col-12 col-md-7 ps-xl-8">
              <div id="weeklyEarningReports"></div>
            </div>
          </div>
          <div class="border rounded p-5 mt-5">
            <div class="row gap-4 gap-sm-0">
              <div class="col-12 col-sm-4">
                <div class="d-flex gap-2 align-items-center">
                  <div class="badge rounded bg-label-primary p-1">
                    <i class="icon-base ti tabler-currency-dollar icon-sm"></i>
                  </div>
                  <h6 class="mb-0 fw-normal">Earnings</h6>
                </div>
                <h4 class="my-2">${{ number_format($totalCents / 100, 2) }}</h4>
                <div class="progress w-75" style="height:4px">
                  <div
                    class="progress-bar"
                    role="progressbar"
                    style="width: {{ $totalCents > 0 ? 65 : 0 }}%"
                    aria-valuenow="{{ $totalCents > 0 ? 65 : 0 }}"
                    aria-valuemin="0"
                    aria-valuemax="100"></div>
                </div>
              </div>
              <div class="col-12 col-sm-4">
                <div class="d-flex gap-2 align-items-center">
                  <div class="badge rounded bg-label-info p-1">
                    <i class="icon-base ti tabler-chart-pie-2 icon-sm"></i>
                  </div>
                  <h6 class="mb-0 fw-normal">Profit</h6>
                </div>
                <h4 class="my-2">${{ number_format($paidCents / 100, 2) }}</h4>
                <div class="progress w-75" style="height:4px">
                  <div
                    class="progress-bar bg-info"
                    role="progressbar"
                    style="width: {{ $totalCents > 0 ? 50 : 0 }}%"
                    aria-valuenow="{{ $totalCents > 0 ? 50 : 0 }}"
                    aria-valuemin="0"
                    aria-valuemax="100"></div>
                </div>
              </div>
              <div class="col-12 col-sm-4">
                <div class="d-flex gap-2 align-items-center">
                  <div class="badge rounded bg-label-danger p-1">
                    <i class="icon-base ti tabler-brand-paypal icon-sm"></i>
                  </div>
                  <h6 class="mb-0 fw-normal">Expense</h6>
                </div>
                <h4 class="my-2">${{ number_format($unpaidCents / 100, 2) }}</h4>
                <div class="progress w-75" style="height:4px">
                  <div
                    class="progress-bar bg-danger"
                    role="progressbar"
                    style="width: {{ $totalCents > 0 ? 65 : 0 }}%"
                    aria-valuenow="{{ $totalCents > 0 ? 65 : 0 }}"
                    aria-valuemin="0"
                    aria-valuemax="100"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-6 col-lg-4 order-md-2 order-lg-0">
      <div class="card h-100">
        <div class="card-header d-flex justify-content-between pb-4">
          <div class="card-title mb-0">
            <h5 class="mb-1">Sales</h5>
            <p class="card-subtitle">Last 6 Months</p>
          </div>
          <div class="dropdown">
            <button class="btn btn-text-secondary rounded-pill text-body-secondary border-0 p-2 me-n1 waves-effect" type="button" id="salesLastMonthMenu" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
              <i class="icon-base ti tabler-dots-vertical icon-md text-body-secondary"></i>
            </button>
            <div class="dropdown-menu dropdown-menu-end" aria-labelledby="salesLastMonthMenu">
              <a class="dropdown-item waves-effect" href="javascript:void(0);">View More</a>
              <a class="dropdown-item waves-effect" href="javascript:void(0);">Delete</a>
            </div>
          </div>
        </div>
        <div class="card-body">
          <div id="salesLastMonth" style="min-height: 335px;"></div>
        </div>
      </div>
    </div>
  </div>

  <div class="card mb-6">
    <div class="card-header">
      <h5 class="mb-0">Rooms</h5>
    </div>
    <div class="card-datatable table-responsive">
      <table class="datatables-property-rooms table border-top">
        <thead>
          <tr>
            <th></th>
            <th>Room</th>
            <th>Type</th>
            <th>Status</th>
            <th>Rent (USD)</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($rooms as $room)
            <tr>
              <td></td>
              <td>{{ $room->room_number }}</td>
              <td>{{ $room->roomType?->name ?? '-' }}</td>
              <td>
                <span class="badge {{ $roomStatusLabels[$room->status] ?? 'bg-label-secondary' }}">
                  {{ ucfirst($room->status) }}
                </span>
              </td>
              <td>${{ number_format(($room->monthly_rent_cents ?? 0) / 100, 2) }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>

  <div class="row g-6 mb-6">
    <div class="col-lg-6">
      <div class="card h-100">
        <div class="card-header">
          <h5 class="mb-0">Occupants</h5>
        </div>
        <div class="card-datatable table-responsive">
          <table class="datatables-property-occupants table border-top">
            <thead>
              <tr>
                <th></th>
                <th>Name</th>
                <th>Email</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($occupants as $occupant)
                <tr>
                  <td></td>
                  <td>{{ $occupant->name }}</td>
                  <td>{{ $occupant->email }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <div class="col-lg-6">
      <div class="card h-100">
        <div class="card-header">
          <h5 class="mb-0">Tenant Users</h5>
        </div>
        <div class="card-datatable table-responsive">
          <table class="datatables-property-users table border-top">
            <thead>
              <tr>
                <th></th>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($tenantUsers as $tenantUser)
                <tr>
                  <td></td>
                  <td>{{ $tenantUser->name }}</td>
                  <td>{{ $tenantUser->email }}</td>
                  <td>{{ $tenantUser->pivot?->role ?? '-' }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <h5 class="mb-0">Contracts</h5>
    </div>
    <div class="card-datatable table-responsive">
      <table class="datatables-property-contracts table border-top">
        <thead>
          <tr>
            <th></th>
            <th>Room</th>
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
              <td>{{ $contract->room?->room_number ?? '-' }}</td>
              <td>{{ $contract->occupant?->name ?? '-' }}</td>
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
      const initTable = (selector, searchPlaceholder) => {
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

      initTable('.datatables-property-rooms', 'Search Room');
      initTable('.datatables-property-occupants', 'Search Occupant');
      initTable('.datatables-property-users', 'Search Tenant User');
      initTable('.datatables-property-contracts', 'Search Contract');

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

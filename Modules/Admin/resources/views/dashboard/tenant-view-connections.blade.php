@extends('admin::components.layouts.master')
@section('title', 'Tenant Connections | RoomGate Admin')

@push('page-styles')
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/datatables-bs5/datatables.bootstrap5.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/css/pages/page-user-view.css" />
@endpush

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="row">
    @include('admin::dashboard.tenant-view.partials.sidebar')

    <div class="col-xl-8 col-lg-7 order-0 order-md-1" data-ajax-container="user-view">
      @include('admin::dashboard.tenant-view.partials.tabs')

      <div class="card mb-6">
        <div class="card-body">
          <h5 class="mb-2">Integrations</h5>
          <div class="row g-4">
            <div class="col-md-6">
              <div class="border rounded p-4 h-100">
                <div class="text-body-secondary">IoT Device IP</div>
                <div class="fw-semibold">{{ $appSettings->iot_device_ip ?? '—' }}</div>
                <div class="text-body-secondary mt-2">Tenant Slug</div>
                <div>{{ $tenant->slug }}</div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="border rounded p-4 h-100">
                <div class="text-body-secondary">Utility Providers</div>
                <div class="fw-semibold">{{ $utilityProviders->count() }}</div>
                <div class="text-body-secondary mt-2">Utility Meters</div>
                <div>{{ $utilityMeters->count() }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="card mb-4">
        <h5 class="card-header">Utility Providers</h5>
        <div class="card-datatable table-responsive">
          <table class="table datatables-tenant-providers">
            <thead>
              <tr>
                <th></th>
                <th>Name</th>
                <th>Status</th>
                <th>Contact</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($utilityProviders as $provider)
                <tr>
                  <td></td>
                  <td>{{ $provider->name }}</td>
                  <td class="text-capitalize">{{ $provider->status }}</td>
                  <td>{{ $provider->contact_email ?? $provider->contact_phone ?? '—' }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('page-scripts')
  <script src="{{ asset('assets/assets') }}/vendor/libs/datatables-bs5/datatables-bootstrap5.js"></script>
  <script src="{{ asset('assets/assets') }}/js/roomgate-ajax.js"></script>
  <script src="{{ asset('assets/assets') }}/js/admin-user-view-ajax.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const table = document.querySelector('.datatables-tenant-providers');
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
                  placeholder: 'Search',
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
                return 'Provider Details';
              }
            }),
            type: 'column'
          }
        }
      });
    });
  </script>
@endpush

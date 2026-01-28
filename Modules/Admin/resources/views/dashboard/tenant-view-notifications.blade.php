@extends('admin::components.layouts.master')
@section('title', 'Tenant Notifications | RoomGate Admin')

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
        <h5 class="card-header">Outbound Messages</h5>
        <div class="card-datatable table-responsive">
          <table class="table datatables-tenant-outbound">
            <thead>
              <tr>
                <th></th>
                <th>Channel</th>
                <th>Status</th>
                <th>To</th>
                <th>Created</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($outboundMessages as $message)
                <tr>
                  <td></td>
                  <td>{{ $message->channel }}</td>
                  <td class="text-capitalize">{{ $message->status }}</td>
                  <td>{{ $message->to_address ?? '—' }}</td>
                  <td>{{ $message->created_at?->toDateString() ?? '—' }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>

      <div class="card mb-4">
        <h5 class="card-header">In-App Notifications</h5>
        <div class="card-datatable table-responsive">
          <table class="table datatables-tenant-inapp">
            <thead>
              <tr>
                <th></th>
                <th>Title</th>
                <th>Type</th>
                <th>Read</th>
                <th>Created</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($inAppNotifications as $notification)
                <tr>
                  <td></td>
                  <td>{{ $notification->title }}</td>
                  <td class="text-capitalize">{{ $notification->type }}</td>
                  <td>{{ $notification->read_at ? 'Yes' : 'No' }}</td>
                  <td>{{ $notification->created_at?->toDateString() ?? '—' }}</td>
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
      ['.datatables-tenant-outbound', '.datatables-tenant-inapp'].forEach(selector => {
        const table = document.querySelector(selector);
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
                  return 'Details';
                }
              }),
              type: 'column'
            }
          }
        });
      });
    });
  </script>
@endpush

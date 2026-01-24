@extends('admin::components.layouts.master')
@section('title', 'Amenities | RoomGate Admin')
@section('page-title', 'Amenities')

@push('page-styles')
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/datatables-bs5/datatables.bootstrap5.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/select2/select2.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/@form-validation/form-validation.css" />
@endpush

@section('content')
@php
  $statusLabels = [
      'active' => 'bg-label-success',
      'inactive' => 'bg-label-warning',
  ];
@endphp

<div class="container-xxl flex-grow-1 container-p-y">
  <div class="card">
    <div class="card-datatable table-responsive">
      <table class="datatables-amenities table border-top">
        <thead>
          <tr>
            <th></th>
            <th>Name</th>
            <th>Tenant</th>
            <th>Price (USD)</th>
            <th>Status</th>
            <th>Rooms</th>
            <th>Room Types</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($amenities as $amenity)
            <tr>
              <td></td>
              <td>{{ $amenity->name }}</td>
              <td>{{ $amenity->tenant?->name ?? 'Unknown' }}</td>
              <td>${{ number_format(($amenity->price_cents ?? 0) / 100, 2) }}</td>
              <td>
                <span class="badge {{ $statusLabels[$amenity->status] ?? 'bg-label-secondary' }}">
                  {{ ucfirst($amenity->status) }}
                </span>
              </td>
              <td>{{ $amenity->rooms->count() }}</td>
              <td>{{ $amenity->roomTypes->count() }}</td>
              <td>
                <div class="d-flex align-items-center">
                  <a href="javascript:;" class="btn btn-icon btn-text-secondary rounded-pill waves-effect me-1" data-bs-toggle="modal" data-bs-target="#editAmenityModal"
                    data-amenity-id="{{ $amenity->id }}"
                    data-amenity-name="{{ $amenity->name }}"
                    data-amenity-tenant="{{ $amenity->tenant_id }}"
                    data-amenity-price="{{ number_format(($amenity->price_cents ?? 0) / 100, 2, '.', '') }}"
                    data-amenity-description="{{ $amenity->description }}"
                    data-amenity-status="{{ $amenity->status }}"
                    data-amenity-rooms="{{ $amenity->rooms->pluck('id')->implode(',') }}"
                    data-amenity-room-types="{{ $amenity->roomTypes->pluck('id')->implode(',') }}">
                    <i class="icon-base ti tabler-edit icon-22px"></i>
                  </a>
                  <form method="POST" action="{{ route('admin.amenities.destroy', $amenity) }}" data-confirm="Delete this amenity?">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-icon btn-text-secondary rounded-pill waves-effect">
                      <i class="icon-base ti tabler-trash icon-22px"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="modal fade" id="addAmenityModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Amenity</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form method="POST" action="{{ route('admin.amenities.store') }}" class="row g-3">
          @csrf
          <div class="col-md-6">
            <label class="form-label" for="amenityName">Amenity Name</label>
            <input type="text" id="amenityName" name="name" class="form-control" placeholder="Wi-Fi" required />
          </div>
          <div class="col-md-6">
            <label class="form-label" for="amenityTenant">Tenant</label>
            <select id="amenityTenant" name="tenant_id" class="select2 form-select" required>
              <option value="">Select tenant</option>
              @foreach ($tenants as $tenant)
                <option value="{{ $tenant->id }}">{{ $tenant->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="amenityPrice">Price (USD)</label>
            <input type="number" id="amenityPrice" name="price" class="form-control" step="0.01" min="0" placeholder="0.00" />
          </div>
          <div class="col-md-6">
            <label class="form-label" for="amenityStatus">Status</label>
            <select id="amenityStatus" name="status" class="form-select" required>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="amenityRooms">Attach Rooms</label>
            <select id="amenityRooms" name="room_ids[]" class="select2 form-select" multiple>
              @foreach ($rooms as $room)
                <option value="{{ $room->id }}">{{ $room->room_number }} ({{ $room->property?->name ?? 'Property' }})</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="amenityRoomTypes">Attach Room Types</label>
            <select id="amenityRoomTypes" name="room_type_ids[]" class="select2 form-select" multiple>
              @foreach ($roomTypes as $roomType)
                <option value="{{ $roomType->id }}">{{ $roomType->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-12">
            <label class="form-label" for="amenityDescription">Description</label>
            <textarea id="amenityDescription" name="description" class="form-control" rows="2" placeholder="Short description"></textarea>
          </div>
          <div class="col-12 d-flex justify-content-end">
            <button type="submit" class="btn btn-primary">Create Amenity</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="editAmenityModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Amenity</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form method="POST" id="editAmenityForm" action="" class="row g-3">
          @csrf
          @method('PATCH')
          <div class="col-md-6">
            <label class="form-label" for="editAmenityName">Amenity Name</label>
            <input type="text" id="editAmenityName" name="name" class="form-control" required />
          </div>
          <div class="col-md-6">
            <label class="form-label" for="editAmenityTenant">Tenant</label>
            <select id="editAmenityTenant" name="tenant_id" class="select2 form-select" required>
              <option value="">Select tenant</option>
              @foreach ($tenants as $tenant)
                <option value="{{ $tenant->id }}">{{ $tenant->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="editAmenityPrice">Price (USD)</label>
            <input type="number" id="editAmenityPrice" name="price" class="form-control" step="0.01" min="0" />
          </div>
          <div class="col-md-6">
            <label class="form-label" for="editAmenityStatus">Status</label>
            <select id="editAmenityStatus" name="status" class="form-select" required>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="editAmenityRooms">Attach Rooms</label>
            <select id="editAmenityRooms" name="room_ids[]" class="select2 form-select" multiple>
              @foreach ($rooms as $room)
                <option value="{{ $room->id }}">{{ $room->room_number }} ({{ $room->property?->name ?? 'Property' }})</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="editAmenityRoomTypes">Attach Room Types</label>
            <select id="editAmenityRoomTypes" name="room_type_ids[]" class="select2 form-select" multiple>
              @foreach ($roomTypes as $roomType)
                <option value="{{ $roomType->id }}">{{ $roomType->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-12">
            <label class="form-label" for="editAmenityDescription">Description</label>
            <textarea id="editAmenityDescription" name="description" class="form-control" rows="2"></textarea>
          </div>
          <div class="col-12 d-flex justify-content-end">
            <button type="submit" class="btn btn-primary">Save Changes</button>
          </div>
        </form>
      </div>
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
          const modal = $(this).closest('.modal');
          $(this).select2({
            placeholder: placeholder,
            allowClear: true,
            width: '100%',
            dropdownParent: modal.length ? modal : $(document.body)
          });
        });
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

      initTable('.datatables-amenities', 'Search Amenity', '<i class="icon-base ti tabler-plus me-0 me-sm-1 icon-16px"></i><span class="d-none d-sm-inline-block">Add Amenity</span>', '#addAmenityModal');

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

      const editModal = document.getElementById('editAmenityModal');
      if (editModal) {
        editModal.addEventListener('show.bs.modal', function (event) {
          const trigger = event.relatedTarget;
          const form = document.getElementById('editAmenityForm');
          const amenityId = trigger.getAttribute('data-amenity-id');

          form.action = `{{ url('/admin/amenities') }}/${amenityId}`;
          document.getElementById('editAmenityName').value = trigger.getAttribute('data-amenity-name') || '';
          document.getElementById('editAmenityTenant').value = trigger.getAttribute('data-amenity-tenant') || '';
          document.getElementById('editAmenityPrice').value = trigger.getAttribute('data-amenity-price') || '0.00';
          document.getElementById('editAmenityDescription').value = trigger.getAttribute('data-amenity-description') || '';
          document.getElementById('editAmenityStatus').value = trigger.getAttribute('data-amenity-status') || 'active';

          const roomIds = (trigger.getAttribute('data-amenity-rooms') || '').split(',').filter(Boolean);
          const roomTypeIds = (trigger.getAttribute('data-amenity-room-types') || '').split(',').filter(Boolean);

          if (window.$ && $.fn.select2) {
            $('#editAmenityTenant').val(trigger.getAttribute('data-amenity-tenant') || '').trigger('change');
            $('#editAmenityRooms').val(roomIds).trigger('change');
            $('#editAmenityRoomTypes').val(roomTypeIds).trigger('change');
          }
        });
      }
    });
  </script>
@endpush

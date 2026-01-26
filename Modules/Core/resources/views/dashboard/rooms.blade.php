@extends('core::components.layouts.master')
@section('title', 'Rooms | RoomGate')
@section('page-title', 'Rooms')

@push('page-styles')
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/datatables-bs5/datatables.bootstrap5.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/select2/select2.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/@form-validation/form-validation.css" />
@endpush

@section('content')
@php
  $statusLabels = [
      'available' => 'bg-label-success',
      'occupied' => 'bg-label-warning',
      'maintenance' => 'bg-label-danger',
      'inactive' => 'bg-label-secondary',
  ];
@endphp

<div class="container-xxl flex-grow-1 container-p-y">
  @if (!($canCreateRoom ?? true))
    <div class="alert alert-warning d-flex align-items-start" role="alert">
      <i class="icon-base ti tabler-alert-triangle me-2"></i>
      <div>
        <div class="fw-semibold">Plan limit reached</div>
        <div>You have reached your room limit. Upgrade your plan to add more rooms.</div>
      </div>
    </div>
  @endif
  <div class="card">
    <div class="card-datatable table-responsive">
      <table class="datatables-rooms table border-top">
        <thead>
          <tr>
            <th></th>
            <th>Room</th>
            <th>Property</th>
            <th>Type</th>
            <th>Rent (USD)</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($rooms as $room)
            <tr>
              <td></td>
              <td>
                <a href="{{ route('core.rooms.show', $room) }}" class="text-heading">
                  {{ $room->room_number }}
                </a>
              </td>
              <td>{{ $room->property?->name ?? 'Unknown' }}</td>
              <td>{{ $room->roomType?->name ?? '-' }}</td>
              <td>${{ number_format(($room->monthly_rent_cents ?? 0) / 100, 2) }}</td>
              <td>
                <span class="badge {{ $statusLabels[$room->status] ?? 'bg-label-secondary' }}">
                  {{ ucfirst($room->status) }}
                </span>
              </td>
              <td>
                <div class="d-flex align-items-center">
                  <a href="javascript:;" class="btn btn-icon btn-text-secondary rounded-pill waves-effect me-1" data-bs-toggle="modal" data-bs-target="#editRoomModal"
                    data-room-id="{{ $room->id }}"                    data-room-property="{{ $room->property_id }}"
                    data-room-type="{{ $room->room_type_id }}"
                    data-room-number="{{ $room->room_number }}"
                    data-room-description="{{ $room->description }}"
                    data-room-size="{{ $room->size }}"
                    data-room-floor="{{ $room->floor }}"
                    data-room-max-occupants="{{ $room->max_occupants }}"
                    data-room-rent="{{ number_format(($room->monthly_rent_cents ?? 0) / 100, 2, '.', '') }}"
                    data-room-status="{{ $room->status }}">
                    <i class="icon-base ti tabler-edit icon-22px"></i>
                  </a>
                  <form method="POST" action="{{ route('core.rooms.destroy', $room) }}" data-confirm="Delete this room?">
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

<div class="modal fade" id="addRoomModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Room</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form method="POST" action="{{ route('core.rooms.store') }}" class="row g-3">
          @csrf
          <div class="col-md-6">
            <label class="form-label" for="roomNumber">Room Number</label>
            <input type="text" id="roomNumber" name="room_number" class="form-control" placeholder="A-101" required />
          </div>
<div class="col-md-6">
            <label class="form-label" for="roomProperty">Property</label>
            <select id="roomProperty" name="property_id" class="select2 form-select" required>
              <option value="">Select property</option>
              @foreach ($properties as $property)
                <option value="{{ $property->id }}">{{ $property->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="roomType">Room Type</label>
            <select id="roomType" name="room_type_id" class="select2 form-select">
              <option value="">Select type</option>
              @foreach ($roomTypes as $roomType)
                <option value="{{ $roomType->id }}">{{ $roomType->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="roomRent">Monthly Rent (USD)</label>
            <input type="number" id="roomRent" name="monthly_rent" class="form-control" step="0.01" min="0" placeholder="120.00" />
          </div>
          <div class="col-md-6">
            <label class="form-label" for="roomStatus">Status</label>
            <select id="roomStatus" name="status" class="form-select" required>
              <option value="available">Available</option>
              <option value="occupied">Occupied</option>
              <option value="maintenance">Maintenance</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="roomMaxOccupants">Max Occupants</label>
            <input type="number" id="roomMaxOccupants" name="max_occupants" class="form-control" min="1" value="1" required />
          </div>
          <div class="col-md-4">
            <label class="form-label" for="roomSize">Size</label>
            <input type="text" id="roomSize" name="size" class="form-control" placeholder="25 sqm" />
          </div>
          <div class="col-md-4">
            <label class="form-label" for="roomFloor">Floor</label>
            <input type="number" id="roomFloor" name="floor" class="form-control" placeholder="1" />
          </div>
          <div class="col-12">
            <label class="form-label" for="roomDescription">Description</label>
            <textarea id="roomDescription" name="description" class="form-control" rows="2" placeholder="Short description"></textarea>
          </div>
          <div class="col-12 d-flex justify-content-end">
            <button type="submit" class="btn btn-primary">Create Room</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="editRoomModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Room</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form method="POST" id="editRoomForm" action="" class="row g-3">
          @csrf
          @method('PATCH')
          <div class="col-md-6">
            <label class="form-label" for="editRoomNumber">Room Number</label>
            <input type="text" id="editRoomNumber" name="room_number" class="form-control" required />
          </div>
<div class="col-md-6">
            <label class="form-label" for="editRoomProperty">Property</label>
            <select id="editRoomProperty" name="property_id" class="select2 form-select" required>
              <option value="">Select property</option>
              @foreach ($properties as $property)
                <option value="{{ $property->id }}">{{ $property->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="editRoomType">Room Type</label>
            <select id="editRoomType" name="room_type_id" class="select2 form-select">
              <option value="">Select type</option>
              @foreach ($roomTypes as $roomType)
                <option value="{{ $roomType->id }}">{{ $roomType->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="editRoomRent">Monthly Rent (USD)</label>
            <input type="number" id="editRoomRent" name="monthly_rent" class="form-control" step="0.01" min="0" />
          </div>
          <div class="col-md-6">
            <label class="form-label" for="editRoomStatus">Status</label>
            <select id="editRoomStatus" name="status" class="form-select" required>
              <option value="available">Available</option>
              <option value="occupied">Occupied</option>
              <option value="maintenance">Maintenance</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="editRoomMaxOccupants">Max Occupants</label>
            <input type="number" id="editRoomMaxOccupants" name="max_occupants" class="form-control" min="1" required />
          </div>
          <div class="col-md-4">
            <label class="form-label" for="editRoomSize">Size</label>
            <input type="text" id="editRoomSize" name="size" class="form-control" />
          </div>
          <div class="col-md-4">
            <label class="form-label" for="editRoomFloor">Floor</label>
            <input type="number" id="editRoomFloor" name="floor" class="form-control" />
          </div>
          <div class="col-12">
            <label class="form-label" for="editRoomDescription">Description</label>
            <textarea id="editRoomDescription" name="description" class="form-control" rows="2"></textarea>
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
        const addButton = addTarget
          ? {
              text: addText,
              className: 'add-new btn btn-primary rounded-2 waves-effect waves-light',
              attr: {
                'data-bs-toggle': 'modal',
                'data-bs-target': addTarget
              }
            }
          : {
              text: addText,
              className: 'add-new btn btn-label-secondary rounded-2 disabled',
              attr: {
                'aria-disabled': 'true'
              }
            };
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
                    addButton
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

      const canCreateRoom = @json($canCreateRoom ?? true);
      const roomLimit = @json($roomLimit ?? null);
      const addRoomText = canCreateRoom
        ? '<i class="icon-base ti tabler-plus me-0 me-sm-1 icon-16px"></i><span class="d-none d-sm-inline-block">Add Room</span>'
        : '<span class="d-none d-sm-inline-block">Limit reached</span>';

      initTable(
        '.datatables-rooms',
        'Search Room',
        addRoomText,
        canCreateRoom ? '#addRoomModal' : null
      );

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

      const editModal = document.getElementById('editRoomModal');
      if (editModal) {
        editModal.addEventListener('show.bs.modal', function (event) {
          const trigger = event.relatedTarget;
          const form = document.getElementById('editRoomForm');
          const roomId = trigger.getAttribute('data-room-id');

          form.action = `{{ url('/core/rooms') }}/${roomId}`;
          document.getElementById('editRoomNumber').value = trigger.getAttribute('data-room-number') || '';
          document.getElementById('editRoomProperty').value = trigger.getAttribute('data-room-property') || '';
          document.getElementById('editRoomType').value = trigger.getAttribute('data-room-type') || '';
          document.getElementById('editRoomRent').value = trigger.getAttribute('data-room-rent') || '0.00';
          document.getElementById('editRoomStatus').value = trigger.getAttribute('data-room-status') || 'available';
          document.getElementById('editRoomMaxOccupants').value = trigger.getAttribute('data-room-max-occupants') || 1;
          document.getElementById('editRoomSize').value = trigger.getAttribute('data-room-size') || '';
          document.getElementById('editRoomFloor').value = trigger.getAttribute('data-room-floor') || '';
          document.getElementById('editRoomDescription').value = trigger.getAttribute('data-room-description') || '';

          if (window.$ && $.fn.select2) {
            $('#editRoomProperty').trigger('change');
            $('#editRoomType').trigger('change');
          }
        });
      }
    });
  </script>
@endpush



@extends('core::components.layouts.master')
@section('title', 'Room Types | RoomGate')
@section('page-title', 'Room Types')

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
      <table class="datatables-room-types table border-top">
        <thead>
          <tr>
            <th></th>
            <th>Name</th>
            <th>Capacity</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($roomTypes as $roomType)
            <tr>
              <td></td>
              <td>{{ $roomType->name }}</td>
              <td>{{ $roomType->capacity ?? '-' }}</td>
              <td>
                <span class="badge {{ $statusLabels[$roomType->status] ?? 'bg-label-secondary' }}">
                  {{ ucfirst($roomType->status) }}
                </span>
              </td>
              <td>
                <div class="d-flex align-items-center">
                  <a href="javascript:;" class="btn btn-icon btn-text-secondary rounded-pill waves-effect me-1" data-bs-toggle="modal" data-bs-target="#editRoomTypeModal"
                    data-room-type-id="{{ $roomType->id }}"
                    data-room-type-name="{{ $roomType->name }}"                    data-room-type-capacity="{{ $roomType->capacity }}"
                    data-room-type-description="{{ $roomType->description }}"
                    data-room-type-status="{{ $roomType->status }}">
                    <i class="icon-base ti tabler-edit icon-22px"></i>
                  </a>
                  <form method="POST" action="{{ route('core.room-types.destroy', $roomType) }}" data-confirm="Delete this room type?">
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

<div class="modal fade" id="addRoomTypeModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Room Type</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form method="POST" action="{{ route('core.room-types.store') }}" class="row g-3">
          @csrf
          <div class="col-md-6">
            <label class="form-label" for="roomTypeName">Name</label>
            <input type="text" id="roomTypeName" name="name" class="form-control" placeholder="Studio" required />
          </div>
<div class="col-md-6">
            <label class="form-label" for="roomTypeCapacity">Capacity</label>
            <input type="number" id="roomTypeCapacity" name="capacity" class="form-control" min="1" placeholder="2" />
          </div>
          <div class="col-md-6">
            <label class="form-label" for="roomTypeStatus">Status</label>
            <select id="roomTypeStatus" name="status" class="form-select" required>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label" for="roomTypeDescription">Description</label>
            <textarea id="roomTypeDescription" name="description" class="form-control" rows="2" placeholder="Short description"></textarea>
          </div>
          <div class="col-12 d-flex justify-content-end">
            <button type="submit" class="btn btn-primary">Create Room Type</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="editRoomTypeModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Room Type</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form method="POST" id="editRoomTypeForm" action="" class="row g-3">
          @csrf
          @method('PATCH')
          <div class="col-md-6">
            <label class="form-label" for="editRoomTypeName">Name</label>
            <input type="text" id="editRoomTypeName" name="name" class="form-control" required />
          </div>
<div class="col-md-6">
            <label class="form-label" for="editRoomTypeCapacity">Capacity</label>
            <input type="number" id="editRoomTypeCapacity" name="capacity" class="form-control" min="1" />
          </div>
          <div class="col-md-6">
            <label class="form-label" for="editRoomTypeStatus">Status</label>
            <select id="editRoomTypeStatus" name="status" class="form-select" required>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label" for="editRoomTypeDescription">Description</label>
            <textarea id="editRoomTypeDescription" name="description" class="form-control" rows="2"></textarea>
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
      const roomTypesBaseUrl = @json(route('core.room-types.index'));
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

      initTable('.datatables-room-types', 'Search Room Type', '<i class="icon-base ti tabler-plus me-0 me-sm-1 icon-16px"></i><span class="d-none d-sm-inline-block">Add Room Type</span>', '#addRoomTypeModal');

        if (window.RoomGateDataTables && RoomGateDataTables.applyLayoutClasses) {
          setTimeout(() => {
            RoomGateDataTables.applyLayoutClasses();
          }, 100);
        }

      const editModal = document.getElementById('editRoomTypeModal');
      if (editModal) {
        editModal.addEventListener('show.bs.modal', function (event) {
          const trigger = event.relatedTarget;
          const form = document.getElementById('editRoomTypeForm');
          const roomTypeId = trigger.getAttribute('data-room-type-id');

          form.action = `${roomTypesBaseUrl}/${roomTypeId}`;
          document.getElementById('editRoomTypeName').value = trigger.getAttribute('data-room-type-name') || '';
          document.getElementById('editRoomTypeCapacity').value = trigger.getAttribute('data-room-type-capacity') || '';
          document.getElementById('editRoomTypeDescription').value = trigger.getAttribute('data-room-type-description') || '';
          document.getElementById('editRoomTypeStatus').value = trigger.getAttribute('data-room-type-status') || 'active';
        });
      }
    });
  </script>
@endpush







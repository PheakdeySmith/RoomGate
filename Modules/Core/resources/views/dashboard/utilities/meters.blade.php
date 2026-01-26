@extends('core::components.layouts.master')
@section('title', 'Utility Meters | RoomGate')
@section('page-title', 'Utility Meters')

@push('page-styles')
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/datatables-bs5/datatables.bootstrap5.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/select2/select2.css" />
@endpush

@section('content')
@php
  $statusLabels = [
      'active' => 'bg-label-success',
      'inactive' => 'bg-label-secondary',
  ];
@endphp

<div class="container-xxl flex-grow-1 container-p-y">
  <div class="card">
    <div class="card-datatable table-responsive">
      <table class="datatables-utility-meters table border-top">
        <thead>
          <tr>
            <th></th>
            <th>Meter</th>
            <th>Type</th>
            <th>Scope</th>
            <th>Provider</th>
            <th>Last Reading</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($meters as $meter)
            <tr>
              <td></td>
              <td>{{ $meter->meter_code }}</td>
              <td>{{ $meter->utilityType?->name ?? '-' }}</td>
              <td>
                <div class="d-flex flex-column">
                  <span>{{ $meter->property?->name ?? '-' }}</span>
                  <small class="text-body-secondary">{{ $meter->room?->room_number ?? 'Property level' }}</small>
                </div>
              </td>
              <td>{{ $meter->provider?->name ?? '-' }}</td>
              <td>
                <div class="d-flex flex-column">
                  <span>{{ $meter->last_reading_value ?? '-' }} {{ $meter->unit_of_measure }}</span>
                  <small class="text-body-secondary">{{ optional($meter->last_reading_at)->format('Y-m-d') }}</small>
                </div>
              </td>
              <td>
                <span class="badge {{ $statusLabels[$meter->status] ?? 'bg-label-secondary' }}">
                  {{ ucfirst($meter->status) }}
                </span>
              </td>
              <td>
                <div class="d-flex align-items-center">
                  <a href="javascript:;" class="btn btn-icon btn-text-secondary rounded-pill waves-effect me-1"
                     data-bs-toggle="modal" data-bs-target="#editMeterModal"
                     data-meter-id="{{ $meter->id }}"
                     data-meter-code="{{ $meter->meter_code }}"
                     data-meter-type="{{ $meter->utility_type_id }}"
                     data-meter-provider="{{ $meter->provider_id }}"
                     data-meter-property="{{ $meter->property_id }}"
                     data-meter-room="{{ $meter->room_id }}"
                     data-meter-unit="{{ $meter->unit_of_measure }}"
                     data-meter-status="{{ $meter->status }}"
                     data-meter-installed="{{ optional($meter->installed_at)->format('Y-m-d') }}">
                    <i class="icon-base ti tabler-edit icon-22px"></i>
                  </a>
                  <form method="POST" action="{{ route('core.utility-meters.destroy', $meter) }}" data-confirm="Delete this meter?">
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

<div class="modal fade" id="addMeterModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Meter</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form method="POST" action="{{ route('core.utility-meters.store') }}" class="row g-3">
          @csrf
          <div class="col-md-6">
            <label class="form-label" for="meterCode">Meter Code</label>
            <input type="text" id="meterCode" name="meter_code" class="form-control" required />
          </div>
          <div class="col-md-6">
            <label class="form-label" for="meterType">Utility Type</label>
            <select id="meterType" name="utility_type_id" class="select2 form-select" required>
              <option value="">Select type</option>
              @foreach ($utilityTypes as $type)
                <option value="{{ $type->id }}" data-unit="{{ $type->unit_of_measure }}">{{ $type->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="meterProperty">Property</label>
            <select id="meterProperty" name="property_id" class="select2 form-select" required>
              <option value="">Select property</option>
              @foreach ($properties as $property)
                <option value="{{ $property->id }}">{{ $property->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="meterRoom">Room (optional)</label>
            <select id="meterRoom" name="room_id" class="select2 form-select">
              <option value="">Property level</option>
              @foreach ($rooms as $room)
                <option value="{{ $room->id }}" data-property-id="{{ $room->property_id }}">{{ $room->room_number }} ({{ $room->property?->name ?? 'Property' }})</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="meterProvider">Provider</label>
            <select id="meterProvider" name="provider_id" class="select2 form-select">
              <option value="">Select provider</option>
              @foreach ($providers as $provider)
                <option value="{{ $provider->id }}">{{ $provider->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label" for="meterUnit">Unit</label>
            <input type="text" id="meterUnit" name="unit_of_measure" class="form-control" />
          </div>
          <div class="col-md-3">
            <label class="form-label" for="meterStatus">Status</label>
            <select id="meterStatus" name="status" class="form-select" required>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="meterInstalled">Installed At</label>
            <input type="text" id="meterInstalled" name="installed_at" class="form-control flatpickr" placeholder="YYYY-MM-DD" />
          </div>
          <div class="col-12 d-flex justify-content-end">
            <button type="submit" class="btn btn-primary">Create Meter</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="editMeterModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Meter</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form method="POST" id="editMeterForm" action="" class="row g-3">
          @csrf
          @method('PATCH')
          <div class="col-md-6">
            <label class="form-label" for="editMeterCode">Meter Code</label>
            <input type="text" id="editMeterCode" name="meter_code" class="form-control" required />
          </div>
          <div class="col-md-6">
            <label class="form-label" for="editMeterType">Utility Type</label>
            <select id="editMeterType" name="utility_type_id" class="select2 form-select" required>
              <option value="">Select type</option>
              @foreach ($utilityTypes as $type)
                <option value="{{ $type->id }}" data-unit="{{ $type->unit_of_measure }}">{{ $type->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="editMeterProperty">Property</label>
            <select id="editMeterProperty" name="property_id" class="select2 form-select" required>
              <option value="">Select property</option>
              @foreach ($properties as $property)
                <option value="{{ $property->id }}">{{ $property->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="editMeterRoom">Room (optional)</label>
            <select id="editMeterRoom" name="room_id" class="select2 form-select">
              <option value="">Property level</option>
              @foreach ($rooms as $room)
                <option value="{{ $room->id }}" data-property-id="{{ $room->property_id }}">{{ $room->room_number }} ({{ $room->property?->name ?? 'Property' }})</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="editMeterProvider">Provider</label>
            <select id="editMeterProvider" name="provider_id" class="select2 form-select">
              <option value="">Select provider</option>
              @foreach ($providers as $provider)
                <option value="{{ $provider->id }}">{{ $provider->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label" for="editMeterUnit">Unit</label>
            <input type="text" id="editMeterUnit" name="unit_of_measure" class="form-control" />
          </div>
          <div class="col-md-3">
            <label class="form-label" for="editMeterStatus">Status</label>
            <select id="editMeterStatus" name="status" class="form-select" required>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="editMeterInstalled">Installed At</label>
            <input type="text" id="editMeterInstalled" name="installed_at" class="form-control flatpickr" placeholder="YYYY-MM-DD" />
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
  <script src="{{ asset('assets/assets') }}/vendor/libs/flatpickr/flatpickr.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      if (window.flatpickr) {
        document.querySelectorAll('.flatpickr').forEach((el) => {
          flatpickr(el, { dateFormat: 'Y-m-d' });
        });
      }

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

      const filterRooms = (propertySelect, roomSelect) => {
        if (!propertySelect || !roomSelect) {
          return;
        }
        const propertyId = propertySelect.value;
        Array.from(roomSelect.options).forEach((option) => {
          if (!option.value) {
            option.hidden = false;
            return;
          }
          option.hidden = propertyId && option.getAttribute('data-property-id') !== propertyId;
        });
        if (roomSelect.value) {
          const selected = roomSelect.options[roomSelect.selectedIndex];
          if (selected && selected.hidden) {
            roomSelect.value = '';
          }
        }
      };

      const setUnitFromType = (typeSelect, unitInput) => {
        if (!typeSelect || !unitInput) {
          return;
        }
        const option = typeSelect.options[typeSelect.selectedIndex];
        const unit = option ? option.getAttribute('data-unit') : '';
        if (!unitInput.value) {
          unitInput.value = unit || '';
        }
      };

      const table = document.querySelector('.datatables-utility-meters');
      if (table && window.DataTable) {
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
                    placeholder: 'Search Meter',
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
                      text: '<i class="icon-base ti tabler-plus me-0 me-sm-1 icon-16px"></i><span class="d-none d-sm-inline-block">Add Meter</span>',
                      className: 'add-new btn btn-primary rounded-2 waves-effect waves-light',
                      attr: {
                        'data-bs-toggle': 'modal',
                        'data-bs-target': '#addMeterModal'
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
                  return 'Meter';
                }
              }),
              type: 'column'
            }
          }
        });
      }

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

      const addProperty = document.getElementById('meterProperty');
      const addRoom = document.getElementById('meterRoom');
      if (addProperty && addRoom) {
        addProperty.addEventListener('change', () => filterRooms(addProperty, addRoom));
        filterRooms(addProperty, addRoom);
      }
      const addType = document.getElementById('meterType');
      const addUnit = document.getElementById('meterUnit');
      if (addType && addUnit) {
        addType.addEventListener('change', () => setUnitFromType(addType, addUnit));
        setUnitFromType(addType, addUnit);
      }

      const editModal = document.getElementById('editMeterModal');
      if (editModal) {
        editModal.addEventListener('show.bs.modal', function (event) {
          const trigger = event.relatedTarget;
          const form = document.getElementById('editMeterForm');
          const meterId = trigger.getAttribute('data-meter-id');

          form.action = `{{ url('/core/utility-meters') }}/${meterId}`;
          document.getElementById('editMeterCode').value = trigger.getAttribute('data-meter-code') || '';
          document.getElementById('editMeterType').value = trigger.getAttribute('data-meter-type') || '';
          document.getElementById('editMeterProvider').value = trigger.getAttribute('data-meter-provider') || '';
          document.getElementById('editMeterProperty').value = trigger.getAttribute('data-meter-property') || '';
          document.getElementById('editMeterRoom').value = trigger.getAttribute('data-meter-room') || '';
          document.getElementById('editMeterUnit').value = trigger.getAttribute('data-meter-unit') || '';
          document.getElementById('editMeterStatus').value = trigger.getAttribute('data-meter-status') || 'active';
          document.getElementById('editMeterInstalled').value = trigger.getAttribute('data-meter-installed') || '';

          filterRooms(document.getElementById('editMeterProperty'), document.getElementById('editMeterRoom'));
          setUnitFromType(document.getElementById('editMeterType'), document.getElementById('editMeterUnit'));

          if (window.$ && $.fn.select2) {
            $('#editMeterType').trigger('change');
            $('#editMeterProvider').trigger('change');
            $('#editMeterProperty').trigger('change');
            $('#editMeterRoom').trigger('change');
          }
        });
      }
    });
  </script>
@endpush

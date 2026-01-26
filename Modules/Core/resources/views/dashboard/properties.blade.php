@extends('core::components.layouts.master')
@section('title', 'Properties | RoomGate')
@section('page-title', 'Properties')

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
      'archived' => 'bg-label-secondary',
  ];
@endphp

<div class="container-xxl flex-grow-1 container-p-y">
  @if (!($canCreateProperty ?? true))
    <div class="alert alert-warning d-flex align-items-start" role="alert">
      <i class="icon-base ti tabler-alert-triangle me-2"></i>
      <div>
        <div class="fw-semibold">Plan limit reached</div>
        <div>You have reached your property limit. Upgrade your plan to add more properties.</div>
      </div>
    </div>
  @endif
  <div class="card">
    <div class="card-datatable table-responsive">
      <table class="datatables-properties table border-top">
        <thead>
          <tr>
            <th></th>
            <th>Name</th>
            <th>Type</th>
            <th>Location</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($properties as $property)
            <tr>
              <td></td>
              <td>
                <a href="{{ route('core.properties.show', $property) }}" class="text-heading">
                  {{ $property->name }}
                </a>
              </td>
              <td>{{ $property->propertyType?->name ?? '-' }}</td>
              <td>{{ $property->city ?? '-' }}, {{ $property->country ?? '-' }}</td>
              <td>
                <span class="badge {{ $statusLabels[$property->status] ?? 'bg-label-secondary' }}">
                  {{ ucfirst($property->status) }}
                </span>
              </td>
              <td>
                <div class="d-flex align-items-center">
                  <a href="javascript:;" class="btn btn-icon btn-text-secondary rounded-pill waves-effect me-1" data-bs-toggle="modal" data-bs-target="#editPropertyModal"
                    data-property-id="{{ $property->id }}"
                    data-property-name="{{ $property->name }}"
                    data-property-type="{{ $property->property_type_id }}"
                    data-property-description="{{ $property->description }}"
                    data-property-address="{{ $property->address_line_1 }}"
                    data-property-city="{{ $property->city }}"
                    data-property-country="{{ $property->country }}"
                    data-property-status="{{ $property->status }}">
                    <i class="icon-base ti tabler-edit icon-22px"></i>
                  </a>
                  <form method="POST" action="{{ route('core.properties.destroy', $property) }}" data-confirm="Delete this property?">
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

<div class="modal fade" id="addPropertyModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Property</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form method="POST" action="{{ route('core.properties.store') }}" class="row g-3">
          @csrf
          <div class="col-md-6">
            <label class="form-label" for="propertyName">Property Name</label>
            <input type="text" id="propertyName" name="name" class="form-control" placeholder="RoomGate Tower" required />
          </div>
          <div class="col-md-6">
            <label class="form-label" for="propertyType">Property Type</label>
            <select id="propertyType" name="property_type_id" class="select2 form-select">
              <option value="">Select type</option>
              @foreach ($propertyTypes as $type)
                <option value="{{ $type->id }}">{{ $type->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="propertyStatus">Status</label>
            <select id="propertyStatus" name="status" class="form-select" required>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
              <option value="archived">Archived</option>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label" for="propertyAddress">Address</label>
            <input type="text" id="propertyAddress" name="address_line_1" class="form-control" placeholder="Street address" />
          </div>
          <div class="col-md-6">
            <label class="form-label" for="propertyCity">City</label>
            <input type="text" id="propertyCity" name="city" class="form-control" placeholder="City" />
          </div>
          <div class="col-md-6">
            <label class="form-label" for="propertyCountry">Country</label>
            <input type="text" id="propertyCountry" name="country" class="form-control" placeholder="Country" />
          </div>
          <div class="col-12">
            <label class="form-label" for="propertyDescription">Description</label>
            <textarea id="propertyDescription" name="description" class="form-control" rows="2" placeholder="Short description"></textarea>
          </div>
          <div class="col-12 d-flex justify-content-end">
            <button type="submit" class="btn btn-primary">Create Property</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="editPropertyModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Property</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form method="POST" id="editPropertyForm" action="" class="row g-3">
          @csrf
          @method('PATCH')
          <div class="col-md-6">
            <label class="form-label" for="editPropertyName">Property Name</label>
            <input type="text" id="editPropertyName" name="name" class="form-control" required />
          </div>
          <div class="col-md-6">
            <label class="form-label" for="editPropertyType">Property Type</label>
            <select id="editPropertyType" name="property_type_id" class="select2 form-select">
              <option value="">Select type</option>
              @foreach ($propertyTypes as $type)
                <option value="{{ $type->id }}">{{ $type->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="editPropertyStatus">Status</label>
            <select id="editPropertyStatus" name="status" class="form-select" required>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
              <option value="archived">Archived</option>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label" for="editPropertyAddress">Address</label>
            <input type="text" id="editPropertyAddress" name="address_line_1" class="form-control" />
          </div>
          <div class="col-md-6">
            <label class="form-label" for="editPropertyCity">City</label>
            <input type="text" id="editPropertyCity" name="city" class="form-control" />
          </div>
          <div class="col-md-6">
            <label class="form-label" for="editPropertyCountry">Country</label>
            <input type="text" id="editPropertyCountry" name="country" class="form-control" />
          </div>
          <div class="col-12">
            <label class="form-label" for="editPropertyDescription">Description</label>
            <textarea id="editPropertyDescription" name="description" class="form-control" rows="2"></textarea>
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

      const canCreateProperty = @json($canCreateProperty ?? true);
      const propertyLimit = @json($propertyLimit ?? null);
      const addPropertyText = canCreateProperty
        ? '<i class="icon-base ti tabler-plus me-0 me-sm-1 icon-16px"></i><span class="d-none d-sm-inline-block">Add Property</span>'
        : '<span class="d-none d-sm-inline-block">Limit reached</span>';

      initTable(
        '.datatables-properties',
        'Search Property',
        addPropertyText,
        canCreateProperty ? '#addPropertyModal' : null
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

      const editModal = document.getElementById('editPropertyModal');
      if (editModal) {
        editModal.addEventListener('show.bs.modal', function (event) {
          const trigger = event.relatedTarget;
          const form = document.getElementById('editPropertyForm');
          const propertyId = trigger.getAttribute('data-property-id');

          form.action = `{{ url('/core/properties') }}/${propertyId}`;
          document.getElementById('editPropertyName').value = trigger.getAttribute('data-property-name') || '';
          document.getElementById('editPropertyType').value = trigger.getAttribute('data-property-type') || '';
          document.getElementById('editPropertyDescription').value = trigger.getAttribute('data-property-description') || '';
          document.getElementById('editPropertyAddress').value = trigger.getAttribute('data-property-address') || '';
          document.getElementById('editPropertyCity').value = trigger.getAttribute('data-property-city') || '';
          document.getElementById('editPropertyCountry').value = trigger.getAttribute('data-property-country') || '';
          document.getElementById('editPropertyStatus').value = trigger.getAttribute('data-property-status') || 'active';

          if (window.$ && $.fn.select2) {
            $('#editPropertyType').trigger('change');
          }
        });
      }
    });
  </script>
@endpush

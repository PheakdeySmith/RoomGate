@extends('core::components.layouts.master')
@section('title', 'Properties | RoomGate')
@section('page-title', 'Properties')

@push('page-styles')
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/select2/select2.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/@form-validation/form-validation.css" />
  <style>
    .property-card {
      transition: border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
      cursor: pointer;
    }
    .property-card:hover {
      border-color: rgba(105, 108, 255, 0.4);
      box-shadow: 0 6px 16px rgba(47, 43, 61, 0.12);
      background-color: rgba(105, 108, 255, 0.04);
    }
  </style>
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
  <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
    <div>
      <h4 class="mb-1">Your Properties</h4>
      <p class="text-body-secondary mb-0">Open a property to view details, rooms, and activity.</p>
    </div>
    @if ($canCreateProperty ?? true)
      <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#addPropertyModal">
        <i class="icon-base ti tabler-plus me-1"></i>Add Property
      </button>
    @else
      <button class="btn btn-label-secondary" type="button" disabled>
        Limit reached
      </button>
    @endif
  </div>

  <div class="row g-4">
    @forelse ($properties as $property)
      @php
        $propertyInitial = strtoupper(substr($property->name ?? 'P', 0, 1));
        $propertyLocation = trim(($property->city ?? '') . ', ' . ($property->country ?? ''), ', ');
      @endphp
      <div class="col-xxl-3 col-xl-4 col-md-6">
        <div class="card h-100 border property-card position-relative">
          <div class="card-body position-relative">
            <div class="d-flex justify-content-between align-items-start mb-3">
              <div class="d-flex align-items-center gap-3">
                <div class="avatar">
                  <span class="avatar-initial rounded bg-label-primary">{{ $propertyInitial }}</span>
                </div>
                <div>
                  <a href="{{ route('core.properties.show', $property) }}" class="text-heading fw-semibold d-block stretched-link">
                    {{ $property->name }}
                  </a>
                  <div class="text-body-secondary small">{{ $property->propertyType?->name ?? 'Uncategorized' }}</div>
                </div>
              </div>
              <div class="dropdown position-relative" style="z-index: 2;">
                <button class="btn btn-sm btn-icon btn-text-secondary rounded-pill" data-bs-toggle="dropdown" aria-expanded="false">
                  <i class="icon-base ti tabler-dots-vertical"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                  <li>
                    <a class="dropdown-item" href="{{ route('core.properties.show', $property) }}">
                      <i class="icon-base ti tabler-eye me-2"></i>View
                    </a>
                  </li>
                  <li>
                    <a class="dropdown-item" href="{{ route('core.properties.utility-rates', $property) }}">
                      <i class="icon-base ti tabler-bolt me-2"></i>Utility Rates
                    </a>
                  </li>
                  <li>
                    <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#editPropertyModal"
                      data-property-id="{{ $property->id }}"
                      data-property-name="{{ $property->name }}"
                      data-property-type="{{ $property->property_type_id }}"
                      data-property-description="{{ $property->description }}"
                      data-property-address="{{ $property->address_line_1 }}"
                      data-property-city="{{ $property->city }}"
                      data-property-country="{{ $property->country }}"
                      data-property-status="{{ $property->status }}">
                      <i class="icon-base ti tabler-edit me-2"></i>Edit
                    </button>
                  </li>
                  <li>
                    <form method="POST" action="{{ route('core.properties.destroy', $property) }}" data-confirm="Delete this property?">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="dropdown-item text-danger">
                        <i class="icon-base ti tabler-trash me-2"></i>Delete
                      </button>
                    </form>
                  </li>
                </ul>
              </div>
            </div>

            <div class="d-flex align-items-center justify-content-between">
              <div class="text-body-secondary small">
                <i class="icon-base ti tabler-map-pin me-1"></i>{{ $propertyLocation !== '' ? $propertyLocation : 'Location not set' }}
              </div>
              <span class="badge {{ $statusLabels[$property->status] ?? 'bg-label-secondary' }}">
                {{ ucfirst($property->status) }}
              </span>
            </div>
          </div>
        </div>
      </div>
    @empty
      <div class="col-12">
        <div class="card border-dashed shadow-none">
          <div class="card-body text-center py-5">
            <i class="icon-base ti tabler-building-skyscraper icon-32px text-body-secondary mb-2"></i>
            <h6 class="mb-1">No properties yet</h6>
            <p class="text-body-secondary mb-3">Create your first property to start managing rooms.</p>
            @if ($canCreateProperty ?? true)
              <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#addPropertyModal">
                <i class="icon-base ti tabler-plus me-1"></i>Add Property
              </button>
            @endif
          </div>
        </div>
      </div>
    @endforelse
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

      const propertiesBaseUrl = @json(route('core.properties.index'));
      const editModal = document.getElementById('editPropertyModal');
      if (editModal) {
        editModal.addEventListener('show.bs.modal', function (event) {
          const trigger = event.relatedTarget;
          const form = document.getElementById('editPropertyForm');
          const propertyId = trigger.getAttribute('data-property-id');

          form.action = `${propertiesBaseUrl}/${propertyId}`;
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

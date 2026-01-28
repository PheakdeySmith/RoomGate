@extends('core::components.layouts.master')
@section('title', 'Utility Providers | RoomGate')
@section('page-title', 'Utility Providers')

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
      <table class="datatables-utility-providers table border-top">
        <thead>
          <tr>
            <th></th>
            <th>Provider</th>
            <th>Type</th>
            <th>Contact</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($providers as $provider)
            <tr>
              <td></td>
              <td>{{ $provider->name }}</td>
              <td>{{ $provider->utilityType?->name ?? '-' }}</td>
              <td>
                <div class="d-flex flex-column">
                  <span>{{ $provider->contact_name ?? '-' }}</span>
                  <small class="text-body-secondary">{{ $provider->contact_phone ?? $provider->contact_email ?? '-' }}</small>
                </div>
              </td>
              <td>
                <span class="badge {{ $statusLabels[$provider->status] ?? 'bg-label-secondary' }}">
                  {{ ucfirst($provider->status) }}
                </span>
              </td>
              <td>
                <div class="d-flex align-items-center">
                  <a href="javascript:;" class="btn btn-icon btn-text-secondary rounded-pill waves-effect me-1"
                     data-bs-toggle="modal" data-bs-target="#editProviderModal"
                     data-provider-id="{{ $provider->id }}"
                     data-provider-name="{{ $provider->name }}"
                     data-provider-type="{{ $provider->utility_type_id }}"
                     data-provider-account="{{ $provider->account_number }}"
                     data-provider-contact-name="{{ $provider->contact_name }}"
                     data-provider-contact-phone="{{ $provider->contact_phone }}"
                     data-provider-contact-email="{{ $provider->contact_email }}"
                     data-provider-status="{{ $provider->status }}"
                     data-provider-notes="{{ $provider->notes }}">
                    <i class="icon-base ti tabler-edit icon-22px"></i>
                  </a>
                  <form method="POST" action="{{ route('core.utility-providers.destroy', $provider) }}" data-confirm="Delete this provider?">
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

<div class="modal fade" id="addProviderModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Provider</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form method="POST" action="{{ route('core.utility-providers.store') }}" class="row g-3">
          @csrf
          <div class="col-md-6">
            <label class="form-label" for="providerName">Provider Name</label>
            <input type="text" id="providerName" name="name" class="form-control" required />
          </div>
          <div class="col-md-6">
            <label class="form-label" for="providerType">Utility Type</label>
            <select id="providerType" name="utility_type_id" class="select2 form-select" required>
              <option value="">Select type</option>
              @foreach ($utilityTypes as $type)
                <option value="{{ $type->id }}">{{ $type->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="providerAccount">Account Number</label>
            <input type="text" id="providerAccount" name="account_number" class="form-control" />
          </div>
          <div class="col-md-6">
            <label class="form-label" for="providerStatus">Status</label>
            <select id="providerStatus" name="status" class="form-select" required>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="providerContactName">Contact Name</label>
            <input type="text" id="providerContactName" name="contact_name" class="form-control" />
          </div>
          <div class="col-md-4">
            <label class="form-label" for="providerContactPhone">Contact Phone</label>
            <input type="text" id="providerContactPhone" name="contact_phone" class="form-control" />
          </div>
          <div class="col-md-4">
            <label class="form-label" for="providerContactEmail">Contact Email</label>
            <input type="email" id="providerContactEmail" name="contact_email" class="form-control" />
          </div>
          <div class="col-12">
            <label class="form-label" for="providerNotes">Notes</label>
            <textarea id="providerNotes" name="notes" class="form-control" rows="2"></textarea>
          </div>
          <div class="col-12 d-flex justify-content-end">
            <button type="submit" class="btn btn-primary">Create Provider</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="editProviderModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Provider</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form method="POST" id="editProviderForm" action="" class="row g-3">
          @csrf
          @method('PATCH')
          <div class="col-md-6">
            <label class="form-label" for="editProviderName">Provider Name</label>
            <input type="text" id="editProviderName" name="name" class="form-control" required />
          </div>
          <div class="col-md-6">
            <label class="form-label" for="editProviderType">Utility Type</label>
            <select id="editProviderType" name="utility_type_id" class="select2 form-select" required>
              <option value="">Select type</option>
              @foreach ($utilityTypes as $type)
                <option value="{{ $type->id }}">{{ $type->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="editProviderAccount">Account Number</label>
            <input type="text" id="editProviderAccount" name="account_number" class="form-control" />
          </div>
          <div class="col-md-6">
            <label class="form-label" for="editProviderStatus">Status</label>
            <select id="editProviderStatus" name="status" class="form-select" required>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="editProviderContactName">Contact Name</label>
            <input type="text" id="editProviderContactName" name="contact_name" class="form-control" />
          </div>
          <div class="col-md-4">
            <label class="form-label" for="editProviderContactPhone">Contact Phone</label>
            <input type="text" id="editProviderContactPhone" name="contact_phone" class="form-control" />
          </div>
          <div class="col-md-4">
            <label class="form-label" for="editProviderContactEmail">Contact Email</label>
            <input type="email" id="editProviderContactEmail" name="contact_email" class="form-control" />
          </div>
          <div class="col-12">
            <label class="form-label" for="editProviderNotes">Notes</label>
            <textarea id="editProviderNotes" name="notes" class="form-control" rows="2"></textarea>
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
      const providersBaseUrl = @json(route('core.utility-providers.index'));
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

      const table = document.querySelector('.datatables-utility-providers');
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
                    placeholder: 'Search Provider',
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
                      text: '<i class="icon-base ti tabler-plus me-0 me-sm-1 icon-16px"></i><span class="d-none d-sm-inline-block">Add Provider</span>',
                      className: 'add-new btn btn-primary rounded-2 waves-effect waves-light',
                      attr: {
                        'data-bs-toggle': 'modal',
                        'data-bs-target': '#addProviderModal'
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
                  return 'Provider';
                }
              }),
              type: 'column'
            }
          }
        });
      }

        if (window.RoomGateDataTables && RoomGateDataTables.applyLayoutClasses) {
          setTimeout(() => {
            RoomGateDataTables.applyLayoutClasses();
          }, 100);
        }

      const editModal = document.getElementById('editProviderModal');
      if (editModal) {
        editModal.addEventListener('show.bs.modal', function (event) {
          const trigger = event.relatedTarget;
          const form = document.getElementById('editProviderForm');
          const providerId = trigger.getAttribute('data-provider-id');

          form.action = `${providersBaseUrl}/${providerId}`;
          document.getElementById('editProviderName').value = trigger.getAttribute('data-provider-name') || '';
          document.getElementById('editProviderType').value = trigger.getAttribute('data-provider-type') || '';
          document.getElementById('editProviderAccount').value = trigger.getAttribute('data-provider-account') || '';
          document.getElementById('editProviderContactName').value = trigger.getAttribute('data-provider-contact-name') || '';
          document.getElementById('editProviderContactPhone').value = trigger.getAttribute('data-provider-contact-phone') || '';
          document.getElementById('editProviderContactEmail').value = trigger.getAttribute('data-provider-contact-email') || '';
          document.getElementById('editProviderStatus').value = trigger.getAttribute('data-provider-status') || 'active';
          document.getElementById('editProviderNotes').value = trigger.getAttribute('data-provider-notes') || '';

          if (window.$ && $.fn.select2) {
            $('#editProviderType').trigger('change');
          }
        });
      }
    });
  </script>
@endpush

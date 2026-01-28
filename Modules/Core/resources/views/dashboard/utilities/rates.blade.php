@extends('core::components.layouts.master')
@section('title', 'Utility Rates | RoomGate')
@section('page-title', 'Utility Rates')

@push('page-styles')
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/datatables-bs5/datatables.bootstrap5.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/select2/select2.css" />
@endpush

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="card">
    <div class="card-datatable table-responsive">
      <table class="datatables-utility-rates table border-top">
        <thead>
          <tr>
            <th></th>
            <th>Type</th>
            <th>Property</th>
            <th>Rate (USD)</th>
            <th>Effective</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($rates as $rate)
            <tr>
              <td></td>
              <td>{{ $rate->utilityType?->name ?? '-' }}</td>
              <td>{{ $rate->property?->name ?? 'All properties' }}</td>
              <td>${{ number_format(($rate->rate_cents ?? 0) / 100, 4) }}</td>
              <td>{{ optional($rate->effective_from)->format('Y-m-d') }} - {{ optional($rate->effective_to)->format('Y-m-d') ?? 'Open' }}</td>
              <td>
                <div class="d-flex align-items-center">
                  <a href="javascript:;" class="btn btn-icon btn-text-secondary rounded-pill waves-effect me-1"
                     data-bs-toggle="modal" data-bs-target="#editRateModal"
                     data-rate-id="{{ $rate->id }}"
                     data-rate-type="{{ $rate->utility_type_id }}"
                     data-rate-property="{{ $rate->property_id }}"
                     data-rate-value="{{ number_format(($rate->rate_cents ?? 0) / 100, 4, '.', '') }}"
                     data-rate-from="{{ optional($rate->effective_from)->format('Y-m-d') }}"
                     data-rate-to="{{ optional($rate->effective_to)->format('Y-m-d') }}">
                    <i class="icon-base ti tabler-edit icon-22px"></i>
                  </a>
                  <form method="POST" action="{{ route('core.utility-rates.destroy', $rate) }}" data-confirm="Delete this rate?">
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

<div class="modal fade" id="addRateModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Rate</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form method="POST" action="{{ route('core.utility-rates.store') }}" class="row g-3">
          @csrf
          <div class="col-md-6">
            <label class="form-label" for="rateType">Utility Type</label>
            <select id="rateType" name="utility_type_id" class="select2 form-select" required>
              <option value="">Select type</option>
              @foreach ($utilityTypes as $type)
                <option value="{{ $type->id }}">{{ $type->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="rateProperty">Property (optional)</label>
            <select id="rateProperty" name="property_id" class="select2 form-select">
              <option value="">All properties</option>
              @foreach ($properties as $property)
                <option value="{{ $property->id }}">{{ $property->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="rateValue">Rate per unit (USD)</label>
            <input type="number" id="rateValue" name="rate" class="form-control" step="0.0001" min="0" required />
          </div>
          <div class="col-md-4">
            <label class="form-label" for="rateFrom">Effective From</label>
            <input type="text" id="rateFrom" name="effective_from" class="form-control flatpickr" placeholder="YYYY-MM-DD" required />
          </div>
          <div class="col-md-4">
            <label class="form-label" for="rateTo">Effective To</label>
            <input type="text" id="rateTo" name="effective_to" class="form-control flatpickr" placeholder="YYYY-MM-DD" />
          </div>
          <div class="col-12 d-flex justify-content-end">
            <button type="submit" class="btn btn-primary">Create Rate</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="editRateModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Rate</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form method="POST" id="editRateForm" action="" class="row g-3">
          @csrf
          @method('PATCH')
          <div class="col-md-6">
            <label class="form-label" for="editRateType">Utility Type</label>
            <select id="editRateType" name="utility_type_id" class="select2 form-select" required>
              <option value="">Select type</option>
              @foreach ($utilityTypes as $type)
                <option value="{{ $type->id }}">{{ $type->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="editRateProperty">Property (optional)</label>
            <select id="editRateProperty" name="property_id" class="select2 form-select">
              <option value="">All properties</option>
              @foreach ($properties as $property)
                <option value="{{ $property->id }}">{{ $property->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="editRateValue">Rate per unit (USD)</label>
            <input type="number" id="editRateValue" name="rate" class="form-control" step="0.0001" min="0" required />
          </div>
          <div class="col-md-4">
            <label class="form-label" for="editRateFrom">Effective From</label>
            <input type="text" id="editRateFrom" name="effective_from" class="form-control flatpickr" placeholder="YYYY-MM-DD" required />
          </div>
          <div class="col-md-4">
            <label class="form-label" for="editRateTo">Effective To</label>
            <input type="text" id="editRateTo" name="effective_to" class="form-control flatpickr" placeholder="YYYY-MM-DD" />
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
      const ratesBaseUrl = @json(route('core.utility-rates.index'));
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

      const table = document.querySelector('.datatables-utility-rates');
      if (table && window.DataTable) {
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
                    placeholder: 'Search Rate',
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
                      text: '<i class="icon-base ti tabler-plus me-0 me-sm-1 icon-16px"></i><span class="d-none d-sm-inline-block">Add Rate</span>',
                      className: 'add-new btn btn-primary rounded-2 waves-effect waves-light',
                      attr: {
                        'data-bs-toggle': 'modal',
                        'data-bs-target': '#addRateModal'
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
                  return 'Rate';
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

      const editModal = document.getElementById('editRateModal');
      if (editModal) {
        editModal.addEventListener('show.bs.modal', function (event) {
          const trigger = event.relatedTarget;
          const form = document.getElementById('editRateForm');
          const rateId = trigger.getAttribute('data-rate-id');

          form.action = `${ratesBaseUrl}/${rateId}`;
          document.getElementById('editRateType').value = trigger.getAttribute('data-rate-type') || '';
          document.getElementById('editRateProperty').value = trigger.getAttribute('data-rate-property') || '';
          document.getElementById('editRateValue').value = trigger.getAttribute('data-rate-value') || '';
          document.getElementById('editRateFrom').value = trigger.getAttribute('data-rate-from') || '';
          document.getElementById('editRateTo').value = trigger.getAttribute('data-rate-to') || '';

          if (window.$ && $.fn.select2) {
            $('#editRateType').trigger('change');
            $('#editRateProperty').trigger('change');
          }
        });
      }
    });
  </script>
@endpush

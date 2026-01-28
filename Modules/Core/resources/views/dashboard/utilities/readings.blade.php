@extends('core::components.layouts.master')
@section('title', 'Utility Readings | RoomGate')
@section('page-title', 'Utility Readings')

@push('page-styles')
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/datatables-bs5/datatables.bootstrap5.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/select2/select2.css" />
@endpush

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="card">
    <div class="card-datatable table-responsive">
      <table class="datatables-utility-readings table border-top">
        <thead>
          <tr>
            <th></th>
            <th>Meter</th>
            <th>Type</th>
            <th>Scope</th>
            <th>Reading</th>
            <th>Reading At</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($readings as $reading)
            <tr>
              <td></td>
              <td>{{ $reading->meter?->meter_code ?? '-' }}</td>
              <td>{{ $reading->meter?->utilityType?->name ?? '-' }}</td>
              <td>
                <div class="d-flex flex-column">
                  <span>{{ $reading->meter?->property?->name ?? '-' }}</span>
                  <small class="text-body-secondary">{{ $reading->meter?->room?->room_number ?? 'Property level' }}</small>
                </div>
              </td>
              <td>{{ $reading->reading_value }} {{ $reading->meter?->unit_of_measure }}</td>
              <td>{{ optional($reading->reading_at)->format('Y-m-d') }}</td>
              <td>
                <div class="d-flex align-items-center">
                  <a href="javascript:;" class="btn btn-icon btn-text-secondary rounded-pill waves-effect me-1"
                     data-bs-toggle="modal" data-bs-target="#editReadingModal"
                     data-reading-id="{{ $reading->id }}"
                     data-reading-meter="{{ $reading->meter_id }}"
                     data-reading-value="{{ $reading->reading_value }}"
                     data-reading-at="{{ optional($reading->reading_at)->format('Y-m-d') }}"
                     data-reading-notes="{{ $reading->notes }}">
                    <i class="icon-base ti tabler-edit icon-22px"></i>
                  </a>
                  <form method="POST" action="{{ route('core.utility-readings.destroy', $reading) }}" data-confirm="Delete this reading?">
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

<div class="modal fade" id="addReadingModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Reading</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form method="POST" action="{{ route('core.utility-readings.store') }}" class="row g-3">
          @csrf
          <div class="col-md-6">
            <label class="form-label" for="readingMeter">Meter</label>
            <select id="readingMeter" name="meter_id" class="select2 form-select" required>
              <option value="">Select meter</option>
              @foreach ($meters as $meter)
                <option value="{{ $meter->id }}">{{ $meter->meter_code }} ({{ $meter->utilityType?->name }})</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label" for="readingValue">Reading Value</label>
            <input type="number" step="0.001" id="readingValue" name="reading_value" class="form-control" required />
          </div>
          <div class="col-md-3">
            <label class="form-label" for="readingAt">Reading At</label>
            <input type="text" id="readingAt" name="reading_at" class="form-control flatpickr" placeholder="YYYY-MM-DD" required />
          </div>
          <div class="col-12">
            <label class="form-label" for="readingNotes">Notes</label>
            <textarea id="readingNotes" name="notes" class="form-control" rows="2"></textarea>
          </div>
          <div class="col-12 d-flex justify-content-end">
            <button type="submit" class="btn btn-primary">Create Reading</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="editReadingModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Reading</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form method="POST" id="editReadingForm" action="" class="row g-3">
          @csrf
          @method('PATCH')
          <div class="col-md-6">
            <label class="form-label" for="editReadingMeter">Meter</label>
            <select id="editReadingMeter" name="meter_id" class="select2 form-select" required>
              <option value="">Select meter</option>
              @foreach ($meters as $meter)
                <option value="{{ $meter->id }}">{{ $meter->meter_code }} ({{ $meter->utilityType?->name }})</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label" for="editReadingValue">Reading Value</label>
            <input type="number" step="0.001" id="editReadingValue" name="reading_value" class="form-control" required />
          </div>
          <div class="col-md-3">
            <label class="form-label" for="editReadingAt">Reading At</label>
            <input type="text" id="editReadingAt" name="reading_at" class="form-control flatpickr" placeholder="YYYY-MM-DD" required />
          </div>
          <div class="col-12">
            <label class="form-label" for="editReadingNotes">Notes</label>
            <textarea id="editReadingNotes" name="notes" class="form-control" rows="2"></textarea>
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
      const readingsBaseUrl = @json(route('core.utility-readings.index'));
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

      const table = document.querySelector('.datatables-utility-readings');
      if (table && window.DataTable) {
        new DataTable(table, {
          order: [[5, 'desc']],
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
                    placeholder: 'Search Reading',
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
                      text: '<i class="icon-base ti tabler-plus me-0 me-sm-1 icon-16px"></i><span class="d-none d-sm-inline-block">Add Reading</span>',
                      className: 'add-new btn btn-primary rounded-2 waves-effect waves-light',
                      attr: {
                        'data-bs-toggle': 'modal',
                        'data-bs-target': '#addReadingModal'
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
                  return 'Reading';
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

      const editModal = document.getElementById('editReadingModal');
      if (editModal) {
        editModal.addEventListener('show.bs.modal', function (event) {
          const trigger = event.relatedTarget;
          const form = document.getElementById('editReadingForm');
          const readingId = trigger.getAttribute('data-reading-id');

          form.action = `${readingsBaseUrl}/${readingId}`;
          document.getElementById('editReadingMeter').value = trigger.getAttribute('data-reading-meter') || '';
          document.getElementById('editReadingValue').value = trigger.getAttribute('data-reading-value') || '';
          document.getElementById('editReadingAt').value = trigger.getAttribute('data-reading-at') || '';
          document.getElementById('editReadingNotes').value = trigger.getAttribute('data-reading-notes') || '';

          if (window.$ && $.fn.select2) {
            $('#editReadingMeter').trigger('change');
          }
        });
      }
    });
  </script>
@endpush

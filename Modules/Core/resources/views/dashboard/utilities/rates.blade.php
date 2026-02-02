@extends('core::components.layouts.master')
@section('title', 'Utility Rates | RoomGate')
@section('page-title', 'Utility Rates')

@push('page-styles')
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/datatables-bs5/datatables.bootstrap5.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/select2/select2.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/flatpickr/flatpickr.css" />
  <style>
    .utility-metric-row {
      align-items: stretch;
    }
    .utility-metric {
      min-height: 48px;
    }
    .utility-metric-divider {
      align-self: stretch;
      width: 1px;
      background: var(--bs-border-color);
      opacity: 0.7;
    }
  </style>
@endpush

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  @if (!empty($propertyContext))
    <div class="d-flex flex-wrap gap-3 justify-content-between align-items-start mb-3">
      <div>
        <h5 class="mb-1">Utility Rates for {{ $propertyContext->name }}</h5>
        <div class="text-body-secondary small">
          Property-specific rates apply first, then all-property defaults.
        </div>
      </div>
      <div class="text-end">
        <form method="GET" action="{{ route('core.properties.utility-rates', $propertyContext) }}" class="d-flex flex-wrap gap-2 justify-content-end">
          <select name="month" class="form-select form-select-sm w-auto">
            @for ($month = 1; $month <= 12; $month++)
              <option value="{{ $month }}" @selected(($filterMonth ?? now()->month) === $month)>
                {{ \Carbon\Carbon::create(null, $month, 1)->format('F') }}
              </option>
            @endfor
          </select>
          <select name="year" class="form-select form-select-sm w-auto">
            @php
              $currentYear = now()->year;
            @endphp
            @for ($year = $currentYear - 4; $year <= $currentYear + 1; $year++)
              <option value="{{ $year }}" @selected(($filterYear ?? $currentYear) === $year)>{{ $year }}</option>
            @endfor
          </select>
          <button type="submit" class="btn btn-sm btn-label-secondary">Filter</button>
        </form>
      </div>
    </div>

    <div class="row g-3 mb-4">
      <div class="col-xxl-6 col-md-6 col-12">
        <div class="card h-100">
          <div class="card-header pb-0">
            <h5 class="card-title mb-1">Electricity</h5>
            <p class="card-subtitle">{{ optional($periodStart)->format('M Y') }}</p>
          </div>
          <div id="utilityElectricityChart"></div>
          <div class="card-body pt-0">
            <div class="d-flex align-items-start mt-3 gap-4 utility-metric-row">
              <div class="utility-metric">
                <div class="text-body-secondary small">Consumption</div>
                <h4 class="mb-0">{{ number_format((float) ($electricityTotals['consumption'] ?? 0), 2) }}</h4>
              </div>
              <div class="utility-metric-divider" aria-hidden="true"></div>
              <div class="utility-metric">
                <div class="text-body-secondary small">Total price</div>
                <h4 class="mb-0">${{ number_format(((int) ($electricityTotals['bill_cents'] ?? 0)) / 100, 2) }}</h4>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-xxl-6 col-md-6 col-12">
        <div class="card h-100">
          <div class="card-header pb-0">
            <h5 class="card-title mb-1">Water</h5>
            <p class="card-subtitle">{{ optional($periodStart)->format('M Y') }}</p>
          </div>
          <div id="utilityWaterChart"></div>
          <div class="card-body pt-0">
            <div class="d-flex align-items-start mt-3 gap-4 utility-metric-row">
              <div class="utility-metric">
                <div class="text-body-secondary small">Consumption</div>
                <h4 class="mb-0">{{ number_format((float) ($waterTotals['consumption'] ?? 0), 2) }}</h4>
              </div>
              <div class="utility-metric-divider" aria-hidden="true"></div>
              <div class="utility-metric">
                <div class="text-body-secondary small">Total price</div>
                <h4 class="mb-0">${{ number_format(((int) ($waterTotals['bill_cents'] ?? 0)) / 100, 2) }}</h4>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

  @endif

  <div class="card">
    <div class="card-datatable table-responsive">
      <table class="datatables-utility-rates table border-top">
        <thead>
          <tr>
            <th></th>
            <th>Type</th>
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
                <option value="{{ $property->id }}" @if (!empty($propertyContext) && $propertyContext->id === $property->id) selected @endif>
                  {{ $property->name }}
                </option>
              @endforeach
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="rateValue">Rate per unit (USD)</label>
            <input type="number" id="rateValue" name="rate" class="form-control" step="0.0001" min="0" required />
          </div>
          <div class="col-md-4">
            <label class="form-label" for="rateFrom">Effective From</label>
            <input type="text" id="rateFrom" name="effective_from" class="form-control flatpickr" placeholder="Month DD, YYYY" required />
          </div>
          <div class="col-md-4">
            <label class="form-label" for="rateTo">Effective To</label>
            <input type="text" id="rateTo" name="effective_to" class="form-control flatpickr" placeholder="Month DD, YYYY" />
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
            <input type="text" id="editRateFrom" name="effective_from" class="form-control flatpickr" placeholder="Month DD, YYYY" required />
          </div>
          <div class="col-md-4">
            <label class="form-label" for="editRateTo">Effective To</label>
            <input type="text" id="editRateTo" name="effective_to" class="form-control flatpickr" placeholder="Month DD, YYYY" />
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
      const ratesUpdateUrl = @json(route('core.utility-rates.update', ['rate' => '__RATE__']));
      const initModalFlatpickr = (modal) => {
        if (!window.flatpickr || !modal) return;
        modal.querySelectorAll('.flatpickr').forEach((el) => {
          if (el._flatpickr) {
            el._flatpickr.destroy();
          }
          flatpickr(el, {
            altInput: true,
            altFormat: 'F j, Y',
            dateFormat: 'Y-m-d',
            disableMobile: true,
            appendTo: modal,
            altInputClass: 'form-control'
          });
        });
      };

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

          form.action = ratesUpdateUrl.replace('__RATE__', rateId);
          document.getElementById('editRateType').value = trigger.getAttribute('data-rate-type') || '';
          document.getElementById('editRateProperty').value = trigger.getAttribute('data-rate-property') || '';
          document.getElementById('editRateValue').value = trigger.getAttribute('data-rate-value') || '';
          document.getElementById('editRateFrom').value = trigger.getAttribute('data-rate-from') || '';
          document.getElementById('editRateTo').value = trigger.getAttribute('data-rate-to') || '';

          if (window.$ && $.fn.select2) {
            $('#editRateType').trigger('change');
            $('#editRateProperty').trigger('change');
          }

          initModalFlatpickr(editModal);
        });
      }

      const addModal = document.getElementById('addRateModal');
      if (addModal) {
        addModal.addEventListener('show.bs.modal', function () {
          initModalFlatpickr(addModal);
        });
      }

      if (window.ApexCharts && document.getElementById('utilityElectricityChart')) {
        const electricitySeries = @json($dailyElectricUsage ?? []);
        const waterSeries = @json($dailyWaterUsage ?? []);

        const toFloatSeries2 = (arr) => (arr || []).map((val) => parseFloat((val || 0).toFixed(2)));

        const colors = {
          success: getComputedStyle(document.documentElement).getPropertyValue('--bs-success').trim() || '#28c76f',
          info: getComputedStyle(document.documentElement).getPropertyValue('--bs-info').trim() || '#00cfe8',
          primary: getComputedStyle(document.documentElement).getPropertyValue('--bs-primary').trim() || '#7367f0',
          warning: getComputedStyle(document.documentElement).getPropertyValue('--bs-warning').trim() || '#ff9f43'
        };

        const renderSparkline = (elId, data, color) => {
          const el = document.getElementById(elId);
          if (!el) return;
          const options = {
            chart: {
              type: 'area',
              height: 75,
              sparkline: { enabled: true }
            },
            series: [{ data: data }],
            stroke: { curve: 'smooth', width: 2 },
            fill: {
              type: 'gradient',
              gradient: {
                shadeIntensity: 0.5,
                opacityFrom: 0.4,
                opacityTo: 0.1,
                stops: [0, 90, 100]
              }
            },
            tooltip: { enabled: false },
            colors: [color]
          };
          new ApexCharts(el, options).render();
        };

        renderSparkline('utilityElectricityChart', toFloatSeries2(electricitySeries), colors.success);
        renderSparkline('utilityWaterChart', toFloatSeries2(waterSeries), colors.info);
      }
    });
  </script>
@endpush

@extends('core::components.layouts.master')
@section('title', 'Room Details | RoomGate')
@section('page-title', 'Room Details')

@push('page-styles')
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/datatables-bs5/datatables.bootstrap5.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/select2/select2.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/flatpickr/flatpickr.css" />
@endpush

@section('content')
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="card mb-6">
      <div class="card-header border-bottom">
        <ul class="nav nav-tabs card-header-tabs" role="tablist">
          <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#room-basic" type="button" role="tab" aria-controls="room-basic" aria-selected="true">
              Basic
            </button>
          </li>
          <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#room-utilities" type="button" role="tab" aria-controls="room-utilities" aria-selected="false">
              Utilities
            </button>
          </li>
        </ul>
      </div>
      <div class="card-body">
        <div class="tab-content">
          <div class="tab-pane fade show active" id="room-basic" role="tabpanel">
            <div class="row g-6">
              <div class="col-lg-8">
                <div class="card h-100">
                  <div class="card-body">
                    @php
                      $statusClassMap = [
                          'available' => 'bg-label-success',
                          'occupied' => 'bg-label-warning',
                          'maintenance' => 'bg-label-danger',
                          'inactive' => 'bg-label-secondary',
                      ];
                    @endphp
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-4">
                      <div>
                        <h4 class="mb-1">{{ $room->room_number }}</h4>
                        <p class="text-body-secondary mb-0">{{ $room->property?->name ?? 'Property' }}</p>
                      </div>
                      <span class="badge {{ $statusClassMap[$room->status] ?? 'bg-label-secondary' }} text-uppercase">
                        {{ $room->status }}
                      </span>
                    </div>

                    <div class="row g-4 mt-2">
                      <div class="col-md-4">
                        <div class="text-body-secondary">Room Type</div>
                        <div class="fw-semibold">{{ $room->roomType?->name ?? '-' }}</div>
                      </div>
                      <div class="col-md-4">
                        <div class="text-body-secondary">Max Occupants</div>
                        <div class="fw-semibold">{{ $room->max_occupants }}</div>
                      </div>
                      <div class="col-md-4">
                        <div class="text-body-secondary">Monthly Rent</div>
                        <div class="fw-semibold">${{ number_format(($room->monthly_rent_cents ?? 0) / 100, 2) }}</div>
                      </div>
                      <div class="col-md-4">
                        <div class="text-body-secondary">Size</div>
                        <div class="fw-semibold">{{ $room->size ?? '-' }}</div>
                      </div>
                      <div class="col-md-4">
                        <div class="text-body-secondary">Floor</div>
                        <div class="fw-semibold">{{ $room->floor ?? '-' }}</div>
                      </div>
                    </div>

                    @if ($room->description)
                      <div class="mt-4">
                        <div class="text-body-secondary">Description</div>
                        <p class="mb-0">{{ $room->description }}</p>
                      </div>
                    @endif
                  </div>
                </div>
              </div>

              <div class="col-lg-4">
                <div class="card h-100">
                  <div class="card-body">
                    <h5 class="mb-4">Occupancy</h5>
                    @if ($activeContract)
                      <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="avatar avatar-sm">
                          <span class="avatar-initial rounded-circle bg-label-primary">
                            {{ strtoupper(substr($activeContract->occupant?->name ?? 'U', 0, 1)) }}
                          </span>
                        </div>
                        <div>
                          <div class="fw-semibold">{{ $activeContract->occupant?->name ?? 'Tenant' }}</div>
                          <div class="text-body-secondary">Active Contract</div>
                        </div>
                      </div>
                      <div class="d-flex justify-content-between text-body-secondary">
                        <span>Start</span>
                        <span>{{ optional($activeContract->start_date)->format('Y-m-d') }}</span>
                      </div>
                      <div class="d-flex justify-content-between text-body-secondary">
                        <span>End</span>
                        <span>{{ optional($activeContract->end_date)->format('Y-m-d') ?? '-' }}</span>
                      </div>
                      <div class="d-flex justify-content-between text-body-secondary">
                        <span>Rent</span>
                        <span>${{ number_format(($activeContract->monthly_rent_cents ?? 0) / 100, 2) }}</span>
                      </div>
                    @else
                      <div class="text-body-secondary">No active contract. Room is available.</div>
                    @endif
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="tab-pane fade" id="room-utilities" role="tabpanel">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
              <div>
                <h5 class="mb-1">Utility History</h5>
                <p class="text-body-secondary mb-0">Meters are set up automatically for this room. Add readings below to track usage.</p>
              </div>
              <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addReadingModal">
                <i class="icon-base ti tabler-plus me-1"></i>Add Reading
              </button>
            </div>

            <div class="row g-4">
              @forelse ($roomMeters as $meter)
                <div class="col-md-6">
                  <div class="border rounded p-4 h-100">
                    <div class="d-flex justify-content-between align-items-start">
                      <div>
                        <div class="text-body-secondary text-uppercase fs-12">{{ $meter->utilityType?->name ?? 'Utility' }}</div>
                        <div class="fw-semibold">{{ $meter->meter_code }}</div>
                      </div>
                      @php
                        $utilityCode = strtolower($meter->utilityType?->code ?? '');
                        $utilityIcon = $utilityCode === 'water' ? 'tabler-droplet' : 'tabler-bolt';
                      @endphp
                      <span class="text-body-secondary">
                        <i class="icon-base ti {{ $utilityIcon }}"></i>
                      </span>
                    </div>
                    @php
                      $stats = $meterStats[$meter->id] ?? null;
                      $latestReading = $stats['latest'] ?? null;
                    @endphp
                    <div class="mt-3">
                      <div class="d-flex justify-content-between text-body-secondary">
                        <span>Last usage</span>
                        <span>
                          {{ $latestReading?->usage_value !== null ? number_format($latestReading->usage_value, 2) : '-' }}
                          {{ $meter->unit_of_measure ?? '' }}
                        </span>
                      </div>
                      <div class="d-flex justify-content-between text-body-secondary">
                        <span>Last reading</span>
                        <span>
                          {{ $latestReading?->reading_value !== null ? number_format($latestReading->reading_value, 2) : '-' }}
                          {{ $meter->unit_of_measure ?? '' }}
                        </span>
                      </div>
                      <div class="d-flex justify-content-between text-body-secondary">
                        <span>Last reading date</span>
                        <span>{{ $latestReading?->reading_at?->format('Y-m-d') ?? '-' }}</span>
                      </div>
                    </div>
                    <div class="mt-3">
                      <div id="meter-trend-{{ $meter->id }}"
                           class="room-meter-sparkline"
                           data-series='@json(($meterTrendSeries[$meter->id] ?? collect())->values())'
                           style="min-height: 75px;"></div>
                    </div>
                  </div>
                </div>
              @empty
                <div class="col-12">
                  <div class="alert alert-info mb-0">No meters configured for this room yet.</div>
                </div>
              @endforelse
            </div>

            <div class="card mt-6">
              <div class="card-header">
                <h5 class="mb-0">Room Utility History</h5>
              </div>
              <div class="card-datatable table-responsive">
                <table class="table datatables-room-readings">
                  <thead>
                    <tr>
                      <th>Tenant</th>
                      <th>Utility</th>
                      <th>Date</th>
                      <th>Reading</th>
                      <th>Usage</th>
                      <th>Notes</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach ($roomReadings as $reading)
                      <tr>
                        <td>{{ $activeContract?->occupant?->name ?? '-' }}</td>
                        <td>{{ $reading->meter?->utilityType?->name ?? '-' }}</td>
                        <td>{{ optional($reading->reading_at)->format('Y-m-d') }}</td>
                        <td>{{ $reading->reading_value }} {{ $reading->meter?->unit_of_measure }}</td>
                        <td>
                          @php
                            $usageValue = $reading->usage_value;
                            $usageClass = $usageValue !== null && $usageValue < 0 ? 'text-danger' : '';
                          @endphp
                          <span class="{{ $usageClass }}">
                            {{ $usageValue !== null ? number_format($usageValue, 2) : '-' }}
                            {{ $reading->meter?->unit_of_measure }}
                          </span>
                        </td>
                        <td>{{ $reading->notes ?: '-' }}</td>
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
        </div>
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
              @foreach ($roomMeters as $meter)
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
            <input type="text" id="readingAt" name="reading_at" class="form-control flatpickr" placeholder="Month DD, YYYY" required />
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
              @foreach ($roomMeters as $meter)
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
            <input type="text" id="editReadingAt" name="reading_at" class="form-control flatpickr" placeholder="Month DD, YYYY" required />
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
          const modal = el.closest('.modal');
          if (el._flatpickr) {
            el._flatpickr.destroy();
          }
          flatpickr(el, {
            altInput: true,
            altFormat: 'F j, Y',
            dateFormat: 'Y-m-d',
            disableMobile: true,
            static: !modal,
            altInputClass: 'form-control',
            appendTo: modal || document.body
          });
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

      const table = document.querySelector('.datatables-room-readings');
      if (table && window.DataTable) {
        new DataTable(table, {
          order: [[2, 'desc']],
          columnDefs: [
            {
              targets: -1,
              orderable: false,
              searchable: false
            }
          ],
          layout: window.RoomGateDataTables?.layout,
          language: Object.assign({}, window.RoomGateDataTables?.language || {}, {
            emptyTable: 'No utility history yet.'
          })
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

      const tabButtons = document.querySelectorAll('[data-bs-toggle="tab"]');
      const storageKey = `room-detail-tab:${@json($room->id)}`;
      if (tabButtons.length) {
        const savedTab = localStorage.getItem(storageKey);
        if (savedTab) {
          const trigger = document.querySelector(`button[data-bs-target="${savedTab}"]`);
          if (trigger && window.bootstrap?.Tab) {
            const tab = new bootstrap.Tab(trigger);
            tab.show();
          }
        }

        tabButtons.forEach((button) => {
          button.addEventListener('shown.bs.tab', (event) => {
            const target = event.target.getAttribute('data-bs-target');
            if (target) {
              localStorage.setItem(storageKey, target);
            }
          });
        });
      }

      document.querySelectorAll('.room-meter-sparkline').forEach((el) => {
        if (!window.ApexCharts) {
          return;
        }
        const series = JSON.parse(el.getAttribute('data-series') || '[]');
        const options = {
          chart: {
            type: 'area',
            height: 75,
            sparkline: { enabled: true }
          },
          stroke: { curve: 'smooth', width: 2 },
          fill: {
            type: 'gradient',
            gradient: {
              shadeIntensity: 0.4,
              opacityFrom: 0.4,
              opacityTo: 0.05,
              stops: [0, 100]
            }
          },
          series: [{ data: series }],
          colors: ['#28c76f'],
          tooltip: { enabled: false },
          grid: { show: false },
          xaxis: { labels: { show: false }, axisBorder: { show: false }, axisTicks: { show: false } },
          yaxis: { labels: { show: false } }
        };
        const chart = new ApexCharts(el, options);
        chart.render();
      });
    });
  </script>
@endpush

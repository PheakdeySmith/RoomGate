@extends('admin::components.layouts.master')

@section('title', 'IoT Control')

@section('content')
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-6">
      <div>
        <h4 class="mb-1">Hey, {{ auth()->user()->name ?? 'Admin' }}!</h4>
        <p class="text-body-secondary mb-0">Connect to your ESP32 device and control your smart home.</p>
      </div>
      <div class="d-flex flex-wrap align-items-center gap-2">
        <div class="input-group">
          <input type="text" class="form-control" id="iotIp" placeholder="192.168.1.50">
          <button type="button" class="btn btn-primary" id="iotCheck">Verify</button>
        </div>
        <span class="badge bg-label-secondary" id="iotStatusBadge">Not connected</span>
      </div>
    </div>

    <div class="row g-6">
      <div class="col-xl-8">
        <div class="card mb-6">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-4">
              <div class="d-flex align-items-center gap-3">
                <span class="avatar avatar-md bg-label-primary d-flex align-items-center justify-content-center">
                  <i class="icon-base ti tabler-snowflake icon-md"></i>
                </span>
                <div>
                  <h5 class="mb-1">Light</h5>
                  <p class="text-body-secondary mb-0">Living Room</p>
                </div>
              </div>
              <div class="form-check form-switch m-0">
                <input class="form-check-input" type="checkbox" id="iotLedToggle" disabled>
              </div>
            </div>

            <div class="row g-4 align-items-center mt-4">
              <div class="col-md-5">
                <div id="iotTempGauge" style="min-height: 220px;"></div>
              </div>
              <div class="col-md-7">
                <div class="row g-4">
                  <div class="col-6">
                    <div class="card border shadow-none bg-label-secondary h-100">
                      <div class="card-body">
                        <p class="mb-1 text-body-secondary">Temperature</p>
                        <h3 class="mb-1">25°C</h3>
                        <p class="mb-0 text-body-secondary">Comfort level</p>
                      </div>
                    </div>
                  </div>
                  <div class="col-6">
                    <div class="card border shadow-none bg-label-secondary h-100">
                      <div class="card-body">
                        <p class="mb-1 text-body-secondary">Humidity</p>
                        <h3 class="mb-1">30%</h3>
                        <p class="mb-0 text-body-secondary">Stable</p>
                      </div>
                    </div>
                  </div>
                  <div class="col-6">
                    <div class="card border shadow-none bg-label-secondary h-100">
                      <div class="card-body">
                        <p class="mb-1 text-body-secondary">Fan Speed</p>
                        <h3 class="mb-1">Medium</h3>
                        <p class="mb-0 text-body-secondary">Auto mode</p>
                      </div>
                    </div>
                  </div>
                  <div class="col-6">
                    <div class="card border shadow-none bg-label-secondary h-100">
                      <div class="card-body">
                        <p class="mb-1 text-body-secondary">Energy</p>
                        <h3 class="mb-1">1.8 kWh</h3>
                        <p class="mb-0 text-body-secondary">Today</p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card-header">
            <h5 class="mb-1">Spending</h5>
            <p class="text-body-secondary mb-0">Weekly usage overview</p>
          </div>
          <div class="card-body">
            <div id="iotUsageChart" style="min-height: 260px;"></div>
          </div>
        </div>
      </div>

      <div class="col-xl-4">
        <div class="card mb-6">
          <div class="card-header d-flex justify-content-between align-items-center">
            <div>
              <h5 class="mb-0">Rooms</h5>
              <small class="text-body-secondary">Quick toggles</small>
            </div>
            <button type="button" class="btn btn-text-secondary rounded-pill p-2 waves-effect">
              <i class="icon-base ti tabler-dots-vertical icon-md text-body-secondary"></i>
            </button>
          </div>
          <div class="card-body">
            <ul class="nav nav-pills nav-fill mb-4" role="tablist">
              <li class="nav-item" role="presentation">
                <button class="nav-link active" data-bs-toggle="pill" type="button" role="tab">Living Room</button>
              </li>
              <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="pill" type="button" role="tab">Kitchen</button>
              </li>
              <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="pill" type="button" role="tab">Bedroom</button>
              </li>
            </ul>

            <div class="d-flex align-items-center justify-content-between mb-4">
              <div class="d-flex align-items-center gap-3">
                <span class="avatar avatar-sm bg-label-primary d-flex align-items-center justify-content-center">
                  <i class="icon-base ti tabler-snowflake icon-sm"></i>
                </span>
                <span class="fw-medium">Light</span>
              </div>
              <div class="form-check form-switch m-0">
                <input class="form-check-input" type="checkbox" id="iotDeviceToggle" disabled>
              </div>
            </div>
            <div class="d-flex align-items-center justify-content-between mb-4">
              <div class="d-flex align-items-center gap-3">
                <span class="avatar avatar-sm bg-label-info d-flex align-items-center justify-content-center">
                  <i class="icon-base ti tabler-fan icon-sm"></i>
                </span>
                <span class="fw-medium">Fan</span>
              </div>
              <div class="form-check form-switch m-0">
                <input class="form-check-input" type="checkbox" disabled>
              </div>
            </div>
            <div class="d-flex align-items-center justify-content-between mb-4">
              <div class="d-flex align-items-center gap-3">
                <span class="avatar avatar-sm bg-label-warning d-flex align-items-center justify-content-center">
                  <i class="icon-base ti tabler-speakerphone icon-sm"></i>
                </span>
                <span class="fw-medium">Volume</span>
              </div>
              <div class="form-check form-switch m-0">
                <input class="form-check-input" type="checkbox" disabled>
              </div>
            </div>
            <div class="d-flex align-items-center justify-content-between mb-4">
              <div class="d-flex align-items-center gap-3">
                <span class="avatar avatar-sm bg-label-secondary d-flex align-items-center justify-content-center">
                  <i class="icon-base ti tabler-wifi icon-sm"></i>
                </span>
                <span class="fw-medium">Wi-Fi</span>
              </div>
              <div class="form-check form-switch m-0">
                <input class="form-check-input" type="checkbox" disabled>
              </div>
            </div>

            <div class="card bg-label-primary shadow-none mb-0">
              <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                  <div>
                    <h6 class="mb-1">Sunny Weather</h6>
                    <p class="mb-0 text-body-secondary">Outdoor</p>
                  </div>
                  <h4 class="mb-0">+22°C</h4>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="card border shadow-none bg-label-secondary">
          <div class="card-body">
            <h6 class="mb-2">Latest Response</h6>
            <p class="mb-2 text-body-secondary" id="iotStatusText">Enter an IP and verify to continue.</p>
            <pre class="mb-0 text-body-secondary" id="iotResponse">No data yet.</pre>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection

@push('page-scripts')
  <script>
    (function () {
      const ipInput = document.getElementById('iotIp');
      const btnCheck = document.getElementById('iotCheck');
      const statusBadge = document.getElementById('iotStatusBadge');
      const statusText = document.getElementById('iotStatusText');
      const responseBox = document.getElementById('iotResponse');
      const ledToggle = document.getElementById('iotLedToggle');
      const deviceToggle = document.getElementById('iotDeviceToggle');

      const setConnected = (connected, message) => {
        statusBadge.classList.toggle('bg-label-success', connected);
        statusBadge.classList.toggle('bg-label-secondary', !connected);
        statusBadge.textContent = connected ? 'Connected' : 'Offline';
        statusText.textContent = message || (connected ? 'Device reachable.' : 'Device offline.');
        ledToggle.disabled = !connected;
        deviceToggle.disabled = !connected;
      };

      const setResponse = (payload) => {
        responseBox.textContent = JSON.stringify(payload, null, 2);
      };

      const getBaseUrl = () => {
        const raw = ipInput.value.trim();
        if (!raw) return null;
        return raw.replace(/^https?:\/\//, '').replace(/\/+$/, '');
      };

      const requestJson = async (url, options = {}) => {
        const res = await fetch(url, options);
        let data;
        try {
          data = await res.json();
        } catch (err) {
          data = { error: 'Invalid JSON response' };
        }
        if (!res.ok) {
          throw new Error(data?.error || 'Request failed');
        }
        return data;
      };

      const verifyDevice = async () => {
        const base = getBaseUrl();
        if (!base) {
          setConnected(false, 'Device offline. Enter the ESP32 IP address.');
          window.RoomGateNotyf?.error('Please enter the ESP32 IP address.');
          return;
        }

        try {
          const data = await requestJson(`{{ route('admin.iot.status') }}?ip=${encodeURIComponent(base)}`);
          setConnected(true, 'Device reachable.');
          setResponse(data);
          const isOn = String(data.led) === '1';
          ledToggle.checked = isOn;
          deviceToggle.checked = isOn;
          window.RoomGateNotyf?.success('ESP32 connected.');
        } catch (err) {
          setConnected(false, 'Device offline. Unable to reach device.');
          setResponse({ error: err.message || 'Failed to connect.' });
          window.RoomGateNotyf?.error(err.message || 'Failed to connect.');
        }
      };

      btnCheck.addEventListener('click', verifyDevice);

      const defaultIp = @json($appSettings->iot_device_ip ?? '');
      if (defaultIp) {
        ipInput.value = defaultIp;
        verifyDevice();
      }

      const setLed = async (state) => {
        const base = getBaseUrl();
        if (!base) return;
        try {
          const data = await requestJson(`{{ route('admin.iot.led') }}`, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ ip: base, state })
          });
          setResponse(data);
          const isOn = String(data.led) === '1';
          ledToggle.checked = isOn;
          deviceToggle.checked = isOn;
          window.RoomGateNotyf?.success(isOn ? 'LED turned on.' : 'LED turned off.');
        } catch (err) {
          setResponse({ error: err.message || 'Failed to update LED.' });
          window.RoomGateNotyf?.error(err.message || 'Failed to update LED.');
        }
      };

      ledToggle.addEventListener('change', () => {
        setLed(ledToggle.checked ? '1' : '0');
      });

      deviceToggle.addEventListener('change', () => {
        ledToggle.checked = deviceToggle.checked;
        setLed(deviceToggle.checked ? '1' : '0');
      });

      if (window.ApexCharts) {
        const gauge = new ApexCharts(document.querySelector('#iotTempGauge'), {
          chart: {
            height: 220,
            type: 'radialBar',
            sparkline: { enabled: true }
          },
          series: [72],
          labels: ['Temp'],
          plotOptions: {
            radialBar: {
              hollow: { size: '60%' },
              dataLabels: {
                name: { show: false },
                value: {
                  fontSize: '22px',
                  fontWeight: 600,
                  offsetY: 8,
                  formatter: () => '21°C'
                }
              }
            }
          },
          colors: ['#7367f0']
        });
        gauge.render();

        const usage = new ApexCharts(document.querySelector('#iotUsageChart'), {
          chart: {
            height: 260,
            type: 'bar',
            toolbar: { show: false }
          },
          series: [
            { name: 'Power', data: [42, 35, 50, 45, 60, 55, 70] }
          ],
          plotOptions: {
            bar: { columnWidth: '45%', borderRadius: 6 }
          },
          dataLabels: { enabled: false },
          colors: ['#7367f0'],
          xaxis: {
            categories: ['Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa', 'Su']
          },
          grid: {
            strokeDashArray: 6
          }
        });
        usage.render();
      }
    })();
  </script>
@endpush

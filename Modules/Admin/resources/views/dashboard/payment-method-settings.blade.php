@extends('admin::components.layouts.master')
@section('title', 'Payment Method Settings | ' . ($appSettings->app_name ?? 'RoomGate') . ' Admin')
@section('page-title', 'Payment Method Settings')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="row g-6">
    <div class="col-lg-4">
      <form method="POST" action="{{ route('admin.payment-method-settings.active') }}">
        @csrf
        <div class="card">
          <h5 class="card-header">Select Active Gateways</h5>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table">
                <tbody>
                @foreach($gateways as $gateway)
                  <tr>
                    <td class="py-2">
                      <div class="form-check mb-0">
                        <input
                          class="form-check-input"
                          type="checkbox"
                          id="gateway_{{ $gateway->id }}"
                          name="gateways[]"
                          value="{{ $gateway->id }}"
                          {{ $gateway->is_active ? 'checked' : '' }}>
                        <label class="form-check-label text-capitalize" for="gateway_{{ $gateway->id }}">
                          {{ $gateway->gateway_name }}
                        </label>
                      </div>
                    </td>
                    <td class="text-end py-2">
                      @if($gateway->is_active)
                        <span class="badge bg-label-success">Active</span>
                      @else
                        <span class="badge bg-label-secondary">Inactive</span>
                      @endif
                      @if($gateway->health_status)
                        @php
                          $healthClass = $gateway->health_status === 'ok'
                            ? 'bg-label-success'
                            : ($gateway->health_status === 'warning' ? 'bg-label-warning' : 'bg-label-danger');
                        @endphp
                        <span class="badge {{ $healthClass }} ms-1 text-uppercase">{{ $gateway->health_status }}</span>
                      @endif
                    </td>
                  </tr>
                @endforeach
                </tbody>
              </table>
            </div>
            @error('gateways')
              <div class="text-danger small mt-2">{{ $message }}</div>
            @enderror
            <div class="mt-4">
              <button type="submit" class="btn btn-primary w-100">Update Activation</button>
            </div>
          </div>
        </div>
      </form>
    </div>

    <div class="col-lg-8">
      <div class="card">
        <h5 class="card-header">Gateway Settings</h5>
        <div class="card-body">
          <ul class="nav nav-tabs mb-4" role="tablist">
            @foreach($gateways as $gateway)
              <li class="nav-item" role="presentation">
                <button
                  class="nav-link text-capitalize {{ $loop->first ? 'active' : '' }}"
                  data-bs-toggle="tab"
                  data-bs-target="#gateway-tab-{{ $gateway->id }}"
                  type="button"
                  role="tab">
                  {{ $gateway->gateway_name }}
                </button>
              </li>
            @endforeach
          </ul>

          <div class="tab-content">
            @foreach($gateways as $gateway)
              <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="gateway-tab-{{ $gateway->id }}" role="tabpanel">
                <form method="POST" action="{{ route('admin.payment-method-settings.update', $gateway->id) }}">
                  @csrf
                  @method('PUT')
                  <input type="hidden" name="gateway_name" value="{{ $gateway->gateway_name }}">

                  <div class="row g-4">
                    @if($gateway->health_message)
                      @php
                        $healthAlert = $gateway->health_status === 'ok'
                          ? 'alert-success'
                          : ($gateway->health_status === 'warning' ? 'alert-warning' : 'alert-danger');
                      @endphp
                      <div class="col-12">
                        <div class="alert {{ $healthAlert }} mb-0">
                          <div class="fw-semibold">Last Health Check</div>
                          <div>{{ $gateway->health_message }}</div>
                          @if($gateway->health_checked_at)
                            <small class="text-body-secondary">Checked at {{ $gateway->health_checked_at->format('Y-m-d H:i:s') }}</small>
                          @endif
                        </div>
                      </div>
                    @endif
                    <div class="col-md-6">
                      <label class="form-label">Gateway Name</label>
                      <input type="text" class="form-control text-capitalize" value="{{ $gateway->gateway_name }}" readonly>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Mode</label>
                      <select class="form-select @error('gateway_mode') is-invalid @enderror" name="gateway_mode">
                        <option value="sandbox" {{ old('gateway_mode', $gateway->gateway_mode) === 'sandbox' ? 'selected' : '' }}>Sandbox</option>
                        <option value="live" {{ old('gateway_mode', $gateway->gateway_mode) === 'live' ? 'selected' : '' }}>Live</option>
                      </select>
                    </div>

                    @if($gateway->gateway_name === 'paypal')
                      <div class="col-md-6">
                        <label class="form-label">Username</label>
                        <input type="text" name="gateway_username" class="form-control" value="{{ old('gateway_username', $gateway->gateway_username) }}">
                      </div>
                      <div class="col-md-6">
                        <label class="form-label">Password</label>
                        <input type="text" name="gateway_password" class="form-control" value="{{ old('gateway_password', $gateway->gateway_password) }}">
                      </div>
                      <div class="col-md-6">
                        <label class="form-label">Signature</label>
                        <input type="text" name="gateway_signature" class="form-control" value="{{ old('gateway_signature', $gateway->gateway_signature) }}">
                      </div>
                      <div class="col-md-6">
                        <label class="form-label">Client ID</label>
                        <input type="text" name="gateway_client_id" class="form-control" value="{{ old('gateway_client_id', $gateway->gateway_client_id) }}">
                      </div>
                      <div class="col-md-12">
                        <label class="form-label">Secret Key</label>
                        <input type="text" name="gateway_secret_key" class="form-control" value="{{ old('gateway_secret_key', $gateway->gateway_secret_key) }}">
                      </div>
                      <div class="col-md-12">
                        <label class="form-label">Webhook ID</label>
                        <input type="text" name="webhook_secret" class="form-control" value="{{ old('webhook_secret', $gateway->webhook_secret) }}">
                      </div>
                    @elseif($gateway->gateway_name === 'stripe')
                      <div class="col-md-6">
                        <label class="form-label">Secret Key</label>
                        <input type="text" name="gateway_secret_key" class="form-control" value="{{ old('gateway_secret_key', $gateway->gateway_secret_key) }}">
                      </div>
                      <div class="col-md-6">
                        <label class="form-label">Publisher Key</label>
                        <input type="text" name="gateway_publisher_key" class="form-control" value="{{ old('gateway_publisher_key', $gateway->gateway_publisher_key) }}">
                      </div>
                      <div class="col-md-12">
                        <label class="form-label">Webhook Secret</label>
                        <input type="text" name="webhook_secret" class="form-control" value="{{ old('webhook_secret', $gateway->webhook_secret) }}">
                      </div>
                    @elseif($gateway->gateway_name === 'bakong')
                      <div class="col-md-6">
                        <label class="form-label">Merchant ID</label>
                        <input type="text" name="merchant_id" class="form-control" value="{{ old('merchant_id', $gateway->merchant_id) }}">
                      </div>
                      <div class="col-md-6">
                        <label class="form-label">Client ID</label>
                        <input type="text" name="gateway_client_id" class="form-control" value="{{ old('gateway_client_id', $gateway->gateway_client_id) }}">
                      </div>
                      <div class="col-md-6">
                        <label class="form-label">Secret Key</label>
                        <input type="text" name="gateway_secret_key" class="form-control" value="{{ old('gateway_secret_key', $gateway->gateway_secret_key) }}">
                      </div>
                      <div class="col-md-6">
                        <label class="form-label">Webhook Secret</label>
                        <input type="text" name="webhook_secret" class="form-control" value="{{ old('webhook_secret', $gateway->webhook_secret) }}">
                      </div>
                    @endif

                    <div class="col-md-12">
                      <div class="form-check">
                        <input
                          class="form-check-input service-charge-toggle"
                          data-target="service-charge-box-{{ $gateway->id }}"
                          type="checkbox"
                          id="service_charge_{{ $gateway->id }}"
                          name="service_charge"
                          value="1"
                          {{ old('service_charge', $gateway->service_charge) ? 'checked' : '' }}>
                        <label class="form-check-label" for="service_charge_{{ $gateway->id }}">
                          Enable Service Charge
                        </label>
                      </div>
                    </div>

                    <div id="service-charge-box-{{ $gateway->id }}" class="{{ old('service_charge', $gateway->service_charge) ? '' : 'd-none' }}">
                      <div class="row g-4">
                        <div class="col-md-6">
                          <label class="form-label d-block">Charge Type</label>
                          <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="charge_type" id="charge_type_percent_{{ $gateway->id }}" value="P" {{ old('charge_type', $gateway->charge_type) === 'P' ? 'checked' : '' }}>
                            <label class="form-check-label" for="charge_type_percent_{{ $gateway->id }}">Percentage</label>
                          </div>
                          <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="charge_type" id="charge_type_flat_{{ $gateway->id }}" value="F" {{ old('charge_type', $gateway->charge_type) === 'F' ? 'checked' : '' }}>
                            <label class="form-check-label" for="charge_type_flat_{{ $gateway->id }}">Flat</label>
                          </div>
                        </div>
                        <div class="col-md-6">
                          <label class="form-label">Charge Value</label>
                          <input type="number" step="0.01" min="0" name="charge" class="form-control" value="{{ old('charge', $gateway->charge) }}">
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Update {{ ucfirst($gateway->gateway_name) }}</button>
                </form>
                <form method="POST" action="{{ route('admin.payment-method-settings.health-check', $gateway->id) }}">
                  @csrf
                  <button type="submit" class="btn btn-label-secondary">Run Health Check</button>
                </form>
                  </div>
              </div>
            @endforeach
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('page-scripts')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.service-charge-toggle').forEach(function (el) {
      el.addEventListener('change', function () {
        const targetId = this.getAttribute('data-target');
        const box = document.getElementById(targetId);
        if (!box) {
          return;
        }
        if (this.checked) {
          box.classList.remove('d-none');
        } else {
          box.classList.add('d-none');
        }
      });
    });
  });
</script>
@endpush

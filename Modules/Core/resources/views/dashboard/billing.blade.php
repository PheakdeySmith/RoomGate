@extends('core::components.layouts.master')
@section('title', 'Billing & Plans | RoomGate')
@section('page-title', 'Billing & Plans')

@push('page-styles')
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/datatables-bs5/datatables.bootstrap5.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css" />
@endpush

@section('content')
  <div class="container-xxl flex-grow-1 container-p-y">
    @php
      $planCards = [
          'Basic' => 'bg-label-primary',
          'Professional' => 'bg-label-success',
          'Enterprise' => 'bg-label-warning',
      ];
      $statusBadges = [
          'paid' => 'bg-label-success',
          'unpaid' => 'bg-label-warning',
          'sent' => 'bg-label-info',
          'void' => 'bg-label-secondary',
      ];
    @endphp

    <div class="row mb-4">
      <div class="col-12">
        <h4 class="mb-1">Plans & Billing</h4>
        <p class="text-body-secondary mb-0">Choose the plan that fits your business today.</p>
      </div>
    </div>

    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
      <div class="text-body-secondary">Billing window</div>
      <label class="switch switch-sm m-0">
        <span class="switch-label fs-6 text-body">Monthly</span>
        <input type="checkbox" class="switch-input price-duration-toggler" />
        <span class="switch-toggle-slider">
          <span class="switch-on"></span>
          <span class="switch-off"></span>
        </span>
        <span class="switch-label fs-6 text-body">Yearly</span>
      </label>
    </div>

    <div class="row g-4 mb-5">
      @foreach ($plans as $plan)
        @php
          $cardTone = $planCards[$plan->name] ?? 'bg-label-primary';
          $isCurrent = $subscription && $subscription->plan_id === $plan->id && in_array($subscription->status, ['active', 'trialing', 'past_due'], true);
          $duration = $plan->interval === 'yearly' ? '/ year' : '/ month';
          $priceClass = $plan->interval === 'yearly' ? 'price-yearly d-none' : 'price-monthly';
        @endphp
        <div class="col-xl-4 col-md-6 {{ $priceClass }}">
          <div class="card h-100 border-0 shadow-sm {{ $cardTone }}">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                  <h5 class="mb-1">{{ $plan->name }}</h5>
                  <small class="text-body-secondary text-uppercase">{{ $plan->interval }}</small>
                </div>
                @if ($isCurrent)
                  <span class="badge bg-white text-heading">Current</span>
                @endif
              </div>
              <div class="d-flex align-items-end gap-2 mb-4">
                <h2 class="mb-0">${{ number_format($plan->price_cents / 100, 0) }}</h2>
                <span class="text-body-secondary">{{ $duration }}</span>
              </div>
              <ul class="list-unstyled mb-4 text-body-secondary">
                <li class="mb-2"><i class="icon-base ti tabler-check me-2"></i>Tenant management</li>
                <li class="mb-2"><i class="icon-base ti tabler-check me-2"></i>Contracts & invoices</li>
                <li class="mb-2"><i class="icon-base ti tabler-check me-2"></i>Maintenance & utilities</li>
              </ul>
              <form method="POST" action="{{ route('core.billing.change-plan', ['tenant' => request()->route('tenant')]) }}">
                @csrf
                <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                <button class="btn btn-primary w-100" type="submit" {{ $isCurrent ? 'disabled' : '' }}>
                  {{ $isCurrent ? 'Current Plan' : 'Select Plan' }}
                </button>
              </form>
            </div>
          </div>
        </div>
      @endforeach
    </div>

    <div class="row">
      <div class="col-12 mb-4">
        <div class="card">
          <div class="card-header">
            <div class="d-flex justify-content-between align-items-center w-100">
              <h5 class="card-title mb-0">Purchase History</h5>
              <a class="btn btn-sm btn-label-secondary" href="{{ route('core.billing.invoices.export', ['tenant' => request()->route('tenant')]) }}">
                Export CSV
              </a>
            </div>
          </div>
          <div class="card-datatable table-responsive">
            <table class="datatables-sub-invoices table border-top">
              <thead>
                <tr>
                  <th></th>
                  <th>Invoice</th>
                  <th>Plan</th>
                  <th>Amount</th>
                  <th>Date</th>
                  <th>Amount</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($invoices as $invoice)
                  <tr>
                    <td></td>
                    <td>{{ $invoice->invoice_number }}</td>
                    <td>{{ $subscription?->plan?->name ?? 'Plan' }}</td>
                    <td>${{ number_format($invoice->amount_cents / 100, 2) }}</td>
                    <td>{{ $invoice->billing_period_start }}</td>
                    <td>${{ number_format($invoice->amount_cents / 100, 2) }}</td>
                    <td>
                      <span class="badge {{ $statusBadges[$invoice->status] ?? 'bg-label-secondary' }}">
                        {{ ucfirst($invoice->status) }}
                      </span>
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
@endsection

@push('page-scripts')
  <script src="{{ asset('assets/assets') }}/vendor/libs/datatables-bs5/datatables-bootstrap5.js"></script>
  <script src="{{ asset('assets/assets') }}/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const tables = [
        '.datatables-sub-invoices'
      ];
      tables.forEach(selector => {
        const table = document.querySelector(selector);
        if (table && window.DataTable) {
          new DataTable(table, {
            order: [[1, 'desc']],
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
            layout: window.RoomGateDataTables?.layout,
            language: window.RoomGateDataTables?.language,
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
        }
      });

      const toggler = document.querySelector('.price-duration-toggler');
      if (toggler) {
        const monthly = document.querySelectorAll('.price-monthly');
        const yearly = document.querySelectorAll('.price-yearly');
        const sync = function () {
          const showYearly = toggler.checked;
          monthly.forEach(card => card.classList.toggle('d-none', showYearly));
          yearly.forEach(card => card.classList.toggle('d-none', !showYearly));
        };
        toggler.addEventListener('change', sync);
        sync();
      }
    });
  </script>
@endpush

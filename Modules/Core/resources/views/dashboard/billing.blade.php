@extends('core::components.layouts.master')
@section('title', 'Billing & Plans | RoomGate')
@section('page-title', 'Billing & Plans')

@push('page-styles')
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/datatables-bs5/datatables.bootstrap5.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css" />
@endpush

@section('content')
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="row">
      <div class="col-lg-5 mb-4">
        <div class="card">
          <div class="card-header">
            <h5 class="card-title mb-0">Current Subscription</h5>
          </div>
          <div class="card-body">
            @if ($subscription)
              <div class="d-flex align-items-center justify-content-between mb-3">
                <div>
                  <h6 class="mb-1">{{ $subscription->plan?->name ?? 'Plan' }}</h6>
                  <small class="text-body-secondary">{{ ucfirst($subscription->status) }}</small>
                </div>
                <span class="badge bg-label-primary text-uppercase">{{ $subscription->plan?->interval ?? 'monthly' }}</span>
              </div>
              @if ($subscription->status === 'past_due')
                <div class="alert alert-warning">
                  Your subscription is past due. Please record a payment to restore access.
                </div>
              @endif
              <div class="mb-3">
                <div class="d-flex justify-content-between">
                  <span>Period Start</span>
                  <span>{{ optional($subscription->current_period_start)->format('Y-m-d') }}</span>
                </div>
                <div class="d-flex justify-content-between">
                  <span>Period End</span>
                  <span>{{ optional($subscription->current_period_end)->format('Y-m-d') }}</span>
                </div>
              </div>
              <form method="POST" action="{{ route('core.billing.cancel', ['tenant' => request()->route('tenant')]) }}" data-confirm="Cancel this subscription?">
                @csrf
                <button class="btn btn-label-danger w-100" type="submit">Cancel Subscription</button>
              </form>
            @else
              <p class="text-body-secondary mb-0">No active subscription yet.</p>
            @endif
          </div>
        </div>
      </div>

      <div class="col-lg-7 mb-4">
        <div class="card">
          <div class="card-header">
            <h5 class="card-title mb-0">Change Plan</h5>
          </div>
          <div class="card-body">
            <form method="POST" action="{{ route('core.billing.change-plan', ['tenant' => request()->route('tenant')]) }}">
              @csrf
              <div class="row g-3 align-items-end">
                <div class="col-md-8">
                  <label class="form-label" for="plan_id">Plan</label>
                  <select class="form-select" id="plan_id" name="plan_id" required>
                    @foreach ($plans as $plan)
                      <option value="{{ $plan->id }}" @selected($subscription && $subscription->plan_id === $plan->id)>
                        {{ $plan->name }} - ${{ number_format($plan->price_cents / 100, 2) }}/{{ $plan->interval }}
                      </option>
                    @endforeach
                  </select>
                </div>
                <div class="col-md-4">
                  <button class="btn btn-primary w-100" type="submit">Update Plan</button>
                </div>
              </div>
            </form>
            <p class="text-body-secondary mt-3 mb-0">
              Plan changes take effect immediately. Billing is manual until a payment provider is configured.
            </p>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-xl-6 mb-4">
        <div class="card">
          <div class="card-header">
            <div class="d-flex justify-content-between align-items-center w-100">
              <h5 class="card-title mb-0">Subscription Invoices</h5>
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
                  <th>Status</th>
                  <th>Amount</th>
                  <th>Due</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($invoices as $invoice)
                  <tr>
                    <td></td>
                    <td>{{ $invoice->invoice_number }}</td>
                    <td>{{ ucfirst($invoice->status) }}</td>
                    <td>${{ number_format($invoice->amount_cents / 100, 2) }}</td>
                    <td>{{ $invoice->due_date }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="col-xl-6 mb-4">
        <div class="card">
          <div class="card-header">
            <div class="d-flex justify-content-between align-items-center w-100">
              <h5 class="card-title mb-0">Subscription Payments</h5>
              <a class="btn btn-sm btn-label-secondary" href="{{ route('core.billing.payments.export', ['tenant' => request()->route('tenant')]) }}">
                Export CSV
              </a>
            </div>
          </div>
          <div class="card-datatable table-responsive">
            <table class="datatables-sub-payments table border-top">
              <thead>
                <tr>
                  <th></th>
                  <th>Invoice</th>
                  <th>Status</th>
                  <th>Amount</th>
                  <th>Paid</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($payments as $payment)
                  <tr>
                    <td></td>
                    <td>#{{ $payment->subscription_invoice_id }}</td>
                    <td>{{ ucfirst($payment->status) }}</td>
                    <td>${{ number_format($payment->amount_cents / 100, 2) }}</td>
                    <td>{{ optional($payment->paid_at)->format('Y-m-d') ?? '-' }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          <div class="card-body border-top">
            <h6 class="mb-3">Record a Manual Payment</h6>
            <form method="POST" action="{{ route('core.billing.payments.store', ['tenant' => request()->route('tenant')]) }}">
              @csrf
              <div class="row g-3 align-items-end">
                <div class="col-md-5">
                  <label class="form-label">Invoice</label>
                  <select class="form-select" name="subscription_invoice_id" required {{ $invoices->isEmpty() ? 'disabled' : '' }}>
                    @forelse ($invoices as $invoice)
                      <option value="{{ $invoice->id }}">{{ $invoice->invoice_number }}</option>
                    @empty
                      <option value="">No invoices</option>
                    @endforelse
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Amount (cents)</label>
                  <input type="number" class="form-control" name="amount_cents" min="1" required {{ $invoices->isEmpty() ? 'disabled' : '' }} />
                </div>
                <div class="col-md-3">
                  <button class="btn btn-primary w-100" type="submit" {{ $invoices->isEmpty() ? 'disabled' : '' }}>Submit</button>
                </div>
                <div class="col-12">
                  <label class="form-label">Reference</label>
                  <input type="text" class="form-control" name="provider_ref" placeholder="Receipt or transfer ref" {{ $invoices->isEmpty() ? 'disabled' : '' }} />
                </div>
              </div>
            </form>
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
        '.datatables-sub-invoices',
        '.datatables-sub-payments'
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
    });
  </script>
@endpush

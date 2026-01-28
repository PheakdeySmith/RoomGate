@extends('admin::components.layouts.master')
@section('title', 'Tenant Billing | RoomGate Admin')

@push('page-styles')
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/datatables-bs5/datatables.bootstrap5.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/animate-css/animate.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/css/pages/page-user-view.css" />
@endpush

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="row">
    @include('admin::dashboard.tenant-view.partials.sidebar')

    <div class="col-xl-8 col-lg-7 order-0 order-md-1" data-ajax-container="user-view">
      @include('admin::dashboard.tenant-view.partials.tabs')

      <div class="card mb-6">
        <h5 class="card-header">Subscription Invoices</h5>
        <div class="card-datatable table-responsive">
          <table class="table datatables-tenant-invoices">
            <thead>
              <tr>
                <th></th>
                <th>Invoice #</th>
                <th>Status</th>
                <th>Amount</th>
                <th>Due</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($subscriptionInvoices as $invoice)
                <tr>
                  <td></td>
                  <td>{{ $invoice->invoice_number }}</td>
                  <td class="text-capitalize">{{ $invoice->status }}</td>
                  <td>{{ number_format($invoice->amount_cents / 100, 2) }} {{ $invoice->currency_code }}</td>
                  <td>{{ $invoice->due_date?->toDateString() ?? '—' }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>

      <div class="card mb-4">
        <h5 class="card-header">Subscription Payments</h5>
        <div class="card-datatable table-responsive">
          <table class="table datatables-tenant-payments">
            <thead>
              <tr>
                <th></th>
                <th>Amount</th>
                <th>Status</th>
                <th>Provider</th>
                <th>Paid At</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($subscriptionPayments as $payment)
                <tr>
                  <td></td>
                  <td>{{ number_format($payment->amount_cents / 100, 2) }} {{ $payment->currency_code }}</td>
                  <td class="text-capitalize">{{ $payment->status }}</td>
                  <td>{{ $payment->provider ?? '—' }}</td>
                  <td>{{ $payment->paid_at?->toDateString() ?? '—' }}</td>
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
  <script src="{{ asset('assets/assets') }}/js/roomgate-ajax.js"></script>
  <script src="{{ asset('assets/assets') }}/js/admin-user-view-ajax.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      ['.datatables-tenant-invoices', '.datatables-tenant-payments'].forEach(selector => {
        const table = document.querySelector(selector);
        if (!table || !window.DataTable) {
          return;
        }
        new DataTable(table, RoomGateDataTables.buildOptions({
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
          ]
        }));
      });
    });
  </script>
@endpush

@extends('admin::components.layouts.master')
@section('title', 'Invoice Print | RoomGate Admin')

@push('page-styles')
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/css/pages/app-invoice-print.css" />
@endpush

@section('content')
@php
  $currency = $invoice->currency_code ?? ($invoice->tenant?->default_currency ?? 'USD');
  $total = number_format(($invoice->total_cents ?? 0) / 100, 2);
  $paid = number_format(($invoice->paid_cents ?? 0) / 100, 2);
  $balance = number_format((($invoice->total_cents ?? 0) - ($invoice->paid_cents ?? 0)) / 100, 2);
@endphp
<div class="invoice-print p-12">
  <div class="d-flex justify-content-between flex-row">
    <div class="mb-6">
      <div class="d-flex svg-illustration mb-6 gap-2 align-items-center">
        <span class="app-brand-logo demo">
          @if ($appSettings->logo_light_path || $appSettings->logo_dark_path)
            <img
              src="{{ asset($appSettings->logo_light_path ?: $appSettings->logo_dark_path) }}"
              alt="{{ $appSettings->app_short_name ?? $appSettings->app_name ?? 'RoomGate' }}"
              class="img-fluid"
              style="height: 26px;">
          @else
            <span class="text-primary fw-bold">{{ $appSettings->app_short_name ?? $appSettings->app_name ?? 'RoomGate' }}</span>
          @endif
        </span>
        <span class="app-brand-text fw-bold fs-4 ms-50">{{ $appSettings->app_short_name ?? $appSettings->app_name ?? 'RoomGate' }}</span>
      </div>
      <p class="mb-1">{{ $invoice->tenant?->name ?? 'Tenant' }}</p>
      <p class="mb-0">{{ $invoice->tenant?->slug ?? '' }}</p>
    </div>
    <div>
      <h5 class="mb-6">INVOICE #{{ $invoice->invoice_number }}</h5>
      <div class="mb-1">
        <span>Date Issued:</span>
        <span>{{ optional($invoice->issue_date)->format('Y-m-d') }}</span>
      </div>
      <div>
        <span>Date Due:</span>
        <span>{{ optional($invoice->due_date)->format('Y-m-d') }}</span>
      </div>
    </div>
  </div>

  <hr class="mb-6" />

  <div class="row d-flex justify-content-between mb-6">
    <div class="col-sm-6 w-50">
      <h6>Invoice To:</h6>
      <p class="mb-1">{{ $invoice->contract?->occupant?->name ?? '—' }}</p>
      <p class="mb-1">{{ $invoice->contract?->room?->property?->name ?? '—' }}</p>
      <p class="mb-1">Room {{ $invoice->contract?->room?->room_number ?? '—' }}</p>
      <p class="mb-0">{{ $invoice->contract?->occupant?->email ?? '—' }}</p>
    </div>
    <div class="col-sm-6 w-50">
      <h6>Summary:</h6>
      <table>
        <tbody>
          <tr>
            <td class="pe-4">Total Due:</td>
            <td>{{ $currency }} {{ $total }}</td>
          </tr>
          <tr>
            <td class="pe-4">Paid:</td>
            <td>{{ $currency }} {{ $paid }}</td>
          </tr>
          <tr>
            <td class="pe-4">Balance:</td>
            <td>{{ $currency }} {{ $balance }}</td>
          </tr>
          <tr>
            <td class="pe-4">Status:</td>
            <td class="text-capitalize">{{ $invoice->status }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <div class="table-responsive border border-bottom-0 border-top-0 rounded">
    <table class="table m-0">
      <thead>
        <tr>
          <th>Item</th>
          <th>Description</th>
          <th>Amount</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($invoice->items as $item)
          <tr>
            <td>{{ $item->item_type ?? 'Item' }}</td>
            <td>{{ $item->description ?? '-' }}</td>
            <td>{{ $currency }} {{ number_format(($item->amount_cents ?? 0) / 100, 2) }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="3" class="text-center text-body-secondary">No invoice items.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="table-responsive">
    <table class="table m-0 table-borderless">
      <tbody>
        <tr>
          <td class="align-top px-0 py-6">
            <p class="mb-1">
              <span class="me-2 fw-medium">Notes:</span>
              <span>{{ $invoice->notes ?? '—' }}</span>
            </p>
          </td>
          <td class="px-0 py-12 w-px-100">
            <p class="mb-2">Subtotal:</p>
            <p class="mb-2">Discount:</p>
            <p class="mb-2 border-bottom pb-2">Total:</p>
          </td>
          <td class="text-end px-0 py-6 w-px-100">
            <p class="fw-medium mb-2">{{ $currency }} {{ number_format(($invoice->subtotal_cents ?? 0) / 100, 2) }}</p>
            <p class="fw-medium mb-2">{{ $currency }} {{ number_format(($invoice->discount_cents ?? 0) / 100, 2) }}</p>
            <p class="fw-medium mb-0 pt-2">{{ $currency }} {{ $total }}</p>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</div>
@endsection

@push('page-scripts')
  <script src="{{ asset('assets/assets') }}/js/app-invoice-print.js"></script>
@endpush

@extends('core::components.layouts.master')
@section('title', 'Invoice Preview | RoomGate')
@section('page-title', 'Invoice Preview')

@push('page-styles')
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/css/pages/app-invoice.css" />
@endpush

@section('content')
@php
  $currency = $invoice->currency_code ?: ($tenant->default_currency ?? 'USD');
  $occupant = $invoice->contract?->occupant;
  $room = $invoice->contract?->room;
  $property = $room?->property;
@endphp
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="row invoice-preview">
    <div class="col-xl-9 col-md-8 col-12 mb-md-0 mb-6">
      <div class="card invoice-preview-card p-sm-12 p-6">
        <div class="card-body invoice-preview-header rounded">
          <div class="d-flex justify-content-between flex-xl-row flex-md-column flex-sm-row flex-column align-items-xl-center align-items-md-start align-items-sm-center align-items-start">
            <div class="mb-xl-0 mb-6 text-heading">
              <div class="d-flex svg-illustration mb-6 gap-2 align-items-center">
                <span class="app-brand-logo demo">
                  @if ($appSettings->logo_light_path || $appSettings->logo_dark_path || $appSettings->logo_small_path)
                    <img
                      src="{{ $appSettings->logo_light_path ? asset($appSettings->logo_light_path) : ($appSettings->logo_dark_path ? asset($appSettings->logo_dark_path) : asset($appSettings->logo_small_path)) }}"
                      alt="{{ $appSettings->app_name }}"
                      class="img-fluid"
                      style="height: 26px;">
                  @else
                    <span class="text-primary">
                      <svg width="32" height="22" viewBox="0 0 32 22" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M0.00172773 0V6.85398C0.00172773 6.85398 -0.133178 9.01207 1.98092 10.8388L13.6912 21.9964L19.7809 21.9181L18.8042 9.88248L16.4951 7.17289L9.23799 0H0.00172773Z" fill="currentColor" />
                        <path opacity="0.06" fill-rule="evenodd" clip-rule="evenodd" d="M7.69824 16.4364L12.5199 3.23696L16.5541 7.25596L7.69824 16.4364Z" fill="#161616" />
                        <path opacity="0.06" fill-rule="evenodd" clip-rule="evenodd" d="M8.07751 15.9175L13.9419 4.63989L16.5849 7.28475L8.07751 15.9175Z" fill="#161616" />
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M7.77295 16.3566L23.6563 0H32V6.88383C32 6.88383 31.8262 9.17836 30.6591 10.4057L19.7824 22H13.6938L7.77295 16.3566Z" fill="currentColor" />
                      </svg>
                    </span>
                  @endif
                </span>
                <span class="app-brand-text fw-bold fs-4 ms-50">{{ $appSettings->app_short_name ?: ($appSettings->app_name ?: 'RoomGate') }}</span>
              </div>
              <p class="mb-2 text-body-secondary">{{ $tenant->name }}</p>
              <p class="mb-0 text-body-secondary">{{ $currency }} invoice</p>
            </div>
            <div>
              <h5 class="mb-6">Invoice #{{ $invoice->invoice_number }}</h5>
              <div class="mb-1 text-heading">
                <span>Date Issued:</span>
                <span class="fw-medium">{{ optional($invoice->issue_date)->format('Y-m-d') }}</span>
              </div>
              <div class="text-heading">
                <span>Date Due:</span>
                <span class="fw-medium">{{ optional($invoice->due_date)->format('Y-m-d') }}</span>
              </div>
            </div>
          </div>
        </div>

        <div class="card-body px-0">
          <div class="row">
            <div class="col-xl-6 col-md-12 col-sm-5 col-12 mb-xl-0 mb-md-6 mb-sm-0 mb-6">
              <h6>Invoice To:</h6>
              <p class="mb-1">{{ $occupant?->name ?? '-' }}</p>
              <p class="mb-1">{{ $room?->room_number ?? 'Room' }} ({{ $property?->name ?? 'Property' }})</p>
              <p class="mb-0">{{ $occupant?->email ?? '-' }}</p>
            </div>
            <div class="col-xl-6 col-md-12 col-sm-7 col-12">
              <h6>Bill To:</h6>
              <table>
                <tbody>
                  <tr>
                    <td class="pe-4">Total Due:</td>
                    <td class="fw-medium">{{ $currency }} {{ number_format(($invoice->total_cents ?? 0) / 100, 2) }}</td>
                  </tr>
                  <tr>
                    <td class="pe-4">Status:</td>
                    <td>{{ ucfirst($invoice->status) }}</td>
                  </tr>
                  <tr>
                    <td class="pe-4">Currency:</td>
                    <td>{{ $currency }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
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
              @foreach ($invoice->items as $item)
                <tr>
                  <td class="text-nowrap text-heading">{{ ucfirst($item->item_type) }}</td>
                  <td class="text-nowrap">{{ $item->description }}</td>
                  <td>{{ $currency }} {{ number_format(($item->amount_cents ?? 0) / 100, 2) }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>

        <div class="table-responsive">
          <table class="table m-0 table-borderless">
            <tbody>
              <tr>
                <td class="align-top pe-6 ps-0 py-6 text-body">
                  <span class="fw-medium text-heading">Note:</span>
                  <span>{{ $invoice->notes ?: '-' }}</span>
                </td>
                <td class="px-0 py-6 w-px-100">
                  <p class="mb-2">Subtotal:</p>
                  <p class="mb-0">Total:</p>
                </td>
                <td class="text-end px-0 py-6 w-px-100 fw-medium text-heading">
                  <p class="fw-medium mb-2">{{ $currency }} {{ number_format(($invoice->subtotal_cents ?? 0) / 100, 2) }}</p>
                  <p class="fw-medium mb-0">{{ $currency }} {{ number_format(($invoice->total_cents ?? 0) / 100, 2) }}</p>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="col-xl-3 col-md-4 col-12 invoice-actions">
      <div class="card">
        <div class="card-body">
          <a href="{{ route('Core.invoices.index') }}" class="btn btn-label-secondary d-grid w-100 mb-4">Back to List</a>
          <a href="{{ route('Core.invoices.edit', ['tenant' => $tenant->slug, 'invoice' => $invoice]) }}" class="btn btn-label-secondary d-grid w-100 mb-4">Edit</a>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@extends('admin::components.layouts.master')
@section('title', 'Invoice Preview | RoomGate Admin')
@section('page-title', 'Invoice Preview')

@push('page-styles')
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/flatpickr/flatpickr.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/css/pages/app-invoice.css" />
@endpush

@section('content')
@php
  $invoice = $subscriptionInvoice;
  $tenantName = $invoice->tenant?->name ?? 'Unknown Tenant';
  $planName = $invoice->subscription?->plan?->name ?? 'Subscription';
  $amount = number_format($invoice->amount_cents / 100, 2);
  $issuedDate = optional($invoice->created_at)->format('M d, Y');
  $dueDate = optional($invoice->due_date)->format('M d, Y');
  $brandName = $appSettings->app_name ?: 'RoomGate';
  $brandLogo = $appSettings->logo_light_path ? asset($appSettings->logo_light_path) : null;
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
                  @if ($brandLogo)
                    <img src="{{ $brandLogo }}" alt="{{ $brandName }}" style="height: 28px;">
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
                <span class="app-brand-text fw-bold fs-4 ms-50">{{ $brandName }}</span>
              </div>
              <p class="mb-1 fw-medium text-heading">Invoice To</p>
              <p class="mb-2">{{ $tenantName }}</p>
            </div>
            <div>
              <h5 class="mb-6">Invoice #{{ $invoice->invoice_number }}</h5>
              <div class="mb-1 text-heading">
                <span>Date Issued:</span>
                <span class="fw-medium">{{ $issuedDate }}</span>
              </div>
              <div class="text-heading">
                <span>Date Due:</span>
                <span class="fw-medium">{{ $dueDate }}</span>
              </div>
            </div>
          </div>
        </div>
        <div class="card-body px-0">
          <div class="row">
            <div class="col-xl-6 col-md-12 col-sm-5 col-12 mb-xl-0 mb-md-6 mb-sm-0 mb-6"></div>
            <div class="col-xl-6 col-md-12 col-sm-7 col-12"></div>
          </div>
        </div>
        <div class="table-responsive border border-bottom-0 border-top-0 rounded">
          <table class="table m-0">
            <thead>
              <tr>
                        <th>Item</th>
                        <th>Description</th>
                        <th>Qty</th>
                        <th>Price</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td class="text-nowrap text-heading">Subscription</td>
                <td class="text-nowrap">{{ $planName }} plan</td>
                <td>1</td>
                <td>${{ $amount }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="table-responsive">
          <table class="table m-0 table-borderless">
            <tbody>
              <tr>
                <td class="align-top pe-6 ps-0 py-6 text-body">
                  <p class="mb-1">
                    <span class="me-2 h6">Salesperson:</span>
                    <span>RoomGate Billing</span>
                  </p>
                  <span>Thanks for your business</span>
                </td>
                <td class="px-0 py-6 w-px-100">
                  <p class="mb-2">Subtotal:</p>
                  <p class="mb-2">Discount:</p>
                  <p class="mb-2 border-bottom pb-2">Tax:</p>
                  <p class="mb-0">Total:</p>
                </td>
                <td class="text-end px-0 py-6 w-px-100 fw-medium text-heading">
                  <p class="fw-medium mb-2">${{ $amount }}</p>
                  <p class="fw-medium mb-2">$0.00</p>
                  <p class="fw-medium mb-2 border-bottom pb-2">0%</p>
                  <p class="fw-medium mb-0">${{ $amount }}</p>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <hr class="mt-0 mb-6" />
        <div class="card-body p-0">
          <div class="row">
            <div class="col-12">
              <span class="fw-medium text-heading">Note:</span>
              <span>Subscription invoice generated by RoomGate.</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-xl-3 col-md-4 col-12 invoice-actions">
      <div class="card">
        <div class="card-body">
          <button class="btn btn-primary d-grid w-100 mb-4" data-bs-toggle="offcanvas" data-bs-target="#sendInvoiceOffcanvas">
            <span class="d-flex align-items-center justify-content-center text-nowrap"><i class="icon-base ti tabler-send icon-xs me-2"></i>Send Invoice</span>
          </button>
          <button class="btn btn-label-secondary d-grid w-100 mb-4" disabled>Download</button>
          <div class="d-flex mb-4">
            <a class="btn btn-label-secondary d-grid w-100 me-4" href="{{ route('admin.subscription-invoices.index') }}">Back</a>
            <a href="{{ route('admin.subscription-invoices.edit', $invoice) }}" class="btn btn-label-secondary d-grid w-100">Edit</a>
          </div>
          <a class="btn btn-success d-grid w-100" href="{{ route('admin.subscriptions.payments') }}">
            <span class="d-flex align-items-center justify-content-center text-nowrap"><i class="icon-base ti tabler-currency-dollar icon-xs me-2"></i>Add Payment</span>
          </a>
        </div>
      </div>
    </div>
  </div>

  <div class="offcanvas offcanvas-end" id="sendInvoiceOffcanvas" aria-hidden="true">
    <div class="offcanvas-header mb-6 border-bottom">
      <h5 class="offcanvas-title">Send Invoice</h5>
      <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body pt-0 flex-grow-1">
      <form>
        <div class="mb-6">
          <label for="invoice-from" class="form-label">From</label>
          <input type="text" class="form-control" id="invoice-from" value="billing@roomgate.app" placeholder="company@email.com" />
        </div>
        <div class="mb-6">
          <label for="invoice-to" class="form-label">To</label>
          <input type="text" class="form-control" id="invoice-to" value="{{ $tenantName }}" placeholder="tenant@email.com" />
        </div>
        <div class="mb-6">
          <label for="invoice-subject" class="form-label">Subject</label>
          <input type="text" class="form-control" id="invoice-subject" value="RoomGate Subscription Invoice" placeholder="Invoice subject" />
        </div>
        <div class="mb-6">
          <label for="invoice-message" class="form-label">Message</label>
          <textarea class="form-control" id="invoice-message" rows="4">Please find your invoice attached.</textarea>
        </div>
        <div class="mb-6">
          <span class="form-check">
            <input class="form-check-input" type="checkbox" id="invoice-attach" checked />
            <label class="form-check-label" for="invoice-attach">Invoice Attached</label>
          </span>
        </div>
        <button type="button" class="btn btn-primary w-100" disabled>Send</button>
      </form>
    </div>
  </div>
</div>
@endsection

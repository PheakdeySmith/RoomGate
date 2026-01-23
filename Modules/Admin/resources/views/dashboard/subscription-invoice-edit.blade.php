@extends('admin::components.layouts.master')
@section('title', 'Edit Invoice | RoomGate Admin')
@section('page-title', 'Edit Invoice')

@push('page-styles')
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/flatpickr/flatpickr.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/select2/select2.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/css/pages/app-invoice.css" />
@endpush

@section('content')
@php
  $invoice = $subscriptionInvoice;
  $brandName = $appSettings->app_name ?: 'RoomGate';
  $brandLogo = $appSettings->logo_light_path ? asset($appSettings->logo_light_path) : null;
@endphp

<div class="container-xxl flex-grow-1 container-p-y">
  <div class="row invoice-add">
    <div class="col-lg-9 col-12 mb-lg-0 mb-6">
      <form id="invoiceEditForm" method="POST" action="{{ route('admin.subscription-invoices.update', $invoice) }}" class="card invoice-preview-card p-sm-12 p-6">
        @csrf
        @method('PATCH')
        <div class="card-body invoice-preview-header rounded">
          <div class="d-flex flex-wrap flex-column flex-sm-row justify-content-between text-heading">
            <div class="mb-md-0 mb-6">
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
              <p class="mb-2">Subscription billing</p>
              <p class="mb-2">Edit subscription invoice</p>
              <p class="mb-3">Update invoice details</p>
            </div>
            <div class="col-md-5 col-8 pe-0 ps-0 ps-md-2">
              <dl class="row mb-0 gx-4">
                <dt class="col-sm-5 mb-2 d-md-flex align-items-center justify-content-end">
                  <span class="h5 text-capitalize mb-0 text-nowrap">Invoice</span>
                </dt>
                <dd class="col-sm-7">
                  <input type="text" class="form-control" id="invoiceNumber" name="invoice_number" value="{{ $invoice->invoice_number }}" required />
                </dd>
                <dt class="col-sm-5 mb-1 d-md-flex align-items-center justify-content-end">
                  <span class="fw-normal">Date Issued:</span>
                </dt>
                <dd class="col-sm-7">
                  <input type="text" class="form-control dob-picker" name="billing_period_start" value="{{ $invoice->billing_period_start->format('Y-m-d') }}" required />
                </dd>
                <dt class="col-sm-5 d-md-flex align-items-center justify-content-end">
                  <span class="fw-normal">Due Date:</span>
                </dt>
                <dd class="col-sm-7 mb-0">
                  <input type="text" class="form-control dob-picker" name="due_date" value="{{ $invoice->due_date->format('Y-m-d') }}" required />
                </dd>
              </dl>
            </div>
          </div>
        </div>

        <div class="card-body px-0">
          <div class="row">
            <div class="col-md-6 col-sm-5 col-12 mb-sm-0 mb-6">
              <h6>Invoice To:</h6>
              <select class="form-select select2 mb-4 w-75" name="tenant_id" required>
                <option value="">Select tenant</option>
                @foreach ($tenants as $tenant)
                  <option value="{{ $tenant->id }}" @selected($tenant->id === $invoice->tenant_id)>{{ $tenant->name }}</option>
                @endforeach
              </select>
              <p class="mb-1">Tenant subscription account</p>
            </div>
            <div class="col-md-6 col-sm-7">
              <h6>Bill To:</h6>
              <table>
                <tbody>
                  <tr>
                    <td class="pe-4">Amount:</td>
                    <td><input type="number" class="form-control" name="amount" value="{{ number_format($invoice->amount_cents / 100, 2, '.', '') }}" min="0" step="0.01" required /></td>
                  </tr>
                  <tr>
                    <td class="pe-4">Currency:</td>
                    <td><input type="text" class="form-control" value="USD" readonly /></td>
                  </tr>
                  <tr>
                    <td class="pe-4">Status:</td>
                    <td>
                      <select class="form-select" name="status" required>
                        <option value="unpaid" @selected($invoice->status === 'unpaid')>Unpaid</option>
                        <option value="paid" @selected($invoice->status === 'paid')>Paid</option>
                        <option value="void" @selected($invoice->status === 'void')>Void</option>
                      </select>
                    </td>
                  </tr>
                  <tr>
                    <td class="pe-4">Period End:</td>
                    <td><input type="text" class="form-control dob-picker" name="billing_period_end" value="{{ $invoice->billing_period_end->format('Y-m-d') }}" required /></td>
                  </tr>
                  <tr>
                    <td class="pe-4">Paid At:</td>
                    <td><input type="text" class="form-control dob-picker" name="paid_at" value="{{ optional($invoice->paid_at)->format('Y-m-d') }}" /></td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <hr class="mt-0 mb-6" />
        <div class="card-body pt-0 px-0">
          <div class="row">
            <div class="col-md-6 col-12 mb-md-0 mb-4">
              <p class="h6">Subscription</p>
              <select class="form-select" name="subscription_id" required>
                <option value="">Select subscription</option>
                @foreach ($subscriptions as $subscription)
                  <option value="{{ $subscription->id }}" @selected($subscription->id === $invoice->subscription_id)>{{ $subscription->tenant?->name ?? 'Tenant' }} - {{ $subscription->plan?->name ?? 'Plan' }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-6 col-12">
              <p class="h6">Notes</p>
              <textarea class="form-control" rows="2" name="metadata[note]" placeholder="Invoice note"></textarea>
            </div>
          </div>
        </div>

        <hr class="my-0" />
        <div class="card-body px-0">
          <div class="row row-gap-4">
            <div class="col-md-6 mb-md-0 mb-4">
              <div class="d-flex align-items-center mb-4">
                <label for="salesperson" class="me-2 fw-medium text-heading">Salesperson:</label>
                <input type="text" class="form-control" id="salesperson" placeholder="RoomGate Billing" disabled />
              </div>
              <input type="text" class="form-control" id="invoiceMsg" placeholder="Thanks for your business" disabled />
            </div>
            <div class="col-md-6 d-flex justify-content-end">
              <div class="invoice-calculations">
                <div class="d-flex justify-content-between mb-2">
                  <span class="w-px-100">Subtotal:</span>
                  <span class="fw-medium text-heading">Auto</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                  <span class="w-px-100">Discount:</span>
                  <span class="fw-medium text-heading">0</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                  <span class="w-px-100">Tax:</span>
                  <span class="fw-medium text-heading">0%</span>
                </div>
                <hr class="my-2" />
                <div class="d-flex justify-content-between">
                  <span class="w-px-100">Total:</span>
                  <span class="fw-medium text-heading">Auto</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <hr class="my-0" />
        <div class="card-body px-0 pb-0">
          <div class="row">
            <div class="col-12">
              <div>
                <label for="note" class="text-heading mb-1 fw-medium">Note:</label>
                <textarea class="form-control" rows="2" id="note" placeholder="Invoice note"></textarea>
              </div>
            </div>
          </div>
        </div>
      </form>
    </div>

    <div class="col-lg-3 col-12 invoice-actions">
      <div class="card mb-6">
        <div class="card-body">
          <button class="btn btn-primary d-grid w-100 mb-4" type="submit" form="invoiceEditForm">
            <span class="d-flex align-items-center justify-content-center text-nowrap"><i class="icon-base ti tabler-send icon-xs me-2"></i>Update Invoice</span>
          </button>
          <a href="{{ route('admin.subscription-invoices.show', $invoice) }}" class="btn btn-label-secondary d-grid w-100 mb-4">Preview</a>
          <a href="{{ route('admin.subscription-invoices.index') }}" class="btn btn-label-secondary d-grid w-100">Back</a>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('page-scripts')
  <script src="{{ asset('assets/assets') }}/vendor/libs/flatpickr/flatpickr.js"></script>
  <script src="{{ asset('assets/assets') }}/vendor/libs/select2/select2.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      if (window.flatpickr) {
        flatpickr('.dob-picker', { dateFormat: 'Y-m-d' });
      }
      if (window.$ && $.fn.select2) {
        $('.select2').each(function () {
          const placeholder = $(this).find('option[value=""]').first().text() || 'Select';
          $(this).select2({
            placeholder: placeholder,
            allowClear: true,
            width: '100%'
          });
        });
      }
    });
  </script>
@endpush

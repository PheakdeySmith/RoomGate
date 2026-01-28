@extends('core::components.layouts.master')
@section('title', 'Add Invoice | RoomGate')
@section('page-title', 'Add Invoice')

@push('page-styles')
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/flatpickr/flatpickr.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/css/pages/app-invoice.css" />
@endpush

@section('content')
@php
  $currency = $tenant->default_currency ?? 'USD';
@endphp
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="row invoice-add">
    <div class="col-lg-9 col-12 mb-lg-0 mb-6">
      <form method="POST" action="{{ route('core.invoices.store') }}" class="card invoice-preview-card p-sm-12 p-6" id="invoiceForm">
        @csrf
        <div class="card-body invoice-preview-header rounded">
          <div class="d-flex flex-wrap flex-column flex-sm-row justify-content-between text-heading">
            <div class="mb-md-0 mb-6">
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
            <div class="col-md-5 col-8 pe-0 ps-0 ps-md-2">
              <dl class="row mb-0 gx-4">
                <dt class="col-sm-5 mb-2 d-md-flex align-items-center justify-content-end">
                  <span class="h5 text-capitalize mb-0 text-nowrap">Invoice</span>
                </dt>
                <dd class="col-sm-7">
                  <input type="text" class="form-control" disabled placeholder="Auto" value="Auto" />
                </dd>
                <dt class="col-sm-5 mb-1 d-md-flex align-items-center justify-content-end">
                  <span class="fw-normal">Date Issued:</span>
                </dt>
                <dd class="col-sm-7">
                  <input type="text" name="issue_date" class="form-control flatpickr" placeholder="YYYY-MM-DD" required />
                </dd>
                <dt class="col-sm-5 d-md-flex align-items-center justify-content-end">
                  <span class="fw-normal">Due Date:</span>
                </dt>
                <dd class="col-sm-7 mb-0">
                  <input type="text" name="due_date" class="form-control flatpickr" placeholder="YYYY-MM-DD" required />
                </dd>
              </dl>
            </div>
          </div>
        </div>

        <div class="card-body px-0">
          <div class="row">
            <div class="col-md-6 col-sm-5 col-12 mb-sm-0 mb-6">
              <h6>Invoice To:</h6>
              <div class="mb-4">
                <label class="form-label" for="contractSelect">Contract</label>
                <select class="form-select" id="contractSelect" name="contract_id" required>
                  <option value="">Select contract</option>
                  @foreach ($contracts as $contract)
                    @php
                      $roomLabel = $contract->room?->room_number ?? 'Room';
                      $propertyLabel = $contract->room?->property?->name ?? 'Property';
                      $occupantName = $contract->occupant?->name ?? 'Tenant';
                      $occupantEmail = $contract->occupant?->email ?? '';
                    @endphp
                    <option
                      value="{{ $contract->id }}"
                      data-rent-cents="{{ (int) ($contract->monthly_rent_cents ?? 0) }}"
                      data-occupant-name="{{ $occupantName }}"
                      data-occupant-email="{{ $occupantEmail }}"
                      data-room="{{ $roomLabel }}"
                      data-property="{{ $propertyLabel }}">
                      {{ $roomLabel }} ({{ $propertyLabel }}) - {{ $occupantName }}
                    </option>
                  @endforeach
                </select>
              </div>
              <p class="mb-1" id="invoiceToName">-</p>
              <p class="mb-1" id="invoiceToRoom">-</p>
              <p class="mb-0" id="invoiceToEmail">-</p>
            </div>
            <div class="col-md-6 col-sm-7">
              <h6>Bill To:</h6>
              <table>
                <tbody>
                  <tr>
                    <td class="pe-4">Total Due:</td>
                    <td class="fw-medium" id="totalDue">{{ $currency }} 0.00</td>
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

        <hr class="mt-0 mb-6" />
        <div class="card-body pt-0 px-0">
          <div class="mb-4">
            <label class="form-label" for="utilitySelect">Utility Bills (optional)</label>
            <select id="utilitySelect" class="form-select" multiple></select>
          </div>
          <div class="table-responsive border border-bottom-0 border-top-0 rounded">
            <table class="table m-0">
              <thead>
                <tr>
                  <th>Item</th>
                  <th>Description</th>
                  <th>Amount</th>
                  <th></th>
                </tr>
              </thead>
              <tbody id="invoiceItemsBody"></tbody>
            </table>
          </div>
          <div class="row mt-4">
            <div class="col-12">
              <button type="button" class="btn btn-sm btn-primary" id="addManualItem">
                <i class="icon-base ti tabler-plus icon-xs me-1_5"></i>Add Manual Item
              </button>
            </div>
          </div>
        </div>

        <hr class="my-0" />
        <div class="card-body px-0">
          <div class="row row-gap-4">
            <div class="col-md-6 mb-md-0 mb-4">
              <input type="text" class="form-control" name="notes" id="invoiceMsg" placeholder="Invoice note (optional)" />
            </div>
            <div class="col-md-6 d-flex justify-content-end">
              <div class="invoice-calculations">
                <div class="d-flex justify-content-between mb-2">
                  <span class="w-px-100">Subtotal:</span>
                  <span class="fw-medium text-heading" id="subtotalDisplay">{{ $currency }} 0.00</span>
                </div>
                <div class="d-flex justify-content-between">
                  <span class="w-px-100">Total:</span>
                  <span class="fw-medium text-heading" id="totalDisplay">{{ $currency }} 0.00</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <hr class="my-0" />
        <div class="card-body px-0 pb-0 d-flex justify-content-end">
          <button type="submit" class="btn btn-primary">Save Invoice</button>
        </div>
      </form>
    </div>

    <div class="col-lg-3 col-12 invoice-actions">
      <div class="card mb-6">
        <div class="card-body">
          <a href="{{ route('Core.invoices.index') }}" class="btn btn-label-secondary d-grid w-100 mb-4">Back to List</a>
          <button type="submit" form="invoiceForm" class="btn btn-primary d-grid w-100">Create Invoice</button>
        </div>
      </div>
      <div>
        <label for="statusSelect" class="form-label">Status</label>
        <select class="form-select mb-6" id="statusSelect" name="status" form="invoiceForm">
          <option value="draft" selected>Draft</option>
          <option value="sent">Sent</option>
          <option value="paid">Paid</option>
          <option value="partial">Partial</option>
          <option value="overdue">Overdue</option>
          <option value="void">Void</option>
        </select>
      </div>
    </div>
  </div>
</div>
@endsection

@push('page-scripts')
  <script src="{{ asset('assets/assets') }}/vendor/libs/flatpickr/flatpickr.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const currency = @json($currency);
      const contractSelect = document.getElementById('contractSelect');
      const utilitySelect = document.getElementById('utilitySelect');
      const itemsBody = document.getElementById('invoiceItemsBody');
      const addManualButton = document.getElementById('addManualItem');
      const invoiceToName = document.getElementById('invoiceToName');
      const invoiceToRoom = document.getElementById('invoiceToRoom');
      const invoiceToEmail = document.getElementById('invoiceToEmail');
      const subtotalDisplay = document.getElementById('subtotalDisplay');
      const totalDisplay = document.getElementById('totalDisplay');
      const totalDue = document.getElementById('totalDue');

      let utilityBills = [];
      let manualItems = [];

      function formatMoney(cents) {
        return `${currency} ${(cents / 100).toFixed(2)}`;
      }

      function getSelectedContractData() {
        const option = contractSelect.options[contractSelect.selectedIndex];
        if (!option || !option.value) {
          return null;
        }
        return {
          rentCents: parseInt(option.getAttribute('data-rent-cents') || '0', 10),
          occupantName: option.getAttribute('data-occupant-name') || '-',
          occupantEmail: option.getAttribute('data-occupant-email') || '-',
          room: option.getAttribute('data-room') || '-',
          property: option.getAttribute('data-property') || '-'
        };
      }

      function buildHiddenInputs() {
        document.querySelectorAll('input[name="utility_bill_ids[]"]').forEach((el) => el.remove());
        utilityBills.filter((bill) => bill.selected).forEach((bill) => {
          const input = document.createElement('input');
          input.type = 'hidden';
          input.name = 'utility_bill_ids[]';
          input.value = bill.id;
          document.getElementById('invoiceForm').appendChild(input);
        });
      }

      function rebuildManualInputs() {
        document.querySelectorAll('[data-manual-row="1"]').forEach((row) => row.remove());
        manualItems.forEach((item, index) => {
          const row = document.createElement('tr');
          row.setAttribute('data-manual-row', '1');
          row.innerHTML = `
            <td class="text-heading">Manual</td>
            <td>
              <input type="text" class="form-control" name="manual_items[${index}][description]" value="${item.description || ''}" placeholder="Description" required />
            </td>
            <td>
              <input type="number" class="form-control manual-amount" name="manual_items[${index}][amount]" value="${item.amount || ''}" min="0.01" step="0.01" required />
            </td>
            <td class="text-end">
              <button type="button" class="btn btn-icon btn-text-danger" data-remove-manual="${index}">
                <i class="icon-base ti tabler-trash icon-md"></i>
              </button>
            </td>
          `;
          itemsBody.appendChild(row);
        });
      }

      function updateTotals() {
        const contractData = getSelectedContractData();
        const rentCents = contractData ? contractData.rentCents : 0;
        const manualCents = manualItems.reduce((sum, item) => {
          const amount = parseFloat(item.amount || '0');
          return sum + Math.round(amount * 100);
        }, 0);
        const utilitiesCents = utilityBills.filter((bill) => bill.selected).reduce((sum, bill) => sum + bill.amount_cents, 0);
        const subtotal = rentCents + utilitiesCents + manualCents;

        subtotalDisplay.textContent = formatMoney(subtotal);
        totalDisplay.textContent = formatMoney(subtotal);
        totalDue.textContent = formatMoney(subtotal);

        buildHiddenInputs();
      }

      function renderItems() {
        itemsBody.innerHTML = '';
        const contractData = getSelectedContractData();
        const rentCents = contractData ? contractData.rentCents : 0;

        const rentRow = document.createElement('tr');
        rentRow.innerHTML = `
          <td class="text-heading">Rent</td>
          <td>Monthly rent</td>
          <td>${formatMoney(rentCents)}</td>
          <td></td>
        `;
        itemsBody.appendChild(rentRow);

        utilityBills.filter((bill) => bill.selected).forEach((bill) => {
          const row = document.createElement('tr');
          row.innerHTML = `
            <td class="text-heading">Utility</td>
            <td>${bill.label}</td>
            <td>${formatMoney(bill.amount_cents)}</td>
            <td></td>
          `;
          itemsBody.appendChild(row);
        });

        rebuildManualInputs();
        attachManualListeners();
        updateTotals();
      }

      function attachManualListeners() {
        document.querySelectorAll('input.manual-amount').forEach((input, index) => {
          input.addEventListener('input', function () {
            manualItems[index].amount = input.value;
            updateTotals();
          });
        });
        document.querySelectorAll('input[name^="manual_items"][name$="[description]"]').forEach((input, index) => {
          input.addEventListener('input', function () {
            manualItems[index].description = input.value;
          });
        });
        document.querySelectorAll('[data-remove-manual]').forEach((button) => {
          button.addEventListener('click', function () {
            const index = parseInt(button.getAttribute('data-remove-manual'), 10);
            manualItems.splice(index, 1);
            renderItems();
          });
        });
      }

      function loadUtilities(contractId) {
        utilitySelect.innerHTML = '';
        utilityBills = [];
        if (!contractId) {
          renderItems();
          return;
        }

        fetch(`{{ route('core.invoices.utilities') }}?contract_id=${contractId}`)
          .then((response) => response.json())
          .then((payload) => {
            utilityBills = (payload.data || []).map((bill) => ({
              ...bill,
              selected: false
            }));
            utilityBills.forEach((bill) => {
              const option = document.createElement('option');
              option.value = bill.id;
              option.textContent = `${bill.label} - ${formatMoney(bill.amount_cents)}`;
              utilitySelect.appendChild(option);
            });
            renderItems();
          });
      }

      contractSelect.addEventListener('change', function () {
        const data = getSelectedContractData();
        if (data) {
          invoiceToName.textContent = data.occupantName;
          invoiceToRoom.textContent = `${data.room} (${data.property})`;
          invoiceToEmail.textContent = data.occupantEmail || '-';
        } else {
          invoiceToName.textContent = '-';
          invoiceToRoom.textContent = '-';
          invoiceToEmail.textContent = '-';
        }
        loadUtilities(contractSelect.value);
      });

      utilitySelect.addEventListener('change', function () {
        const selected = Array.from(utilitySelect.selectedOptions).map((opt) => parseInt(opt.value, 10));
        utilityBills = utilityBills.map((bill) => ({
          ...bill,
          selected: selected.includes(bill.id)
        }));
        renderItems();
      });

      addManualButton.addEventListener('click', function () {
        manualItems.push({ description: '', amount: '' });
        renderItems();
      });

      if (window.flatpickr) {
        document.querySelectorAll('.flatpickr').forEach((el) => {
          if (el._flatpickr) {
            el._flatpickr.destroy();
          }
          flatpickr(el, { dateFormat: 'Y-m-d', disableMobile: true });
        });
      }
    });
  </script>
@endpush

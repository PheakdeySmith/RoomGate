@extends('admin::components.layouts.master')
@section('title', 'Subscription Invoices | RoomGate Admin')
@section('page-title', 'Subscription Invoices')

@push('page-styles')
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/datatables-bs5/datatables.bootstrap5.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/datatables-select-bs5/select.bootstrap5.css" />
@endpush

@section('content')
@php
  $clientCount = $invoiceStats['client_count'] ?? 0;
  $invoiceCount = $invoiceStats['invoice_count'] ?? 0;
  $paidTotal = number_format(($invoiceStats['paid_total_cents'] ?? 0) / 100, 2);
  $unpaidTotal = number_format(($invoiceStats['unpaid_total_cents'] ?? 0) / 100, 2);
@endphp

<div class="container-xxl flex-grow-1 container-p-y">
  <div class="card mb-6">
    <div class="card-widget-separator-wrapper">
      <div class="card-body card-widget-separator">
        <div class="row gy-4 gy-sm-1">
          <div class="col-sm-6 col-lg-3">
            <div class="d-flex justify-content-between align-items-center card-widget-1 border-end pb-4 pb-sm-0">
              <div>
                <h4 class="mb-0">{{ $clientCount }}</h4>
                <p class="mb-0">Clients</p>
              </div>
              <div class="avatar me-sm-6">
                <span class="avatar-initial rounded bg-label-secondary text-heading">
                  <i class="icon-base ti tabler-user icon-26px"></i>
                </span>
              </div>
            </div>
            <hr class="d-none d-sm-block d-lg-none me-6" />
          </div>
          <div class="col-sm-6 col-lg-3">
            <div class="d-flex justify-content-between align-items-center card-widget-2 border-end pb-4 pb-sm-0">
              <div>
                <h4 class="mb-0">{{ $invoiceCount }}</h4>
                <p class="mb-0">Invoices</p>
              </div>
              <div class="avatar me-lg-6">
                <span class="avatar-initial rounded bg-label-secondary text-heading">
                  <i class="icon-base ti tabler-file-invoice icon-26px"></i>
                </span>
              </div>
            </div>
            <hr class="d-none d-sm-block d-lg-none" />
          </div>
          <div class="col-sm-6 col-lg-3">
            <div class="d-flex justify-content-between align-items-center border-end pb-4 pb-sm-0 card-widget-3">
              <div>
                <h4 class="mb-0">${{ $paidTotal }}</h4>
                <p class="mb-0">Paid</p>
              </div>
              <div class="avatar me-sm-6">
                <span class="avatar-initial rounded bg-label-secondary text-heading">
                  <i class="icon-base ti tabler-checks icon-26px"></i>
                </span>
              </div>
            </div>
          </div>
          <div class="col-sm-6 col-lg-3">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h4 class="mb-0">${{ $unpaidTotal }}</h4>
                <p class="mb-0">Unpaid</p>
              </div>
              <div class="avatar">
                <span class="avatar-initial rounded bg-label-secondary text-heading">
                  <i class="icon-base ti tabler-circle-off icon-26px"></i>
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-datatable table-responsive">
      <table class="invoice-list-table table border-top">
        <thead>
          <tr>
            <th></th>
            <th></th>
            <th>#</th>
            <th>Status</th>
            <th>Client</th>
            <th>Total</th>
            <th class="text-truncate">Issued Date</th>
            <th>Balance</th>
            <th>Invoice Status</th>
            <th class="cell-fit">Action</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>
</div>
@endsection

@push('page-scripts')
  <script src="{{ asset('assets/assets') }}/vendor/libs/moment/moment.js"></script>
  <script src="{{ asset('assets/assets') }}/vendor/libs/datatables-bs5/datatables-bootstrap5.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const table = document.querySelector('.invoice-list-table');
      if (!table || !window.DataTable) {
        return;
      }

      const invoiceData = @json($invoiceData->values());
      const assetsPath = @json(asset('assets/assets') . '/');
      const baseUrl = @json(url('/admin/subscription-invoices'));
      const rows = invoiceData.map((invoice) => {
        const amount = (invoice.amount_cents || 0) / 100;
        const balanceAmount = invoice.status_raw === 'paid' ? 0 : amount;
        return {
          id: invoice.id,
          invoice_id: invoice.invoice_number,
          invoice_status: invoice.status,
          issued_date: invoice.issued_date,
          client_name: invoice.tenant_name,
          service: invoice.plan_name,
          total: amount.toFixed(2),
          balance: balanceAmount.toFixed(2),
          due_date: invoice.due_date,
          currency: invoice.currency || 'USD'
        };
      });

      const formatCurrency = (value, currency) => {
        try {
          return new Intl.NumberFormat('en-US', { style: 'currency', currency }).format(value);
        } catch (error) {
          return `${currency} ${value}`;
        }
      };

      const dtInvoice = new DataTable(table, {
        data: rows,
        columns: [
          { data: 'invoice_id' },
          { data: 'invoice_id' },
          { data: 'invoice_id' },
          { data: 'invoice_status' },
          { data: 'client_name' },
          { data: 'total' },
          { data: 'issued_date' },
          { data: 'balance' },
          { data: 'invoice_status' },
          { data: 'action' }
        ],
        columnDefs: [
          {
            className: 'control',
            responsivePriority: 2,
            searchable: false,
            targets: 0,
            render: function () {
              return '';
            }
          },
          {
            targets: 1,
            orderable: false,
            searchable: false,
            responsivePriority: 4,
            render: function () {
              return '<input type="checkbox" class="dt-checkboxes form-check-input">';
            }
          },
          {
            targets: 2,
            render: function (data, type, full) {
              return `<a href="${baseUrl}/${full.id}">#${full.invoice_id}</a>`;
            }
          },
          {
            targets: 3,
            render: function (data, type, full) {
              const invoiceStatus = full.invoice_status;
              const balance = full.balance;
              const dueDate = full.due_date;

              const roleBadgeObj = {
                Sent: '<span class="badge badge-center rounded-pill bg-label-success w-px-30 h-px-30 display-flex align-items-center justify-content-center"><i class="icon-base ti tabler-check icon-16px"></i></span>',
                Draft: '<span class="badge badge-center rounded-pill bg-label-primary w-px-30 h-px-30 display-flex align-items-center justify-content-center"><i class="icon-base ti tabler-folder icon-16px"></i></span>',
                Paid: '<span class="badge badge-center rounded-pill bg-label-warning w-px-30 h-px-30 display-flex align-items-center justify-content-center"><i class="icon-base ti tabler-chart-pie-2 icon-16px"></i></span>'
              };

              const tooltipContent = `
                ${invoiceStatus}<br>
                <span class="fw-medium">Balance:</span> ${balance}<br>
                <span class="fw-medium">Due Date:</span> ${dueDate}
              `.replace(/"/g, '&quot;');

              return `
                <span class="d-inline-block" data-bs-toggle="tooltip" data-bs-html="true" title="${tooltipContent}">
                  ${roleBadgeObj[invoiceStatus] || ''}
                </span>
              `;
            }
          },
          {
            targets: 4,
            responsivePriority: 2,
            render: function (data, type, full) {
              const name = full.client_name;
              const service = full.service;
              const initials = (name.match(/\b\w/g) || [])
                .slice(0, 2)
                .map(letter => letter.toUpperCase())
                .join('');
              return `
                <div class="d-flex justify-content-start align-items-center">
                  <div class="avatar-wrapper">
                    <div class="avatar avatar-sm me-3">
                      <span class="avatar-initial rounded-circle bg-label-primary">${initials}</span>
                    </div>
                  </div>
                  <div class="d-flex flex-column">
                    <span class="text-heading text-truncate"><span class="fw-medium">${name}</span></span>
                    <small class="text-truncate">${service}</small>
                  </div>
                </div>
              `;
            }
          },
          {
            targets: 5,
            render: function (data, type, full) {
              const total = Number(full.total || 0);
              return `<span class="d-none">${total}</span>${formatCurrency(total, full.currency)}`;
            }
          },
          {
            targets: 6,
            render: function (data, type, full) {
              if (!full.issued_date) {
                return '-';
              }
              const dueDate = new Date(full.issued_date);
              return `
                <span class="d-none">${dueDate.toISOString().slice(0, 10).replace(/-/g, '')}</span>
                ${dueDate.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })}
              `;
            }
          },
          {
            targets: 7,
            orderable: false,
            render: function (data, type, full) {
              const balance = Number(full.balance || 0);
              if (balance === 0) {
                return '<span class="badge bg-label-success text-capitalized"> Paid </span>';
              }
              return `<span class="d-none">${balance}</span><span class="text-heading">${formatCurrency(balance, full.currency)}</span>`;
            }
          },
          {
            targets: 8,
            visible: false
          },
          {
            targets: -1,
            title: 'Actions',
            searchable: false,
            orderable: false,
            render: function (data, type, full) {
              return (
                '<div class="d-flex align-items-center">' +
                `<a href="${baseUrl}/${full.id}" data-bs-toggle="tooltip" class="btn btn-icon btn-text-secondary rounded-pill waves-effect" data-bs-placement="top" title="Preview Invoice"><i class="icon-base ti tabler-eye icon-22px"></i></a>` +
                '<div class="dropdown">' +
                '<a href="javascript:;" class="btn dropdown-toggle hide-arrow btn-icon btn-text-secondary rounded-pill waves-effect p-0" data-bs-toggle="dropdown"><i class="icon-base ti tabler-dots-vertical icon-22px"></i></a>' +
                '<div class="dropdown-menu dropdown-menu-end">' +
                `<a href="${baseUrl}/${full.id}/edit" class="dropdown-item">Edit</a>` +
                '<a href="javascript:;" class="dropdown-item disabled">Download</a>' +
                '</div>' +
                '</div>' +
                '</div>'
              );
            }
          }
        ],
        order: [[2, 'desc']],
        displayLength: 10,
        layout: {
          topStart: {
            rowClass: 'row m-3 my-0 justify-content-between',
            features: [
              {
                pageLength: {
                  menu: [10, 25, 50, 100],
                  text: '_MENU_'
                }
              }
            ]
          },
          topEnd: {
            rowClass: 'row m-3 my-0 justify-content-between',
            features: [
              {
                search: {
                  placeholder: 'Search Invoice',
                  text: '_INPUT_'
                }
              },
              {
                buttons: [
                  {
                    extend: 'collection',
                    className: 'btn btn-label-secondary dropdown-toggle me-4',
                    text: '<span class="d-flex align-items-center gap-1"><i class="icon-base ti tabler-upload icon-xs"></i> <span class="d-inline-block">Export</span></span>',
                    buttons: ['print', 'csv', 'excel', 'pdf', 'copy']
                  },
                  {
                    text: '<i class="icon-base ti tabler-plus me-0 me-sm-1 icon-16px"></i><span class="d-none d-sm-inline-block">Create Invoice</span>',
                    className: 'add-new btn btn-primary rounded-2 waves-effect waves-light',
                    action: function () {
                      window.location = @json(route('admin.subscription-invoices.create'));
                    }
                  }
                ]
              }
            ]
          },
          bottomStart: {
            rowClass: 'row mx-3 justify-content-between',
            features: ['info']
          },
          bottomEnd: 'paging'
        },
        language: {
          paginate: {
            next: '<i class="icon-base ti tabler-chevron-right scaleX-n1-rtl icon-18px"></i>',
            previous: '<i class="icon-base ti tabler-chevron-left scaleX-n1-rtl icon-18px"></i>',
            first: '<i class="icon-base ti tabler-chevrons-left scaleX-n1-rtl icon-18px"></i>',
            last: '<i class="icon-base ti tabler-chevrons-right scaleX-n1-rtl icon-18px"></i>'
          }
        },
        responsive: {
          details: {
            display: DataTable.Responsive.display.modal({
              header: function (row) {
                const data = row.data();
                return 'Details of ' + data.client_name;
              }
            }),
            type: 'column',
            renderer: function (api, rowIdx, columns) {
              const data = columns
                .map(function (col) {
                  return col.title !== ''
                    ? `<tr data-dt-row="${col.rowIndex}" data-dt-column="${col.columnIndex}">
                        <td>${col.title}:</td>
                        <td>${col.data}</td>
                      </tr>`
                    : '';
                })
                .join('');

              if (!data) {
                return null;
              }

              const div = document.createElement('div');
              div.classList.add('table-responsive');
              const table = document.createElement('table');
              table.classList.add('table');
              const tbody = document.createElement('tbody');
              tbody.innerHTML = data;
              table.appendChild(tbody);
              div.appendChild(table);
              return div;
            }
          }
        }
      });

      setTimeout(() => {
        const elementsToModify = [
          { selector: '.dt-buttons .btn', classToRemove: 'btn-secondary' },
          { selector: '.dt-buttons.btn-group .btn-group', classToRemove: 'btn-group' },
          { selector: '.dt-buttons.btn-group', classToRemove: 'btn-group', classToAdd: 'd-flex' },
          { selector: '.dt-search .form-control', classToRemove: 'form-control-sm' },
          { selector: '.dt-length .form-select', classToRemove: 'form-select-sm' },
          { selector: '.dt-length', classToAdd: 'mb-md-6 mb-0' },
          { selector: '.dt-layout-start', classToAdd: 'ps-3 mt-0' },
          {
            selector: '.dt-layout-end',
            classToRemove: 'justify-content-between',
            classToAdd: 'justify-content-md-between justify-content-center d-flex flex-wrap gap-4 mt-0 mb-md-0 mb-6'
          },
          { selector: '.dt-layout-table', classToRemove: 'row mt-2' },
          { selector: '.dt-layout-full', classToRemove: 'col-md col-12', classToAdd: 'table-responsive' }
        ];

        elementsToModify.forEach(({ selector, classToRemove, classToAdd }) => {
          document.querySelectorAll(selector).forEach(element => {
            if (classToRemove) {
              classToRemove.split(' ').forEach(className => element.classList.remove(className));
            }
            if (classToAdd) {
              classToAdd.split(' ').forEach(className => element.classList.add(className));
            }
          });
        });
      }, 100);

      document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((el) => {
        new bootstrap.Tooltip(el);
      });
    });
  </script>
@endpush

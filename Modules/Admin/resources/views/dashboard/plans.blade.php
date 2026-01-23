@extends('admin::components.layouts.master')
@section('title', 'Plans | RoomGate Admin')
@section('page-title', 'Plans')

@push('page-styles')
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/datatables-bs5/datatables.bootstrap5.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/select2/select2.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/@form-validation/form-validation.css" />
@endpush

@section('content')
@php
  $statusClass = [
      true => 'bg-label-success',
      false => 'bg-label-secondary',
  ];
  $allLimits = $plans->flatMap->limits;
@endphp

<div class="container-xxl flex-grow-1 container-p-y">
  <div class="card mb-6">
    <div class="card-datatable table-responsive">
      <table class="datatables-plans table border-top">
        <thead>
          <tr>
            <th></th>
            <th>Plan</th>
            <th>Price</th>
            <th>Interval</th>
            <th>Status</th>
            <th>Limits</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($plans as $plan)
            @php
              $price = number_format($plan->price_cents / 100, 2);
              $intervalLabel = ucfirst($plan->interval);
              $statusLabel = $statusClass[$plan->is_active] ?? 'bg-label-secondary';
              $limitPreview = $plan->limits->take(2);
              $extraLimits = max($plan->limits->count() - $limitPreview->count(), 0);
            @endphp
            <tr>
              <td></td>
              <td>
                <div class="d-flex flex-column">
                  <span class="text-heading fw-medium">{{ $plan->name }}</span>
                  <small class="text-body-secondary">{{ $plan->code }}</small>
                </div>
              </td>
              <td>{{ $plan->currency_code }} {{ $price }}</td>
              <td>{{ $intervalLabel }}</td>
              <td><span class="badge {{ $statusLabel }}">{{ $plan->is_active ? 'Active' : 'Inactive' }}</span></td>
              <td>
                <div class="d-flex flex-wrap gap-1">
                  @foreach ($limitPreview as $limit)
                    <span class="badge bg-label-primary text-uppercase">{{ $limit->limit_key }}: {{ $limit->limit_value }}</span>
                  @endforeach
                  @if ($extraLimits > 0)
                    <span class="badge bg-label-secondary">+{{ $extraLimits }}</span>
                  @endif
                </div>
              </td>
              <td>
                <div class="d-flex align-items-center">
                  <button
                    class="btn btn-icon btn-text-secondary rounded-pill waves-effect"
                    data-bs-toggle="modal"
                    data-bs-target="#editPlanModal"
                    data-plan-id="{{ $plan->id }}"
                    data-plan-name="{{ $plan->name }}"
                    data-plan-code="{{ $plan->code }}"
                    data-plan-price="{{ $plan->price_cents }}"
                    data-plan-currency="{{ $plan->currency_code }}"
                    data-plan-interval="{{ $plan->interval }}"
                    data-plan-active="{{ $plan->is_active ? '1' : '0' }}">
                    <i class="icon-base ti tabler-edit icon-md"></i>
                  </button>
                  <button
                    class="btn btn-icon btn-text-secondary rounded-pill waves-effect"
                    data-bs-toggle="modal"
                    data-bs-target="#addLimitModal"
                    data-plan-id="{{ $plan->id }}">
                    <i class="icon-base ti tabler-plus icon-md"></i>
                  </button>
                  <form method="POST" action="{{ route('admin.plans.destroy', $plan) }}" data-confirm="Delete this plan?">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-icon btn-text-danger rounded-pill waves-effect">
                      <i class="icon-base ti tabler-trash icon-md"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>

  <div class="card">
    <div class="card-datatable table-responsive">
      <table class="datatables-plan-limits table border-top">
        <thead>
          <tr>
            <th></th>
            <th>Plan</th>
            <th>Limit Key</th>
            <th>Limit Value</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($allLimits as $limit)
            <tr>
              <td></td>
              <td>{{ $limit->plan?->name ?? 'Unknown' }}</td>
              <td class="text-uppercase">{{ $limit->limit_key }}</td>
              <td>{{ $limit->limit_value }}</td>
              <td>
                <div class="d-flex align-items-center">
                  <button
                    class="btn btn-icon btn-text-secondary rounded-pill waves-effect"
                    data-bs-toggle="modal"
                    data-bs-target="#editLimitModal"
                    data-limit-id="{{ $limit->id }}"
                    data-limit-key="{{ $limit->limit_key }}"
                    data-limit-value="{{ $limit->limit_value }}">
                    <i class="icon-base ti tabler-edit icon-md"></i>
                  </button>
                  <form method="POST" action="{{ route('admin.plan-limits.destroy', $limit) }}" data-confirm="Delete this plan limit?">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-icon btn-text-danger rounded-pill waves-effect">
                      <i class="icon-base ti tabler-trash icon-md"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="modal fade" id="addPlanModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-simple">
    <div class="modal-content">
      <div class="modal-body">
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        <div class="text-center mb-6">
          <span class="badge bg-label-primary rounded-1 p-2 mb-3">
            <i class="icon-base ti tabler-credit-card icon-md"></i>
          </span>
          <h4 class="mb-1">Create Plan</h4>
          <p class="text-body-secondary mb-0">Define pricing, interval, and activation.</p>
        </div>
        <form class="row g-3" method="POST" action="{{ route('admin.plans.store') }}">
          @csrf
          <div class="col-12">
            <label class="form-label" for="planName">Plan Name</label>
            <input type="text" id="planName" name="name" class="form-control" placeholder="Starter" />
          </div>
          <div class="col-12">
            <label class="form-label" for="planCode">Plan Code</label>
            <input type="text" id="planCode" name="code" class="form-control" placeholder="starter" />
          </div>
          <div class="col-6">
            <label class="form-label" for="planPrice">Price (cents)</label>
            <input type="number" id="planPrice" name="price_cents" class="form-control" placeholder="9900" />
          </div>
          <div class="col-6">
            <label class="form-label" for="planCurrency">Currency Code</label>
            <input type="text" id="planCurrency" name="currency_code" class="form-control" placeholder="USD" />
          </div>
          <div class="col-12">
            <label class="form-label" for="planInterval">Interval</label>
            <select id="planInterval" name="interval" class="form-select">
              <option value="monthly">Monthly</option>
              <option value="yearly">Yearly</option>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label" for="planActive">Active</label>
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" id="planActive" name="is_active" value="1" />
              <label class="form-check-label" for="planActive">Enabled</label>
            </div>
          </div>
          <div class="col-12 text-center mt-6">
            <button type="submit" class="btn btn-primary me-sm-4 me-1">Create Plan</button>
            <button type="reset" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="editPlanModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-simple">
    <div class="modal-content">
      <div class="modal-body">
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        <div class="text-center mb-6">
          <span class="badge bg-label-warning rounded-1 p-2 mb-3">
            <i class="icon-base ti tabler-edit icon-md"></i>
          </span>
          <h4 class="mb-1">Edit Plan</h4>
          <p class="text-body-secondary mb-0">Update pricing and availability.</p>
        </div>
        <form class="row g-3" method="POST" id="editPlanForm">
          @csrf
          @method('PATCH')
          <div class="col-12">
            <label class="form-label" for="editPlanName">Plan Name</label>
            <input type="text" id="editPlanName" name="name" class="form-control" />
          </div>
          <div class="col-12">
            <label class="form-label" for="editPlanCode">Plan Code</label>
            <input type="text" id="editPlanCode" name="code" class="form-control" />
          </div>
          <div class="col-6">
            <label class="form-label" for="editPlanPrice">Price (cents)</label>
            <input type="number" id="editPlanPrice" name="price_cents" class="form-control" />
          </div>
          <div class="col-6">
            <label class="form-label" for="editPlanCurrency">Currency Code</label>
            <input type="text" id="editPlanCurrency" name="currency_code" class="form-control" />
          </div>
          <div class="col-12">
            <label class="form-label" for="editPlanInterval">Interval</label>
            <select id="editPlanInterval" name="interval" class="form-select">
              <option value="monthly">Monthly</option>
              <option value="yearly">Yearly</option>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label" for="editPlanActive">Active</label>
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" id="editPlanActive" name="is_active" value="1" />
              <label class="form-check-label" for="editPlanActive">Enabled</label>
            </div>
          </div>
          <div class="col-12 text-center mt-6">
            <button type="submit" class="btn btn-primary me-sm-4 me-1">Update Plan</button>
            <button type="reset" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="addLimitModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-simple">
    <div class="modal-content">
      <div class="modal-body">
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        <div class="text-center mb-6">
          <span class="badge bg-label-info rounded-1 p-2 mb-3">
            <i class="icon-base ti tabler-adjustments icon-md"></i>
          </span>
          <h4 class="mb-1">Add Plan Limit</h4>
          <p class="text-body-secondary mb-0">Set caps for plan features.</p>
        </div>
        <form class="row g-3" method="POST" id="addLimitForm" action="{{ route('admin.plan-limits.store') }}">
          @csrf
          <div class="col-12">
            <label class="form-label" for="limitPlan">Plan</label>
            <select id="limitPlan" name="plan_id" class="select2 form-select">
              @foreach ($plans as $plan)
                <option value="{{ $plan->id }}">{{ $plan->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-12">
            <label class="form-label" for="limitKey">Limit Key</label>
            <input type="text" id="limitKey" name="limit_key" class="form-control" placeholder="properties_max" />
          </div>
          <div class="col-12">
            <label class="form-label" for="limitValue">Limit Value</label>
            <input type="text" id="limitValue" name="limit_value" class="form-control" placeholder="10" />
          </div>
          <div class="col-12 text-center mt-6">
            <button type="submit" class="btn btn-primary me-sm-4 me-1">Add Limit</button>
            <button type="reset" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="editLimitModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-simple">
    <div class="modal-content">
      <div class="modal-body">
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        <div class="text-center mb-6">
          <span class="badge bg-label-warning rounded-1 p-2 mb-3">
            <i class="icon-base ti tabler-adjustments icon-md"></i>
          </span>
          <h4 class="mb-1">Edit Plan Limit</h4>
          <p class="text-body-secondary mb-0">Update caps and feature access.</p>
        </div>
        <form class="row g-3" method="POST" id="editLimitForm">
          @csrf
          @method('PATCH')
          <div class="col-12">
            <label class="form-label" for="editLimitKey">Limit Key</label>
            <input type="text" id="editLimitKey" name="limit_key" class="form-control" />
          </div>
          <div class="col-12">
            <label class="form-label" for="editLimitValue">Limit Value</label>
            <input type="text" id="editLimitValue" name="limit_value" class="form-control" />
          </div>
          <div class="col-12 text-center mt-6">
            <button type="submit" class="btn btn-primary me-sm-4 me-1">Update Limit</button>
            <button type="reset" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

@push('page-scripts')
  <script src="{{ asset('assets/assets') }}/vendor/libs/datatables-bs5/datatables-bootstrap5.js"></script>
  <script src="{{ asset('assets/assets') }}/vendor/libs/select2/select2.js"></script>
  <script src="{{ asset('assets/assets') }}/vendor/libs/@form-validation/popular.js"></script>
  <script src="{{ asset('assets/assets') }}/vendor/libs/@form-validation/bootstrap5.js"></script>
  <script src="{{ asset('assets/assets') }}/vendor/libs/@form-validation/auto-focus.js"></script>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      if (window.$ && $.fn.select2) {
        $('.select2').each(function () {
          const placeholder = $(this).find('option[value=""]').first().text() || 'Select';
          const $modal = $(this).closest('.modal');
          $(this).select2({
            placeholder: placeholder,
            allowClear: true,
            width: '100%',
            dropdownParent: $modal.length ? $modal : undefined
          });
        });
      }

      const initTable = (selector, searchPlaceholder, addText, addTarget) => {
        const table = document.querySelector(selector);
        if (!table || !window.DataTable) {
          return;
        }
        new DataTable(table, {
          order: [[1, 'asc']],
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
          layout: {
            topStart: {
              rowClass: 'row my-md-0 me-3 ms-0 justify-content-between',
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
              features: [
                {
                  search: {
                    placeholder: searchPlaceholder,
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
                      text: addText,
                      className: 'add-new btn btn-primary rounded-2 waves-effect waves-light',
                      attr: {
                        'data-bs-toggle': 'modal',
                        'data-bs-target': addTarget
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
                header: function () {
                  return 'Details';
                }
              }),
              type: 'column'
            }
          }
        });
      };

      initTable('.datatables-plans', 'Search Plan', '<i class="icon-base ti tabler-plus me-0 me-sm-1 icon-16px"></i><span class="d-none d-sm-inline-block">Add Plan</span>', '#addPlanModal');
      initTable('.datatables-plan-limits', 'Search Limit', '<i class="icon-base ti tabler-plus me-0 me-sm-1 icon-16px"></i><span class="d-none d-sm-inline-block">Add Limit</span>', '#addLimitModal');

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

      const editPlanModal = document.getElementById('editPlanModal');
      if (editPlanModal) {
        editPlanModal.addEventListener('show.bs.modal', function (event) {
          const trigger = event.relatedTarget;
          const planId = trigger.getAttribute('data-plan-id');
          const planName = trigger.getAttribute('data-plan-name');
          const planCode = trigger.getAttribute('data-plan-code');
          const planPrice = trigger.getAttribute('data-plan-price');
          const planCurrency = trigger.getAttribute('data-plan-currency');
          const planInterval = trigger.getAttribute('data-plan-interval');
          const planActive = trigger.getAttribute('data-plan-active');
          const form = document.getElementById('editPlanForm');

          form.action = `{{ url('/admin/plans') }}/${planId}`;
          document.getElementById('editPlanName').value = planName || '';
          document.getElementById('editPlanCode').value = planCode || '';
          document.getElementById('editPlanPrice').value = planPrice || '';
          document.getElementById('editPlanCurrency').value = planCurrency || '';
          document.getElementById('editPlanInterval').value = planInterval || 'monthly';
          document.getElementById('editPlanActive').checked = planActive === '1';
        });
      }

      const addLimitModal = document.getElementById('addLimitModal');
      if (addLimitModal) {
        addLimitModal.addEventListener('show.bs.modal', function (event) {
          const trigger = event.relatedTarget;
          const planId = trigger.getAttribute('data-plan-id');
          if (planId) {
            const select = document.getElementById('limitPlan');
            if (select) {
              select.value = planId;
            }
          }
        });
      }

      const editLimitModal = document.getElementById('editLimitModal');
      if (editLimitModal) {
        editLimitModal.addEventListener('show.bs.modal', function (event) {
          const trigger = event.relatedTarget;
          const limitId = trigger.getAttribute('data-limit-id');
          const limitKey = trigger.getAttribute('data-limit-key');
          const limitValue = trigger.getAttribute('data-limit-value');
          const form = document.getElementById('editLimitForm');

          form.action = `{{ url('/admin/plan-limits') }}/${limitId}`;
          document.getElementById('editLimitKey').value = limitKey || '';
          document.getElementById('editLimitValue').value = limitValue || '';
        });
      }
    });
  </script>
@endpush

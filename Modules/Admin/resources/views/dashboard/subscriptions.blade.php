@extends('admin::components.layouts.master')
@section('title', 'Subscriptions | RoomGate Admin')
@section('page-title', 'Subscriptions')

@push('page-styles')
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/flatpickr/flatpickr.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/datatables-bs5/datatables.bootstrap5.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/select2/select2.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/@form-validation/form-validation.css" />
@endpush

@section('content')
@php
  $statusLabels = [
      'active' => 'bg-label-success',
      'trialing' => 'bg-label-warning',
      'past_due' => 'bg-label-danger',
      'cancelled' => 'bg-label-secondary',
      'expired' => 'bg-label-secondary',
  ];
@endphp

<div class="container-xxl flex-grow-1 container-p-y">
  <div class="card mb-6">
    <div class="card-datatable table-responsive">
      <table class="datatables-subscriptions table border-top">
        <thead>
          <tr>
            <th></th>
            <th>Tenant</th>
            <th>Plan</th>
            <th>Status</th>
            <th>Period</th>
            <th>Auto Renew</th>
            <th>Provider</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($subscriptions as $subscription)
            @php
              $statusLabel = $statusLabels[$subscription->status] ?? 'bg-label-secondary';
              $periodStart = optional($subscription->current_period_start)->format('Y-m-d');
              $periodEnd = optional($subscription->current_period_end)->format('Y-m-d');
              $trialEnds = optional($subscription->trial_ends_at)->format('Y-m-d');
            @endphp
            <tr>
              <td></td>
              <td>{{ $subscription->tenant?->name ?? 'Unknown' }}</td>
              <td>{{ $subscription->plan?->name ?? 'Unknown' }}</td>
              <td><span class="badge {{ $statusLabel }}">{{ ucfirst(str_replace('_', ' ', $subscription->status)) }}</span></td>
              <td>
                <div class="d-flex flex-column">
                  <small class="text-body-secondary">{{ $periodStart }} to {{ $periodEnd }}</small>
                  @if ($trialEnds)
                    <small class="text-body-secondary">Trial ends {{ $trialEnds }}</small>
                  @endif
                </div>
              </td>
              <td>{{ $subscription->auto_renew ? 'Yes' : 'No' }}</td>
              <td>{{ $subscription->provider }}</td>
              <td>
                <div class="d-flex align-items-center">
                  <button
                    class="btn btn-icon btn-text-secondary rounded-pill waves-effect"
                    data-bs-toggle="modal"
                    data-bs-target="#editSubscriptionModal"
                    data-subscription-id="{{ $subscription->id }}"
                    data-subscription-status="{{ $subscription->status }}"
                    data-subscription-auto="{{ $subscription->auto_renew ? '1' : '0' }}"
                    data-subscription-start="{{ $periodStart }}"
                    data-subscription-end="{{ $periodEnd }}"
                    data-subscription-trial="{{ $trialEnds }}"
                    data-subscription-cancelled="{{ optional($subscription->cancelled_at)->format('Y-m-d') }}"
                    data-subscription-provider="{{ $subscription->provider }}"
                    data-subscription-provider-ref="{{ $subscription->provider_ref }}">
                    <i class="icon-base ti tabler-edit icon-md"></i>
                  </button>
                  <form method="POST" action="{{ route('admin.subscriptions.destroy', $subscription) }}" data-confirm="Delete this subscription?">
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

<div class="modal fade" id="addSubscriptionModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-simple">
    <div class="modal-content">
      <div class="modal-body">
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        <div class="text-center mb-6">
          <h4 class="address-title mb-2">Create Subscription</h4>
          <p class="address-subtitle">Select a plan and set the billing window.</p>
        </div>
        <form class="row g-6" method="POST" action="{{ route('admin.subscriptions.store') }}">
          @csrf
          <div class="col-12">
            <label class="form-label">Plan</label>
            <div class="row">
              @foreach ($plans as $plan)
                @php
                  $planIcon = $plan->interval === 'yearly' ? 'tabler-crown' : 'tabler-credit-card';
                  $planLabel = $plan->interval === 'yearly' ? 'Yearly billing' : 'Monthly billing';
                  $priceLabel = number_format($plan->price_cents / 100, 2);
                @endphp
                <div class="col-md mb-md-0 mb-4">
                  <div class="form-check custom-option custom-option-icon">
                    <label class="form-check-label custom-option-content" for="planOption{{ $plan->id }}">
                      <span class="custom-option-body">
                        <span class="badge bg-label-primary p-2 me-3 rounded">
                          <i class="icon-base ti {{ $planIcon }} icon-30px"></i>
                        </span>
                        <span class="custom-option-title">{{ $plan->name }}</span>
                        <small> Billing window ({{ $planLabel }}) </small>
                        <small class="d-block text-body-secondary mt-1">{{ $plan->currency_code }} {{ $priceLabel }}</small>
                      </span>
                      <input
                        name="plan_id"
                        class="form-check-input"
                        type="radio"
                        value="{{ $plan->id }}"
                        id="planOption{{ $plan->id }}"
                        {{ $loop->first ? 'checked' : '' }} />
                    </label>
                  </div>
                </div>
              @endforeach
            </div>
          </div>
          <div class="col-12">
            <label class="form-label" for="subscriptionTenant">Tenant</label>
            <select id="subscriptionTenant" name="tenant_id" class="select2 form-select">
              @foreach ($tenants as $tenant)
                <option value="{{ $tenant->id }}">{{ $tenant->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-12">
            <label class="form-label" for="subscriptionStatus">Status</label>
            <select id="subscriptionStatus" name="status" class="form-select">
              <option value="active">Active</option>
              <option value="trialing">Trialing</option>
              <option value="past_due">Past Due</option>
              <option value="cancelled">Cancelled</option>
              <option value="expired">Expired</option>
            </select>
          </div>
          <div class="col-6">
            <label class="form-label" for="subscriptionStart">Period Start</label>
            <input type="text" id="subscriptionStart" name="current_period_start" class="form-control dob-picker" placeholder="YYYY-MM-DD" />
          </div>
          <div class="col-6">
            <label class="form-label" for="subscriptionEnd">Period End</label>
            <input type="text" id="subscriptionEnd" name="current_period_end" class="form-control dob-picker" placeholder="YYYY-MM-DD" />
          </div>
          <div class="col-6">
            <label class="form-label" for="subscriptionTrial">Trial Ends At</label>
            <input type="text" id="subscriptionTrial" name="trial_ends_at" class="form-control dob-picker" placeholder="YYYY-MM-DD" />
          </div>
          <div class="col-6">
            <label class="form-label" for="subscriptionProvider">Provider</label>
            <input type="text" id="subscriptionProvider" name="provider" class="form-control" placeholder="manual" />
          </div>
          <div class="col-12">
            <label class="form-label" for="subscriptionRef">Provider Reference</label>
            <input type="text" id="subscriptionRef" name="provider_ref" class="form-control" placeholder="INV-1001" />
          </div>
          <div class="col-12">
            <label class="form-label" for="subscriptionAuto">Auto Renew</label>
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" id="subscriptionAuto" name="auto_renew" value="1" />
              <label class="form-check-label" for="subscriptionAuto">Enabled</label>
            </div>
          </div>
          <div class="col-12 text-center mt-6">
            <button type="submit" class="btn btn-primary me-sm-4 me-1">Create Subscription</button>
            <button type="reset" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="editSubscriptionModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-simple">
    <div class="modal-content">
      <div class="modal-body">
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        <div class="text-center mb-6">
          <span class="badge bg-label-warning rounded-1 p-2 mb-3">
            <i class="icon-base ti tabler-edit icon-md"></i>
          </span>
          <h4 class="mb-1">Edit Subscription</h4>
          <p class="text-body-secondary mb-0">Manage status, period, and provider.</p>
        </div>
        <form class="row g-3" method="POST" id="editSubscriptionForm">
          @csrf
          @method('PATCH')
          <div class="col-12">
            <label class="form-label" for="editSubscriptionStatus">Status</label>
            <select id="editSubscriptionStatus" name="status" class="form-select">
              <option value="active">Active</option>
              <option value="trialing">Trialing</option>
              <option value="past_due">Past Due</option>
              <option value="cancelled">Cancelled</option>
              <option value="expired">Expired</option>
            </select>
          </div>
          <div class="col-6">
            <label class="form-label" for="editSubscriptionStart">Period Start</label>
            <input type="text" id="editSubscriptionStart" name="current_period_start" class="form-control dob-picker" placeholder="YYYY-MM-DD" />
          </div>
          <div class="col-6">
            <label class="form-label" for="editSubscriptionEnd">Period End</label>
            <input type="text" id="editSubscriptionEnd" name="current_period_end" class="form-control dob-picker" placeholder="YYYY-MM-DD" />
          </div>
          <div class="col-6">
            <label class="form-label" for="editSubscriptionTrial">Trial Ends At</label>
            <input type="text" id="editSubscriptionTrial" name="trial_ends_at" class="form-control dob-picker" placeholder="YYYY-MM-DD" />
          </div>
          <div class="col-6">
            <label class="form-label" for="editSubscriptionCancelled">Cancelled At</label>
            <input type="text" id="editSubscriptionCancelled" name="cancelled_at" class="form-control dob-picker" placeholder="YYYY-MM-DD" />
          </div>
          <div class="col-6">
            <label class="form-label" for="editSubscriptionProvider">Provider</label>
            <input type="text" id="editSubscriptionProvider" name="provider" class="form-control" />
          </div>
          <div class="col-6">
            <label class="form-label" for="editSubscriptionRef">Provider Reference</label>
            <input type="text" id="editSubscriptionRef" name="provider_ref" class="form-control" />
          </div>
          <div class="col-12">
            <label class="form-label" for="editSubscriptionAuto">Auto Renew</label>
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" id="editSubscriptionAuto" name="auto_renew" value="1" />
              <label class="form-check-label" for="editSubscriptionAuto">Enabled</label>
            </div>
          </div>
          <div class="col-12 text-center mt-6">
            <button type="submit" class="btn btn-primary me-sm-4 me-1">Update Subscription</button>
            <button type="reset" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

@endsection

@push('page-scripts')
  <script src="{{ asset('assets/assets') }}/vendor/libs/flatpickr/flatpickr.js"></script>
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
      const initDatePickers = (scope) => {
        if (!window.flatpickr) {
          return;
        }
        const inputs = (scope || document).querySelectorAll('.dob-picker');
        inputs.forEach((input) => {
          if (input._flatpickr) {
            input._flatpickr.destroy();
          }
          const modal = input.closest('.modal');
          flatpickr(input, {
            dateFormat: 'Y-m-d',
            allowInput: true,
            appendTo: modal || document.body
          });
        });
      };

      initDatePickers();

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

      initTable('.datatables-subscriptions', 'Search Subscription', '<i class="icon-base ti tabler-plus me-0 me-sm-1 icon-16px"></i><span class="d-none d-sm-inline-block">Add Subscription</span>', '#addSubscriptionModal');

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

      const editSubscriptionModal = document.getElementById('editSubscriptionModal');
      if (editSubscriptionModal) {
        editSubscriptionModal.addEventListener('show.bs.modal', function (event) {
          const trigger = event.relatedTarget;
          const subscriptionId = trigger.getAttribute('data-subscription-id');
          const status = trigger.getAttribute('data-subscription-status');
          const autoRenew = trigger.getAttribute('data-subscription-auto');
          const start = trigger.getAttribute('data-subscription-start');
          const end = trigger.getAttribute('data-subscription-end');
          const trial = trigger.getAttribute('data-subscription-trial');
          const cancelled = trigger.getAttribute('data-subscription-cancelled');
          const provider = trigger.getAttribute('data-subscription-provider');
          const providerRef = trigger.getAttribute('data-subscription-provider-ref');
          const form = document.getElementById('editSubscriptionForm');

          form.action = `{{ url('/admin/subscriptions') }}/${subscriptionId}`;
          document.getElementById('editSubscriptionStatus').value = status || 'active';
          document.getElementById('editSubscriptionAuto').checked = autoRenew === '1';
          document.getElementById('editSubscriptionStart').value = start || '';
          document.getElementById('editSubscriptionEnd').value = end || '';
          document.getElementById('editSubscriptionTrial').value = trial || '';
          document.getElementById('editSubscriptionCancelled').value = cancelled || '';
          document.getElementById('editSubscriptionProvider').value = provider || '';
          document.getElementById('editSubscriptionRef').value = providerRef || '';
        });
        editSubscriptionModal.addEventListener('shown.bs.modal', function () {
          initDatePickers(editSubscriptionModal);
        });
      }

      const addSubscriptionModal = document.getElementById('addSubscriptionModal');
      if (addSubscriptionModal) {
        addSubscriptionModal.addEventListener('shown.bs.modal', function () {
          initDatePickers(addSubscriptionModal);
        });
      }

    });
  </script>
@endpush

@extends('admin::components.layouts.master')
@section('title', 'Audit Logs | RoomGate Admin')
@section('page-title', 'Audit Logs')

@push('page-styles')
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/flatpickr/flatpickr.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/select2/select2.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/datatables-bs5/datatables.bootstrap5.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css" />
@endpush

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <h4 class="mb-4" data-i18n="audit.title">Audit Logs</h4>

  <div class="card mb-4">
    <div class="card-body">
      <form method="GET" class="row g-3 align-items-end">
        <div class="col-sm-6 col-lg-3">
          <label class="form-label" for="q" data-i18n="labels.search">Search</label>
          <input type="text" id="q" name="q" class="form-control" value="{{ request('q') }}" placeholder="Model, id, action" />
        </div>
        <div class="col-sm-6 col-lg-3">
          <label class="form-label" for="action" data-i18n="labels.action">Action</label>
          <select id="action" name="action" class="select2 form-select" data-allow-clear="true">
            <option value="">All</option>
            @foreach (['created', 'updated', 'deleted', 'restored'] as $action)
              <option value="{{ $action }}" @selected(request('action') === $action)>{{ ucfirst($action) }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-sm-6 col-lg-3">
          <label class="form-label" for="model_type" data-i18n="labels.model">Model</label>
          <select id="model_type" name="model_type" class="select2 form-select" data-allow-clear="true">
            <option value="">All</option>
            @foreach ($models as $model)
              <option value="{{ $model }}" @selected(request('model_type') === $model)>{{ $model }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-sm-6 col-lg-3">
          <label class="form-label" for="user_id" data-i18n="labels.user">User</label>
          <select id="user_id" name="user_id" class="select2 form-select" data-allow-clear="true">
            <option value="">All</option>
            @foreach ($users as $user)
              <option value="{{ $user->id }}" @selected((string) request('user_id') === (string) $user->id)>
                {{ $user->name }} ({{ $user->email }})
              </option>
            @endforeach
          </select>
        </div>
        <div class="col-sm-6 col-lg-3">
          <label class="form-label" for="from" data-i18n="labels.from">From</label>
          <input
            type="text"
            id="from"
            name="from"
            class="form-control dob-picker"
            placeholder="YYYY-MM-DD"
            value="{{ request('from') }}" />
        </div>
        <div class="col-sm-6 col-lg-3">
          <label class="form-label" for="to" data-i18n="labels.to">To</label>
          <input
            type="text"
            id="to"
            name="to"
            class="form-control dob-picker"
            placeholder="YYYY-MM-DD"
            value="{{ request('to') }}" />
        </div>
        <div class="col-12">
          <button class="btn btn-primary me-2" type="submit" data-i18n="actions.filter">Filter</button>
          <a href="{{ route('admin.audit-logs') }}" class="btn btn-label-secondary" data-i18n="actions.reset">Reset</a>
        </div>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-datatable table-responsive">
      <table class="datatables-audit table border-top">
        <thead>
          <tr>
            <th></th>
            <th></th>
            <th>When</th>
            <th>Action</th>
            <th>Model</th>
            <th>Record</th>
            <th>By</th>
            <th>IP</th>
            <th>Changes</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($logs as $log)
            <tr>
              <td></td>
              <td></td>
              <td>{{ $log->created_at?->format('Y-m-d H:i') }}</td>
              <td>{{ ucfirst($log->action) }}</td>
              <td>{{ $log->model_type }}</td>
              <td>{{ $log->model_id }}</td>
              <td>{{ $log->user?->name ?? 'System' }}</td>
              <td>{{ $log->ip_address }}</td>
              <td>
                <div class="d-inline-flex align-items-center gap-2">
                  <button
                    class="btn btn-sm btn-label-secondary"
                    data-bs-toggle="modal"
                    data-bs-target="#auditLogModal"
                    data-before='@json($log->before_json)'
                    data-after='@json($log->after_json)'>
                    View
                  </button>
                  @if ($log->action === 'deleted' && $log->can_restore)
                    <form method="POST" action="{{ route('admin.audit-logs.restore', $log) }}" data-confirm="Restore this record?">
                      @csrf
                      <button class="btn btn-sm btn-primary" type="submit" {{ $log->can_restore ? '' : 'disabled' }}>
                        Restore
                      </button>
                    </form>
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="9" class="text-center text-body-secondary">No audit logs found.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="modal fade" id="auditLogModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-body">
        <button type="button" class="btn-close btn-pinned" data-bs-dismiss="modal" aria-label="Close"></button>
        <h5 class="mb-4">Change Details</h5>
        <div class="row g-4">
          <div class="col-md-6">
            <h6>Before</h6>
            <pre class="bg-body-secondary p-3 rounded-2" id="auditLogBefore"></pre>
          </div>
          <div class="col-md-6">
            <h6>After</h6>
            <pre class="bg-body-secondary p-3 rounded-2" id="auditLogAfter"></pre>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('page-scripts')
<script src="{{ asset('assets/assets') }}/vendor/libs/select2/select2.js"></script>
<script src="{{ asset('assets/assets') }}/vendor/libs/flatpickr/flatpickr.js"></script>
<script src="{{ asset('assets/assets') }}/vendor/libs/datatables-bs5/datatables-bootstrap5.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
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
    if (window.flatpickr) {
      flatpickr('.dob-picker', { dateFormat: 'Y-m-d' });
    }

    const table = document.querySelector('.datatables-audit');
    if (table && window.DataTable) {
      new DataTable(table, {
        order: [[2, 'desc']],
        layout: {
          topStart: {
            rowClass: 'row m-3 my-0 justify-content-between',
            features: [
              {
                pageLength: {
                  menu: [10, 25, 50, 100],
                  text: 'Show _MENU_'
                }
              }
            ]
          },
          topEnd: {
            features: [
              {
                search: {
                  placeholder: 'Search Logs',
                  text: '_INPUT_'
                }
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
                return 'Audit Log Details';
              }
            }),
            type: 'column'
          }
        }
      });
    }

    setTimeout(() => {
      const elementsToModify = [
        { selector: '.dt-buttons .btn', classToRemove: 'btn-secondary' },
        { selector: '.dt-search', classToAdd: 'me-4' },
        { selector: '.dt-search .form-control', classToRemove: 'form-control-sm' },
        { selector: '.dt-length', classToAdd: 'mb-0 mb-md-5' },
        { selector: '.dt-length .form-select', classToRemove: 'form-select-sm' },
        { selector: '.dt-layout-start', classToAdd: 'mt-0 px-5' },
        {
          selector: '.dt-layout-end',
          classToRemove: 'justify-content-between',
          classToAdd: 'justify-content-md-between justify-content-center d-flex flex-wrap gap-md-4 mb-sm-0 mb-6 mt-0'
        },
        { selector: '.dt-layout-start', classToAdd: 'mt-0' },
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
  });
</script>
<script>
  const auditLogModal = document.getElementById('auditLogModal');
  if (auditLogModal) {
    auditLogModal.addEventListener('show.bs.modal', function (event) {
      const trigger = event.relatedTarget;
      const beforeJson = trigger.getAttribute('data-before');
      const afterJson = trigger.getAttribute('data-after');
      const before = beforeJson ? JSON.parse(beforeJson) : null;
      const after = afterJson ? JSON.parse(afterJson) : null;

      document.getElementById('auditLogBefore').textContent = before ? JSON.stringify(before, null, 2) : '—';
      document.getElementById('auditLogAfter').textContent = after ? JSON.stringify(after, null, 2) : '—';
    });
  }
</script>
@endpush

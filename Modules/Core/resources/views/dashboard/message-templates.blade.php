@extends('core::components.layouts.master')
@section('title', 'Message Templates | RoomGate')
@section('page-title', 'Message Templates')

@push('page-styles')
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/datatables-bs5/datatables.bootstrap5.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/sweetalert2/sweetalert2.css" />
@endpush

@section('content')
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="mb-0">Message Templates</h4>
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#templateCreateModal">
        <i class="icon-base ti tabler-plus me-2"></i>New Template
      </button>
    </div>

    <div class="card">
      <div class="card-datatable table-responsive">
        <table class="datatables-templates table border-top">
          <thead>
            <tr>
              <th></th>
              <th>Key</th>
              <th>Name</th>
              <th>Channel</th>
              <th>Status</th>
              <th>Updated</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            @foreach ($templates as $template)
              <tr>
                <td></td>
                <td>{{ $template->key }}</td>
                <td>{{ $template->name }}</td>
                <td>{{ strtoupper($template->channel) }}</td>
                <td>
                  <span class="badge bg-label-{{ $template->is_active ? 'success' : 'secondary' }}">
                    {{ $template->is_active ? 'Active' : 'Inactive' }}
                  </span>
                </td>
                <td>{{ optional($template->updated_at)->format('Y-m-d H:i') }}</td>
                <td>
                  <div class="d-flex gap-2">
                    <button
                      class="btn btn-sm btn-label-secondary"
                      data-bs-toggle="modal"
                      data-bs-target="#templateTestModal"
                      data-id="{{ $template->id }}"
                      data-name="{{ $template->name }}">
                      Test
                    </button>
                    <button
                      class="btn btn-sm btn-label-primary"
                      data-bs-toggle="modal"
                      data-bs-target="#templateEditModal"
                      data-id="{{ $template->id }}"
                      data-key="{{ $template->key }}"
                      data-name="{{ $template->name }}"
                      data-channel="{{ $template->channel }}"
                      data-subject="{{ $template->subject }}"
                      data-body="{{ $template->body }}"
                      data-active="{{ $template->is_active ? '1' : '0' }}">
                      Edit
                    </button>
                    <form method="POST" action="{{ route('core.templates.destroy', ['tenant' => request()->route('tenant'), 'template' => $template]) }}" data-confirm="Delete this template?">
                      @csrf
                      @method('DELETE')
                      <button class="btn btn-sm btn-label-danger" type="submit">Delete</button>
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

  <div class="modal fade" id="templateCreateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <form method="POST" action="{{ route('core.templates.store', ['tenant' => request()->route('tenant')]) }}">
          @csrf
          <div class="modal-header">
            <h5 class="modal-title">New Template</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label" for="template_key">Key</label>
                <input type="text" class="form-control" id="template_key" name="key" required />
              </div>
              <div class="col-md-6">
                <label class="form-label" for="template_name">Name</label>
                <input type="text" class="form-control" id="template_name" name="name" required />
              </div>
              <div class="col-md-4">
                <label class="form-label" for="template_channel">Channel</label>
                <select class="form-select" id="template_channel" name="channel" required>
                  <option value="email">Email</option>
                  <option value="sms">SMS</option>
                  <option value="whatsapp">WhatsApp</option>
                  <option value="push">Push</option>
                </select>
              </div>
              <div class="col-md-8">
                <label class="form-label" for="template_subject">Subject</label>
                <input type="text" class="form-control" id="template_subject" name="subject" />
              </div>
              <div class="col-12">
                <label class="form-label" for="template_body">Body</label>
                <textarea class="form-control" id="template_body" name="body" rows="6" required></textarea>
              </div>
              <div class="col-12">
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" id="template_active" name="is_active" value="1" checked />
                  <label class="form-check-label" for="template_active">Active</label>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button class="btn btn-label-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
            <button class="btn btn-primary" type="submit">Save</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="modal fade" id="templateEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <form method="POST" id="templateEditForm">
          @csrf
          @method('PATCH')
          <div class="modal-header">
            <h5 class="modal-title">Edit Template</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Key</label>
                <input type="text" class="form-control" id="edit_key" disabled />
              </div>
              <div class="col-md-6">
                <label class="form-label" for="edit_name">Name</label>
                <input type="text" class="form-control" id="edit_name" name="name" required />
              </div>
              <div class="col-md-4">
                <label class="form-label" for="edit_channel">Channel</label>
                <select class="form-select" id="edit_channel" name="channel" required>
                  <option value="email">Email</option>
                  <option value="sms">SMS</option>
                  <option value="whatsapp">WhatsApp</option>
                  <option value="push">Push</option>
                </select>
              </div>
              <div class="col-md-8">
                <label class="form-label" for="edit_subject">Subject</label>
                <input type="text" class="form-control" id="edit_subject" name="subject" />
              </div>
              <div class="col-12">
                <label class="form-label" for="edit_body">Body</label>
                <textarea class="form-control" id="edit_body" name="body" rows="6" required></textarea>
              </div>
              <div class="col-12">
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" id="edit_active" name="is_active" value="1" />
                  <label class="form-check-label" for="edit_active">Active</label>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button class="btn btn-label-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
            <button class="btn btn-primary" type="submit">Save Changes</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="modal fade" id="templateTestModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form method="POST" id="templateTestForm">
          @csrf
          <div class="modal-header">
            <h5 class="modal-title">Send Test</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Recipient Email</label>
              <input type="email" name="email" class="form-control" required />
              <small class="text-body-secondary">A test message will be queued.</small>
            </div>
          </div>
          <div class="modal-footer">
            <button class="btn btn-label-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
            <button class="btn btn-primary" type="submit">Send Test</button>
          </div>
        </form>
      </div>
    </div>
  </div>
@endsection

@push('page-scripts')
  <script src="{{ asset('assets/assets') }}/vendor/libs/datatables-bs5/datatables-bootstrap5.js"></script>
  <script src="{{ asset('assets/assets') }}/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const table = document.querySelector('.datatables-templates');
      if (table && window.DataTable) {
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
          layout: window.RoomGateDataTables?.layout,
          language: window.RoomGateDataTables?.language,
          responsive: {
            details: {
              display: DataTable.Responsive.display.modal({
                header: function () {
                  return 'Template Details';
                }
              }),
              type: 'column'
            }
          }
        });
      }

      const editModal = document.getElementById('templateEditModal');
      if (editModal) {
        editModal.addEventListener('show.bs.modal', function (event) {
          const button = event.relatedTarget;
          const id = button.getAttribute('data-id');
          const form = document.getElementById('templateEditForm');
          form.action = `{{ route('core.templates.update', ['tenant' => request()->route('tenant'), 'template' => 'TEMPLATE_ID']) }}`.replace('TEMPLATE_ID', id);
          document.getElementById('edit_key').value = button.getAttribute('data-key');
          document.getElementById('edit_name').value = button.getAttribute('data-name');
          document.getElementById('edit_channel').value = button.getAttribute('data-channel');
          document.getElementById('edit_subject').value = button.getAttribute('data-subject') || '';
          document.getElementById('edit_body').value = button.getAttribute('data-body') || '';
          document.getElementById('edit_active').checked = button.getAttribute('data-active') === '1';
        });
      }

      const testModal = document.getElementById('templateTestModal');
      if (testModal) {
        testModal.addEventListener('show.bs.modal', function (event) {
          const button = event.relatedTarget;
          const id = button.getAttribute('data-id');
          const form = document.getElementById('templateTestForm');
          form.action = `{{ route('core.templates.test', ['tenant' => request()->route('tenant'), 'template' => 'TEMPLATE_ID']) }}`.replace('TEMPLATE_ID', id);
        });
      }
    });
  </script>
@endpush

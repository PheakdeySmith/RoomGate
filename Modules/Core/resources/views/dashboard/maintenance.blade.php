@extends('core::components.layouts.master')
@section('title', 'Maintenance | RoomGate')
@section('page-title', 'Maintenance Requests')

@push('page-styles')
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/datatables-bs5/datatables.bootstrap5.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css" />
@endpush

@section('content')
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="mb-0">Maintenance Requests</h4>
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#maintenanceCreateModal">
        <i class="icon-base ti tabler-plus me-2"></i>New Request
      </button>
    </div>

    <div class="card">
      <div class="card-datatable table-responsive">
        <table class="datatables-maintenance table border-top">
          <thead>
            <tr>
              <th></th>
              <th>ID</th>
              <th>Title</th>
              <th>Status</th>
              <th>Priority</th>
              <th>Assigned</th>
              <th>Requested</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            @foreach ($requests as $item)
              <tr>
                <td></td>
                <td>
                  <a href="{{ route('core.maintenance.show', ['tenant' => request()->route('tenant'), 'maintenanceRequest' => $item]) }}">
                    #{{ $item->id }}
                  </a>
                </td>
                <td>
                  <a href="{{ route('core.maintenance.show', ['tenant' => request()->route('tenant'), 'maintenanceRequest' => $item]) }}">
                    {{ $item->title }}
                  </a>
                </td>
                <td>{{ ucfirst(str_replace('_', ' ', $item->status)) }}</td>
                <td>{{ ucfirst($item->priority) }}</td>
                <td>{{ $item->assignedTo?->name ?? '-' }}</td>
                <td>{{ optional($item->requested_at)->format('Y-m-d H:i') }}</td>
                <td>
                  <div class="d-flex gap-2">
                    <button
                      class="btn btn-sm btn-label-primary"
                      data-bs-toggle="modal"
                      data-bs-target="#maintenanceStatusModal"
                      data-id="{{ $item->id }}"
                      data-status="{{ $item->status }}">
                      Status
                    </button>
                    <button
                      class="btn btn-sm btn-label-secondary"
                      data-bs-toggle="modal"
                      data-bs-target="#maintenanceCommentModal"
                      data-id="{{ $item->id }}">
                      Comment
                    </button>
                    <button
                      class="btn btn-sm btn-label-info"
                      data-bs-toggle="modal"
                      data-bs-target="#maintenanceAttachModal"
                      data-id="{{ $item->id }}">
                      Attach
                    </button>
                  </div>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="modal fade" id="maintenanceCreateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <form method="POST" action="{{ route('core.maintenance.store', ['tenant' => request()->route('tenant')]) }}">
          @csrf
          <div class="modal-header">
            <h5 class="modal-title">New Maintenance Request</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Title</label>
                <input type="text" class="form-control" name="title" required />
              </div>
              <div class="col-md-6">
                <label class="form-label">Priority</label>
                <select class="form-select" name="priority" required>
                  <option value="low">Low</option>
                  <option value="medium" selected>Medium</option>
                  <option value="high">High</option>
                  <option value="urgent">Urgent</option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label">Property</label>
                <select class="form-select" name="property_id">
                  <option value="">Select</option>
                  @foreach ($properties as $property)
                    <option value="{{ $property->id }}">{{ $property->name }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label">Room</label>
                <select class="form-select" name="room_id">
                  <option value="">Select</option>
                  @foreach ($rooms as $room)
                    <option value="{{ $room->id }}">{{ $room->room_number }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label">Assign To</label>
                <select class="form-select" name="assigned_to_user_id">
                  <option value="">Unassigned</option>
                  @foreach ($members as $member)
                    <option value="{{ $member->id }}">{{ $member->name }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-12">
                <label class="form-label">Description</label>
                <textarea class="form-control" name="description" rows="4"></textarea>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button class="btn btn-label-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
            <button class="btn btn-primary" type="submit">Create</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="modal fade" id="maintenanceStatusModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form method="POST" id="maintenanceStatusForm">
          @csrf
          <div class="modal-header">
            <h5 class="modal-title">Update Status</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Status</label>
              <select class="form-select" name="status" id="maintenanceStatusSelect">
                <option value="open">Open</option>
                <option value="in_progress">In Progress</option>
                <option value="resolved">Resolved</option>
                <option value="closed">Closed</option>
                <option value="cancelled">Cancelled</option>
              </select>
            </div>
            <div>
              <label class="form-label">Note</label>
              <input type="text" class="form-control" name="note" />
            </div>
          </div>
          <div class="modal-footer">
            <button class="btn btn-label-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
            <button class="btn btn-primary" type="submit">Update</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="modal fade" id="maintenanceCommentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form method="POST" id="maintenanceCommentForm">
          @csrf
          <div class="modal-header">
            <h5 class="modal-title">Add Comment</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Comment</label>
              <textarea class="form-control" name="body" rows="4" required></textarea>
            </div>
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" name="is_internal" value="1" id="commentInternal">
              <label class="form-check-label" for="commentInternal">Internal only</label>
            </div>
          </div>
          <div class="modal-footer">
            <button class="btn btn-label-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
            <button class="btn btn-primary" type="submit">Add</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="modal fade" id="maintenanceAttachModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form method="POST" id="maintenanceAttachForm" enctype="multipart/form-data">
          @csrf
          <div class="modal-header">
            <h5 class="modal-title">Upload Attachment</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">File</label>
              <input class="form-control" type="file" name="file" required />
            </div>
            <div class="mb-3">
              <label class="form-label">Comment ID (optional)</label>
              <input class="form-control" type="number" name="comment_id" />
            </div>
          </div>
          <div class="modal-footer">
            <button class="btn btn-label-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
            <button class="btn btn-primary" type="submit">Upload</button>
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
      const table = document.querySelector('.datatables-maintenance');
      if (table && window.DataTable) {
        new DataTable(table, {
          order: [[1, 'desc']],
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
                  return 'Maintenance Details';
                }
              }),
              type: 'column'
            }
          }
        });
      }

      const statusModal = document.getElementById('maintenanceStatusModal');
      statusModal?.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const id = button.getAttribute('data-id');
        const status = button.getAttribute('data-status');
        const form = document.getElementById('maintenanceStatusForm');
        form.action = `{{ route('core.maintenance.status', ['tenant' => request()->route('tenant'), 'maintenanceRequest' => 'REQ_ID']) }}`.replace('REQ_ID', id);
        document.getElementById('maintenanceStatusSelect').value = status;
      });

      const commentModal = document.getElementById('maintenanceCommentModal');
      commentModal?.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const id = button.getAttribute('data-id');
        const form = document.getElementById('maintenanceCommentForm');
        form.action = `{{ route('core.maintenance.comments.store', ['tenant' => request()->route('tenant'), 'maintenanceRequest' => 'REQ_ID']) }}`.replace('REQ_ID', id);
      });

      const attachModal = document.getElementById('maintenanceAttachModal');
      attachModal?.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const id = button.getAttribute('data-id');
        const form = document.getElementById('maintenanceAttachForm');
        form.action = `{{ route('core.maintenance.attachments.store', ['tenant' => request()->route('tenant'), 'maintenanceRequest' => 'REQ_ID']) }}`.replace('REQ_ID', id);
      });
    });
  </script>
@endpush

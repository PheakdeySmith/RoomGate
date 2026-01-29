@extends('core::components.layouts.master')
@section('title', 'Maintenance Request | RoomGate')
@section('page-title', 'Maintenance Request')

@push('page-styles')
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/datatables-bs5/datatables.bootstrap5.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css" />
@endpush

@section('content')
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h4 class="mb-1">Request #{{ $requestModel->id }}</h4>
        <p class="text-body-secondary mb-0">{{ $requestModel->title }}</p>
      </div>
      <a href="{{ route('core.maintenance.index', ['tenant' => $tenant->slug]) }}" class="btn btn-label-secondary">
        Back
      </a>
    </div>

    <div class="row">
      <div class="col-lg-4 mb-4">
        <div class="card">
          <div class="card-header">
            <h5 class="card-title mb-0">Details</h5>
          </div>
          <div class="card-body">
            <div class="mb-2 d-flex justify-content-between">
              <span>Status</span>
              <span class="badge bg-label-primary">{{ ucfirst(str_replace('_', ' ', $requestModel->status)) }}</span>
            </div>
            <div class="mb-2 d-flex justify-content-between">
              <span>Priority</span>
              <span>{{ ucfirst($requestModel->priority) }}</span>
            </div>
            <div class="mb-2 d-flex justify-content-between">
              <span>Category</span>
              <span>{{ $requestModel->category }}</span>
            </div>
            <div class="mb-2 d-flex justify-content-between">
              <span>Property</span>
              <span>{{ $requestModel->property?->name ?? '-' }}</span>
            </div>
            <div class="mb-2 d-flex justify-content-between">
              <span>Room</span>
              <span>{{ $requestModel->room?->room_number ?? '-' }}</span>
            </div>
            <div class="mb-2 d-flex justify-content-between">
              <span>Assigned</span>
              <span>{{ $requestModel->assignedTo?->name ?? '-' }}</span>
            </div>
            <div class="mb-2 d-flex justify-content-between">
              <span>Requested</span>
              <span>{{ optional($requestModel->requested_at)->format('Y-m-d H:i') }}</span>
            </div>
            <div class="mb-2">
              <span class="d-block text-body-secondary">Description</span>
              <p class="mb-0">{{ $requestModel->description ?? '-' }}</p>
            </div>
          </div>
        </div>

        <div class="card mt-4">
          <div class="card-header">
            <h5 class="card-title mb-0">Update Status</h5>
          </div>
          <div class="card-body">
            <form method="POST" action="{{ route('core.maintenance.status', ['tenant' => $tenant->slug, 'maintenanceRequest' => $requestModel]) }}">
              @csrf
              <div class="mb-3">
                <label class="form-label">Status</label>
                <select class="form-select" name="status">
                  @foreach (['open', 'in_progress', 'resolved', 'closed', 'cancelled'] as $status)
                    <option value="{{ $status }}" @selected($requestModel->status === $status)>
                      {{ ucfirst(str_replace('_', ' ', $status)) }}
                    </option>
                  @endforeach
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label">Note</label>
                <input type="text" class="form-control" name="note" />
              </div>
              <button class="btn btn-primary w-100" type="submit">Update</button>
            </form>
          </div>
        </div>
      </div>

      <div class="col-lg-8">
        <div class="card mb-4">
          <div class="card-header">
            <h5 class="card-title mb-0">Status History</h5>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table mb-0">
                <thead>
                  <tr>
                    <th>When</th>
                    <th>From</th>
                    <th>To</th>
                    <th>By</th>
                    <th>Note</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse ($requestModel->statusEvents as $event)
                    <tr>
                      <td>{{ optional($event->created_at)->format('Y-m-d H:i') }}</td>
                      <td>{{ $event->from_status ?? '-' }}</td>
                      <td>{{ $event->to_status }}</td>
                      <td>{{ $event->changedBy?->name ?? '-' }}</td>
                      <td>{{ $event->note ?? '-' }}</td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="5" class="text-center text-body-secondary">No status changes yet.</td>
                    </tr>
                  @endforelse
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div class="card mb-4">
          <div class="card-header">
            <h5 class="card-title mb-0">Comments</h5>
          </div>
          <div class="card-body">
            <form method="POST" action="{{ route('core.maintenance.comments.store', ['tenant' => $tenant->slug, 'maintenanceRequest' => $requestModel]) }}" class="mb-4">
              @csrf
              <div class="mb-3">
                <textarea class="form-control" name="body" rows="3" placeholder="Add a comment" required></textarea>
              </div>
              <div class="d-flex justify-content-between align-items-center">
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" name="is_internal" value="1" id="commentInternal">
                  <label class="form-check-label" for="commentInternal">Internal only</label>
                </div>
                <button class="btn btn-primary" type="submit">Post</button>
              </div>
            </form>

            @forelse ($requestModel->comments as $comment)
              <div class="border rounded p-3 mb-3">
                <div class="d-flex justify-content-between">
                  <strong>{{ $comment->user?->name ?? 'User' }}</strong>
                  <small class="text-body-secondary">{{ optional($comment->created_at)->format('Y-m-d H:i') }}</small>
                </div>
                <p class="mb-2">{{ $comment->body }}</p>
                @if ($comment->is_internal)
                  <span class="badge bg-label-secondary">Internal</span>
                @endif
              </div>
            @empty
              <p class="text-body-secondary mb-0">No comments yet.</p>
            @endforelse
          </div>
        </div>

        <div class="card mb-4">
          <div class="card-header">
            <h5 class="card-title mb-0">Attachments</h5>
          </div>
          <div class="card-body">
            <form method="POST" action="{{ route('core.maintenance.attachments.store', ['tenant' => $tenant->slug, 'maintenanceRequest' => $requestModel]) }}" enctype="multipart/form-data" class="mb-4">
              @csrf
              <div class="row g-3">
                <div class="col-md-8">
                  <input class="form-control" type="file" name="file" required />
                </div>
                <div class="col-md-4">
                  <button class="btn btn-primary w-100" type="submit">Upload</button>
                </div>
              </div>
            </form>

            <div class="table-responsive">
              <table class="table mb-0">
                <thead>
                  <tr>
                    <th>File</th>
                    <th>Size</th>
                    <th>Uploaded</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  @forelse ($requestModel->attachments as $attachment)
                    <tr>
                      <td>{{ basename($attachment->file_path) }}</td>
                      <td>{{ $attachment->file_size_bytes ? number_format($attachment->file_size_bytes / 1024, 1) . ' KB' : '-' }}</td>
                      <td>{{ optional($attachment->created_at)->format('Y-m-d H:i') }}</td>
                      <td>
                        <a class="btn btn-sm btn-label-primary" href="{{ route('core.maintenance.attachments.show', ['tenant' => $tenant->slug, 'attachment' => $attachment]) }}">
                          Download
                        </a>
                      </td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="4" class="text-center text-body-secondary">No attachments yet.</td>
                    </tr>
                  @endforelse
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div class="card mb-4">
          <div class="card-header">
            <h5 class="card-title mb-0">Work Orders</h5>
          </div>
          <div class="card-body">
            <form method="POST" action="{{ route('core.maintenance.work-orders.store', ['tenant' => $tenant->slug, 'maintenanceRequest' => $requestModel]) }}" class="mb-4">
              @csrf
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">Vendor</label>
                  <input class="form-control" type="text" name="vendor_name" />
                </div>
                <div class="col-md-6">
                  <label class="form-label">Scheduled For</label>
                  <input class="form-control" type="datetime-local" name="scheduled_for" />
                </div>
                <div class="col-md-4">
                  <label class="form-label">Status</label>
                  <select class="form-select" name="status">
                    @foreach (['created', 'scheduled', 'in_progress', 'completed', 'cancelled'] as $status)
                      <option value="{{ $status }}">{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Cost (cents)</label>
                  <input class="form-control" type="number" name="cost_cents" min="0" />
                </div>
                <div class="col-md-4">
                  <label class="form-label">Currency</label>
                  <input class="form-control" type="text" name="currency_code" value="{{ $tenant->default_currency ?? 'USD' }}" />
                </div>
                <div class="col-12">
                  <label class="form-label">Notes</label>
                  <textarea class="form-control" name="notes" rows="2"></textarea>
                </div>
                <div class="col-12 text-end">
                  <button class="btn btn-primary" type="submit">Create Work Order</button>
                </div>
              </div>
            </form>

            <div class="table-responsive">
              <table class="table mb-0">
                <thead>
                  <tr>
                    <th>Vendor</th>
                    <th>Status</th>
                    <th>Scheduled</th>
                    <th>Cost</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse ($requestModel->workOrders as $workOrder)
                    <tr>
                      <td>{{ $workOrder->vendor_name ?? '-' }}</td>
                      <td>{{ ucfirst(str_replace('_', ' ', $workOrder->status)) }}</td>
                      <td>{{ optional($workOrder->scheduled_for)->format('Y-m-d H:i') ?? '-' }}</td>
                      <td>{{ $workOrder->cost_cents ? number_format($workOrder->cost_cents / 100, 2) . ' ' . ($workOrder->currency_code ?? 'USD') : '-' }}</td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="4" class="text-center text-body-secondary">No work orders yet.</td>
                    </tr>
                  @endforelse
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection

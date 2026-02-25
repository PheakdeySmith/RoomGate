@extends('admin::components.layouts.master')
@section('title', 'Ops Tooling | RoomGate Admin')
@section('page-title', 'Ops Tooling')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="row g-6 mb-6">
    <div class="col-md-6 col-xl-3">
      <div class="card h-100">
        <div class="card-body">
          <h6 class="mb-1">Queue Connection</h6>
          <h4 class="mb-0 text-capitalize">{{ $queueConnection }}</h4>
        </div>
      </div>
    </div>
    <div class="col-md-6 col-xl-3">
      <div class="card h-100">
        <div class="card-body">
          <h6 class="mb-1">Cache Store</h6>
          <h4 class="mb-0 text-capitalize">{{ $cacheStore }}</h4>
        </div>
      </div>
    </div>
    <div class="col-md-6 col-xl-3">
      <div class="card h-100">
        <div class="card-body">
          <h6 class="mb-1">Failed Jobs</h6>
          <h4 class="mb-0">{{ $failedJobsCount }}</h4>
        </div>
      </div>
    </div>
    <div class="col-md-6 col-xl-3">
      <div class="card h-100">
        <div class="card-body">
          <h6 class="mb-1">Queued Jobs</h6>
          <h4 class="mb-0">{{ $jobsCount }}</h4>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-6">
    <div class="col-xl-6">
      <div class="card h-100">
        <h5 class="card-header">System Health</h5>
        <div class="card-body">
          <ul class="list-unstyled mb-0">
            <li class="mb-2">Environment: <span class="fw-medium">{{ $healthChecks['app_env'] }}</span></li>
            <li class="mb-2">Database: <span class="badge {{ $healthChecks['db_ok'] ? 'bg-label-success' : 'bg-label-danger' }}">{{ $healthChecks['db_ok'] ? 'OK' : 'Down' }}</span></li>
            <li class="mb-2">Cache: <span class="badge {{ $healthChecks['cache_ok'] ? 'bg-label-success' : 'bg-label-danger' }}">{{ $healthChecks['cache_ok'] ? 'OK' : 'Down' }}</span></li>
            <li class="mb-2">DB Backup: <span class="fw-medium">{{ $latestDbBackup['name'] ?? 'Not found' }}</span></li>
            <li>Upload Backup: <span class="fw-medium">{{ $latestUploadBackup['name'] ?? 'Not found' }}</span></li>
          </ul>
          <div class="mt-4">
            <form method="POST" action="{{ route('admin.ops-tooling.failed-jobs.retry') }}">
              @csrf
              <button type="submit" class="btn btn-primary">Retry Failed Jobs</button>
            </form>
          </div>
        </div>
      </div>
    </div>

    <div class="col-xl-6">
      <div class="card h-100">
        <h5 class="card-header">Recent Webhooks</h5>
        <div class="table-responsive text-nowrap">
          <table class="table">
            <thead>
              <tr>
                <th>Provider</th>
                <th>Type</th>
                <th>Status</th>
                <th>Received</th>
                <th>Replay</th>
              </tr>
            </thead>
            <tbody>
              @forelse($webhookEvents as $event)
                <tr>
                  <td class="text-capitalize">{{ $event->provider }}</td>
                  <td>{{ $event->event_type }}</td>
                  <td><span class="badge bg-label-secondary text-capitalize">{{ $event->status }}</span></td>
                  <td>{{ optional($event->received_at)->format('Y-m-d H:i') ?: '-' }}</td>
                  <td>
                    <form method="POST" action="{{ route('admin.ops-tooling.webhooks.replay', $event) }}">
                      @csrf
                      <button type="submit" class="btn btn-sm btn-label-primary">Replay</button>
                    </form>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="5" class="text-center text-body-secondary">No webhook events found.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

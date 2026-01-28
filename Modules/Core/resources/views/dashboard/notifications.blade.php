@extends('core::components.layouts.master')
@section('title', 'Notifications | RoomGate')
@section('page-title', 'Notifications')

@section('content')
@php
  $typeStyles = [
      'success' => 'bg-label-success',
      'warning' => 'bg-label-warning',
      'danger' => 'bg-label-danger',
      'error' => 'bg-label-danger',
      'info' => 'bg-label-primary',
  ];
@endphp
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
      <h5 class="mb-0">Notifications</h5>
      <form method="POST" action="{{ route('core.notifications.mark-all-read') }}">
        @csrf
        <button type="submit" class="btn btn-label-secondary">Mark all as read</button>
      </form>
    </div>
    <div class="card-body">
      <div class="list-group list-group-flush">
        @forelse ($notifications as $notification)
          @php
            $badgeClass = $typeStyles[$notification->type ?? 'info'] ?? 'bg-label-primary';
            $icon = $notification->icon ?: 'tabler-bell';
          @endphp
          <div class="list-group-item d-flex align-items-start gap-3 {{ $notification->read_at ? 'opacity-75' : '' }}">
            <div class="avatar">
              <span class="avatar-initial rounded-circle {{ $badgeClass }}">
                <i class="icon-base ti {{ $icon }}"></i>
              </span>
            </div>
            <div class="flex-grow-1">
              <div class="d-flex justify-content-between">
                <h6 class="mb-1">{{ $notification->title }}</h6>
                <small class="text-body-secondary">{{ $notification->created_at?->diffForHumans() }}</small>
              </div>
              <p class="mb-2 text-body">{{ $notification->body }}</p>
              <div class="d-flex gap-2">
                @if ($notification->link_url)
                  <a href="{{ $notification->link_url }}" class="btn btn-sm btn-outline-primary">Open</a>
                @endif
                <form method="POST" action="{{ route('core.notifications.mark-read', $notification) }}">
                  @csrf
                  <button type="submit" class="btn btn-sm btn-outline-secondary">Mark read</button>
                </form>
              </div>
            </div>
          </div>
        @empty
          <div class="text-center text-body-secondary py-4">No notifications yet.</div>
        @endforelse
      </div>
    </div>
    @if (method_exists($notifications, 'links'))
      <div class="card-footer">
        {{ $notifications->links() }}
      </div>
    @endif
  </div>
</div>
@endsection

@php
  $tenantStatus = [
      'active' => 'bg-label-success',
      'suspended' => 'bg-label-warning',
      'closed' => 'bg-label-secondary',
  ];
  $statusClass = $tenantStatus[$tenant->status] ?? 'bg-label-secondary';
  $subscriptionEnd = $subscription?->current_period_end;
  $periodTotal = $subscription?->current_period_start && $subscriptionEnd
      ? $subscription->current_period_start->diffInDays($subscriptionEnd)
      : null;
  $periodUsed = $subscription?->current_period_start
      ? $subscription->current_period_start->diffInDays(now())
      : null;
  $periodProgress = ($periodTotal && $periodUsed !== null)
      ? min(max((int) round(($periodUsed / max($periodTotal, 1)) * 100), 0), 100)
      : 0;
@endphp

<div class="col-xl-4 col-lg-5 order-1 order-md-0">
  <div class="card mb-6">
    <div class="card-body pt-12">
      <div class="user-avatar-section">
        <div class="d-flex align-items-center flex-column">
          <div class="avatar avatar-xl mb-4">
            <span class="avatar-initial rounded bg-label-primary">
              <i class="icon-base ti tabler-building-community icon-lg"></i>
            </span>
          </div>
          <div class="user-info text-center">
            <h5 class="mb-1">{{ $tenant->name }}</h5>
            <span class="badge {{ $statusClass }}">{{ ucfirst($tenant->status) }}</span>
          </div>
        </div>
      </div>
      <div class="d-flex justify-content-around flex-wrap my-6 gap-0 gap-md-3 gap-lg-4">
        <div class="d-flex align-items-center me-5 gap-4">
          <div class="avatar">
            <div class="avatar-initial bg-label-primary rounded">
              <i class="icon-base ti tabler-building icon-lg"></i>
            </div>
          </div>
          <div>
            <h5 class="mb-0">{{ $stats['properties'] ?? 0 }}</h5>
            <span>Properties</span>
          </div>
        </div>
        <div class="d-flex align-items-center gap-4">
          <div class="avatar">
            <div class="avatar-initial bg-label-primary rounded">
              <i class="icon-base ti tabler-door icon-lg"></i>
            </div>
          </div>
          <div>
            <h5 class="mb-0">{{ $stats['rooms'] ?? 0 }}</h5>
            <span>Rooms</span>
          </div>
        </div>
      </div>
      <div class="d-flex justify-content-around flex-wrap my-6 gap-0 gap-md-3 gap-lg-4">
        <div class="d-flex align-items-center me-5 gap-4">
          <div class="avatar">
            <div class="avatar-initial bg-label-primary rounded">
              <i class="icon-base ti tabler-file-text icon-lg"></i>
            </div>
          </div>
          <div>
            <h5 class="mb-0">{{ $stats['contracts'] ?? 0 }}</h5>
            <span>Active Contracts</span>
          </div>
        </div>
        <div class="d-flex align-items-center gap-4">
          <div class="avatar">
            <div class="avatar-initial bg-label-primary rounded">
              <i class="icon-base ti tabler-receipt-2 icon-lg"></i>
            </div>
          </div>
          <div>
            <h5 class="mb-0">{{ $stats['open_invoices'] ?? 0 }}</h5>
            <span>Open Invoices</span>
          </div>
        </div>
      </div>
      <h5 class="pb-4 border-bottom mb-4">Details</h5>
      <div class="info-container">
        <ul class="list-unstyled mb-6">
          <li class="mb-2">
            <span class="h6">Slug:</span>
            <span>{{ $tenant->slug }}</span>
          </li>
          <li class="mb-2">
            <span class="h6">Owner:</span>
            <span>{{ $owner?->name ?? '—' }}</span>
          </li>
          <li class="mb-2">
            <span class="h6">Email:</span>
            <span>{{ $owner?->email ?? '—' }}</span>
          </li>
          <li class="mb-2">
            <span class="h6">Timezone:</span>
            <span>{{ $tenant->timezone ?? 'UTC' }}</span>
          </li>
          <li class="mb-2">
            <span class="h6">Currency:</span>
            <span>{{ $tenant->default_currency ?? 'USD' }}</span>
          </li>
          <li class="mb-2">
            <span class="h6">Created:</span>
            <span>{{ optional($tenant->created_at)->toDateString() }}</span>
          </li>
        </ul>
        <div class="d-flex flex-wrap gap-2">
          @php
            $toggleStatus = $tenant->status === 'active' ? 'suspended' : 'active';
            $toggleLabel = $tenant->status === 'active' ? 'Suspend' : 'Activate';
          @endphp
          <form method="POST" action="{{ route('admin.tenants.status.update', $tenant) }}" data-confirm="{{ $toggleLabel }} this tenant?">
            @csrf
            @method('PATCH')
            <input type="hidden" name="status" value="{{ $toggleStatus }}" />
            <button type="submit" class="btn btn-label-warning btn-sm">
              <i class="icon-base ti tabler-player-stop icon-16px me-1"></i>{{ $toggleLabel }}
            </button>
          </form>
          <form method="POST" action="{{ route('admin.tenants.owner.reset-password', $tenant) }}" data-confirm="Send password reset link to owner?">
            @csrf
            <button type="submit" class="btn btn-label-secondary btn-sm">
              <i class="icon-base ti tabler-key icon-16px me-1"></i>Reset Owner
            </button>
          </form>
          <a href="{{ route('Core.crm', ['tenant' => $tenant->slug]) }}" class="btn btn-label-primary btn-sm" target="_blank" rel="noopener">
            <i class="icon-base ti tabler-switch-2 icon-16px me-1"></i>Switch
          </a>
        </div>
      </div>
    </div>
  </div>

  <div class="card mb-6 border border-2 border-primary rounded primary-shadow">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-start">
        <span class="badge bg-label-primary">{{ $plan?->name ?? 'No plan' }}</span>
        <div class="d-flex justify-content-center">
          <sub class="h5 pricing-currency mb-auto mt-1 text-primary">$</sub>
          <h1 class="mb-0 text-primary">{{ $plan ? (int) ($plan->price_cents / 100) : 0 }}</h1>
          <sub class="h6 pricing-duration mt-auto mb-3 fw-normal">{{ $plan?->interval ?? 'month' }}</sub>
        </div>
      </div>
      <ul class="list-unstyled g-2 my-6">
        <li class="mb-2 d-flex align-items-center">
          <i class="icon-base ti tabler-circle-filled icon-10px text-secondary me-2"></i>
          <span>{{ $planLimits['tenant_users_max'] ?? '—' }} Users</span>
        </li>
        <li class="mb-2 d-flex align-items-center">
          <i class="icon-base ti tabler-circle-filled icon-10px text-secondary me-2"></i>
          <span>{{ $planLimits['properties_max'] ?? '—' }} Properties</span>
        </li>
        <li class="mb-2 d-flex align-items-center">
          <i class="icon-base ti tabler-circle-filled icon-10px text-secondary me-2"></i>
          <span>{{ $planLimits['rooms_max'] ?? '—' }} Rooms</span>
        </li>
      </ul>
      <div class="d-flex justify-content-between align-items-center mb-1">
        <span class="h6 mb-0">Days</span>
        <span class="h6 mb-0">
          {{ $periodUsed !== null && $periodTotal !== null ? $periodUsed.' of '.$periodTotal.' Days' : '—' }}
        </span>
      </div>
      <div class="progress mb-1 bg-label-primary" style="height: 6px;">
        <div
          class="progress-bar"
          role="progressbar"
          style="width: {{ $periodProgress }}%;"
          aria-valuenow="{{ $periodProgress }}"
          aria-valuemin="0"
          aria-valuemax="100"></div>
      </div>
      <small>
        {{ $subscriptionEnd ? $subscriptionEnd->diffInDays(now()) . ' days remaining' : 'No active subscription' }}
      </small>
    </div>
  </div>
</div>

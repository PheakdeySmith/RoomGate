@extends('core::components.layouts.master')
@section('title', 'Room Details | RoomGate')
@section('page-title', 'Room Details')

@section('content')
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="row g-6">
      <div class="col-lg-8">
        <div class="card">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-4">
              <div>
                <h4 class="mb-1">{{ $room->room_number }}</h4>
                <p class="text-body-secondary mb-0">{{ $room->property?->name ?? 'Property' }}</p>
              </div>
              <span class="badge bg-label-primary text-uppercase">{{ $room->status }}</span>
            </div>

            <div class="row g-4 mt-2">
              <div class="col-md-4">
                <div class="text-body-secondary">Room Type</div>
                <div class="fw-semibold">{{ $room->roomType?->name ?? '-' }}</div>
              </div>
              <div class="col-md-4">
                <div class="text-body-secondary">Max Occupants</div>
                <div class="fw-semibold">{{ $room->max_occupants }}</div>
              </div>
              <div class="col-md-4">
                <div class="text-body-secondary">Monthly Rent</div>
                <div class="fw-semibold">${{ number_format(($room->monthly_rent_cents ?? 0) / 100, 2) }}</div>
              </div>
              <div class="col-md-4">
                <div class="text-body-secondary">Size</div>
                <div class="fw-semibold">{{ $room->size ?? '-' }}</div>
              </div>
              <div class="col-md-4">
                <div class="text-body-secondary">Floor</div>
                <div class="fw-semibold">{{ $room->floor ?? '-' }}</div>
              </div>
            </div>

            @if ($room->description)
              <div class="mt-4">
                <div class="text-body-secondary">Description</div>
                <p class="mb-0">{{ $room->description }}</p>
              </div>
            @endif
          </div>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="card">
          <div class="card-body">
            <h5 class="mb-4">Occupancy</h5>
            @if ($activeContract)
              <div class="d-flex align-items-center gap-3 mb-3">
                <div class="avatar avatar-sm">
                  <span class="avatar-initial rounded-circle bg-label-primary">
                    {{ strtoupper(substr($activeContract->occupant?->name ?? 'U', 0, 1)) }}
                  </span>
                </div>
                <div>
                  <div class="fw-semibold">{{ $activeContract->occupant?->name ?? 'Tenant' }}</div>
                  <div class="text-body-secondary">Active Contract</div>
                </div>
              </div>
              <div class="d-flex justify-content-between text-body-secondary">
                <span>Start</span>
                <span>{{ optional($activeContract->start_date)->format('Y-m-d') }}</span>
              </div>
              <div class="d-flex justify-content-between text-body-secondary">
                <span>End</span>
                <span>{{ optional($activeContract->end_date)->format('Y-m-d') ?? '-' }}</span>
              </div>
              <div class="d-flex justify-content-between text-body-secondary">
                <span>Rent</span>
                <span>${{ number_format(($activeContract->monthly_rent_cents ?? 0) / 100, 2) }}</span>
              </div>
            @else
              <div class="text-body-secondary">No active contract. Room is available.</div>
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection

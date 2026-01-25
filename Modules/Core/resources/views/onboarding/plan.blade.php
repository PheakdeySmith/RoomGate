@extends('core::components.layouts.blank')

@section('title', 'Choose a Plan')

@section('content')
  <div class="authentication-wrapper authentication-basic container-p-y">
    <div class="authentication-inner py-6">
      <div class="text-center mb-6">
        <h4 class="mb-2">Choose your plan</h4>
        <p class="text-body-secondary mb-0">Continue with the free plan or upgrade for more features.</p>
      </div>

      <div class="text-center">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#pricingModal">
          View pricing
        </button>
      </div>
    </div>
  </div>

  <div class="modal fade" id="pricingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-simple modal-pricing">
      <div class="modal-content">
        <div class="modal-body">
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          <div class="rounded-top">
            <h4 class="text-center mb-2">Pricing Plans</h4>
            <p class="text-center mb-0">Choose the best plan to fit your needs.</p>
            <div class="d-flex align-items-center justify-content-center flex-wrap gap-2 pt-12 pb-4">
              <label class="switch switch-sm ms-sm-12 ps-sm-12 me-0">
                <span class="switch-label fs-6 text-body">Monthly</span>
                <input type="checkbox" class="switch-input price-duration-toggler" checked />
                <span class="switch-toggle-slider">
                  <span class="switch-on"></span>
                  <span class="switch-off"></span>
                </span>
                <span class="switch-label fs-6 text-body">Annually</span>
              </label>
              <div class="mt-n5 ms-n10 ml-2 mb-12 d-none d-sm-flex align-items-center gap-1">
                <i class="icon-base ti tabler-corner-left-down icon-lg text-body-secondary scaleX-n1-rtl"></i>
                <span class="badge badge-sm bg-label-primary rounded-1 mb-2">Save up to 10%</span>
              </div>
            </div>

            <div class="row gy-6">
              @php
                $images = [
                  'page-pricing-basic.png',
                  'page-pricing-standard.png',
                  'page-pricing-enterprise.png',
                ];
              @endphp
              @foreach ($plans as $index => $plan)
                @php
                  $price = number_format($plan->price_cents / 100, 2);
                  $isCurrent = $subscription && $subscription->plan_id === $plan->id;
                  $image = $images[$index] ?? $images[0];
                @endphp
                <div class="col-xl mb-md-0">
                  <div class="card {{ $isCurrent ? 'border-primary' : 'border' }} rounded shadow-none">
                    <div class="card-body pt-12 p-5">
                      <div class="mt-3 mb-5 text-center">
                        <img src="{{ asset('assets/assets/img/illustrations/' . $image) }}" alt="Plan Image" height="120" />
                      </div>
                      <h4 class="card-title text-center text-capitalize mb-1">{{ $plan->name }}</h4>
                      <p class="text-center mb-5 text-body-secondary">{{ ucfirst($plan->interval) }} billing</p>
                      <div class="text-center h-px-50">
                        <div class="d-flex justify-content-center">
                          <sup class="h6 text-body pricing-currency mt-2 mb-0 me-1">$</sup>
                          <h1 class="mb-0 text-primary">{{ $price }}</h1>
                          <sub class="h6 text-body pricing-duration mt-auto mb-1">/month</sub>
                        </div>
                      </div>

                      <ul class="list-group ps-6 my-5 pt-9">
                        @foreach (($plan->limits ?? []) as $limit)
                          <li class="mb-4">{{ $limit->limit_key }}: {{ $limit->limit_value }}</li>
                        @endforeach
                      </ul>

                      <form method="POST" action="{{ route('core.onboarding.plan.store') }}">
                        @csrf
                        <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                        <button type="submit" class="btn {{ $isCurrent ? 'btn-label-success' : 'btn-primary' }} d-grid w-100">
                          {{ $isCurrent ? 'Your Current Plan' : 'Choose Plan' }}
                        </button>
                      </form>
                    </div>
                  </div>
                </div>
              @endforeach
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection

@push('page-scripts')
  <script src="{{ asset('assets/assets') }}/js/pages-pricing.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const modalEl = document.getElementById('pricingModal');
      if (modalEl && window.bootstrap) {
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
      }
    });
  </script>
@endpush

@extends('core::components.layouts.blank')

@section('title', 'Get Started')

@push('page-styles')
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/bs-stepper/bs-stepper.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/select2/select2.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/flatpickr/flatpickr.css" />
  <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/@form-validation/form-validation.css" />
@endpush

@section('content')
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="row align-items-center mb-6">
      <div class="col-md-8">
        <h4 class="fw-bold mb-2">Get started with RoomGate</h4>
        <p class="text-body-secondary mb-0">Tell us about your property so we can set up your workspace.</p>
      </div>
      <div class="col-md-4 text-md-end mt-3 mt-md-0">
        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <button type="submit" class="btn btn-label-secondary">Logout</button>
        </form>
      </div>
    </div>

    <div class="row">
      <div class="col-12">
        <div class="card shadow-sm">
          <div class="card-body">
            <div id="wizard-property-listing" class="bs-stepper vertical wizard-vertical">
              <div class="row g-0">
                <div class="col-lg-4 border-end">
                  <div class="bs-stepper-header" role="tablist">
                    <div class="step" data-target="#personal-details" role="tab" id="personal-details-trigger">
                      <button type="button" class="step-trigger">
                        <span class="bs-stepper-circle">1</span>
                        <span class="bs-stepper-label">
                          <span class="bs-stepper-title">Tenant</span>
                          <span class="bs-stepper-subtitle">Account details</span>
                        </span>
                      </button>
                    </div>
                    <div class="line"></div>
                    <div class="step" data-target="#property-details" role="tab" id="property-details-trigger">
                      <button type="button" class="step-trigger">
                        <span class="bs-stepper-circle">2</span>
                        <span class="bs-stepper-label">
                          <span class="bs-stepper-title">Property</span>
                          <span class="bs-stepper-subtitle">Location & type</span>
                        </span>
                      </button>
                    </div>
                    <div class="line"></div>
                    <div class="step" data-target="#confirm-details" role="tab" id="confirm-details-trigger">
                      <button type="button" class="step-trigger">
                        <span class="bs-stepper-circle">3</span>
                        <span class="bs-stepper-label">
                          <span class="bs-stepper-title">Confirm</span>
                          <span class="bs-stepper-subtitle">Finish setup</span>
                        </span>
                      </button>
                    </div>
                  </div>
                </div>

                <div class="col-lg-8">
                  <div class="bs-stepper-content">
                <form id="wizard-property-listing-form" action="{{ route('core.onboarding.store') }}" method="POST">
                  @csrf

                  <div id="personal-details" class="content">
                    <div class="row g-6">
                      <div class="col-12 form-control-validation">
                        <p class="mb-2">Account Type <span class="text-danger">*</span></p>
                        <div class="row gy-3 align-items-stretch">
                          <div class="col-md-6 col-xl-4 d-flex">
                            <div class="form-check custom-option custom-option-icon h-100 w-100">
                              <label class="form-check-label custom-option-content h-100 w-100" for="accountTypePersonal">
                                <span class="custom-option-body">
                                  <i class="icon-base ti tabler-user icon-lg mb-2"></i>
                                  <span class="custom-option-title">Personal</span>
                                  <small>Manage your own rooms and tenants.</small>
                                </span>
                                <input class="form-check-input" type="radio" name="extra[account_type]" id="accountTypePersonal" value="personal" checked />
                              </label>
                            </div>
                          </div>
                          <div class="col-md-6 col-xl-4 d-flex">
                            <div class="form-check custom-option custom-option-icon h-100 w-100">
                              <label class="form-check-label custom-option-content h-100 w-100" for="accountTypeOrg">
                                <span class="custom-option-body">
                                  <i class="icon-base ti tabler-building icon-lg mb-2"></i>
                                  <span class="custom-option-title">Organization</span>
                                  <small>For teams managing properties together.</small>
                                </span>
                                <input class="form-check-input" type="radio" name="extra[account_type]" id="accountTypeOrg" value="organization" />
                              </label>
                            </div>
                          </div>
                        </div>
                      </div>
                      <div class="col-sm-6 form-control-validation">
                        <label class="form-label" for="plFirstName">First Name <span class="text-danger">*</span></label>
                        <input type="text" id="plFirstName" name="extra[first_name]" class="form-control" placeholder="John" />
                      </div>
                      <div class="col-sm-6 form-control-validation">
                        <label class="form-label" for="plLastName">Last Name <span class="text-danger">*</span></label>
                        <input type="text" id="plLastName" name="extra[last_name]" class="form-control" placeholder="Doe" />
                      </div>
                      <div class="col-sm-6 form-control-validation">
                        <label class="form-label" for="contactPhone">Contact Number <span class="text-danger">*</span></label>
                        <input type="text" id="contactPhone" name="extra[contact_number]" class="form-control contact-number-mask" placeholder="(000) 000-0000" />
                      </div>
                      <div class="col-sm-6 form-control-validation" id="orgNameField" style="display: none;">
                        <label class="form-label" for="tenantName">Organization Name <span class="text-danger">*</span></label>
                        <input type="text" id="tenantName" name="tenant_name" class="form-control" placeholder="RoomGate Properties" />
                      </div>
                      <div class="col-12 d-flex justify-content-between mt-6">
                        <button type="button" class="btn btn-label-secondary btn-prev" disabled>
                          <i class="icon-base ti tabler-arrow-left icon-xs me-sm-2 me-0"></i>
                          <span class="align-middle d-sm-inline-block d-none">Previous</span>
                        </button>
                        <button type="button" class="btn btn-primary btn-next">
                          <span class="align-middle d-sm-inline-block d-none me-sm-2">Next</span>
                          <i class="icon-base ti tabler-arrow-right icon-xs"></i>
                        </button>
                      </div>
                    </div>
                  </div>

                  <div id="property-details" class="content">
                    <div class="row g-6">
                      <div class="col-sm-6 form-control-validation">
                        <label class="form-label" for="propertyName">Property Name <span class="text-danger">*</span></label>
                        <input type="text" id="propertyName" name="property_name" class="form-control" placeholder="RoomGate Residence" />
                      </div>
                      <div class="col-sm-6 form-control-validation">
                        <label class="form-label" for="plPropertyType">Property Type <span class="text-danger">*</span></label>
                        <select id="plPropertyType" name="property_type" class="select2 form-select" data-allow-clear="true">
                          <option value="">Select Property Type</option>
                          <option value="Apartment">Apartment</option>
                          <option value="House">House</option>
                          <option value="Villa">Villa</option>
                          <option value="Studio">Studio</option>
                        </select>
                      </div>
                      <div class="col-sm-6 form-control-validation">
                        <label class="form-label" for="plCountry">Country <span class="text-danger">*</span></label>
                        <select id="plCountry" name="country" class="select2 form-select" data-allow-clear="true">
                          <option value="">Select</option>
                          <option value="Cambodia">Cambodia</option>
                          <option value="Thailand">Thailand</option>
                          <option value="Vietnam">Vietnam</option>
                          <option value="Singapore">Singapore</option>
                          <option value="United States">United States</option>
                        </select>
                      </div>
                      <div class="col-sm-6 form-control-validation">
                        <label class="form-label" for="plState">State/Province <span class="text-danger">*</span></label>
                        <input type="text" id="plState" name="state_province" class="form-control" placeholder="Phnom Penh" />
                      </div>
                      <div class="col-sm-6 form-control-validation">
                        <label class="form-label" for="plCity">City <span class="text-danger">*</span></label>
                        <input type="text" id="plCity" name="city" class="form-control" placeholder="Phnom Penh" />
                      </div>
                      <div class="col-sm-6 form-control-validation">
                        <label class="form-label" for="plZipCode">Zip Code <span class="text-danger">*</span></label>
                        <input type="text" id="plZipCode" name="postal_code" class="form-control" placeholder="99950" />
                      </div>
                      <div class="col-12 form-control-validation">
                        <label class="form-label" for="plAddress">Address <span class="text-danger">*</span></label>
                        <textarea id="plAddress" name="address_line_1" class="form-control" rows="2" placeholder="12, Business Park"></textarea>
                      </div>
                      <div class="col-12 d-flex justify-content-between mt-6">
                        <button type="button" class="btn btn-label-secondary btn-prev">
                          <i class="icon-base ti tabler-arrow-left icon-xs me-sm-2 me-0"></i>
                          <span class="align-middle d-sm-inline-block d-none">Previous</span>
                        </button>
                        <button type="button" class="btn btn-primary btn-next">
                          <span class="align-middle d-sm-inline-block d-none me-sm-2">Next</span>
                          <i class="icon-base ti tabler-arrow-right icon-xs"></i>
                        </button>
                      </div>
                    </div>
                  </div>

                  <div id="confirm-details" class="content">
                    <div class="row g-6">
                      <div class="col-12">
                        <div class="alert alert-info">
                          <h6 class="mb-1">Almost done!</h6>
                          <p class="mb-0">We will create your tenant, property, and starter plan. You can upgrade after setup.</p>
                        </div>
                      </div>
                      <div class="col-12 d-flex justify-content-between mt-6">
                        <button type="button" class="btn btn-label-secondary btn-prev">
                          <i class="icon-base ti tabler-arrow-left icon-xs me-sm-2 me-0"></i>
                          <span class="align-middle d-sm-inline-block d-none">Previous</span>
                        </button>
                        <button class="btn btn-success btn-submit btn-next" type="submit">
                          <span class="align-middle d-sm-inline-block d-none me-sm-2">Finish</span>
                          <i class="icon-base ti tabler-check icon-xs"></i>
                        </button>
                      </div>
                    </div>
                  </div>
                </form>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection

@push('page-scripts')
  <script src="{{ asset('assets/assets') }}/vendor/libs/cleave-zen/cleave-zen.js"></script>
  <script src="{{ asset('assets/assets') }}/vendor/libs/bs-stepper/bs-stepper.js"></script>
  <script src="{{ asset('assets/assets') }}/vendor/libs/select2/select2.js"></script>
  <script src="{{ asset('assets/assets') }}/vendor/libs/flatpickr/flatpickr.js"></script>
  <script src="{{ asset('assets/assets') }}/vendor/libs/@form-validation/popular.js"></script>
  <script src="{{ asset('assets/assets') }}/vendor/libs/@form-validation/bootstrap5.js"></script>
  <script src="{{ asset('assets/assets') }}/vendor/libs/@form-validation/auto-focus.js"></script>
  <script src="{{ asset('assets/assets') }}/js/roomgate-onboarding.js"></script>
@endpush

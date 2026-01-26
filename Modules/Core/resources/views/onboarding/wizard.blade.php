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
                        <div class="row g-6">
                          <div class="col-md mb-md-0">
                            <div class="form-check custom-option custom-option-icon">
                              <label class="form-check-label custom-option-content" for="accountTypePersonal">
                                <span class="custom-option-body">
                                  <svg width="41" height="40" viewBox="0 0 41 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M20.5 5C15.667 5 11.75 8.917 11.75 13.75C11.75 18.583 15.667 22.5 20.5 22.5C25.333 22.5 29.25 18.583 29.25 13.75C29.25 8.917 25.333 5 20.5 5Z" fill="currentColor" fill-opacity="0.2"/>
                                    <path d="M20.5 6.75C24.367 6.75 27.5 9.883 27.5 13.75C27.5 17.617 24.367 20.75 20.5 20.75C16.633 20.75 13.5 17.617 13.5 13.75C13.5 9.883 16.633 6.75 20.5 6.75Z" fill="currentColor"/>
                                    <path d="M7 34.75C7 29.804 13.268 26.25 20.5 26.25C27.732 26.25 34 29.804 34 34.75H32.25C32.25 30.91 27.012 28 20.5 28C13.988 28 8.75 30.91 8.75 34.75H7Z" fill="currentColor"/>
                                  </svg>
                                  <span class="custom-option-title">Personal</span>
                                  <small>Manage your own rooms and tenants.<br />Personal workspace.</small>
                                </span>
                                <input class="form-check-input" type="radio" name="extra[account_type]" id="accountTypePersonal" value="personal" checked />
                              </label>
                            </div>
                          </div>
                          <div class="col-md mb-md-0">
                            <div class="form-check custom-option custom-option-icon">
                              <label class="form-check-label custom-option-content" for="accountTypeOrg">
                                <span class="custom-option-body">
                                  <svg width="41" height="40" viewBox="0 0 41 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M6.5 33.75V6.25C6.5 5.91848 6.6317 5.60054 6.86612 5.36612C7.10054 5.1317 7.41848 5 7.75 5H22.75C23.0815 5 23.3995 5.1317 23.6339 5.36612C23.8683 5.60054 24 5.91848 24 6.25V33.75" fill="currentColor" fill-opacity="0.2"/>
                                    <path d="M24 33.75V16.25C24 15.9185 24.1317 15.6005 24.3661 15.3661C24.6005 15.1317 24.9185 15 25.25 15H32.25C32.5815 15 32.8995 15.1317 33.1339 15.3661C33.3683 15.6005 33.5 15.9185 33.5 16.25V33.75" fill="currentColor" fill-opacity="0.2"/>
                                    <path d="M5 34.75H36V32.75H5V34.75Z" fill="currentColor"/>
                                    <path d="M22.75 5H7.75C7.41848 5 7.10054 5.1317 6.86612 5.36612C6.6317 5.60054 6.5 5.91848 6.5 6.25V33.75H8.5V7H22V33.75H24V6.25C24 5.91848 23.8683 5.60054 23.6339 5.36612C23.3995 5.1317 23.0815 5 22.75 5Z" fill="currentColor"/>
                                    <path d="M32.25 15H25.25C24.9185 15 24.6005 15.1317 24.3661 15.3661C24.1317 15.6005 24 15.9185 24 16.25V33.75H26V17H31.5V33.75H33.5V16.25C33.5 15.9185 33.3683 15.6005 33.1339 15.3661C32.8995 15.1317 32.5815 15 32.25 15Z" fill="currentColor"/>
                                  </svg>
                                  <span class="custom-option-title">Organization</span>
                                  <small>For teams managing properties<br />together.</small>
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

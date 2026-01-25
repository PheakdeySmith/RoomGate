@extends('admin::components.layouts.master')
@section('title', 'Settings | ' . ($appSettings->app_name ?? 'RoomGate') . ' Admin')
@section('page-title', 'Settings')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <div class="card mb-6">
      <h5 class="card-header">Business Info</h5>
      <div class="card-body">
        <div class="row g-6">
          <div class="col-md-6">
            <div class="row">
              <label class="col-sm-3 col-form-label text-sm-end" for="app_name">App Name</label>
              <div class="col-sm-9">
                <input type="text" id="app_name" name="app_name" class="form-control"
                  value="{{ old('app_name', $settings->app_name) }}" placeholder="RoomGate" />
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="row">
              <label class="col-sm-3 col-form-label text-sm-end" for="app_short_name">Short Name</label>
              <div class="col-sm-9">
                <input type="text" id="app_short_name" name="app_short_name" class="form-control"
                  value="{{ old('app_short_name', $settings->app_short_name) }}" placeholder="RoomGate" />
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="row">
              <label class="col-sm-3 col-form-label text-sm-end" for="company_name">Company</label>
              <div class="col-sm-9">
                <input type="text" id="company_name" name="company_name" class="form-control"
                  value="{{ old('company_name', $settings->company_name) }}" placeholder="RoomGate Co." />
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="row">
              <label class="col-sm-3 col-form-label text-sm-end" for="tagline">Tagline</label>
              <div class="col-sm-9">
                <input type="text" id="tagline" name="tagline" class="form-control"
                  value="{{ old('tagline', $settings->tagline) }}" placeholder="Smart rental management" />
              </div>
            </div>
          </div>
          <div class="col-12">
            <div class="row">
              <label class="col-sm-3 col-form-label text-sm-end" for="description">Description</label>
              <div class="col-sm-9">
                <textarea id="description" name="description" class="form-control" rows="3"
                  placeholder="Short description for metadata">{{ old('description', $settings->description) }}</textarea>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="card mb-6">
      <h5 class="card-header">Contact</h5>
      <div class="card-body">
        <div class="row g-6">
          <div class="col-md-6">
            <div class="row">
              <label class="col-sm-3 col-form-label text-sm-end" for="address">Address</label>
              <div class="col-sm-9">
                <input type="text" id="address" name="address" class="form-control"
                  value="{{ old('address', $settings->address) }}" placeholder="Street, City, Country" />
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="row">
              <label class="col-sm-3 col-form-label text-sm-end" for="phone">Phone</label>
              <div class="col-sm-9">
                <input type="text" id="phone" name="phone" class="form-control"
                  value="{{ old('phone', $settings->phone) }}" placeholder="+855 12 345 678" />
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="row">
              <label class="col-sm-3 col-form-label text-sm-end" for="email">Email</label>
              <div class="col-sm-9">
                <input type="email" id="email" name="email" class="form-control"
                  value="{{ old('email', $settings->email) }}" placeholder="info@roomgate.app" />
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="row">
              <label class="col-sm-3 col-form-label text-sm-end" for="website">Website</label>
              <div class="col-sm-9">
                <input type="url" id="website" name="website" class="form-control"
                  value="{{ old('website', $settings->website) }}" placeholder="https://roomgate.app" />
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="row">
              <label class="col-sm-3 col-form-label text-sm-end" for="support_email">Support Email</label>
              <div class="col-sm-9">
                <input type="email" id="support_email" name="support_email" class="form-control"
                  value="{{ old('support_email', $settings->support_email) }}" placeholder="support@roomgate.app" />
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="row">
              <label class="col-sm-3 col-form-label text-sm-end" for="support_phone">Support Phone</label>
              <div class="col-sm-9">
                <input type="text" id="support_phone" name="support_phone" class="form-control"
                  value="{{ old('support_phone', $settings->support_phone) }}" placeholder="+855 12 000 000" />
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="card mb-6">
      <h5 class="card-header">Links & Policies</h5>
      <div class="card-body">
        <div class="row g-6">
          <div class="col-md-6">
            <div class="row">
              <label class="col-sm-3 col-form-label text-sm-end" for="terms_url">Terms URL</label>
              <div class="col-sm-9">
                <input type="url" id="terms_url" name="terms_url" class="form-control"
                  value="{{ old('terms_url', $settings->terms_url) }}" placeholder="https://roomgate.app/terms" />
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="row">
              <label class="col-sm-3 col-form-label text-sm-end" for="privacy_url">Privacy URL</label>
              <div class="col-sm-9">
                <input type="url" id="privacy_url" name="privacy_url" class="form-control"
                  value="{{ old('privacy_url', $settings->privacy_url) }}" placeholder="https://roomgate.app/privacy" />
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="row">
              <label class="col-sm-3 col-form-label text-sm-end" for="facebook_url">Facebook</label>
              <div class="col-sm-9">
                <input type="url" id="facebook_url" name="facebook_url" class="form-control"
                  value="{{ old('facebook_url', $settings->facebook_url) }}" placeholder="https://facebook.com/roomgate" />
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="row">
              <label class="col-sm-3 col-form-label text-sm-end" for="instagram_url">Instagram</label>
              <div class="col-sm-9">
                <input type="url" id="instagram_url" name="instagram_url" class="form-control"
                  value="{{ old('instagram_url', $settings->instagram_url) }}" placeholder="https://instagram.com/roomgate" />
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="row">
              <label class="col-sm-3 col-form-label text-sm-end" for="linkedin_url">LinkedIn</label>
              <div class="col-sm-9">
                <input type="url" id="linkedin_url" name="linkedin_url" class="form-control"
                  value="{{ old('linkedin_url', $settings->linkedin_url) }}" placeholder="https://linkedin.com/company/roomgate" />
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="row">
              <label class="col-sm-3 col-form-label text-sm-end" for="telegram_url">Telegram</label>
              <div class="col-sm-9">
                <input type="url" id="telegram_url" name="telegram_url" class="form-control"
                  value="{{ old('telegram_url', $settings->telegram_url) }}" placeholder="https://t.me/roomgate" />
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="row">
              <label class="col-sm-3 col-form-label text-sm-end" for="iot_device_ip">IoT Device IP</label>
              <div class="col-sm-9">
                <input type="text" id="iot_device_ip" name="iot_device_ip" class="form-control"
                  value="{{ old('iot_device_ip', $settings->iot_device_ip) }}" placeholder="192.168.1.50" />
                <div class="form-text">Default ESP32 IP for IoT Control.</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="card mb-6">
      <h5 class="card-header">Branding Assets</h5>
      <div class="card-body">
        <div class="row g-6">
          <div class="col-md-6">
            <div class="row">
              <label class="col-sm-3 col-form-label text-sm-end" for="logo_light">Logo (Light)</label>
              <div class="col-sm-9">
                <input type="file" id="logo_light" name="logo_light" class="form-control" accept="image/*"
                  data-preview-target="preview-logo-light" />
                <div class="mt-3 {{ $settings->logo_light_path ? '' : 'd-none' }}" id="preview-logo-light-wrapper">
                  <img
                    src="{{ $settings->logo_light_path ? asset($settings->logo_light_path) : '' }}"
                    data-original-src="{{ $settings->logo_light_path ? asset($settings->logo_light_path) : '' }}"
                    id="preview-logo-light"
                    class="img-fluid rounded border"
                    style="max-height: 60px;"
                    alt="Light logo">
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="row">
              <label class="col-sm-3 col-form-label text-sm-end" for="logo_dark">Logo (Dark)</label>
              <div class="col-sm-9">
                <input type="file" id="logo_dark" name="logo_dark" class="form-control" accept="image/*"
                  data-preview-target="preview-logo-dark" />
                <div class="mt-3 {{ $settings->logo_dark_path ? '' : 'd-none' }}" id="preview-logo-dark-wrapper">
                  <img
                    src="{{ $settings->logo_dark_path ? asset($settings->logo_dark_path) : '' }}"
                    data-original-src="{{ $settings->logo_dark_path ? asset($settings->logo_dark_path) : '' }}"
                    id="preview-logo-dark"
                    class="img-fluid rounded border"
                    style="max-height: 60px;"
                    alt="Dark logo">
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="row">
              <label class="col-sm-3 col-form-label text-sm-end" for="logo_small">Logo (Small)</label>
              <div class="col-sm-9">
                <input type="file" id="logo_small" name="logo_small" class="form-control" accept="image/*"
                  data-preview-target="preview-logo-small" />
                <div class="mt-3 {{ $settings->logo_small_path ? '' : 'd-none' }}" id="preview-logo-small-wrapper">
                  <img
                    src="{{ $settings->logo_small_path ? asset($settings->logo_small_path) : '' }}"
                    data-original-src="{{ $settings->logo_small_path ? asset($settings->logo_small_path) : '' }}"
                    id="preview-logo-small"
                    class="img-fluid rounded border"
                    style="max-height: 48px;"
                    alt="Small logo">
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="row">
              <label class="col-sm-3 col-form-label text-sm-end" for="login_logo">Login Logo</label>
              <div class="col-sm-9">
                <input type="file" id="login_logo" name="login_logo" class="form-control" accept="image/*"
                  data-preview-target="preview-login-logo" />
                <div class="mt-3 {{ $settings->login_logo_path ? '' : 'd-none' }}" id="preview-login-logo-wrapper">
                  <img
                    src="{{ $settings->login_logo_path ? asset($settings->login_logo_path) : '' }}"
                    data-original-src="{{ $settings->login_logo_path ? asset($settings->login_logo_path) : '' }}"
                    id="preview-login-logo"
                    class="img-fluid rounded border"
                    style="max-height: 60px;"
                    alt="Login logo">
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="row">
              <label class="col-sm-3 col-form-label text-sm-end" for="footer_logo">Footer Logo</label>
              <div class="col-sm-9">
                <input type="file" id="footer_logo" name="footer_logo" class="form-control" accept="image/*"
                  data-preview-target="preview-footer-logo" />
                <div class="mt-3 {{ $settings->footer_logo_path ? '' : 'd-none' }}" id="preview-footer-logo-wrapper">
                  <img
                    src="{{ $settings->footer_logo_path ? asset($settings->footer_logo_path) : '' }}"
                    data-original-src="{{ $settings->footer_logo_path ? asset($settings->footer_logo_path) : '' }}"
                    id="preview-footer-logo"
                    class="img-fluid rounded border"
                    style="max-height: 48px;"
                    alt="Footer logo">
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="row">
              <label class="col-sm-3 col-form-label text-sm-end" for="favicon">Favicon</label>
              <div class="col-sm-9">
                <input type="file" id="favicon" name="favicon" class="form-control" accept="image/*"
                  data-preview-target="preview-favicon" />
                <div class="mt-3 {{ $settings->favicon_path ? '' : 'd-none' }}" id="preview-favicon-wrapper">
                  <img
                    src="{{ $settings->favicon_path ? asset($settings->favicon_path) : '' }}"
                    data-original-src="{{ $settings->favicon_path ? asset($settings->favicon_path) : '' }}"
                    id="preview-favicon"
                    class="img-fluid rounded border"
                    style="max-height: 40px;"
                    alt="Favicon">
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-body">
        <div class="row">
          <div class="col-sm-9 offset-sm-3">
            <button type="submit" class="btn btn-primary me-4">Save Settings</button>
            <button type="reset" class="btn btn-label-secondary">Reset</button>
          </div>
        </div>
      </div>
    </div>
  </form>
</div>
@endsection

@push('page-scripts')
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const form = document.querySelector('form[action="{{ route('admin.settings.update') }}"]');
      if (!form) {
        return;
      }

      const bindPreview = (input) => {
        const targetId = input.getAttribute('data-preview-target');
        if (!targetId) {
          return;
        }
        const preview = document.getElementById(targetId);
        const wrapper = document.getElementById(`${targetId}-wrapper`);
        if (!preview) {
          return;
        }

        input.addEventListener('change', () => {
          const file = input.files && input.files[0];
          if (!file) {
            return;
          }
          preview.src = URL.createObjectURL(file);
          if (wrapper) {
            wrapper.classList.remove('d-none');
          }
        });
      };

      form.querySelectorAll('input[type="file"][data-preview-target]').forEach(bindPreview);

      form.addEventListener('reset', () => {
        form.querySelectorAll('img[data-original-src]').forEach((img) => {
          const original = img.getAttribute('data-original-src') || '';
          img.src = original;
          const wrapper = document.getElementById(`${img.id}-wrapper`);
          if (wrapper) {
            if (original) {
              wrapper.classList.remove('d-none');
            } else {
              wrapper.classList.add('d-none');
            }
          }
        });
      });
    });
  </script>
@endpush

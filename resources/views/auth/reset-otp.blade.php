@php
  $appName = $appSettings->app_name ?? config('app.name', 'RoomGate');
@endphp
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="light-style customizer-hide" dir="ltr" data-theme="theme-default" data-assets-path="{{ asset('assets/assets') }}/" data-template="vertical-menu-template">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Reset Password | {{ $appName }}</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('assets/assets') }}/img/favicon/favicon.ico" />
    <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/fonts/tabler-icons.css" />
    <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/css/core.css" />
    <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/css/theme-default.css" />
    <link rel="stylesheet" href="{{ asset('assets/assets') }}/css/demo.css" />
    <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/node-waves/node-waves.css" />
    <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
    <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/@form-validation/form-validation.css" />
    <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/css/pages/page-auth.css" />
  </head>
  <body>
    <div class="authentication-wrapper authentication-basic px-4">
      <div class="authentication-inner py-5">
        <div class="card">
          <div class="card-body">
            <div class="app-brand justify-content-center mb-4">
              <a href="{{ url('/') }}" class="app-brand-link gap-2">
                <span class="app-brand-logo demo">
                  <img src="{{ $appSettings->login_logo_path ? asset($appSettings->login_logo_path) : asset('template/assets/img/favicon/favicon.ico') }}" alt="{{ $appName }}" style="height: 28px;">
                </span>
                <span class="app-brand-text demo text-heading fw-bold">{{ $appName }}</span>
              </a>
            </div>
            <h4 class="mb-1">Reset your password</h4>
            <p class="mb-4">Enter the 6-digit code and your new password.</p>

            @if (session('status'))
              <div class="alert alert-success">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('password.otp.reset') }}">
              @csrf
              <input type="hidden" name="email" value="{{ old('email', $email) }}" />
              <div class="mb-3">
                <label class="form-label" for="resetCode">Reset Code</label>
                <input type="text" id="resetCode" name="code" class="form-control" placeholder="123456" maxlength="6" />
                @error('code')
                  <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
              </div>
              <div class="mb-3 form-password-toggle">
                <label class="form-label" for="password">New Password</label>
                <div class="input-group input-group-merge">
                  <input type="password" id="password" name="password" class="form-control" placeholder="New password" />
                  <span class="input-group-text cursor-pointer"><i class="ti tabler-eye-off"></i></span>
                </div>
                @error('password')
                  <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
              </div>
              <div class="mb-4 form-password-toggle">
                <label class="form-label" for="password_confirmation">Confirm Password</label>
                <div class="input-group input-group-merge">
                  <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" placeholder="Confirm password" />
                  <span class="input-group-text cursor-pointer"><i class="ti tabler-eye-off"></i></span>
                </div>
              </div>
              <button class="btn btn-primary d-grid w-100">Reset Password</button>
            </form>
          </div>
        </div>
      </div>
    </div>

    <script src="{{ asset('assets/assets') }}/vendor/libs/jquery/jquery.js"></script>
    <script src="{{ asset('assets/assets') }}/vendor/libs/popper/popper.js"></script>
    <script src="{{ asset('assets/assets') }}/vendor/js/bootstrap.js"></script>
    <script src="{{ asset('assets/assets') }}/vendor/libs/node-waves/node-waves.js"></script>
    <script src="{{ asset('assets/assets') }}/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="{{ asset('assets/assets') }}/vendor/libs/@form-validation/popular.js"></script>
    <script src="{{ asset('assets/assets') }}/vendor/libs/@form-validation/bootstrap5.js"></script>
    <script src="{{ asset('assets/assets') }}/vendor/libs/@form-validation/auto-focus.js"></script>
    <script src="{{ asset('assets/assets') }}/js/main.js"></script>
  </body>
</html>

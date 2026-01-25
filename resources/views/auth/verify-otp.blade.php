@php
  $appName = $appSettings->app_name ?? config('app.name', 'RoomGate');
@endphp
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="light-style customizer-hide" dir="ltr" data-theme="theme-default" data-assets-path="{{ asset('assets/assets') }}/" data-template="vertical-menu-template">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Email Verification | {{ $appName }}</title>
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
            <h4 class="mb-1">Verify your email</h4>
            <p class="mb-4">Enter the 6-digit code we sent to your email.</p>

            @if (session('status'))
              <div class="alert alert-success">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('verification.otp.verify') }}" class="mb-3">
              @csrf
              <input type="hidden" name="email" value="{{ old('email', $email) }}" />
              <div class="mb-3">
                <label class="form-label" for="otpCode">Verification Code</label>
                <input type="text" id="otpCode" name="code" class="form-control" placeholder="123456" maxlength="6" />
                @error('code')
                  <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
              </div>
              <button class="btn btn-primary d-grid w-100 mb-3">Verify</button>
            </form>

            <form method="POST" action="{{ route('verification.otp.resend') }}" class="mb-3" id="resendForm">
              @csrf
              <input type="hidden" name="email" value="{{ old('email', $email) }}" />
              <button type="submit" class="btn btn-label-secondary d-grid w-100" id="resendButton" disabled>Resend code</button>
            </form>

            <div class="text-center text-body-secondary small mb-4" id="resendTimer">You can resend in 60s</div>

            <div class="divider my-4">
              <div class="divider-text">Change email</div>
            </div>
            <button class="btn btn-outline-primary d-grid w-100 mb-3" id="toggleChangeEmail" type="button">Change email</button>
            <form method="POST" action="{{ route('verification.otp.change') }}" id="changeEmailForm" style="display: none;">
              @csrf
              <input type="hidden" name="current_email" value="{{ old('email', $email) }}" />
              <div class="mb-3">
                <label class="form-label" for="newEmail">New Email</label>
                <input type="email" id="newEmail" name="email" class="form-control" placeholder="you@example.com" />
                @error('email')
                  <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
              </div>
              <button type="submit" class="btn btn-primary d-grid w-100">Update email & resend</button>
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
    <script>
      (function () {
        const email = @json($email);
        const key = email ? `otp_resend_until_${email}` : 'otp_resend_until';
        const btn = document.getElementById('resendButton');
        const label = document.getElementById('resendTimer');
        if (!btn || !label) return;

        const now = Date.now();
        let until = parseInt(localStorage.getItem(key) || '0', 10);
        if (!until || until < now) {
          until = now + 60000;
          localStorage.setItem(key, String(until));
        }

        const tick = () => {
          const remaining = Math.max(0, Math.ceil((until - Date.now()) / 1000));
          if (remaining <= 0) {
            btn.disabled = false;
            label.textContent = 'You can resend now.';
            return;
          }
          btn.disabled = true;
          label.textContent = `You can resend in ${remaining}s`;
          setTimeout(tick, 1000);
        };
        tick();

        const form = document.getElementById('resendForm');
        if (form) {
          form.addEventListener('submit', () => {
            const next = Date.now() + 60000;
            localStorage.setItem(key, String(next));
          });
        }

        const toggle = document.getElementById('toggleChangeEmail');
        const changeForm = document.getElementById('changeEmailForm');
        if (toggle && changeForm) {
          toggle.addEventListener('click', () => {
            changeForm.style.display = '';
            toggle.style.display = 'none';
            const input = document.getElementById('newEmail');
            if (input) {
              input.focus();
            }
          });
        }
      })();
    </script>
  </body>
</html>

<!doctype html>
<html
  lang="en"
  class="layout-wide customizer-hide"
  dir="ltr"
  data-skin="default"
  data-bs-theme="light"
  data-assets-path="{{ asset('template/assets/') }}/"
  data-template="vertical-menu-template-no-customizer">
  <head>
    <meta charset="utf-8" />
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <meta name="robots" content="noindex, nofollow" />
    @php
      $appName = $appSettings->app_name ?: 'RoomGate';
      $brandName = $appSettings->app_short_name ?: $appName;
      $favicon = $appSettings->favicon_path ? asset($appSettings->favicon_path) : asset('template/assets/img/favicon/favicon.ico');
      $loginLogo = $appSettings->login_logo_path ? asset($appSettings->login_logo_path) : null;
      $lightLogo = $appSettings->logo_light_path ? asset($appSettings->logo_light_path) : null;
      $darkLogo = $appSettings->logo_dark_path ? asset($appSettings->logo_dark_path) : $lightLogo;
      $brandLogo = $loginLogo ?: $lightLogo ?: $darkLogo;
    @endphp
    <title>Login with Telegram | {{ $appName }}</title>

    <meta name="description" content="{{ $appSettings->description ?? ($appName . ' login') }}" />

    <link rel="icon" type="image/x-icon" href="{{ $favicon }}" />

    <link rel="stylesheet" href="{{ asset('template/assets/vendor/fonts/iconify-icons.css') }}" />

    <script src="{{ asset('template/assets/vendor/libs/@algolia/autocomplete-js.js') }}"></script>

    <link rel="stylesheet" href="{{ asset('template/assets/vendor/libs/node-waves/node-waves.css') }}" />
    <link rel="stylesheet" href="{{ asset('template/assets/vendor/css/core.css') }}" />
    <link rel="stylesheet" href="{{ asset('template/assets/css/demo.css') }}" />

    <link rel="stylesheet" href="{{ asset('template/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css') }}" />

    <link rel="stylesheet" href="{{ asset('template/assets/vendor/libs/@form-validation/form-validation.css') }}" />

    <link rel="stylesheet" href="{{ asset('template/assets/vendor/css/pages/page-auth.css') }}" />

    <script src="{{ asset('template/assets/vendor/js/helpers.js') }}"></script>
    <script src="{{ asset('template/assets/js/config.js') }}"></script>
  </head>

  <body>
    <div class="container-xxl">
      <div class="authentication-wrapper authentication-basic container-p-y">
        <div class="authentication-inner py-6">
          <div class="card">
            <div class="card-body">
              <div class="app-brand justify-content-center mb-6">
                <a href="{{ url('/') }}" class="app-brand-link">
                  <span class="app-brand-logo demo">
                    @if ($brandLogo)
                      <img
                        src="{{ $brandLogo }}"
                        alt="{{ $brandName }}"
                        style="height: 26px;">
                    @else
                      <span class="text-primary">
                        <svg width="32" height="22" viewBox="0 0 32 22" fill="none" xmlns="http://www.w3.org/2000/svg">
                          <path
                            fill-rule="evenodd"
                            clip-rule="evenodd"
                            d="M0.00172773 0V6.85398C0.00172773 6.85398 -0.133178 9.01207 1.98092 10.8388L13.6912 21.9964L19.7809 21.9181L18.8042 9.88248L16.4951 7.17289L9.23799 0H0.00172773Z"
                            fill="currentColor" />
                          <path
                            opacity="0.06"
                            fill-rule="evenodd"
                            clip-rule="evenodd"
                            d="M7.69824 16.4364L12.5199 3.23696L16.5541 7.25596L7.69824 16.4364Z"
                            fill="#161616" />
                          <path
                            opacity="0.06"
                            fill-rule="evenodd"
                            clip-rule="evenodd"
                            d="M8.07751 15.9175L13.9419 4.63989L16.5849 7.28475L8.07751 15.9175Z"
                            fill="#161616" />
                          <path
                            fill-rule="evenodd"
                            clip-rule="evenodd"
                            d="M7.77295 16.3566L23.6563 0H32V6.88383C32 6.88383 31.8262 9.17836 30.6591 10.4057L19.7824 22H13.6938L7.77295 16.3566Z"
                            fill="currentColor" />
                        </svg>
                      </span>
                    @endif
                  </span>
                  <span class="app-brand-text demo text-heading fw-bold">{{ $brandName }}</span>
                </a>
              </div>

              <h4 class="mb-1">Login with Telegram</h4>
              <p class="mb-6">Click to continue with Telegram.</p>

              <div class="d-grid gap-3 mb-6">
                <button id="tgBtn" class="btn btn-primary d-flex align-items-center justify-content-center gap-2">
                  <i class="icon-base ti tabler-brand-telegram"></i>
                  <span>Continue with Telegram</span>
                </button>
              </div>

            </div>
          </div>
        </div>
      </div>
    </div>

    <script>
      (function () {
        const botId = @json($botId);
        const redirectUrl = @json($redirectUrl);
        const btn = document.getElementById('tgBtn');
        if (!btn || !botId || !redirectUrl) return;

        btn.addEventListener('click', () => {
          const origin = encodeURIComponent(window.location.origin);
          const returnTo = encodeURIComponent(redirectUrl);
          const url = `https://oauth.telegram.org/auth?bot_id=${botId}&origin=${origin}&request_access=write&return_to=${returnTo}`;
          window.open(url, '_blank', 'width=500,height=650');
        });
      })();
    </script>
  </body>
</html>

<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    @php
      $appName = $appSettings->app_name ?: 'RoomGate';
      $favicon = $appSettings->favicon_path ? asset($appSettings->favicon_path) : asset('assets/assets/img/favicon/favicon.ico');
    @endphp
    <title>{{ $appName }}</title>
    <link rel="icon" type="image/x-icon" href="{{ $favicon }}" />
    <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/css/core.css" />
    <link rel="stylesheet" href="{{ asset('assets/assets') }}/css/demo.css" />
  </head>
  <body class="bg-body">
    <div class="container-xxl d-flex align-items-center justify-content-center min-vh-100">
      <div class="card p-4 text-center">
        <h2 class="mb-2">{{ $appName }}</h2>
        <p class="text-body-secondary mb-4">{{ $appSettings->tagline ?: 'Frontend coming soon.' }}</p>
        <a href="{{ route('login') }}" class="btn btn-primary">Go to Login</a>
      </div>
    </div>
  </body>
</html>

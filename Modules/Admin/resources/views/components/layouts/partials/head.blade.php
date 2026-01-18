<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <meta name="robots" content="noindex, nofollow" />
    @php
        $appTitle = $appSettings->app_name ?: 'RoomGate';
        $favicon = $appSettings->favicon_path ? asset($appSettings->favicon_path) : asset('assets/assets/img/favicon/favicon.ico');
    @endphp
    <title>@yield('title', $appTitle . ' Admin')</title>

    <meta name="description" content="{{ $appSettings->description ?? '' }}" />

    <link rel="icon" type="image/x-icon" href="{{ $favicon }}" />

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&ampdisplay=swap"
        rel="stylesheet" />

    <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/fonts/iconify-icons.css" />

    <script src="{{ asset('assets/assets') }}/vendor/libs/@algolia/autocomplete-js.js"></script>

    <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/node-waves/node-waves.css" />
    <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/pickr/pickr-themes.css" />
    <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/css/core.css" />
    <link rel="stylesheet" href="{{ asset('assets/assets') }}/css/demo.css" />

    <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />

    <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/apex-charts/apex-charts.css" />
    <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/datatables-bs5/datatables.bootstrap5.css" />
    <link rel="stylesheet"
        href="{{ asset('assets/assets') }}/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css" />
    <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/fonts/flag-icons.css" />
    <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/notyf/notyf.css" />
    <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/animate-css/animate.css" />
    <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/sweetalert2/sweetalert2.css" />

    @stack('page-styles')

    <script src="{{ asset('assets/assets') }}/vendor/js/helpers.js"></script>
    <script src="{{ asset('assets/assets') }}/vendor/js/template-customizer.js"></script>
    <script src="{{ asset('assets/assets') }}/js/config.js"></script>
</head>

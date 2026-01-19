<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <meta name="robots" content="noindex, nofollow" />
    @php
        $appTitle = $appSettings->app_name ?: 'RoomGate';
        $favicon = $appSettings->favicon_path ? asset($appSettings->favicon_path) : asset('assets/assets/img/favicon/favicon.ico');
    @endphp
    <title>@yield('title', $appTitle)</title>

    <meta name="description" content="{{ $appSettings->description ?? '' }}" />

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ $favicon }}" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&ampdisplay=swap"
        rel="stylesheet" />

    <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/fonts/iconify-icons.css" />

    <script src="{{ asset('assets/assets') }}/vendor/libs/@algolia/autocomplete-js.js"></script>

    <!-- Core CSS -->
    <!-- build:css assets/vendor/css/theme.css  -->

    <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/node-waves/node-waves.css" />

    <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/pickr/pickr-themes.css" />

    <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/css/core.css" />
    <link rel="stylesheet" href="{{ asset('assets/assets') }}/css/demo.css" />

    <!-- Vendors CSS -->

    <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />

    <!-- endbuild -->

    <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/apex-charts/apex-charts.css" />
    <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/swiper/swiper.css" />
    <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/libs/datatables-bs5/datatables.bootstrap5.css" />
    <link rel="stylesheet"
        href="{{ asset('assets/assets') }}/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css" />
    <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/fonts/flag-icons.css" />
    <link rel="stylesheet" href="{{ asset('assets/assets') }}/css/cat.css" />
    <link rel="stylesheet" href="{{ asset('assets/assets') }}/css/roomgate-ajax.css" />
    <style>
        table.dataTable td.control:before {
            content: '+';
            display: inline-block;
            box-sizing: border-box;
            width: 20px;
            height: 20px;
            border: 1px solid currentColor;
            border-radius: 3px;
            font-size: 12px;
            line-height: 18px;
            text-align: center;
            color: var(--bs-secondary-color);
        }
        table.dataTable tr.dt-hasChild td.control:before {
            content: '-';
        }
        html.dark table.dataTable td.control:before,
        :root[data-bs-theme=dark] table.dataTable td.control:before,
        :root[data-theme=dark] table.dataTable td.control:before {
            color: rgba(255, 255, 255, 0.75);
        }
    </style>


    <!-- Page CSS -->
    <link rel="stylesheet" href="{{ asset('assets/assets') }}/vendor/css/pages/cards-advance.css" />
    @stack('page-styles')

    <!-- Helpers -->
    <script src="{{ asset('assets/assets') }}/vendor/js/helpers.js"></script>
    <!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->

    <!--? Template customizer: To hide customizer set displayCustomizer value false in config.js.  -->
    <script src="{{ asset('assets/assets') }}/vendor/js/template-customizer.js"></script>

    <!--? Config:  Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file.  -->

    <script src="{{ asset('assets/assets') }}/js/config.js"></script>
</head>

<!doctype html>
<html lang="en" class="layout-blank" dir="ltr" data-skin="default" data-bs-theme="light" data-assets-path="{{ asset('assets/assets') }}/" data-template="vertical-menu-template">
@include('core::components.layouts.partials.head')
<body>
  <div class="container-xxl">
    @yield('content')
  </div>
  @include('core::components.layouts.partials.scripts')
</body>
</html>

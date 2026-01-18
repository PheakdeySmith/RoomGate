<!doctype html>

<html lang="en" class="layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr" data-skin="default"
  data-bs-theme="light" data-assets-path="{{ asset('assets/assets') }}/" data-template="vertical-menu-template">

@include("admin::components.layouts.partials.head")

<body>
  <div class="layout-wrapper layout-content-navbar">
    <div class="layout-container">
      @include("admin::components.layouts.partials.sidebar")

      <div class="layout-page">
        @include("admin::components.layouts.partials.navbar")

        <div class="content-wrapper">
          @yield("content")

          @include("admin::components.layouts.partials.footer")

          <div class="content-backdrop fade"></div>
        </div>
      </div>
    </div>

    <div class="layout-overlay layout-menu-toggle"></div>
    <div class="drag-target"></div>
  </div>

  @include("admin::components.layouts.partials.scripts")
</body>

</html>

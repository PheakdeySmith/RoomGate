<!doctype html>

<html lang="en" class=" layout-navbar-fixed layout-menu-fixed layout-compact " dir="ltr" data-skin="default"
  data-bs-theme="light" data-assets-path="{{ asset('assets/assets') }}/" data-template="vertical-menu-template">

@include("core::components.layouts.partials.head")

<body>
  <!-- Layout wrapper -->
  <div class="layout-wrapper layout-content-navbar  ">
    <div class="layout-container">
      @include("core::components.layouts.partials.sidebar")

      <!-- Layout container -->
      <div class="layout-page">
        @include("core::components.layouts.partials.navbar")

        <!-- Content wrapper -->
        <div class="content-wrapper">
          <!-- Content -->
          @yield("content")
          <!-- / Content -->

          @include("core::components.layouts.partials.footer")

          <div class="content-backdrop fade"></div>
        </div>
        <!-- Content wrapper -->
      </div>
      <!-- / Layout page -->
    </div>

    <!-- Overlay -->
    <div class="layout-overlay layout-menu-toggle"></div>

    <!-- Drag Target Area To SlideIn Menu On Small Screens -->
    <div class="drag-target"></div>
  </div>
  <!-- / Layout wrapper -->
  <!-- / Layout wrapper -->
  <div class="box">
  </div>


  @include("core::components.layouts.partials.scripts")
</body>

</html>
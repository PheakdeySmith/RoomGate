<script src="{{ asset('assets/assets') }}/vendor/libs/jquery/jquery.js"></script>
<script src="{{ asset('assets/assets') }}/vendor/libs/popper/popper.js"></script>
<script src="{{ asset('assets/assets') }}/vendor/js/bootstrap.js"></script>
<script src="{{ asset('assets/assets') }}/vendor/libs/node-waves/node-waves.js"></script>
<script src="{{ asset('assets/assets') }}/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
<script src="{{ asset('assets/assets') }}/vendor/libs/hammer/hammer.js"></script>
<script src="{{ asset('assets/assets') }}/vendor/libs/i18n/i18n.js"></script>
<script src="{{ asset('assets/assets') }}/vendor/libs/pickr/pickr.js"></script>
<script src="{{ asset('assets/assets') }}/vendor/js/menu.js"></script>

<script src="{{ asset('assets/assets') }}/vendor/libs/apex-charts/apexcharts.js"></script>
<script src="{{ asset('assets/assets') }}/vendor/libs/datatables-bs5/datatables-bootstrap5.js"></script>
<script src="{{ asset('assets/assets') }}/vendor/libs/notyf/notyf.js"></script>
<script src="{{ asset('assets/assets') }}/vendor/libs/sweetalert2/sweetalert2.js"></script>
<script src="{{ asset('assets/assets') }}/js/cat.js"></script>
<script src="{{ asset('assets/assets') }}/js/roomgate-datatables.js"></script>

<script src="{{ asset('assets/assets') }}/js/main.js"></script>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('img[data-app-light-img], img[data-app-dark-img]').forEach((img) => {
      const light = img.getAttribute('data-app-light-img');
      const dark = img.getAttribute('data-app-dark-img');
      const absolute = [light, dark].find((value) => value && (value.startsWith('/') || value.startsWith('http')));
      if (absolute) {
        img.src = absolute;
        img.removeAttribute('data-app-light-img');
        img.removeAttribute('data-app-dark-img');
      }
    });

    if (!window.Notyf || window.RoomGateNotyf) {
      return;
    }

    class CustomNotyf extends Notyf {
      _renderNotification(options) {
        const notification = super._renderNotification(options);
        if (options.message) {
          notification.message.innerHTML = options.message;
        }
        return notification;
      }
    }

    const notyf = new CustomNotyf({
      duration: 3000,
      ripple: true,
      dismissible: false,
      position: { x: 'right', y: 'top' },
      types: [
        {
          type: 'info',
          background: config.colors.info,
          className: 'notyf__info',
          icon: {
            className: 'icon-base ti tabler-info-circle-filled icon-md text-white',
            tagName: 'i'
          }
        },
        {
          type: 'warning',
          background: config.colors.warning,
          className: 'notyf__warning',
          icon: {
            className: 'icon-base ti tabler-alert-triangle-filled icon-md text-white',
            tagName: 'i'
          }
        },
        {
          type: 'success',
          background: config.colors.success,
          className: 'notyf__success',
          icon: {
            className: 'icon-base ti tabler-circle-check-filled icon-md text-white',
            tagName: 'i'
          }
        },
        {
          type: 'error',
          background: config.colors.danger,
          className: 'notyf__error',
          icon: {
            className: 'icon-base ti tabler-xbox-x-filled icon-md text-white',
            tagName: 'i'
          }
        }
      ]
    });

    window.RoomGateNotyf = notyf;

    const flash = {
      success: @json(session('status')),
      info: @json(session('info')),
      warning: @json(session('warning')),
      error: @json(session('error'))
    };
    const errors = @json($errors->all());

    if (flash.success) {
      notyf.success(flash.success);
    }
    if (flash.info) {
      notyf.open({ type: 'info', message: flash.info });
    }
    if (flash.warning) {
      notyf.open({ type: 'warning', message: flash.warning });
    }
    if (flash.error) {
      notyf.error(flash.error);
    }
    if (errors && errors.length) {
      errors.forEach((message) => notyf.error(message));
    }

    const confirmForms = document.querySelectorAll('form[data-confirm]');
    const swal = window.Swal
      ? window.Swal.mixin({
          buttonsStyling: false,
          customClass: {
            confirmButton: 'btn btn-primary',
            cancelButton: 'btn btn-label-danger',
            denyButton: 'btn btn-label-secondary'
          }
        })
      : null;

    confirmForms.forEach((form) => {
      if (form.dataset.confirmBound === '1') {
        return;
      }
      form.dataset.confirmBound = '1';
      form.addEventListener('submit', function (event) {
        const message = form.getAttribute('data-confirm');
        if (!message) {
          return;
        }
        event.preventDefault();
        if (!swal) {
          if (window.confirm(message)) {
            form.submit();
          }
          return;
        }
        swal
          .fire({
            title: 'Are you sure?',
            text: message,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, continue',
            cancelButtonText: 'Cancel',
            reverseButtons: true
          })
          .then((result) => {
            if (result.isConfirmed) {
              form.submit();
            }
          });
      });
    });
  });
</script>

@stack('page-scripts')

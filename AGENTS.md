## RoomGate AI Coding Guide

Follow these rules when making changes in this repo.

### Structure
- Keep domain features inside `Modules/*` and reuse `components/layouts` partials.
- Use `admin::components.layouts.master` for admin views and `core` layout for tenant views.
- Do not add new standalone templates in `public/` unless asked.

### UI
- Use Vuexy template patterns. Match the existing DataTables layout/JS patterns.
- Use Notyf for success/warn/error toasts and SweetAlert2 for confirmations.
- Keep markup consistent with existing admin templates (cards, table classes, modal styles).

### DataTables
- Always include the responsive control column and `className: 'control'`.
- Use the same layout config and icons as other admin tables.

### Settings/Branding
- Branding assets are stored under `public/uploads/images`.
- Use `$appSettings` for app name/logo/favicon across layouts.
- When updating settings, clear cache key `business_settings:current`.

### Files & Privacy
- Public assets: `public/uploads/images`.
- Private assets: `public/uploads/private` and serve through controller with auth + owner/role checks.
- Log uploads/downloads to `audit_logs` with `action` = `uploaded`/`downloaded`.

### Audit Logging
- Use `App\Services\AuditLogger` for custom audit events.
- Avoid logging sensitive fields (passwords, tokens).

### Production Defaults
- Apply throttle middleware for auth POST routes unless told otherwise.
- Remove demo data in production seeders.

### Security
- Keep `SecurityHeaders` middleware enabled for all web routes.
- Do not add inline third‑party scripts without a clear reason.

### Style
- Prefer small, focused changes; avoid sweeping refactors unless requested.
- Keep comments minimal and purposeful.

### AJAX Style
- Use data attributes: `data-ajax-container`, `data-ajax-tabs`, `data-ajax-link`.
- Initialize with `RoomGateAjax.initSwappableTabs` for tab swaps; always show the standard overlay loader.
- After swap, dispatch a `user-view:loaded` (or feature-specific) event to re-init widgets.

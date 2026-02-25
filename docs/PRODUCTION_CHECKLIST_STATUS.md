# Production Checklist Status (as of 2026-02-25)

This tracks the current implementation vs `docs/PRODUCTION_CHECKLIST.md`.

## Core Architecture
- Tenant scoping enforced in queries/policies: **Done** (global scopes in `app/Models/Concerns/BelongsToTenant.php` and policies via `app/Policies/TenantOwnedPolicy.php` + `app/Providers/AppServiceProvider.php`).
- Central plan/feature gating service used: **Done** (`app/Services/PlanGate.php` used in Core controllers).
- Role/permission strategy locked: **Partial** (Spatie middleware + roles in `tenant_users`, but tenant role model still custom).
- Admin vs tenant routing separated by middleware: **Done** (`Modules/Admin/routes/web.php`, `Modules/Core/routes/web.php` + `tenant` middleware).
- Core services defined: **Partial** (`AuditLogger`, `PlanGate`, `CurrentTenant` implemented; other services not yet present).
- Functional requirements documented: **Done** (`docs/FUNCTIONAL_REQUIREMENTS.md`).

## Subscriptions & Billing
- Plans + plan limits tables: **Done** (`Modules/Core/database/migrations/2026_01_21_000001_create_core_tenant_and_subscription_tables.php`).
- Subscriptions table wired to tenants: **Done** (same migration + tenant relations).
- Subscription invoices + payments tables: **Done** (same migration).
- Bakong callback flow documented/handled: **Done** (`app/Http/Controllers/Webhooks/BakongPaymentWebhookController.php`, `app/Jobs/ProcessBakongPaymentWebhook.php`, `docs/WEBHOOKS.md`).
- Renewal + grace period logic: **Done** (`app/Console/Commands/ProcessSubscriptionRenewals.php` + `routes/console.php`).

## Data & Ops
- DB backups configured + restore tested: **Partial** (`scripts/backup-db.ps1`; restore not verified).
- Uploads backups configured: **Partial** (`scripts/backup-uploads.ps1`; restore not verified).
- Queue workers configured with retries: **Done** (`app/Jobs/SendOutboundMessage.php` backoff/tries + `docs/QUEUE_JOBS.md`).
- Scheduled tasks: **Done** (`routes/console.php` + command classes).
- Global exception handler safe errors: **Done** (`bootstrap/app.php` JSON safe errors + logging).
- Webhook payloads stored w/ idempotency: **Done** (`database/migrations/2026_01_28_000005_create_webhook_events_table.php` + `app/Services/WebhookEventService.php`).
- Multi-table writes wrapped in DB transactions: **Partial** (many controllers use `DB::transaction`, but not all flows are wrapped).

## Security
- Security headers enabled for web routes: **Done** (`app/Http/Middleware/SecurityHeaders.php`, `bootstrap/app.php`).
- Auth routes throttled: **Done** (`routes/auth.php`).
- Private files served via controller w/ auth checks: **Done** (`app/Http/Controllers/PrivateFileController.php`, `Modules/Core/app/Http/Controllers/MaintenanceAttachmentController.php`).
- Audit logging for critical actions: **Done** (`app/Services/AuditLogger.php` + `app/Providers/AppServiceProvider.php`).

## Notifications
- Event-driven notification flow defined: **Partial** (rent invoice + subscription + tenant member + maintenance events wired in `app/Providers/AppServiceProvider.php`).
- Tenant template overrides with global fallback: **Done** (`app/Services/NotificationService.php`).
- Queue-based sending with retries/backoff: **Done** (`app/Jobs/SendOutboundMessage.php` + scheduler in `routes/console.php`).
- Dedupe keys prevent duplicates: **Done** (`database/migrations/2026_01_28_000001_update_outbound_message_dedupe_index.php` + `NotificationService`).
- Bounce/failed handling defined: **Done** (`app/Http/Controllers/Webhooks/OutboundMessageWebhookController.php` + `OutboundMessageStatusService`).

## Payments & Webhooks
- Idempotency keys enforced: **Done** (webhook payloads + unique index in `webhook_events`).
- Unique constraints prevent double billing: **Partial** (subscription payments lack provider/idempotency unique constraints).
- Webhook events stored/replayable: **Done** (`webhook_events` + `docs/WEBHOOKS.md`).
- Payment state machine documented: **Done** (`docs/PAYMENT_STATE_MACHINE.md`).

## Observability
- Error monitoring configured: **Done** (Sentry/Bugsnag hooks in `bootstrap/app.php`).
- Central log aggregation: **Partial** (docs only; not configured in code).
- Audit log filters/exports: **Done** (admin + tenant exports in controllers).

## UX & QA
- Consistent Notyf + SweetAlert2 usage: **Partial** (loaded in both admin/core layouts; not validated across all pages).
- Responsive DataTables on mobile: **Done** (shared helper in `public/assets/assets/js/roomgate-datatables.js`).
- Seeders removed/disabled for production: **Done** (demo seeders skipped in production).

## Operations & Compliance
- CI/CD pipeline with tests + migrations: **Done** (`docs/DEPLOYMENT.md`).
- Staging mirrors production: **Partial** (`docs/STAGING.md`, not verified).
- Rollback plan: **Done** (`docs/DEPLOYMENT.md`).
- Data retention policy: **Done** (`docs/PRIVACY_RETENTION.md`).
- Uptime/health checks: **Done** (`/up` + `docs/DEPLOYMENT.md`).
- Privacy/consent handling: **Done** (`docs/PRIVACY_RETENTION.md`).
- Feature flags strategy: **Missing**.

## Remaining Next (Not Started)
- Feature flags strategy + ownership

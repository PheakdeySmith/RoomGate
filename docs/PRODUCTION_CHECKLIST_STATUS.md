# Production Checklist Status (as of 2026-01-28)

This tracks the current implementation vs `docs/PRODUCTION_CHECKLIST.md`.

## Core Architecture
- Tenant scoping enforced in queries/policies: **Done** (global scopes + tenant policies).
- Central plan/feature gating service used: **Done** (`app/Services/PlanGate.php`).
- Role/permission strategy locked: **Partial** (Spatie middleware present; tenant role model in `tenant_users`).
- Admin vs tenant routing separated by middleware: **Done** (`Modules/Admin/routes/web.php`, `Modules/Core/routes/web.php`).
- Core services defined: **Partial** (`AuditLogger`, `PlanGate`, `CurrentTenant` added; others not implemented).
- Functional requirements documented: **Done** (`docs/FUNCTIONAL_REQUIREMENTS.md`).

## Subscriptions & Billing
- Plans + plan limits tables: **Done** (migrations in `Modules/Core/database/migrations`).
- Subscriptions table wired to tenants: **Done**.
- Subscription invoices + payments tables: **Done**.
- Bakong callback flow documented/handled: **Missing**.
- Renewal + grace period logic: **Done** (`subscriptions:renew` command + scheduler).

## Data & Ops
- DB backups configured + restore tested: **Partial** (scripts + docs; restore not verified).
- Uploads backups configured: **Partial** (scripts + docs).
- Queue workers configured with retries: **Done** (retry/backoff documented; `SendOutboundMessage` backoff set).
- Scheduled tasks: **Done** (scheduler entries exist; documented).
- Global exception handler safe errors: **Missing** (not verified).
- Webhook payloads stored w/ idempotency: **Done** (`webhook_events` + service + route).
- Multi-table writes wrapped in DB transactions: **Partial** (several controllers use `DB::transaction`).

## Security
- Security headers enabled for web routes: **Done** (`app/Http/Middleware/SecurityHeaders.php`, `bootstrap/app.php`).
- Auth routes throttled: **Done** (`routes/auth.php`).
- Private files served via controller w/ auth checks: **Done** (`app/Http/Controllers/PrivateFileController.php`).
- Audit logging for critical actions: **Done** (centralized AuditLogger + tenant_id).

## Notifications
- Event-driven notification flow defined: **Partial** (rent invoice + contract events wired).
- Tenant template overrides with global fallback: **Done** (`app/Services/NotificationService.php`).
- Queue-based sending with retries/backoff: **Done** (`app/Jobs/SendOutboundMessage.php`).
- Dedupe keys prevent duplicates: **Done** (tenant-scoped dedupe index migration).
- Bounce/failed handling defined: **Done** (webhook status updates).

## Payments & Webhooks
- Idempotency keys enforced: **Done** (webhook payloads + unique index).
- Unique constraints prevent double billing: **Partial** (some uniques exist).
- Webhook events stored/replayable: **Done** (`webhook_events` + `docs/WEBHOOKS.md`).
- Payment state machine documented: **Missing**.

## Observability
- Error monitoring configured: **Partial** (docs + env placeholders).
- Central log aggregation: **Partial** (docs).
- Audit log filters/exports: **Partial** (admin view exists).

## UX & QA
- Consistent Notyf + SweetAlert2 usage: **Partial** (admin + core layouts now include).
- Responsive DataTables on mobile: **Done** (shared helper applied).
- Seeders removed/disabled for production: **Done** (demo seeders skipped in production).

## Operations & Compliance
- CI/CD pipeline with tests + migrations: **Done** (`docs/DEPLOYMENT.md`).
- Staging mirrors production: **Missing** (not documented).
- Rollback plan: **Done** (`docs/DEPLOYMENT.md`).
- Data retention policy: **Done** (`docs/PRIVACY_RETENTION.md`).
- Uptime/health checks: **Done** (`/up` + `docs/DEPLOYMENT.md`).
- Privacy/consent handling: **Done** (`docs/PRIVACY_RETENTION.md`).
- Feature flags strategy: **Missing**.

## Remaining Next (Not Started)
- Bakong callback flow + payment reconciliation jobs
- Payment state machine documentation
- Feature flags strategy + ownership

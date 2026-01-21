# Production Checklist

Use this checklist to track production readiness.

## Core Architecture
- [ ] Tenant scoping enforced in queries/policies (`tenant_id` filter everywhere).
- [ ] Central plan/feature gating service used for UI + backend checks.
- [ ] Role/permission strategy locked (Spatie + tenant role model).
- [ ] Admin vs tenant routing separated by middleware.
- [ ] Core services defined (TenantContext, Permission, Billing, Subscription, AuditLogger, FileService).
- [ ] Functional requirements documented (user stories, acceptance criteria, edge cases).

## Subscriptions & Billing
- [ ] Plans + plan limits tables.
- [ ] Subscriptions table wired to tenants.
- [ ] Subscription invoices + payments tables.
- [ ] Bakong payment callback flow documented and handled.
- [ ] Renewal + grace period logic.

## Data & Ops
- [ ] DB backups configured + restore tested.
- [ ] Uploads backups configured (`public/uploads/images`, `public/uploads/private`).
- [ ] Queue workers configured with retries.
- [ ] Scheduled tasks (invoices, reminders, cleanup).
- [ ] Global exception handler returns safe errors and logs full context.
- [ ] Webhook payloads stored with idempotency keys for replay safety.
- [ ] Multi-table writes wrapped in DB transactions.

## Security
- [ ] Security headers enabled for web routes.
- [ ] Auth routes throttled.
- [ ] Private files served via controller with auth/role checks.
- [ ] Audit logging for critical actions.

## Notifications
- [ ] Event-driven notification flow defined (services emit events).
- [ ] Tenant template overrides with global fallback.
- [ ] Queue-based sending with retries/backoff.
- [ ] Dedupe keys prevent duplicate sends.
- [ ] Bounce/failed handling defined.

## Payments & Webhooks
- [ ] Idempotency keys enforced for payment/webhook processing.
- [ ] Unique constraints in DB prevent double billing.
- [ ] Webhook events stored and replayable.
- [ ] Payment state machine documented (pending -> paid -> failed).

## Observability
- [ ] Error monitoring (Sentry/Bugsnag) configured.
- [ ] Central log aggregation in place.
- [ ] Audit log filters + exports available.

## UX & QA
- [ ] Consistent Notyf + SweetAlert2 usage.
- [ ] Responsive DataTables behavior confirmed on mobile.
- [ ] Seeders removed/disabled for production.

## Operations & Compliance
- [ ] CI/CD pipeline with tests and migration checks.
- [ ] Staging environment mirrors production.
- [ ] Rollback plan for deployments + migrations.
- [ ] Data retention policy for logs/audit/messages.
- [ ] Uptime/health checks configured.
- [ ] Privacy/consent handling documented if required.
- [ ] Feature flags strategy documented and toggle count kept minimal.

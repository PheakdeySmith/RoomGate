# Operations Notes

## Backups
- Database: configure daily dump + weekly full backup, store off-site.
- Uploads: back up `public/uploads/images` and `public/uploads/private`.
- Restore test: perform at least monthly, document steps and timing.
- Scripts: `scripts/backup-db.ps1` and `scripts/backup-uploads.ps1` (schedule via Task Scheduler/cron).
- Deployment checklist + rollback steps: `docs/DEPLOYMENT.md`.

## Queues & Scheduler
- Queue driver: Redis + Horizon (recommended).
- Worker retry policy: 5 attempts with backoff (60s, 300s, 900s).
- Scheduler: run `php artisan schedule:run` every minute.
- Scheduled commands: `rent:generate-invoices`, `rent:send-overdue-reminders`, `notifications:send-queued`.

## Monitoring
- Error monitoring: Sentry or Bugsnag (recommended).
- Log aggregation: centralize app + queue logs.
- Health checks: expose `/up` and verify with uptime monitor.
- Monitoring setup: `docs/MONITORING.md`.

## Webhooks & Idempotency
- Store incoming webhook payloads with `idempotency_key`.
- Enforce unique constraint to prevent double processing.
- Support replay from stored payloads.
- Outbound message webhooks: `docs/WEBHOOKS.md` (use `OUTBOUND_WEBHOOK_SECRET`).

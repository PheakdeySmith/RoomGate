# Deployment & CI/CD

## CI/CD pipeline (suggested)
1) Install dependencies (composer + npm).
2) Run tests (`php artisan test`).
3) Build assets (`npm run build`).
4) Run migrations (`php artisan migrate --force`).
5) Clear/rebuild caches (`php artisan optimize`).

## Rollback plan
- Keep previous release artifacts for at least 1 deploy cycle.
- If migration fails, run `php artisan migrate:rollback --step=1` and redeploy last known good build.
- If code deploy fails, roll back to last tagged release and run `php artisan optimize`.

## Health checks
- Endpoint: `GET /up` returns `{"status":"ok"}`.
- Uptime monitors should check `/up` and alert on non-200 responses.

## Release checklist
- Backup database + uploads (see `docs/OPERATIONS.md`).
- Confirm queue workers are running.
- Verify scheduler is running.
- Verify health check endpoint.

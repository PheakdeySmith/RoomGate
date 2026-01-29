# Staging Environment

Staging should mirror production as closely as possible to validate deploys.

## Baseline requirements
- Same Laravel + PHP versions as production.
- Same queue driver and cache driver (Redis recommended).
- Same mail + webhook providers (use sandbox keys if available).
- Same log aggregation and error monitoring integration.
- Same storage layout for uploads (or a sandboxed bucket).

## Data policy
- Use a sanitized copy of production data when possible.
- Do not include real payment or personal data unless required and approved.

## Verification checklist
- Health check: /up returns {"status":"ok"}.
- Queue workers running with retries/backoff.
- Scheduler running every minute.
- Error monitoring receives test exception.
- Logs visible in aggregation system.
- Backups and restore steps documented and tested.
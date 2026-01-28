# Monitoring & Logging

## Error monitoring
- Use Sentry or Bugsnag (set `SENTRY_DSN` or `BUGSNAG_API_KEY`).
- Capture exceptions in web + queue contexts.
- Track performance sampling via `SENTRY_TRACES_SAMPLE_RATE`.

## Log aggregation
- Centralize Laravel logs to a managed sink (ELK/Loki/CloudWatch).
- Include queue worker logs and scheduler logs.

## Alerts
- 5xx rate spikes
- Queue backlog growth
- Failed jobs spike
- Health check failures (`/up`)

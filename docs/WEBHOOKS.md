# Webhooks

Use this document to standardize inbound webhook processing.

## Outbound message status
- Endpoint: `POST /api/webhooks/outbound-messages/{provider}`
- Secret: send `X-Webhook-Secret` (matches `OUTBOUND_WEBHOOK_SECRET`)
- Idempotency: send `Idempotency-Key` or payload `id`/`event_id`
- Required payload identifiers (one of):
  - `outbound_message_id` (preferred)
  - `dedupe_key` + `tenant_id`
  - `provider_message_id` or `message_id`

## Storage & replay
- All webhook payloads are stored in `webhook_events` with `status`.
- Use `provider` + `idempotency_key` uniqueness to prevent reprocessing.
- Failed payloads can be replayed by re-posting the stored payload.

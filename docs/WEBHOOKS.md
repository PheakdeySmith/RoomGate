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

## Bakong payment callbacks
- Endpoint: `POST /api/webhooks/payments/bakong`
- Secret: send `X-Webhook-Secret` (matches `BAKONG_WEBHOOK_SECRET`)
- Idempotency: send `Idempotency-Key` or payload `idempotency_key`/`event_id`/`transaction_id`
- Reconciliation: payload is recorded in `webhook_events`, then queued to `ProcessBakongPaymentWebhook`.
- Payment matching priority:
  - `subscription_payment_id`
  - `provider_ref` / `transaction_id`
  - `invoice_number` -> latest payment on that invoice
- Status mapping:
  - `success|succeeded|paid|completed` -> `paid`
  - `failed|error|declined` -> `failed`
  - `refunded|cancelled|canceled` -> `cancelled`
  - `pending|processing` -> `pending`

## Storage & replay
- All webhook payloads are stored in `webhook_events` with `status`.
- Use `provider` + `idempotency_key` uniqueness to prevent reprocessing.
- Failed payloads can be replayed by re-posting the stored payload.

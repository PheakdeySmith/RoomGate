# Payment State Machine

This document defines RoomGate subscription payment state transitions.

## Scope
- Table: `subscription_payments`
- Tracked status values: `pending`, `paid`, `failed`, `cancelled`
- Sources of transitions:
  - Admin updates (`AdminSubscriptionController`)
  - Tenant billing action (`BillingController`)
  - Bakong webhook reconciliation (`ProcessBakongPaymentWebhook`)

## State transitions
- `pending` -> `paid`
  - Trigger: successful provider callback or manual confirmation.
  - Side effects: set `paid_at` if empty, mark related `subscription_invoices.status = paid`.
  - Event: `SubscriptionPaymentReceived`.
- `pending` -> `failed`
  - Trigger: provider failure callback or manual failure update.
  - Side effects: keep invoice unpaid.
  - Event: `SubscriptionPaymentFailed`.
- `pending` -> `cancelled`
  - Trigger: provider refund/cancel callback or manual cancel.
  - Side effects: no invoice auto-adjustment.
  - Event: `SubscriptionPaymentRefunded`.
- `failed` -> `paid`
  - Trigger: retry succeeds.
  - Side effects: invoice marked paid and `paid_at` set if empty.
  - Event: `SubscriptionPaymentReceived`.
- `paid` -> `cancelled`
  - Trigger: refund/cancel processing.
  - Side effects: no automatic invoice voiding in current implementation.
  - Event: `SubscriptionPaymentRefunded`.

## Idempotency and replay
- Incoming callbacks are stored in `webhook_events`.
- Uniqueness: `provider + idempotency_key`.
- Processed events are marked `processed`; duplicates return success without reprocessing.

## Unsupported transitions
- Any unknown provider status remains unchanged and webhook event is marked failed with reason.

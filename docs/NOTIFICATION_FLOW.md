# Notification Flow & Routing

Use this document to keep notification triggers and recipients consistent.

## Flow
1) Feature dispatches a domain event  
2) Resolve template (tenant override → global fallback)  
3) Build render vars  
4) Create `outbound_messages` row  
5) Queue delivery + retry on failure  
6) Update `outbound_messages.status` + `attempt_count`  
7) Handle provider webhooks to mark `bounced`/`failed` (idempotent)

## Routing Matrix

### User & Access
- **Invitation sent** → invitee email
- **Invitation accepted** → tenant owner/admin
- **User suspended/disabled** → affected user + tenant owner/admin

### Tenant Billing
- **Invoice created** → payer + tenant owner/admin
- **Invoice due soon** → payer + billing admin/owner
- **Invoice overdue** → payer + billing admin/owner
- **Payment received** → payer + billing admin/owner
- **Payment failed** → payer + billing admin/owner
- **Refund issued** → payer + billing admin/owner
- **Dispute opened/resolved** → billing admin/owner

### Subscription (Platform)
- **Subscription created** → tenant owner
- **Trial ending soon** → tenant owner
- **Renewal succeeded/failed** → tenant owner
- **Subscription cancelled/expired** → tenant owner + platform admin (optional)

### Maintenance
- **Request created** → assigned staff + owner/admin
- **Request assigned** → assigned staff + requester
- **Status changed** → requester + assigned staff
- **Comment added** → requester + assigned staff

### Access / Doors (optional)
- **Access denied** → owner/admin or security role
- **QR expired/revoked** → affected user + owner/admin (optional)

## Notes
- Use `dedupe_key` on outbound messages to prevent duplicate sends.
- Centralize routing + delivery in a single `NotificationService` so features just call it.
- Prefer event listeners (see `RentInvoiceCreated` / `RentInvoiceOverdue`) to keep controllers thin.
- Webhook consumers should pass `outbound_message_id` or `dedupe_key` for reliable matching.

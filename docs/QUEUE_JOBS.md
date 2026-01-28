# Queue & Jobs Map

This document lists background jobs and queues for the RoomGate schema.

## Queued Jobs (Redis + Horizon)
- **Outbound messages**: send email/SMS/WhatsApp/push, retry failed sends.  
  Tables: `outbound_messages`
- **Invoice generation**: create invoices for contracts + utilities.  
  Tables: `contracts`, `utility_rates`, `meter_readings`, `invoices`, `invoice_items`
- **Subscription billing**: create subscription invoices and process payments.  
  Tables: `subscriptions`, `subscription_invoices`, `subscription_payments`
- **Payment reconciliation**: handle Bakong callbacks and update payment status.  
  Tables: `payments`, `subscription_payments`
- **Maintenance notifications**: notify assigned staff + tenants on status changes.  
  Tables: `maintenance_requests`, `maintenance_status_events`, `maintenance_comments`
- **Audit batching (optional)**: batch audit writes if volume spikes.  
  Tables: `audit_logs`

## Scheduled Jobs (cron -> queue)
- **Daily**: generate due invoices for contracts/utilities.  
  Tables: `contracts`, `utility_rates`, `meter_readings`, `invoices`
- **Daily**: subscription renewal + grace period check.  
  Tables: `subscriptions`
- **Daily**: expire invitations + QR codes.  
  Tables: `tenant_invitations`, `user_qr_codes`
- **Hourly**: retry failed outbound messages.  
  Tables: `outbound_messages`
- **Weekly**: cleanup old logs (optional).  
  Tables: `audit_logs`

## Retry & Backoff
- `SendOutboundMessage` uses 5 tries with backoff: 60s, 300s, 900s.
- `notifications:send-queued` dispatches queued messages every 5 minutes (see `routes/console.php`).

## Caching (Redis)
- **Plan limits**: cache by plan id for gating.  
  Tables: `plans`, `plan_limits`
- **Tenant settings**: cache app settings + branding.  
  Tables: `business_settings`
- **Permission lookups**: cache user permissions/roles.  
  Tables: `roles`, `permissions`, `model_has_roles`

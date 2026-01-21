# Features Overview

High-level feature map for RoomGate based on schema and current docs.

## Core Platform
- Authentication + identity providers (`users`, `auth_identities`)
- RBAC (Spatie roles/permissions)
- Audit logging
- Settings/branding

## Multi-Tenant
- Tenants + membership (`tenants`, `tenant_users`)
- Invitations (`tenant_invitations`)

## Property & Rooms
- Properties
- Room types
- Rooms
- Amenities + assignments
- Property staff assignments (`property_users`)

## Leasing & Billing (Tenant)
- Contracts (leases)
- Invoices + items
- Payments + allocations
- Refunds + disputes

## Utilities
- Utility types
- Meters + readings
- Utility rates

## Access Control
- Doors + door-room mapping
- Physical keys + issues
- QR access + permissions
- Access events

## Maintenance
- Requests
- Status events
- Comments + attachments
- Work orders

## Notifications
- Templates (global + tenant)
- Outbound messages (queue, retries)

## Subscriptions (Platform)
- Plans + limits
- Subscriptions
- Subscription invoices + payments

## Ops & Quality
- Queues + scheduled jobs (see `docs/QUEUE_JOBS.md`)
- Production checklist (see `docs/PRODUCTION_CHECKLIST.md`)

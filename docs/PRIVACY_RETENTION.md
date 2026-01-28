# Privacy, Consent & Retention

## Consent basics
- Collect only data required for tenancy and billing.
- Provide clear notice for in-app notifications and email delivery.
- Allow tenants to opt out of non-critical notifications where feasible.

## Data retention (suggested defaults)
- Audit logs: retain 12-24 months.
- Outbound messages: retain 6-12 months.
- Webhook events: retain 30-90 days.
- Deleted tenants/users: anonymize after 30-90 days (keep billing records per legal requirements).

## Access controls
- Enforce tenant scoping (`tenant_id`) in queries and policies.
- Restrict private uploads through controller + auth.

## Operational notes
- Document data export requests and response time.
- Track deletion/anonymization tasks via maintenance jobs.

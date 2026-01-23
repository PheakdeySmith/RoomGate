# RoomGate Implementation Plan

This document breaks the RoomGate build into small, ordered tasks with analysis.

## Status Tracker
Use this section to mark progress.

### Done
- [ ] Authentication system (login/register/forgot password)
- [ ] Roles + permissions baseline
- [ ] Audit logs

### In Progress
- [ ] Tenants + tenant_users
- [ ] Admin/tenant separation

### Next
- [ ] Properties
- [ ] Rooms + room types
- [ ] Contracts + invoices + payments

## Phase 0 — Foundations (Must‑Have)
1) Tenant Core  
   - Create tenants  
   - Tenant membership (tenant_users)  
   - Tenant invitations  
2) Roles & Permissions  
   - Spatie roles/permissions  
   - Admin vs tenant guard separation  
   - Middleware policies per module  
3) Global Settings  
   - Branding + app settings  
   - Feature gating hook (plan/limits service)  
   - Central PlanGate/FeatureGate service for UI + backend checks  
   - Define limit keys (properties_max, rooms_max, staff_max, documents_max, etc.)  

## Phase 1 — Property & Room Management
4) Properties  
   - CRUD properties  
   - Status (active/inactive/archived)  
5) Room Types + Rooms  
   - Room type CRUD  
   - Room CRUD + availability state  
6) Amenities  
   - Amenities CRUD  
   - Attach to rooms / room types  

## Phase 2 — Leasing (Core Business Flow)
7) Contracts (Leases)  
   - Create/approve lease  
   - Link tenant + room  
8) Invoices  
   - Generate invoices (rent + utilities)  
   - Statuses: due/paid/overdue  
9) Payments  
   - Record payments  
   - Apply to invoices  
   - Refund/dispute handling (later)  

## Phase 3 — Operations
10) Maintenance  
    - Requests, assignments, status changes  
    - Comments + attachments  
11) Access Control  
    - Doors, QR codes, access logs  
    - Permissions by tenant/room  

## Phase 4 — Subscription & Plans (Platform Revenue)
12) Plans + Limits  
13) Subscriptions  
14) Subscription invoices + payments  
15) Bakong callback flow  

## Phase 5 — Notifications + Messaging
16) Central NotificationService  
17) Templates + outbound messages  
18) Event → notification routing  
19) In‑app messaging (optional)  

## Phase 6 — Reporting & Analytics
20) Occupancy, revenue, delinquency  
21) Maintenance SLA  
22) Export reports  

## Analysis Notes
- Everything depends on **tenants + tenant_users** first.  
- Billing relies on **contracts + invoices + payments**.  
- Notifications should be centralized so all modules reuse it later.  
- Subscriptions are separate from tenant billing (platform revenue).  
- Plan/feature gating should be enforced in one service; no scattered checks.  
- Free plan uses limits with graceful upgrade prompts.  

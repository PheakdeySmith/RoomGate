# RoomGate Chat Schema (In-App, Tenant-Scoped)

This document outlines a production-ready chat schema for RoomGate.
It is designed for multi-tenant usage, role-aware access, and Laravel conventions.

## Goals
- Tenant-scoped conversations (no cross-tenant leakage).
- Support direct and group chats.
- Efficient message retrieval (conversation + time ordering).
- Clear membership tracking (join/leave + read state).
- Extensible metadata for future features.

## Core Tables (Production-Ready)

### conversations
Stores chat threads.

Columns:
- id (BIGINT, PK)
- tenant_id (BIGINT, required)
- type (ENUM: direct, group)
- title (VARCHAR 255, nullable)
- created_by (BIGINT, user id)
- direct_key (VARCHAR 64, nullable)
- last_message_at (DATETIME, nullable)
- created_at, updated_at

Indexes:
- (tenant_id, type)
- (tenant_id, last_message_at)
- unique (tenant_id, direct_key) where type = direct

Notes:
- "direct" is 1:1 chat.
- "group" is multi-user chat.
- direct_key = "min(user_id,other_user_id):max(user_id,other_user_id)" to prevent duplicate 1:1 threads.

### conversation_participants
Links users to conversations.

Columns:
- conversation_id (BIGINT)
- user_id (BIGINT)
- role (ENUM: owner, admin, staff, tenant) optional snapshot
- last_read_at (DATETIME, nullable)
- muted_at (DATETIME, nullable)
- left_at (DATETIME, nullable)
- nickname (VARCHAR 120, nullable)
- notification_level (ENUM: all, mentions, none) default all
- created_at, updated_at

Constraints:
- unique (conversation_id, user_id)
- FK conversation_id -> conversations.id
- FK user_id -> users.id

Indexes:
- (user_id, left_at)
- (conversation_id, left_at)

Notes:
- Use left_at to preserve membership history.
- last_read_at supports unread counts.

### messages
Stores messages per conversation.

Columns:
- id (BIGINT, PK)
- conversation_id (BIGINT)
- sender_id (BIGINT, user id)
- type (ENUM: text, file, system)
- body (TEXT, nullable)
- metadata (JSON, nullable)
- edited_at (DATETIME, nullable)
- deleted_at (DATETIME, nullable)   // soft delete per message
- created_at, updated_at

Indexes:
- (conversation_id, created_at)
- (sender_id, created_at)
- (conversation_id, deleted_at)

Notes:
- body can be NULL for file/system messages.
- metadata can store reply_to, edit history, etc.

## Required Support Tables (For Reliability)

### message_deliveries
Tracks delivery/read state per user (scales for group chats).

Columns:
- message_id (BIGINT)
- user_id (BIGINT)
- delivered_at (DATETIME, nullable)
- read_at (DATETIME, nullable)
- created_at

Constraints:
- unique (message_id, user_id)
- FK message_id -> messages.id
- FK user_id -> users.id

Indexes:
- (user_id, read_at)
- (message_id)

Notes:
- For direct chats, this is optional but still useful.
- For large groups, use async fan-out to populate deliveries.

### conversation_blocks
Optional user-level blocks (anti-abuse).

Columns:
- tenant_id (BIGINT)
- blocker_id (BIGINT)
- blocked_id (BIGINT)
- created_at

Constraints:
- unique (tenant_id, blocker_id, blocked_id)

Notes:
- Prevents direct chats and hides messages.

## Optional Tables

### message_attachments
Stores file uploads for messages.

Columns:
- message_id (BIGINT)
- file_path (TEXT, stored in private uploads)
- mime_type (VARCHAR 80)
- size_bytes (INT)
- original_name (VARCHAR 255)
- created_at

Constraints:
- FK message_id -> messages.id

Notes:
- Serve via controller with auth + tenant checks.

### message_reactions
Tracks reactions (like, heart, etc).

Columns:
- message_id (BIGINT)
- user_id (BIGINT)
- reaction (VARCHAR 32)
- created_at

Constraints:
- unique (message_id, user_id, reaction)
- FK message_id -> messages.id
- FK user_id -> users.id

### conversation_pins (optional)
Pinned messages per conversation.

Columns:
- conversation_id (BIGINT)
- message_id (BIGINT)
- pinned_by (BIGINT)
- created_at

Constraints:
- unique (conversation_id, message_id)

## Access Rules
- All queries must include tenant_id scoping.
- Only participants can read or post.
- Admin/owner can add/remove participants within their tenant.
- Enforce block rules for direct chats.

## Real-Time Flow (Later)
- Broadcast message created event to conversation channel.
- Update unread counts using participant.last_read_at.
- Typing/presence can be tracked via cache + websockets.

## Real-Time Improvements (Telegram-like Features)

### Online Status / Last Seen
- Store presence in cache (Redis) with short TTL keys.
- Write last_seen_at to user profile periodically (or when disconnecting).
- Example key: `presence:user:{id}` -> timestamp.

### Typing Indicators
- Emit websocket events (no DB writes).
- Event payload: conversation_id, user_id, typing=true/false.
- Clients auto-expire typing state after 3-5 seconds.

### Read Receipts
- Update conversation_participants.last_read_at when a user opens a thread.
- Optional: add messages.read_at per user via a separate table if you need per-message read state at scale.

### Delivery Status
- Optional: add message_deliveries table (message_id, user_id, delivered_at, read_at).
- Use if you want per-user delivery tracking for group chats.

### Notifications (Optional)
- Push notifications require user_device table + provider integration.
- Keep this separate from core chat tables.

## Suggested Real-Time Stack
- Websocket: Laravel Echo + Soketi (self-host) or Pusher.
- Cache: Redis for presence/typing.
- Queue: Laravel queue for fan-out and notification delivery.

## Suggested Defaults
- Use BIGINT primary keys (Laravel default).
- Use JSON for metadata.
- Use app timestamps (created_at/updated_at), no triggers required.

## Production Readiness Checklist

### Data & Constraints
- Add NOT NULL constraints where appropriate (tenant_id, conversation_id, sender_id).
- Enforce unique direct conversations by participant pair (see below).
- Add soft deletes only if you need reversible removal; otherwise, prefer delete + audit log.

### Direct Chat Uniqueness
To prevent duplicate 1:1 threads:
- Add a derived column on conversations: direct_key (VARCHAR 64).
- direct_key = "min(user_id,other_user_id):max(user_id,other_user_id)".
- Unique index on (tenant_id, direct_key).

### Indexing & Query Paths
- conversations: (tenant_id, last_message_at) for chat list ordering.
- conversation_participants: (user_id, left_at) for user inbox list.
- messages: (conversation_id, created_at) for paging.
- messages: (sender_id, created_at) for moderation/search.

### Tenant Safety
- Always filter by tenant_id in queries and policies.
- Verify participant membership before reading/posting.
- For group admin actions, check role on participant pivot.

### Message Delivery & Ordering
- Use server timestamps for created_at ordering.
- For real-time, emit message id + created_at to avoid ordering drift.
- For message_deliveries, fill delivered_at in background jobs.

### Attachments & Privacy
- Store files under private uploads.
- Serve via controller with auth + tenant check.
- Log uploads/downloads to audit_logs (action = uploaded/downloaded).

### Retention & Compliance
- Define retention policy (e.g., 365 days) and scheduled purge.
- Provide export endpoints for compliance if needed.

### Realtime & Scaling
- Use Redis for presence/typing state with TTL.
- Queue outbound notifications to avoid blocking message send.
- Consider rate limiting for spam control.

### Optional Auditing
- Log message create/delete events (exclude sensitive body when required).

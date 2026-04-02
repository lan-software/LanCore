# Database Design Description (DBDD)

**Document Identifier:** LanCore-DBDD-001
**Version:** 0.1.0
**Date:** 2026-04-02
**Status:** Draft
**Classification:** Unclassified

### Author

| Role | Name |
|------|------|
| Project Lead | Markus Kohn |

---

## 1. Scope

### 1.1 Identification

This Database Design Description (DBDD) describes the database design for the **LanCore** system.

### 1.2 System Overview

LanCore uses PostgreSQL as its primary relational database, with 77+ tables spanning authentication, event management, ticketing, payments, content, notifications, integrations, and monitoring domains.

### 1.3 Document Overview

This document describes the database schema, entity relationships, data types, and design decisions.

---

## 2. Referenced Documents

- [SRS](SRS.md) — Software Requirements Specification
- [SDD](SDD.md) — Software Design Description

---

## 3. Database-Wide Design Decisions

### 3.1 Database Management System

| Property | Value |
|----------|-------|
| Production DBMS | PostgreSQL 15+ |
| Development/Test DBMS | SQLite (for speed) |
| ORM | Eloquent (Laravel) |
| Migration Tool | Laravel Migrations (`artisan migrate`) |

### 3.2 Conventions

- **Primary Keys:** Auto-incrementing unsigned big integers (`id`)
- **Timestamps:** `created_at` and `updated_at` on all tables
- **Soft Deletes:** Applied where logical deletion is preferred over hard deletion
- **Foreign Keys:** Enforced with `constrained()` and `cascadeOnDelete()` where appropriate
- **Naming:** Snake_case table and column names; pivot tables alphabetical (e.g., `event_sponsor`)
- **JSONB:** Used for flexible structured data (seat plans, banner images, sidebar favorites)
- **Indexes:** Applied on foreign keys and frequently queried columns

### 3.3 Schema Management

All schema changes managed via Laravel migrations in `database/migrations/`. Migrations are timestamped and executed in order. Migration files total 77+.

---

## 4. Database Design

### 4.1 Core Authentication & User Management

#### 4.1.1 users

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | Primary key |
| name | varchar | Display name |
| email | varchar (unique) | Login email |
| email_verified_at | timestamp | Email verification timestamp |
| password | varchar | Bcrypt-hashed password |
| two_factor_secret | text (nullable) | Encrypted TOTP secret |
| two_factor_recovery_codes | text (nullable) | Encrypted recovery codes |
| two_factor_confirmed_at | timestamp (nullable) | 2FA confirmation |
| remember_token | varchar | Session remember token |
| stripe_id | varchar (nullable) | Stripe customer ID (Cashier) |
| pm_type | varchar (nullable) | Payment method type |
| pm_last_four | varchar (nullable) | Last 4 digits of payment method |
| trial_ends_at | timestamp (nullable) | Trial period end |
| ticket_discoverable | boolean | Ticket discovery visibility |
| ticket_discovery_name | varchar (nullable) | Custom discovery name |
| sidebar_favorites | jsonb (nullable) | Navigation favorites |
| created_at | timestamp | |
| updated_at | timestamp | |

#### 4.1.2 roles

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | Primary key |
| name | varchar (unique) | Role name (Admin, Superadmin, SponsorManager) |
| created_at | timestamp | |
| updated_at | timestamp | |

#### 4.1.3 role_user (pivot)

| Column | Type | Description |
|--------|------|-------------|
| role_id | bigint FK | References roles.id |
| user_id | bigint FK | References users.id |

#### 4.1.4 sessions

| Column | Type | Description |
|--------|------|-------------|
| id | varchar PK | Session ID |
| user_id | bigint FK (nullable) | References users.id |
| ip_address | varchar | Client IP |
| user_agent | text | Browser user agent |
| payload | longtext | Encrypted session data |
| last_activity | integer | Unix timestamp |

### 4.2 Event & Venue

#### 4.2.1 events

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | Primary key |
| name | varchar | Event name |
| description | text | Event description |
| start_date | datetime | Event start |
| end_date | datetime | Event end |
| status | varchar | EventStatus enum (draft, published) |
| seat_capacity | integer (nullable) | Total seat capacity |
| banner_images | jsonb (nullable) | Array of banner image data |
| primary_program_id | bigint FK (nullable) | References programs.id |
| created_at | timestamp | |
| updated_at | timestamp | |

#### 4.2.2 venues

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | Primary key |
| name | varchar | Venue name |
| description | text (nullable) | Venue description |
| address_id | bigint FK | References addresses.id |
| event_id | bigint FK | References events.id |
| created_at | timestamp | |
| updated_at | timestamp | |

#### 4.2.3 addresses

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | Primary key |
| street | varchar | Street address |
| city | varchar | City |
| state | varchar (nullable) | State/province |
| country | varchar | Country |
| postal_code | varchar (nullable) | Postal/zip code |
| created_at | timestamp | |
| updated_at | timestamp | |

#### 4.2.4 venue_images

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | Primary key |
| venue_id | bigint FK | References venues.id |
| path | varchar | Storage path |
| alt_text | varchar (nullable) | Accessibility text |
| sort_order | integer | Display order |
| created_at | timestamp | |
| updated_at | timestamp | |

### 4.3 Programs & Scheduling

#### 4.3.1 programs

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | Primary key |
| event_id | bigint FK | References events.id |
| name | varchar | Program name |
| description | text (nullable) | Description |
| visibility | varchar | ProgramVisibility enum |
| created_at | timestamp | |
| updated_at | timestamp | |

#### 4.3.2 time_slots

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | Primary key |
| program_id | bigint FK | References programs.id |
| name | varchar | Time slot name |
| description | text (nullable) | Description |
| start_time | datetime | Slot start |
| end_time | datetime | Slot end |
| visibility | varchar | Visibility level |
| sort_order | integer | Display order |
| created_at | timestamp | |
| updated_at | timestamp | |

### 4.4 Ticketing

#### 4.4.1 ticket_types

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | Primary key |
| event_id | bigint FK | References events.id |
| ticket_category_id | bigint FK (nullable) | References ticket_categories.id |
| ticket_group_id | bigint FK (nullable) | References ticket_groups.id |
| name | varchar | Type name |
| description | text (nullable) | Description |
| price | decimal | Ticket price |
| quota | integer | Available quantity |
| max_per_user | integer (nullable) | Per-user purchase limit |
| sale_start | datetime (nullable) | Sales open date |
| sale_end | datetime (nullable) | Sales close date |
| is_seated | boolean | Seating flag |
| is_locked | boolean | Lock flag |
| created_at | timestamp | |
| updated_at | timestamp | |

#### 4.4.2 tickets

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | Primary key |
| ticket_type_id | bigint FK | References ticket_types.id |
| event_id | bigint FK | References events.id |
| owner_id | bigint FK (nullable) | References users.id (purchaser) |
| manager_id | bigint FK (nullable) | References users.id (manager) |
| user_id | bigint FK (nullable) | References users.id (attendee) |
| order_id | bigint FK (nullable) | References orders.id |
| status | varchar | TicketStatus enum |
| validation_id | varchar (unique, nullable) | Check-in validation code |
| created_at | timestamp | |
| updated_at | timestamp | |

#### 4.4.3 ticket_categories

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | Primary key |
| event_id | bigint FK | References events.id |
| name | varchar | Category name |
| description | text (nullable) | Description |
| created_at | timestamp | |
| updated_at | timestamp | |

#### 4.4.4 ticket_groups

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | Primary key |
| event_id | bigint FK | References events.id |
| name | varchar | Group name |
| created_at | timestamp | |
| updated_at | timestamp | |

#### 4.4.5 ticket_addons

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | Primary key |
| event_id | bigint FK | References events.id |
| name | varchar | Add-on name |
| description | text (nullable) | Description |
| price | decimal | Add-on price |
| quota | integer (nullable) | Available quantity |
| created_at | timestamp | |
| updated_at | timestamp | |

#### 4.4.6 ticket_ticket_addon (pivot)

| Column | Type | Description |
|--------|------|-------------|
| ticket_id | bigint FK | References tickets.id |
| ticket_addon_id | bigint FK | References ticket_addons.id |
| price | decimal | Price at time of purchase |
| created_at | timestamp | |

### 4.5 Shop & Orders

#### 4.5.1 orders

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | Primary key |
| user_id | bigint FK | References users.id |
| event_id | bigint FK | References events.id |
| status | varchar | OrderStatus enum |
| payment_method | varchar | PaymentMethod enum |
| total | decimal | Order total |
| stripe_session_id | varchar (nullable) | Stripe checkout session |
| created_at | timestamp | |
| updated_at | timestamp | |

#### 4.5.2 order_lines

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | Primary key |
| order_id | bigint FK | References orders.id |
| description | varchar | Line item description |
| quantity | integer | Quantity |
| unit_price | decimal | Price per unit |
| total | decimal | Line total |
| created_at | timestamp | |
| updated_at | timestamp | |

#### 4.5.3 carts

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | Primary key |
| user_id | bigint FK | References users.id |
| event_id | bigint FK | References events.id |
| created_at | timestamp | |
| updated_at | timestamp | |

**Unique constraint:** (user_id, event_id) — one cart per user per event.

#### 4.5.4 cart_items

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | Primary key |
| cart_id | bigint FK | References carts.id |
| purchasable_type | varchar | Polymorphic type (TicketType, Addon) |
| purchasable_id | bigint | Polymorphic ID |
| quantity | integer | Item quantity |
| created_at | timestamp | |
| updated_at | timestamp | |

#### 4.5.5 vouchers

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | Primary key |
| event_id | bigint FK | References events.id |
| code | varchar (unique) | Voucher code |
| type | varchar | VoucherType enum (fixed_amount, percentage) |
| value | decimal | Discount value |
| max_uses | integer (nullable) | Usage limit |
| used_count | integer | Current usage count |
| expires_at | datetime (nullable) | Expiry date |
| created_at | timestamp | |
| updated_at | timestamp | |

#### 4.5.6 purchase_requirements

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | Primary key |
| name | varchar | Requirement name |
| description | text | Requirement description |
| is_required | boolean | Mandatory flag |
| created_at | timestamp | |
| updated_at | timestamp | |

#### 4.5.7 global_purchase_conditions

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | Primary key |
| name | varchar | Condition name |
| description | text | Condition text |
| requires_scroll | boolean | Must scroll to read |
| created_at | timestamp | |
| updated_at | timestamp | |

#### 4.5.8 payment_provider_conditions

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | Primary key |
| payment_method | varchar | PaymentMethod enum |
| name | varchar | Condition name |
| description | text | Condition text |
| requires_scroll | boolean | Must scroll to read |
| created_at | timestamp | |
| updated_at | timestamp | |

#### 4.5.9 checkout_acknowledgements

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | Primary key |
| user_id | bigint FK | References users.id |
| acknowledgeable_type | varchar | Polymorphic type |
| acknowledgeable_id | bigint | Polymorphic ID |
| acknowledged_at | timestamp | When acknowledged |
| created_at | timestamp | |
| updated_at | timestamp | |

### 4.6 Sponsoring

#### 4.6.1 sponsor_levels

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | Primary key |
| name | varchar | Level name (Gold, Silver, etc.) |
| sort_order | integer | Display order |
| created_at | timestamp | |
| updated_at | timestamp | |

#### 4.6.2 sponsors

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | Primary key |
| sponsor_level_id | bigint FK | References sponsor_levels.id |
| name | varchar | Sponsor name |
| logo | varchar (nullable) | Logo file path |
| link | varchar (nullable) | Sponsor website URL |
| created_at | timestamp | |
| updated_at | timestamp | |

#### 4.6.3 Pivot Tables

- **event_sponsor** — (event_id, sponsor_id)
- **program_sponsor** — (program_id, sponsor_id)
- **sponsor_time_slot** — (sponsor_id, time_slot_id)
- **sponsor_user** — (sponsor_id, user_id)

### 4.7 Games

#### 4.7.1 games

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | Primary key |
| name | varchar | Game name |
| description | text (nullable) | Description |
| created_at | timestamp | |
| updated_at | timestamp | |

#### 4.7.2 game_modes

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | Primary key |
| game_id | bigint FK | References games.id |
| name | varchar | Mode name |
| team_size | integer (nullable) | Players per team |
| parameters | jsonb (nullable) | Custom mode parameters |
| created_at | timestamp | |
| updated_at | timestamp | |

### 4.8 Seating

#### 4.8.1 seat_plans

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | Primary key |
| event_id | bigint FK | References events.id |
| name | varchar | Plan name |
| data | jsonb | Canvas seat plan data |
| created_at | timestamp | |
| updated_at | timestamp | |

### 4.9 News & Content

#### 4.9.1 news_articles

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | Primary key |
| user_id | bigint FK | References users.id (author) |
| title | varchar | Article title |
| body | longtext | Rich text content |
| visibility | varchar | ArticleVisibility enum |
| seo_title | varchar (nullable) | SEO meta title |
| seo_description | text (nullable) | SEO meta description |
| notify_users | boolean | Send notifications on publish |
| archived_at | timestamp (nullable) | Archive timestamp |
| published_at | timestamp (nullable) | Publication timestamp |
| created_at | timestamp | |
| updated_at | timestamp | |

#### 4.9.2 news_comments

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | Primary key |
| news_article_id | bigint FK | References news_articles.id |
| user_id | bigint FK | References users.id |
| body | text | Comment text |
| created_at | timestamp | |
| updated_at | timestamp | |

#### 4.9.3 news_comment_votes

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | Primary key |
| news_comment_id | bigint FK | References news_comments.id |
| user_id | bigint FK | References users.id |
| value | integer | Vote value (+1 or -1) |
| created_at | timestamp | |
| updated_at | timestamp | |

### 4.10 Announcements

#### 4.10.1 announcements

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | Primary key |
| event_id | bigint FK (nullable) | References events.id |
| user_id | bigint FK | References users.id (author) |
| title | varchar | Title |
| body | text | Content |
| priority | varchar | AnnouncementPriority enum |
| created_at | timestamp | |
| updated_at | timestamp | |

#### 4.10.2 announcement_dismissals

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | Primary key |
| announcement_id | bigint FK | References announcements.id |
| user_id | bigint FK | References users.id |
| created_at | timestamp | |

### 4.11 Achievements

#### 4.11.1 achievements

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | Primary key |
| name | varchar | Achievement name |
| description | text (nullable) | Description |
| icon | varchar (nullable) | Icon path |
| created_at | timestamp | |
| updated_at | timestamp | |

#### 4.11.2 achievement_user (pivot)

| Column | Type | Description |
|--------|------|-------------|
| achievement_id | bigint FK | References achievements.id |
| user_id | bigint FK | References users.id |
| earned_at | timestamp | When earned |

#### 4.11.3 achievement_events

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | Primary key |
| achievement_id | bigint FK | References achievements.id |
| event_class | varchar | GrantableEvent enum value |
| created_at | timestamp | |
| updated_at | timestamp | |

### 4.12 Notifications

#### 4.12.1 notification_preferences

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | Primary key |
| user_id | bigint FK (unique) | References users.id |
| news_mail | boolean | Email on news |
| news_push | boolean | Push on news |
| events_mail | boolean | Email on events |
| events_push | boolean | Push on events |
| comments_mail | boolean | Email on comments |
| comments_push | boolean | Push on comments |
| programs_mail | boolean | Email on programs |
| programs_push | boolean | Push on programs |
| announcements_mail | boolean | Email on announcements |
| announcements_push | boolean | Push on announcements |
| created_at | timestamp | |
| updated_at | timestamp | |

#### 4.12.2 push_subscriptions

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | Primary key |
| user_id | bigint FK | References users.id |
| endpoint | text | Push service endpoint URL |
| public_key | varchar | VAPID public key |
| auth_token | varchar | Authentication token |
| created_at | timestamp | |
| updated_at | timestamp | |

#### 4.12.3 notifications (Laravel)

| Column | Type | Description |
|--------|------|-------------|
| id | uuid PK | Notification ID |
| type | varchar | Notification class |
| notifiable_type | varchar | Polymorphic type |
| notifiable_id | bigint | Polymorphic ID |
| data | json | Notification payload |
| read_at | timestamp (nullable) | Read timestamp |
| archived_at | timestamp (nullable) | Archive timestamp |
| created_at | timestamp | |
| updated_at | timestamp | |

### 4.13 Integrations & Webhooks

#### 4.13.1 integration_apps

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | Primary key |
| name | varchar | App name |
| description | text (nullable) | Description |
| callback_url | varchar (nullable) | SSO callback URL |
| nav_title | varchar (nullable) | Navigation link title |
| nav_url | varchar (nullable) | Navigation link URL |
| nav_icon | varchar (nullable) | Navigation icon |
| webhook_events | jsonb (nullable) | Subscribed webhook events |
| webhook_secret | varchar (nullable) | HMAC signing secret |
| announcement_webhook_secret | varchar (nullable) | Announcement-specific secret |
| role_update_webhook | boolean | Subscribe to role updates |
| created_at | timestamp | |
| updated_at | timestamp | |

#### 4.13.2 integration_tokens

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | Primary key |
| integration_app_id | bigint FK | References integration_apps.id |
| token_hash | varchar | SHA-256 hash of token |
| name | varchar | Token name |
| last_used_at | timestamp (nullable) | Last usage |
| expires_at | timestamp (nullable) | Expiry date |
| created_at | timestamp | |
| updated_at | timestamp | |

#### 4.13.3 webhooks

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | Primary key |
| integration_app_id | bigint FK (nullable) | References integration_apps.id |
| url | varchar | Delivery URL |
| events | jsonb | Subscribed events array |
| secret | varchar | HMAC signing secret |
| is_active | boolean | Active flag |
| sent_count | integer | Total deliveries |
| created_at | timestamp | |
| updated_at | timestamp | |

#### 4.13.4 webhook_deliveries

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | Primary key |
| webhook_id | bigint FK | References webhooks.id |
| event | varchar | Event type delivered |
| payload | jsonb | Delivered payload |
| status_code | integer (nullable) | HTTP response code |
| duration_ms | integer (nullable) | Request duration |
| response_body | text (nullable) | Response body |
| created_at | timestamp | |

### 4.14 Stripe/Cashier Billing

#### 4.14.1 customers

Standard Laravel Cashier table linking users to Stripe customer records.

#### 4.14.2 subscriptions / subscription_items

Standard Laravel Cashier tables for subscription management with metered billing support.

### 4.15 Audit Trail

#### 4.15.1 audits (owen-it/laravel-auditing)

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | Primary key |
| user_type | varchar (nullable) | Auditor type |
| user_id | bigint (nullable) | Auditor ID |
| event | varchar | Operation (created, updated, deleted) |
| auditable_type | varchar | Audited model type |
| auditable_id | bigint | Audited model ID |
| old_values | json | Previous values |
| new_values | json | New values |
| url | varchar (nullable) | Request URL |
| ip_address | varchar (nullable) | Client IP |
| user_agent | varchar (nullable) | Browser agent |
| tags | varchar (nullable) | Audit tags |
| created_at | timestamp | |
| updated_at | timestamp | |

### 4.16 Infrastructure Tables

- **cache** — File/database cache entries
- **cache_locks** — Distributed lock management
- **jobs** — Queue job storage
- **job_batches** — Batch job tracking
- **failed_jobs** — Failed job records
- **pulse_entries**, **pulse_aggregates**, **pulse_values** — Laravel Pulse monitoring
- **telescope_entries**, **telescope_entries_tags**, **telescope_monitoring** — Laravel Telescope

---

## 5. Entity Relationship Summary

```
User ──< Role (many-to-many via role_user)
User ──< Ticket (as owner, manager, or user)
User ──< Order
User ──< Cart
User ──< NewsArticle (as author)
User ──< NewsComment
User ──< Achievement (many-to-many via achievement_user)
User ── NotificationPreference (one-to-one)
User ──< PushSubscription
User ──< AnnouncementDismissal

Event ──< Venue
Event ──< Program
Event ──< TicketType
Event ──< TicketCategory
Event ──< TicketGroup
Event ──< Addon
Event ──< Cart
Event ──< Order
Event ──< SeatPlan
Event ──< Sponsor (many-to-many via event_sponsor)
Event ──< Announcement
Event ── Program (primary_program_id)

Program ──< TimeSlot
Program ──< Sponsor (many-to-many)

TicketType ──< Ticket
Ticket ──< Addon (many-to-many via ticket_ticket_addon)

Order ──< OrderLine
Order ──< Ticket

SponsorLevel ──< Sponsor

NewsArticle ──< NewsComment
NewsComment ──< NewsCommentVote

Achievement ──< AchievementEvent

IntegrationApp ──< IntegrationToken
IntegrationApp ──< Webhook
Webhook ──< WebhookDelivery
```

---

## 6. Notes

### 6.1 JSONB Usage

| Table | Column | Content |
|-------|--------|---------|
| events | banner_images | Array of image paths and metadata |
| seat_plans | data | Canvas seat plan structure |
| game_modes | parameters | Custom mode parameters |
| users | sidebar_favorites | Navigation bookmark IDs |
| integration_apps | webhook_events | Subscribed event type array |
| webhooks | events | Subscribed event type array |

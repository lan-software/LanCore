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
| phone | varchar(50) (nullable) | Phone number |
| street | varchar(255) (nullable) | Street address |
| city | varchar(255) (nullable) | City |
| zip_code | varchar(20) (nullable) | ZIP / postal code |
| country | varchar(2) (nullable) | ISO 3166-1 alpha-2 country code |
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
| seats_per_user | integer (default 1) | Physical seats consumed per user on the ticket |
| max_users_per_ticket | integer (default 1) | Maximum assignable users per ticket (group tickets) |
| check_in_mode | varchar (default 'individual') | CheckInMode enum: 'individual' or 'group' |
| is_row_ticket | boolean (deprecated) | Legacy row ticket flag — deprecated, retained for backward compatibility |
| is_seatable | boolean | Seating flag |
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
| order_id | bigint FK (nullable) | References orders.id |
| status | varchar | TicketStatus enum |
| validation_id | varchar (unique, nullable) | **Deprecated** — legacy plaintext check-in validation code; nullable; to be removed in a follow-up migration once all consumers use LCT1 tokens |
| validation_nonce_hash | CHAR(64) (unique, nullable) | HMAC-SHA256(nonce, pepper) in lowercase hex; used for token-to-ticket lookup without storing the nonce; NULL when ticket is cancelled or no token has been issued |
| validation_kid | VARCHAR(16) (nullable) | Key identifier of the Ed25519 signing key used for the current token; NULL when no token is active |
| validation_issued_at | timestamp (nullable) | When the current LCT1 token was issued |
| validation_expires_at | timestamp (nullable) | When the current LCT1 token expires (event end + grace period) |
| checked_in_at | datetime (nullable) | Ticket-level check-in timestamp |
| created_at | timestamp | |
| updated_at | timestamp | |

**Indexes on tickets:**
- `UNIQUE (validation_nonce_hash)` — enforces the DB≠QR invariant; no two active tokens can map to the same hash
- Existing `UNIQUE (validation_id)` retained until the column is dropped

**Deprecation plan for `validation_id`:**
1. Phase 1 (current): Column is nullable; new tokens are issued as LCT1 and stored in `validation_nonce_hash`; `validation_id` is not populated for new tokens
2. Phase 2 (follow-up migration): Column dropped; index removed; any remaining code paths referencing `validation_id` removed

#### 4.4.2a ticket_user (pivot)

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | Primary key |
| ticket_id | bigint FK | References tickets.id (cascade delete) |
| user_id | bigint FK | References users.id (cascade delete) |
| checked_in_at | datetime (nullable) | Per-user check-in timestamp |
| created_at | timestamp | |
| updated_at | timestamp | |

**Unique constraint:** `(ticket_id, user_id)`

**Design note:** Replaces the previous singular `user_id` column on `tickets`. For non-group tickets (`max_users_per_ticket = 1`), the pivot contains 0 or 1 rows. For group tickets, it contains up to `max_users_per_ticket` rows.

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
| subtotal | decimal | Order subtotal before discount |
| discount | decimal | Discount amount (from voucher) |
| total | decimal | Order total after discount |
| voucher_id | bigint FK (nullable) | References vouchers.id |
| provider_session_id | varchar (nullable) | Payment provider session ID (Stripe: checkout session) |
| provider_transaction_id | varchar (nullable) | Payment provider transaction ID (Stripe: payment intent) |
| metadata | text (nullable) | JSON-encoded fulfillment data (ticket types, quantities, addon IDs) |
| paid_at | timestamp (nullable) | Timestamp when payment was confirmed (on-site payments) |
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

#### 4.5.10 purchase_requirement_purchasable (pivot)

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | Primary key |
| purchase_requirement_id | bigint FK | References purchase_requirements.id |
| purchasable_type | varchar | Polymorphic type (e.g., TicketType) |
| purchasable_id | bigint | Polymorphic ID |
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

### 4.8 Competition

#### 4.8.1 competitions

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | Primary key |
| name | varchar | Competition name |
| slug | varchar (unique) | URL-friendly identifier |
| description | text (nullable) | Description |
| event_id | bigint FK (nullable) | References events.id |
| game_id | bigint FK (nullable) | References games.id |
| game_mode_id | bigint FK (nullable) | References game_modes.id |
| type | varchar | CompetitionType enum (tournament, league, race) |
| stage_type | varchar | StageType enum (single_elimination, etc.) |
| status | varchar | CompetitionStatus enum (draft, registration_open, etc.) |
| team_size | integer (nullable) | Players per team |
| max_teams | integer (nullable) | Maximum number of teams |
| registration_opens_at | timestamp (nullable) | Registration window start |
| registration_closes_at | timestamp (nullable) | Registration window end |
| starts_at | timestamp (nullable) | Competition start time |
| ends_at | timestamp (nullable) | Competition end time |
| lanbrackets_id | bigint (nullable) | Foreign ID in LanBrackets |
| lanbrackets_share_token | varchar (nullable) | Bracket view share token |
| settings | jsonb (nullable) | Configuration (result_submission_mode, etc.) |
| metadata | jsonb (nullable) | Arbitrary metadata |
| created_at | timestamp | |
| updated_at | timestamp | |

#### 4.8.2 competition_teams

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | Primary key |
| competition_id | bigint FK | References competitions.id (cascade) |
| name | varchar | Team name (unique per competition) |
| tag | varchar(10) (nullable) | Short team tag |
| captain_user_id | bigint FK (nullable) | References users.id |
| lanbrackets_id | bigint (nullable) | Foreign ID in LanBrackets |
| created_at | timestamp | |
| updated_at | timestamp | |

#### 4.8.3 competition_team_members

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | Primary key |
| team_id | bigint FK | References competition_teams.id (cascade) |
| user_id | bigint FK | References users.id (cascade) |
| joined_at | timestamp | When user joined the team |
| left_at | timestamp (nullable) | When user left (null = active) |
| created_at | timestamp | |
| updated_at | timestamp | |

#### 4.8.4 match_result_proofs

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | Primary key |
| competition_id | bigint FK | References competitions.id (cascade) |
| lanbrackets_match_id | bigint | Match ID in LanBrackets |
| submitted_by_user_id | bigint FK | References users.id |
| submitted_by_team_id | bigint FK (nullable) | References competition_teams.id |
| screenshot_path | varchar | S3 file path to proof screenshot |
| scores | jsonb | Reported scores array |
| is_disputed | boolean | Whether the other side disputes this result |
| resolved_at | timestamp (nullable) | When dispute was resolved |
| created_at | timestamp | |
| updated_at | timestamp | |

### 4.9 Seating

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

### 4.17 Orchestration

#### 4.17.1 game_servers

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | Primary key |
| name | varchar | Human-readable server label |
| host | varchar | IP address or hostname |
| port | unsigned integer | Server port |
| game_id | bigint FK | References games.id (cascade delete) |
| game_mode_id | bigint FK (nullable) | References game_modes.id (null on delete) |
| status | varchar (default 'available') | GameServerStatus enum (available, in_use, offline, maintenance) |
| allocation_type | varchar | GameServerAllocationType enum (competition, casual, flexible) |
| credentials | text (nullable) | Encrypted JSON — RCON password, connection secrets |
| metadata | jsonb (nullable) | Arbitrary server metadata |
| created_at | timestamp | |
| updated_at | timestamp | |

**Indexes:** `(game_id, status, allocation_type)` composite for server selection queries.

#### 4.17.2 orchestration_jobs

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | Primary key |
| game_server_id | bigint FK (nullable) | References game_servers.id (null on delete) — assigned after server selection |
| competition_id | bigint FK | References competitions.id (cascade delete) |
| lanbrackets_match_id | unsigned bigint | Match ID from LanBrackets |
| game_id | bigint FK | References games.id (cascade delete) |
| game_mode_id | bigint FK (nullable) | References game_modes.id (null on delete) |
| status | varchar (default 'pending') | OrchestrationJobStatus enum (pending, selecting_server, deploying, active, completed, failed, cancelled) |
| match_config | jsonb (nullable) | Game-specific match configuration blob |
| match_handler | varchar (nullable) | FQCN of the MatchHandlerContract implementation used |
| error_message | text (nullable) | Failure reason |
| attempts | unsigned integer (default 0) | Processing attempt count |
| started_at | timestamp (nullable) | When deployment completed and match became active |
| completed_at | timestamp (nullable) | When match completed and server was released |
| created_at | timestamp | |
| updated_at | timestamp | |

**Unique constraint:** `(competition_id, lanbrackets_match_id)` prevents duplicate orchestration per match.
**Indexes:** `(status)` for job processing queries.

#### 4.17.3 match_chat_messages

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | Primary key |
| orchestration_job_id | bigint FK | References orchestration_jobs.id (cascade delete) |
| steam_id | varchar | Player's Steam ID 64 |
| player_name | varchar | Player display name at time of message |
| message | text | Chat message content |
| is_team_chat | boolean (default false) | Team chat vs all chat |
| timestamp | timestamp | When the message was sent (from match handler) |
| created_at | timestamp | |

**Indexes:** `(orchestration_job_id, timestamp)` for chat log queries.

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

Game ──< GameServer
GameServer ──< OrchestrationJob
Competition ──< OrchestrationJob
OrchestrationJob ──< MatchChatMessage
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

### 6.2 Ticket Token Storage Invariants

The following values are **never stored in the database**:

| Value | Rationale |
|-------|-----------|
| LCT1 token string | QR payload; stored only in the generated PDF |
| Plaintext nonce | One-way derivation; only the HMAC is stored |
| Ed25519 private key bytes | Stored on filesystem only (`storage/keys/ticket_signing/`) |
| HMAC pepper | Supplied via `TICKET_TOKEN_PEPPER` environment variable only |

The `validation_nonce_hash` column stores a 64-character lowercase hex HMAC-SHA256 digest. An attacker with read access to the database cannot reverse this to obtain the nonce, because the pepper is not in the database. The nonce itself is not in the database either, so an attacker with the pepper still cannot reconstruct a valid token without the Ed25519 private key.

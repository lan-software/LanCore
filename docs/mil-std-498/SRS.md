# Software Requirements Specification (SRS)

**Document Identifier:** LanCore-SRS-001
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

This Software Requirements Specification (SRS) specifies the requirements for the **LanCore** Computer Software Configuration Item (CSCI) — a LAN Party & BYOD Event Management Platform.

### 1.2 System Overview

LanCore is a monolithic web application comprising a Laravel 13 backend and a Vue.js 3 frontend connected via Inertia.js v2. It is organized into 17 domain modules, each encapsulating a bounded context of the event management domain.

### 1.3 Document Overview

This document specifies the detailed software requirements for each CSCI domain, including functional capabilities, interface requirements, internal data requirements, and quality factors.

---

## 2. Referenced Documents

- [SSS](SSS.md) — System/Subsystem Specification
- [IRS](IRS.md) — Interface Requirements Specification
- [SDD](SDD.md) — Software Design Description
- [DBDD](DBDD.md) — Database Design Description

---

## 3. Requirements

### 3.1 Required States and Modes

The LanCore CSCI shall support the following operational states:

| State | Description |
|-------|-------------|
| Active | Application fully operational, serving requests via FrankenPHP/Octane |
| Maintenance | `php artisan down`; returns 503 to all non-allowed requests |
| Queue Processing | Horizon workers active, processing background jobs |
| Migrating | Database migrations in progress; application temporarily unavailable |
| Demo | Database pre-seeded with synthetic data via `SeedDemoCommand`; Stripe test-mode API keys (`STRIPE_KEY=pk_test_...`, `STRIPE_SECRET=sk_test_...`) are configured via environment so `StripePaymentProvider` handles all transactions through Stripe's sandbox with no real charges; all other CSCI capabilities function normally |

### 3.2 CSCI Capability Requirements

#### 3.2.1 Event Domain (CSCI-EVT)

**Models:** Event
**Controllers:** EventController, PublicEventController, EventAuditController
**Actions:** CreateEvent, UpdateEvent, PublishEvent, UnpublishEvent, DeleteEvent

| Req ID | Requirement |
|--------|------------|
| EVT-F-001 | The software shall provide CRUD operations for events via EventController |
| EVT-F-002 | The software shall store events with: name, description, start_date, end_date, status, banner_images, seat_capacity |
| EVT-F-003 | The software shall enforce EventPolicy authorization on all event operations |
| EVT-F-004 | The software shall support event status transitions: draft → published, published → draft |
| EVT-F-005 | The software shall emit EventPublished event on publication, triggering HandleEventPublishedWebhooks listener |
| EVT-F-006 | The software shall support venues with: name, description, and associated Address (street, city, state, country) *(implemented in Venue Domain, see §3.2.15)* |
| EVT-F-007 | The software shall support venue images with alt text and sort ordering *(implemented in Venue Domain, see §3.2.15)* |
| EVT-F-008 | The software shall provide audit trail views for events via EventAuditController |
| EVT-F-009 | The software shall provide public event listing via PublicEventController |
| EVT-F-010 | The software shall support a primary program assignment per event |
| EVT-F-011 | The software shall provide per-user event context selection for "My Pages" views via `POST /my-event-context` (store) and `DELETE /my-event-context` (destroy), implemented in `EventContextController::storeMy/destroyMy`. The selected event shall be validated via `Event::scopeForUser` to confirm the user has participation (ticket ownership/management, active team membership, or order) in that event. The selection shall be stored in session key `my_selected_event_id` and applied as a filter on my-competitions, my-teams, my-orders, and my-tickets index views. A stale selection (user loses participation) shall be automatically cleared when the `myEventContext` shared prop is computed. |

#### 3.2.2 Ticketing Domain (CSCI-TKT)

**Models:** Ticket, TicketType, TicketCategory, TicketGroup, Addon
**Controllers:** TicketController, AdminTicketController, TicketTypeController, TicketCategoryController, AddonController, TicketTypeAuditController, TicketCategoryAuditController, AddonAuditController
**Actions:** CreateTicketType, UpdateTicketType, DeleteTicketType, CreateTicketCategory, UpdateTicketCategory, DeleteTicketCategory, CreateAddon, UpdateAddon, DeleteAddon, UpdateTicketAssignments
**Enums:** TicketStatus, CheckInMode

| Req ID | Requirement |
|--------|------------|
| TKT-F-001 | The software shall support ticket types with: name, description, price, quota, max_per_user, sale_start, sale_end |
| TKT-F-002 | The software shall support ticket categories belonging to events for logical grouping |
| TKT-F-003 | The software shall support ticket groups as an alternative grouping mechanism |
| TKT-F-004 | The software shall track individual tickets with statuses: available, assigned, used, checked_in |
| TKT-F-005 | The software shall generate unique validation IDs per ticket (TicketValidationId) |
| TKT-F-006 | The software shall support three ticket assignment roles: owner, manager, user |
| TKT-F-007 | The software shall support ticket add-ons with pricing history tracked via pivot table |
| TKT-F-008 | The software shall enforce TicketPolicy, TicketTypePolicy, TicketCategoryPolicy, and AddonPolicy |
| TKT-F-009 | ~~The software shall support seating/row ticket types linked to seat plans~~ **(Deprecated — superseded by TKT-F-013..016)** |
| TKT-F-010 | The software shall provide admin ticket management views (AdminTicketController) |
| TKT-F-011 | The software shall support ticket locking to prevent concurrent modifications |
| TKT-F-012 | The software shall maintain audit trails for ticket types, categories, and add-ons |
| TKT-F-013 | The software shall support group ticket types with a configurable `max_users_per_ticket` field (default 1) on TicketType |
| TKT-F-014 | The software shall support multi-user ticket assignment via a `ticket_user` pivot table, replacing the singular `user_id` on tickets |
| TKT-F-015 | The software shall support configurable check-in modes per ticket type (`individual` or `group`) via a `check_in_mode` field and CheckInMode enum |
| TKT-F-016 | The software shall calculate seat capacity consumption as `seats_per_user × max_users_per_ticket`, reserved at purchase time |
| TKT-F-017 | The software shall issue an LCT1 signed ticket token (`LCT1.<kid>.<body>.<sig>`) for each ticket upon order fulfillment, where `body = base64url(json({tid, nonce, iat, exp, evt}))` and `sig = base64url(Ed25519_sign(sk[kid], "LCT1." + kid + "." + body))`; the token shall be embedded in the ticket QR code payload |
| TKT-F-018 | The software shall store per-ticket signing metadata in `tickets` columns: `validation_nonce_hash` CHAR(64) UNIQUE (HMAC-SHA256 of the deterministic nonce with the pepper), `validation_kid` VARCHAR(16), `validation_issued_at` TIMESTAMP, `validation_expires_at` TIMESTAMP, and `validation_rotation_epoch` BIGINT UNSIGNED DEFAULT 0 (rotation counter used in nonce derivation). The plaintext nonce and the full LCT1 token string shall never be stored; the nonce is derived deterministically at render time from `HMAC_SHA256(pepper, ticket_id_le64 \|\| epoch_le64)` truncated to 16 bytes |
| TKT-F-019 | The software shall rotate the LCT1 token (increment `validation_rotation_epoch`, recompute nonce hash, persist new kid/issued/expires, dispatch PDF regeneration) on exactly four events: initial issuance during `FulfillOrder`, and `UpdateTicketAssignments::{updateManager, addUser, removeUser, rotateToken}`. Rotation shall not fire on any read path (QR render, PDF regeneration from cache miss) |
| TKT-F-020 | The software shall clear `validation_nonce_hash`, `validation_kid`, `validation_issued_at`, and `validation_expires_at` when a ticket is cancelled, rendering any previously issued token unresolvable |
| TKT-F-021 | The software shall provide a `GET /api/entrance/signing-keys` endpoint (Bearer-authenticated via existing integration middleware) that returns all active and retired-but-unexpired Ed25519 public keys in JWKS format; response shall be cacheable by consumers for a configurable TTL |
| TKT-F-022 | The software shall provide a `php artisan tickets:keys:rotate` command that generates a new Ed25519 key pair, assigns a `kid`, writes the private key to `storage/keys/ticket_signing/{kid}.key`, and designates the new key as the active signing key |
| TKT-F-023 | The software shall return structured error codes in the validate endpoint response for token-related failure modes: `invalid_signature` (signature verification failed), `unknown_kid` (no public key for the given kid), `expired` (token past `validation_expires_at`), `revoked` (nonce hash not found or cleared); these supplement the existing `valid`, `already_checked_in`, and `invalid` decision values |
| TKT-F-024 | The software shall provide a `POST /tickets/{ticket}/rotate-token` endpoint (authorization: ticket owner or manager; rate limit 10 requests/minute/user) that delegates to `UpdateTicketAssignments::rotateToken`, enabling ticket holders to self-invalidate previously printed copies |
| TKT-F-025 | On any rotation triggered after initial issuance (addUser, removeUser, updateManager, rotateToken, rotateTokenUser), the software shall dispatch a `TicketTokenRotatedNotification` to: the ticket owner, every user currently attached to the ticket, the user just removed (on `removeUser`), and the previously-assigned manager (on `updateManager`). Delivery channels are `database` and `mail`; this notification is security-relevant and not suppressible via `NotificationPreference` |
| TKT-F-026 | The software shall serve the QR code (`GET /tickets/{ticket}/qr`) and the cached PDF (`GET /tickets/{ticket}/download`) without rotating the stored nonce. The QR endpoint recomputes the deterministic nonce from the stored epoch and re-signs against the stored kid/issued/expires. The PDF endpoint reuses the cached `tickets/{id}.pdf` on the private disk; when absent, it generates a fresh PDF using the same non-rotating render |
| TKT-F-027 | The ticket PDF shall be rendered as A4 portrait tri-fold with three 99 mm panels divided by two dashed horizontal fold guides. Panel 1 (top): event banner hero with organization identity. Panel 2 (middle): QR code + ticket-holder data (scan-visible face when folded). Panel 3 (bottom): all currently active `GlobalPurchaseCondition` rows rendered as small legal text, followed by the organization legal footer |
| TKT-F-028 | The software shall provide a `php artisan tickets:rotate-all` command that rotates the token on every ticket with a non-null `validation_nonce_hash`. A `--only-legacy` flag restricts the pass to tickets whose `validation_rotation_epoch` is still 0. The command is required once during the deterministic-nonce deploy and invalidates every previously printed QR |
| TKT-F-029 | The ticket PDF shall render a personalised forensic watermark across the A4 background, composed of the ticket owner's name, event name, venue + city, organisation name, ticket type, and ticket ID, at approximately 12% opacity, repeated diagonally. Angle and start-offset shall vary per ticket, seeded deterministically by ticket ID. A safe rectangle around the middle-panel QR code shall be left blank so scanners see an unobstructed code. When GD TTF support or the font file is unavailable the watermark shall be omitted without failing PDF generation |

#### 3.2.3 Shop Domain (CSCI-SHP)

**Models:** Order, OrderLine, Cart, CartItem, Voucher, PurchaseRequirement, GlobalPurchaseCondition, PaymentProviderCondition, CheckoutAcknowledgement
**Controllers:** ShopController, CartController, OrderController, VoucherController, VoucherAuditController, PurchaseRequirementController, GlobalPurchaseConditionController, PaymentProviderConditionController, UserOrderController
**Actions:** CreateCheckoutSession, FulfillOrder, CreateOrder, CreateVoucher, UpdateVoucher, DeleteVoucher, CreatePurchaseRequirement, UpdatePurchaseRequirement, DeletePurchaseRequirement, CreateGlobalPurchaseCondition, UpdateGlobalPurchaseCondition, DeleteGlobalPurchaseCondition, CreatePaymentProviderCondition, UpdatePaymentProviderCondition, DeletePaymentProviderCondition
**Payment Providers:** StripePaymentProvider, OnSitePaymentProvider, PaymentProviderManager
**Contracts:** Purchasable, PurchasableDependency, PaymentProvider, PaymentResult
**Concerns:** InteractsWithShop

| Req ID | Requirement |
|--------|------------|
| SHP-F-001 | The software shall maintain one cart per user per event |
| SHP-F-002 | The software shall support polymorphic cart items (tickets, add-ons via Purchasable interface) |
| SHP-F-003 | The software shall create Stripe Checkout sessions via StripePaymentProvider |
| SHP-F-004 | The software shall support on-site payment via OnSitePaymentProvider; orders remain pending until admin confirms payment received, which triggers fulfillment |
| SHP-F-005 | The software shall select payment provider via PaymentProviderManager factory (Stripe, OnSite, PayPal) |
| SHP-F-006 | The software shall track order statuses: pending, confirmed, failed, refunded, cancelled |
| SHP-F-007 | The software shall support vouchers with types: fixed_amount, percentage |
| SHP-F-008 | The software shall validate voucher applicability per event |
| SHP-F-009 | The software shall enforce configurable purchase requirements via PurchaseRequirement model |
| SHP-F-010 | The software shall support global purchase conditions (terms, privacy) and payment-provider-specific conditions |
| SHP-F-011 | The software shall track user acknowledgements of checkout conditions |
| SHP-F-012 | The software shall emit TicketPurchased event on order fulfillment |
| SHP-F-013 | The software shall emit CartItemAdded event when items are added to cart |
| SHP-F-014 | The software shall enforce OrderPolicy, VoucherPolicy, PurchaseRequirementPolicy, GlobalPurchaseConditionPolicy, PaymentProviderConditionPolicy |
| SHP-F-015 | The software shall fulfill orders via Stripe `checkout.session.completed` webhook when the user does not return to the checkout success URL |
| SHP-F-016 | The software shall ensure order fulfillment is idempotent — both the success URL redirect and the webhook may trigger fulfillment, but tickets shall only be created once |
| SHP-F-017 | The software shall provide a user-facing "My Orders" view listing the authenticated user's orders with order details, line items, and associated tickets |
| SHP-F-018 | The software shall expose a single, admin-configurable shop currency via `shop_settings.currency`, resolved by `CurrencyResolver`; orders shall snapshot the currency in force at creation time to `orders.currency`; all historical invoices and receipts shall render the snapshot, not the live setting |
| SHP-F-019 | The software shall create PayPal Orders v2 via `PayPalPaymentProvider` using `createOrder` and `capturePaymentOrder` from the `srmklive/paypal` package |
| SHP-F-020 | The software shall fulfill PayPal orders idempotently via both the return-URL capture and the `PAYMENT.CAPTURE.COMPLETED` webhook, verified locally via `verifyWebHookLocally` using `PAYPAL_WEBHOOK_ID` |

#### 3.2.4 Program Domain (CSCI-PRG)

**Models:** Program, TimeSlot
**Controllers:** ProgramController, ProgramAuditController
**Actions:** CreateProgram, UpdateProgram, DeleteProgram, CreateTimeSlot, UpdateTimeSlot, DeleteTimeSlot

| Req ID | Requirement |
|--------|------------|
| PRG-F-001 | The software shall support programs belonging to events with name and description |
| PRG-F-002 | The software shall support program visibility: public, registered_only, hidden |
| PRG-F-003 | The software shall support time slots with ordering and visibility control |
| PRG-F-004 | The software shall emit ProgramTimeSlotApproaching events for schedule notifications |
| PRG-F-005 | The software shall support user subscription to program notifications via ProgramNotificationSubscription |
| PRG-F-006 | The software shall support sponsor assignment to programs and time slots |
| PRG-F-007 | The software shall maintain audit trails for program modifications |

#### 3.2.5 Seating Domain (CSCI-SET)

**Models:** SeatPlan, SeatAssignment
**Controllers:** SeatPlanController, SeatPlanAuditController, SeatPickerController, SeatPlanBackgroundController
**Actions:** CreateSeatPlan, UpdateSeatPlan, DeleteSeatPlan, AssignSeat, ReleaseSeat
**Support:** SeatPlanTreeSyncer, LegacySeatPlanConverter, SeatingCategoryRules
**Resources:** SeatPlanResource, SeatPlanEditorResource
**Events/Listeners:** SeatAssignmentInvalidated → NotifyAffectedAssignees

| Req ID | Requirement |
|--------|------------|
| SET-F-001 | The software shall provide an admin-facing canvas-based seat plan editor (EditorShell / EditorCanvas / PropertiesPanel) supporting drag-to-place seats, draggable labels, left-drag marquee selection, right-click (or middle-click) drag panning, a dedicated delete tool (D), wheel zoom centred on the cursor, auto-selection of a block after creation, a Move-to-block control in the properties panel, undo/redo, and keyboard shortcuts |
| SET-F-002 | The software shall store seat plan data in a normalized relational schema (`seat_plans`, `seat_plan_blocks`, `seat_plan_rows`, `seat_plan_seats`, `seat_plan_labels`) with cascading foreign keys rooted at `seat_plans` |
| SET-F-003 | The software shall associate seat plans with events |
| SET-F-004 | The software shall enforce SeatPlanPolicy authorization |
| SET-F-005 | The software shall maintain audit trails for seat plan modifications including per-entity Auditable rows on `SeatPlan`, `SeatPlanBlock`, `SeatPlanRow`, `SeatPlanSeat`, `SeatPlanLabel` |
| SET-F-006 | The software shall persist a seat assignment as the tuple (ticket, user, seat_plan, seat) with database-enforced uniqueness on (`seat_plan_id`, `seat_plan_seat_id`) and on (ticket, user); the seat FK is `restrictOnDelete` so the two-phase save flow is the only code path that can release assignments |
| SET-F-007 | The software shall allow ticket owners, ticket managers, the assignee themselves, and ManageTicketing permission holders to pick or change a seat assignment, provided the ticket has not been checked in (TicketPolicy::pickSeat). Roles are non-exclusive — being a ticket user never blocks owner or manager rights |
| SET-F-008 | The software shall auto-release seat assignments when a ticket transitions to Cancelled status, when a ticket is deleted, or when a user is removed from a group ticket's user pivot |
| SET-F-009 | The software shall render occupied seats on public seat plans showing the assignee's initials, gated by the assignee's `is_seat_visible_publicly` flag with an override that always reveals the name to viewers who themselves hold an active ticket (owner, manager, or pivot user) for the same event |
| SET-F-010 | The software shall provide a Settings → Privacy page through which a user may toggle `is_seat_visible_publicly` |
| SET-F-011 | The software shall support per-block ticket-category restrictions via the `seat_plan_block_category_restrictions(seat_plan_block_id, ticket_category_id)` pivot; an empty pivot for a block = open to all categories (permissive default) |
| SET-F-012 | On seat-plan update, the software shall diff the proposed block/row/seat tree against existing seat assignments and block the save unless the admin explicitly confirms when assignments would be invalidated (seat removed OR category allowlist would reject the current assignment) |
| SET-F-013 | Upon a confirmed invalidating save, the software shall release affected assignments in a single transaction and emit a `SeatAssignmentInvalidated` domain event per released assignment |
| SET-F-014 | The software shall notify ticket owner, ticket manager and the affected assigned user about each invalidated assignment via email + in-app (database) channels by default, with push as an opt-in per-user preference (`push_on_seating`); email is opt-out (`mail_on_seating`, default true) |
| SET-F-015 | The software shall support an optional global background image per seat plan (`seat_plans.background_image_url`) and per block (`seat_plan_blocks.background_image_url`), uploaded through authenticated endpoints gated by `SeatPlanPolicy::update` |
| SET-F-016 | The software shall preserve the pre-normalization `{blocks:[{seats,labels,allowed_ticket_category_ids}]}` wire shape via `SeatPlanResource`, so `@alisaitteke/seatmap-canvas` consumers (Picker, public Welcome overlay) require no frontend rework |
| SET-F-018 | The software shall support an optional `seat_title_prefix` per block that is prepended to every seat's display title. The raw seat title remains unchanged in storage; `SeatPlanResource` emits prefixed titles to picker consumers, while `SeatPlanEditorResource` keeps the raw title and exposes the prefix separately for admin editing |
| SET-F-019 | The software shall surface save feedback in the editor: a `Saving…` state during the request, a green `Saved` confirmation with a `canvas-confetti` burst coloured by the plan's block/seat palette on success, and a dismissible red banner carrying the server-side error text on failure. The confetti animation is suppressed when `prefers-reduced-motion: reduce` is set |

#### 3.2.6 Sponsoring Domain (CSCI-SPO)

**Models:** Sponsor, SponsorLevel
**Controllers:** SponsorController, SponsorLevelController, SponsorAuditController, SponsorLevelAuditController
**Actions:** CreateSponsor, UpdateSponsor, DeleteSponsor, CreateSponsorLevel, UpdateSponsorLevel, DeleteSponsorLevel

| Req ID | Requirement |
|--------|------------|
| SPO-F-001 | The software shall support sponsors with: name, logo, link |
| SPO-F-002 | The software shall support sponsor levels (tiers) with ordering |
| SPO-F-003 | The software shall support many-to-many sponsor assignments to: events, programs, time slots, users |
| SPO-F-004 | The software shall enforce SponsorPolicy and SponsorLevelPolicy |
| SPO-F-005 | The software shall maintain audit trails for sponsors and sponsor levels |

#### 3.2.7 News Domain (CSCI-NWS)

**Models:** NewsArticle, NewsComment, NewsCommentVote
**Controllers:** NewsArticleController, NewsCommentController, PublicNewsController, NewsArticleAuditController, NewsCommentAuditController
**Actions:** CreateNewsArticle, UpdateNewsArticle, DeleteNewsArticle

| Req ID | Requirement |
|--------|------------|
| NWS-F-001 | The software shall support news articles with: title, body (rich text), SEO metadata, visibility |
| NWS-F-002 | The software shall support article visibility: public, draft, members_only, archived |
| NWS-F-003 | The software shall support user comments on articles |
| NWS-F-004 | The software shall support comment voting (upvote/downvote) via NewsCommentVote |
| NWS-F-005 | The software shall emit NewsArticlePublished event, triggering SendNewsNotification and HandleNewsArticlePublishedWebhooks |
| NWS-F-006 | The software shall emit NewsArticleRead event for analytics |
| NWS-F-007 | The software shall enforce NewsArticlePolicy and NewsCommentPolicy |
| NWS-F-008 | The software shall maintain audit trails for articles and comments |

#### 3.2.8 Announcement Domain (CSCI-ANN)

**Models:** Announcement
**Controllers:** AnnouncementController, PublicAnnouncementController, AnnouncementDismissalController
**Actions:** CreateAnnouncement, UpdateAnnouncement, DeleteAnnouncement
**Events:** AnnouncementPublished, AnnouncementsViewed

| Req ID | Requirement |
|--------|------------|
| ANN-F-001 | The software shall support announcements with: title, body, priority, event association |
| ANN-F-002 | The software shall support priority levels: low, normal, high, critical |
| ANN-F-003 | The software shall track per-user dismissals via AnnouncementDismissal |
| ANN-F-004 | The software shall emit AnnouncementPublished event, triggering SendAnnouncementNotification and HandleAnnouncementPublishedWebhooks |
| ANN-F-005 | The software shall enforce AnnouncementPolicy |

#### 3.2.9 Achievement Domain (CSCI-ACH)

**Models:** Achievement, AchievementEvent
**Controllers:** AchievementController
**Actions:** CreateAchievement, UpdateAchievement, DeleteAchievement, GrantAchievement

| Req ID | Requirement |
|--------|------------|
| ACH-F-001 | The software shall support achievement definitions with name, description, and icon |
| ACH-F-002 | The software shall support event-based achievement triggers via AchievementEvent and GrantableEvent enum |
| ACH-F-003 | The software shall automatically process achievements via ProcessAchievements listener |
| ACH-F-004 | The software shall track achievement-to-user assignments with earned_at timestamp |
| ACH-F-005 | The software shall enforce AchievementPolicy |
| ACH-F-006 | The software shall send an AchievementEarnedNotification containing achievement name, description, color, and icon when a user earns an achievement |
| ACH-F-007 | The software shall display achievement notification details (name and description) in NotificationBell, notifications/Index, and notifications/Archive Vue components |
| ACH-F-008 | The software shall maintain a `earned_user_count` integer column on `achievements` (default 0), increment it whenever an achievement is granted to a user (deduplicated per user), decrement it on revocation (clamped at 0), and expose a derived `earned_percentage` accessor on the `Achievement` model returning `round(earned_user_count / max(1, $totalUsers) * 100, 1)` where `$totalUsers` is the total registered-user count cached via `Cache::remember('users.count', 60, fn () => User::count())`. The achievement rarity shall be displayed alongside each earned achievement on the public profile page (USR-F-023). A console command `php artisan profiles:backfill-achievement-counts` shall be provided to seed `earned_user_count` from existing pivot data |

#### 3.2.10 Notification Domain (CSCI-NTF)

**Models:** NotificationPreference, ProgramNotificationSubscription, PushSubscription
**Controllers:** NotificationController, NotificationSettingsController, ProgramSubscriptionController, PushSubscriptionController
**Events:** NotificationPreferencesUpdated, NotificationsArchived, ProfileUpdated, TicketDiscoverySettingsUpdated, UserAttributesUpdated, UserRolesChanged
**Listeners:** HandleProfileUpdatedWebhooks, HandleUserRolesChangedWebhooks, SendUserAttributesUpdatedNotification, SendUserRolesChangedNotification

| Req ID | Requirement |
|--------|------------|
| NTF-F-001 | The software shall store per-user notification preferences with mail and push toggles per category |
| NTF-F-002 | The software shall support notification categories: news, events, comments, programs, announcements, seating |
| NTF-F-003 | The software shall support Web Push subscriptions with endpoint, public key, and auth token |
| NTF-F-004 | The software shall support program-specific notification subscriptions |
| NTF-F-005 | The software shall support notification archiving with archived_at timestamp |
| NTF-F-006 | The software shall deliver notifications via: database, mail, web push channels |
| NTF-F-007 | The software shall allow users to dismiss the push notification prompt; dismissal shall persist for the duration of the login session and reset upon logout |

#### 3.2.11 Integration Domain (CSCI-INT)

**Models:** IntegrationApp, IntegrationToken
**Controllers:** IntegrationAppController, IntegrationTokenController, IntegrationSsoController, IntegrationUserController
**Actions:** CreateIntegrationApp, UpdateIntegrationApp, DeleteIntegrationApp, CreateIntegrationToken, RotateIntegrationToken, RevokeIntegrationToken, GenerateSsoAuthorizationCode, ExchangeSsoAuthorizationCode, ResolveIntegrationUser, SyncIntegrationWebhooks
**Middleware:** AuthenticateIntegration

| Req ID | Requirement |
|--------|------------|
| INT-F-001 | The software shall support integration app registration with: name, description, callback URL, webhook subscriptions — created via the admin UI, via Artisan commands, or via a declarative configuration file (`config/integrations.php`) reconciled at boot or on demand via the `integrations:sync` Artisan command |
| INT-F-002 | The software shall generate API tokens with support for rotation and revocation |
| INT-F-003 | The software shall hash API tokens before database storage |
| INT-F-004 | The software shall provide OAuth2-like SSO with authorization code flow |
| INT-F-005 | The software shall authenticate integration API requests via Bearer token (AuthenticateIntegration middleware) |
| INT-F-006 | The software shall support navigation hints (nav_title, nav_url, nav_icon) for integrations |
| INT-F-007 | The software shall emit IntegrationAccessed events and dispatch webhooks |
| INT-F-008 | The software shall provide stateless API routes under `/api/integration/` |
| INT-F-009 | The software shall enforce IntegrationAppPolicy |
| INT-F-010 | The software shall provide Artisan commands for integration management (create, list, revoke tokens) |
| INT-F-011 | The software shall support declarative integration-app registration via `config/integrations.php`. Each configured app shall specify: slug, display name, host (or a computed host derived from a deployment domain plus a style selector), callback path, allowed scopes, optional navigation hints (`nav_url`, `nav_icon`, `nav_label`), webhook subscription flags plus endpoints (`send_announcements` / `announcement_path`, `send_role_updates` / `roles_path`), optional pre-shared token, optional pre-shared announcement and role webhook secrets |
| INT-F-012 | The software shall provide an `integrations:sync` Artisan command that reconciles `config/integrations.php` against the database: for each configured slug it shall upsert the `IntegrationApp` row; it shall delete and recreate the app's `IntegrationToken` rows and subscribed `Webhook` rows from configuration; and it shall leave rows for slugs not listed in the configuration untouched |
| INT-F-013 | The software shall accept a caller-supplied plaintext token value at reconciliation time, hash it with SHA-256 before storage, and persist only the hash plus an 8-character display prefix (preserving the storage contract from INT-F-003) |
| INT-F-014 | The reconciler shall log at INFO level every app upsert, token rotation, and webhook refresh, including the release identifier (`LANCORE_RELEASE_NAME` and `LANCORE_RELEASE_REVISION` when set by the Helm chart) and a UTC timestamp, so operators can correlate token invalidations with a specific deployment |

#### 3.2.12 Webhook Domain (CSCI-WHK)

**Models:** Webhook, WebhookDelivery
**Controllers:** WebhookController
**Actions:** CreateWebhook, UpdateWebhook, DeleteWebhook, DispatchWebhooks
**Events:** WebhookDispatched
**Listeners:** SendWebhookPayload, HandleUserRegisteredWebhooks

| Req ID | Requirement |
|--------|------------|
| WHK-F-001 | The software shall support webhook registration with: URL, events, HMAC secret |
| WHK-F-002 | The software shall support 8 webhook event types (per WebhookEvent enum) |
| WHK-F-003 | The software shall sign webhook payloads with HMAC-SHA256 using per-webhook secrets |
| WHK-F-004 | The software shall track delivery attempts with: status code, duration, response body |
| WHK-F-005 | The software shall dispatch webhooks asynchronously via queued SendWebhookPayload listener |
| WHK-F-006 | The software shall track sent_count per webhook |
| WHK-F-007 | The software shall enforce WebhookPolicy |

#### 3.2.13 Games Domain (CSCI-GAM)

**Models:** Game, GameMode
**Controllers:** GameController, GameModeController
**Actions:** CreateGame, UpdateGame, DeleteGame, CreateGameMode, UpdateGameMode, DeleteGameMode

| Req ID | Requirement |
|--------|------------|
| GAM-F-001 | The software shall support a game catalog with: name, description |
| GAM-F-002 | The software shall support game modes with: name, team size, parameters |
| GAM-F-003 | The software shall enforce GamePolicy and GameModePolicy |

#### 3.2.14 Competition Domain (CSCI-COMP)

**Models:** Competition, CompetitionTeam, CompetitionTeamMember, MatchResultProof
**Controllers:** CompetitionController, UserCompetitionController, TeamController, MatchResultController, LanBracketsWebhookController
**Actions:** CreateCompetition, UpdateCompetition, DeleteCompetition, CreateTeam, JoinTeam, LeaveTeam, SubmitMatchResult, HandleLanBracketsWebhook
**Jobs:** SyncCompetitionToLanBrackets, SyncTeamsToLanBrackets
**Policies:** CompetitionPolicy, CompetitionTeamPolicy, MatchResultProofPolicy
**Services:** LanBracketsClient

| Req ID | Requirement |
|--------|------------|
| COMP-F-001 | The software shall support competition creation with: name, type, stage_type, team_size, max_teams, event/game references |
| COMP-F-002 | The software shall enforce CompetitionPolicy with ManageCompetitions permission for admin CRUD |
| COMP-F-003 | The software shall support competition lifecycle transitions: Draft → RegistrationOpen → RegistrationClosed → Running → Finished → Archived |
| COMP-F-004 | The software shall support competition deletion (draft/archived only) |
| COMP-F-005 | The software shall support team creation with captain assignment during registration |
| COMP-F-006 | The software shall support team joining with capacity validation |
| COMP-F-007 | The software shall support team leaving via `LeaveTeam::execute(): bool`. When a member who is not the last member leaves, captaincy is rotated to the oldest-joined active member and `execute()` returns `false`. When the last member leaves, the team is deleted and `execute()` returns `true`. On success the user is redirected to `my-competitions.show` with a flash `success` message. Non-members receive a 403 via `CompetitionTeamPolicy::leave()`. |
| COMP-F-008 | The software shall support match result submission with screenshot proof upload, proxied to LanBrackets API |
| COMP-F-009 | The software shall handle LanBrackets webhooks (competition.completed, match.result_reported, bracket.generated) with HMAC signature verification |
| COMP-F-010 | The software shall sync competitions to LanBrackets via queued job with external_reference_id mapping |
| COMP-F-011 | The software shall sync teams as participants to LanBrackets when registration closes |
| COMP-F-012 | The software shall provide user-facing competition views scoped to team membership only |
| COMP-F-013 | The software shall allow authenticated users to submit a join request for a team via `RequestToJoinTeam` action; a `TeamJoinRequestNotification` shall be dispatched to the team captain |
| COMP-F-014 | The software shall allow the team captain to resolve a join request (approve or reject) via `ResolveJoinRequest` action; a `JoinRequestResolvedNotification` shall be dispatched to the requesting user |
| COMP-F-015 | The software shall prevent duplicate join requests from the same user to the same team, and reject requests when the team is at capacity |

#### 3.2.15 Venue Domain (CSCI-VEN)

**Models:** Venue, VenueImage, Address
**Controllers:** VenueController
**Actions:** CreateVenue, UpdateVenue, DeleteVenue
**Policies:** VenuePolicy

| Req ID | Requirement |
|--------|------------|
| VEN-F-001 | The software shall support venues with: name, description, and associated Address (street, city, state, country) |
| VEN-F-002 | The software shall support venue images with alt text and sort ordering |
| VEN-F-003 | The software shall enforce VenuePolicy authorization on all venue operations |

#### 3.2.15 User Management (CSCI-USR)

**Models:** User, Role
**Controllers:** ProfileController, SecurityController, UserController, SidebarFavoriteController, TicketDiscoveryController, UserAchievementsController
**Actions:** CreateNewUser, ResetUserPassword, UpdateUserAttributes, DeleteUser, ChangeRoles
**Enums:** RoleName, Permission (cross-cutting), RolePermissionMap
**Contracts:** PermissionEnum
**Traits:** HasPermissions

| Req ID | Requirement |
|--------|------------|
| USR-F-001 | The software shall support user registration via Laravel Fortify with email verification |
| USR-F-002 | The software shall support login with email and password |
| USR-F-003 | The software shall support TOTP-based two-factor authentication with recovery codes |
| USR-F-004 | The software shall support password reset via email link |
| USR-F-005 | The software shall support password confirmation for sensitive operations |
| USR-F-006 | The software shall support role-based access with five static roles: User, Moderator, Admin, Superadmin, SponsorManager via `RoleName` enum |
| USR-F-007 | The software shall emit UserRolesChanged and UserAttributesUpdated events on changes |
| USR-F-008 | The software shall support ticket discovery settings (user visibility to other users) |
| USR-F-009 | The software shall support sidebar favorites for navigation customization |
| USR-F-010 | The software shall support appearance/theme settings |
| USR-F-011 | The software shall integrate with Stripe Cashier for billing customer management |
| USR-F-012 | The software shall store user address fields (phone, street, city, zip_code, country) on the users table. These fields shall not appear in any public-facing API response, public profile page, integration DTO, or webhook payload (privacy carve-out enforced by SEC-021) |
| USR-F-013 | The software shall enforce profile completeness (address + at least phone or email) via `hasCompleteProfile()` before allowing cart item additions |
| USR-F-014 | The software shall define granular per-domain permissions via domain-specific `Permission` enums (23 cases across 15 domain enums plus 3 cases in `app/Enums/Permission.php` and 1 case in `AuditPermission.php` — 27 cases total across 17 enums) implementing the `PermissionEnum` interface, with cross-cutting audit permissions in a separate `AuditPermission` enum |
| USR-F-015 | The software shall map permissions to roles statically via `RolePermissionMap::forRole()` without database tables, with `RolePermissionMap::all()` collecting every permission case across all domain enums |
| USR-F-016 | The software shall provide `hasPermission()`, `hasAnyPermission()`, and `allPermissions()` methods on the User model via the `HasPermissions` trait, with request-scoped caching of resolved permissions |
| USR-F-017 | The software shall centralize superadmin authorization bypass via a single `Gate::before()` callback in `AppServiceProvider`, removing per-policy `before()` methods |
| USR-F-018 | The software shall share the authenticated user's resolved permissions with the frontend via Inertia shared props as a flat string array |
| USR-F-019 | The software shall provide a `usePermissions()` Vue composable exposing typed `can(PermissionValue)` and `canAny(PermissionValue...)` helper functions backed by a `Permission` TypeScript constant object for compile-time safety |
| USR-F-020 | The software shall render sidebar navigation sections conditionally based on the user's resolved permissions |
| USR-F-021 | The software shall store a `locale` column (VARCHAR, nullable) on the `users` table representing the user's preferred display language; the value shall be one of `en`, `de`, `fr`, `es` or NULL (meaning use the application default `en`); the profile settings form shall expose a locale selector and validate against this set |
| USR-F-022 | The software shall provide a public-facing `username` column on the `users` table (`VARCHAR(32)`, nullable, unique with case-insensitive collation), validated against the regex `^[A-Za-z0-9][A-Za-z0-9_-]{1,30}[A-Za-z0-9]$` (3–32 characters, gamer character set, no leading or trailing punctuation), enforced by a `usernameRules()` method on `App\Concerns\ProfileValidationRules`. The signup form (`App\Actions\Fortify\CreateNewUser`) shall require a username and persist it on user creation. A `RequireUsername` middleware in the `web` group shall redirect any authenticated user with `username = null` to `/onboarding/username` (allowlisting logout, language switch, asset, and onboarding routes) until the user picks one. The `Onboarding\UsernameController` (GET + POST) shall serve and persist the choice. Username changes after initial selection shall be allowed via the existing profile settings form |
| USR-F-023 | The software shall provide a public profile page at `/u/{username}` (case-insensitive route-model-binding) rendered by `App\Http\Controllers\PublicProfileController`. The controller shall enforce the user's `profile_visibility` setting: `public` (renders for unauthenticated visitors), `logged_in` (renders only for authenticated viewers; otherwise HTTP 404), `private` (renders only when the viewer is the profile owner; otherwise HTTP 404). The Inertia page `resources/js/pages/u/Show.vue` shall render only public fields: `username`, `profile_emoji`, `short_bio`, `profile_description`, resolved `avatar_url`, resolved `banner_url`, and the user's earned achievements with rarity (ACH-F-008). The fields `name`, `email`, `phone`, `street`, `city`, `zip_code`, `country`, and `locale` shall not be present in the response body or shared Inertia props for this route. Visibility-mode failures shall return HTTP 404 (not 403) so the response is indistinguishable from a non-existent username |
| USR-F-024 | The software shall provide profile customization fields on the `users` table: `short_bio` (`VARCHAR(160)`, nullable), `profile_description` (TEXT, nullable), `profile_emoji` (`VARCHAR(16)`, nullable; sized to accommodate ZWJ-joined emoji sequences), `avatar_source` (`VARCHAR(16)`, NOT NULL, default `'default'`, enum-cast to `App\Domain\Profile\Enums\AvatarSource`: `Default`/`Gravatar`/`Custom`/`Steam` — the `Steam` case is reserved for a future Steam-account-linking iteration and shall fall back to `Default` URL until that iteration ships), `avatar_path` (`VARCHAR(255)`, nullable; relative path on the public disk), `banner_path` (`VARCHAR(255)`, nullable). Custom avatar uploads shall be normalized to 1000×1000 px WebP server-side via `App\Domain\Profile\Actions\NormalizeAvatar`. Banner uploads shall be normalized to 1500×500 px (3:1) WebP via `App\Domain\Profile\Actions\NormalizeBanner`. Ingress size shall be capped at 5 MB; mime types shall be restricted to `image/jpeg`, `image/png`, `image/webp`. Replacing an avatar or banner shall delete the previous file. `User::avatarUrl()` shall resolve the configured `avatar_source` chain and return a non-null URL on every call. Endpoints: `POST/DELETE /settings/profile/avatar` and `POST/DELETE /settings/profile/banner` served by `App\Http\Controllers\Settings\ProfileMediaController` |
| USR-F-025 | The software shall provide a `profile_visibility` column on the `users` table (`VARCHAR(16)`, NOT NULL, default `'logged_in'`, enum-cast to `App\Domain\Profile\Enums\ProfileVisibility`: `Public`/`LoggedIn`/`Private`) and shall expose it as a radio group in `resources/js/pages/settings/Privacy.vue`, persisted via the existing `App\Http\Controllers\Settings\PrivacyController::update`. The default value shall apply to both new signups and users migrated from before this requirement shipped |
| USR-F-026 | The software shall provide a profile preview action at `GET /settings/profile/preview` rendering the same Inertia page as the public profile (USR-F-023) for the authenticated user, bypassing the visibility check (always rendered as if the visibility were `Public`). The rendered page shall include a "Preview" indicator marking the response as a preview rather than the live public view. The preview must not change the persisted `profile_visibility` value |

#### 3.2.16 Orchestration Domain (CSCI-ORC)

**Models:** GameServer, OrchestrationJob, MatchChatMessage
**Controllers:** GameServerController, OrchestrationJobController, Tmt2WebhookController
**Actions:** CreateGameServer, UpdateGameServer, DeleteGameServer, SelectServerForMatch, ResolveMatchHandler, ProcessOrchestrationJob, CompleteOrchestrationJob, ReleaseGameServer, RetryOrchestrationJob, CancelOrchestrationJob, ForceReleaseGameServer, HandleTmt2Webhook
**Enums:** GameServerStatus, GameServerAllocationType, OrchestrationJobStatus, Permission
**Contracts:** MatchHandlerContract, SupportsChatFeature
**Handlers:** Tmt2MatchHandler
**Listeners:** HandleMatchReadyForOrchestration, HandleMatchCompleted
**Jobs:** ProcessMatchOrchestration
**Policies:** GameServerPolicy, OrchestrationJobPolicy
**Events (in Competition domain):** MatchReadyForOrchestration, MatchCompleted

| Req ID | Requirement |
|--------|------------|
| ORC-F-001 | The software shall provide CRUD operations for game servers via GameServerController with ManageGameServers permission |
| ORC-F-002 | The software shall store game servers with: name, host, port, game_id, game_mode_id, status, allocation_type, encrypted credentials, metadata |
| ORC-F-003 | The software shall enforce GameServerPolicy and OrchestrationJobPolicy with ManageGameServers and ViewOrchestration permissions |
| ORC-F-004 | The software shall support game server allocation types: Competition (reserved for matches), Casual (pooled for casual play), Flexible (both) |
| ORC-F-005 | The software shall select available servers in priority order: Competition > Flexible > Casual, with pessimistic locking to prevent race conditions |
| ORC-F-006 | The software shall create orchestration jobs from MatchReadyForOrchestration events emitted by the Competition domain |
| ORC-F-007 | The software shall process orchestration jobs via queued workers (ProcessMatchOrchestration) on a dedicated orchestration queue with retry logic (3 tries, 10s backoff) |
| ORC-F-008 | The software shall deploy match configs via MatchHandlerContract implementations resolved per game, with TMT2 as the first concrete handler for CS2/Source 2 games |
| ORC-F-009 | The software shall track orchestration job lifecycle: Pending → SelectingServer → Deploying → Active → Completed/Failed/Cancelled |
| ORC-F-010 | The software shall release game servers (set status to Available) upon match completion or job failure |
| ORC-F-011 | The software shall prevent duplicate orchestration jobs per competition+match pair via unique database constraint |
| ORC-F-012 | The software shall support MatchHandler feature interfaces (SupportsChatFeature) for optional capabilities like in-game chat capture via MatchChatMessage model |
| ORC-F-013 | The software shall receive TMT2 webhooks per orchestration job and auto-report MATCH_END results to LanBrackets for automated bracket progression |
| ORC-F-014 | The software shall support admin manual controls: retry failed jobs, cancel pending/failed jobs, force-release in-use servers |
| ORC-F-015 | The software shall display server connection info (IP:port, password) to match participants when the orchestration job is active |

#### 3.2.17 Api Domain (CSCI-API)

**Clients:** Tmt2Client

| Req ID | Requirement |
|--------|------------|
| API-F-001 | The software shall provide an HTTP client for TMT2 REST API with bearer token authentication, retries, and feature flag (tmt2.enabled) |
| API-F-002 | The software shall support TMT2 match lifecycle operations: create, get, update, delete matches via Tmt2Client |

#### 3.2.18 Organization Settings (CSCI-ORG)

**Controllers:** OrganizationSettingsController
**Actions:** UpdateOrganizationSettings, UploadOrganizationLogo, RemoveOrganizationLogo

| Req ID | Requirement |
|--------|------------|
| ORG-F-001 | The software shall store organization identity fields — name, logo path, address, and legal notice — in a key/value settings table or equivalent |
| ORG-F-002 | The software shall restrict all organization settings routes to administrators |
| ORG-F-003 | The software shall serve the organization identity as a shared Inertia prop (`organization`) on every page, containing at minimum `{ name, logoUrl }` |
| ORG-F-004 | The software shall cache the `organization` shared prop under cache key `inertia.organization` with a 1-hour TTL and invalidate this cache whenever `OrganizationSettingsController::update`, `uploadLogo`, or `removeLogo` is called |
| ORG-F-005 | The software shall support logo upload to the `public` disk and return a signed URL; logo removal shall delete the file and null the setting |

#### 3.2.19 Integration Client Library CSCI (CSCI-ICLIB)

**Package:** `lan-software/lancore-client` (standalone Composer package, separate repository, published on Packagist)

**Primary components:** `LanCoreClient`, `EntranceClient` (opt-in sub-client), `LanCoreUser` DTO, exception hierarchy, `VerifyLanCoreWebhook` middleware, `HandlesLanCore*Webhook` abstract controllers (one per event), `LanCoreServiceProvider`.

This CSCI is consumed by all Lan\* satellite applications (LanBrackets, LanEntrance, LanShout, LanHelp, LanChart, LanBase) as the canonical LanCore Integration API client. It replaces per-satellite implementations of `app/Services/LanCoreClient.php`, duplicated webhook verification middleware, and ad-hoc exception handling.

| Req ID | Requirement |
|--------|------------|
| ICLIB-F-001 | The package shall expose a `LanCoreClient` service with at least the following public methods: `ssoAuthorizeUrl(): string`, `exchangeCode(string $code): LanCoreUser`, `resolveUserById(int $id): LanCoreUser`, `resolveUserByEmail(string $email): LanCoreUser`, `currentUser(): LanCoreUser` |
| ICLIB-F-002 | The package shall expose a `LanCoreUser` DTO as the canonical return type for all user-returning client methods, containing at minimum: `id: int`, `username: ?string` (nullable to accommodate users registered before USR-F-022 shipped and still in the one-time onboarding state — see CAP-USR-011), `name: string` (real name; satellite-internal use only — never publicly displayed; consumption is governed by ICLIB-F-010), `email: ?string`, `locale: ?string`, `avatar: ?string` (deprecated alias retained for backward compatibility) → `avatar_url: string` (always non-null per USR-F-024 fallback chain), `avatar_source: string` (one of `default`/`gravatar`/`custom`/`steam`), `profile_url: ?string` (absolute URL of the user's public profile, null when `username` is null), `roles: string[]`, `createdAt: ?CarbonImmutable` |
| ICLIB-F-003 | The package shall expose the following exception hierarchy, all extending a common `LanCoreException` base: `LanCoreDisabledException` (raised when `config('lancore.enabled') === false`), `LanCoreUnavailableException` (raised on connection failure or HTTP 5xx), `LanCoreRequestException` (raised on HTTP 4xx, exposing a `statusCode` property), `InvalidLanCoreUserException` (raised on malformed user payload) |
| ICLIB-F-004 | The package shall provide a `VerifyLanCoreWebhook` Laravel middleware that verifies the `X-Webhook-Signature` header as `sha256=<hex>` against `hash_hmac('sha256', $rawBody, config('lancore.webhooks.secret'))`, rejects requests whose `X-Webhook-Event` is not in the controller's allowlist, and bypasses verification when the configured secret is an empty string (local development only) |
| ICLIB-F-005 | The package shall provide one abstract invokable controller per webhook event type (`HandlesLanCoreUserRegisteredWebhook`, `HandlesLanCoreUserRolesUpdatedWebhook`, `HandlesLanCoreProfileUpdatedWebhook`, `HandlesLanCoreAnnouncementPublishedWebhook`, `HandlesLanCoreNewsArticlePublishedWebhook`, `HandlesLanCoreEventPublishedWebhook`, `HandlesLanCoreTicketPurchasedWebhook`, `HandlesLanCoreIntegrationAccessedWebhook`), each receiving a typed payload DTO specific to its event and exposing template-method hooks (`resolveUser`, `handle`) that satellites implement with their own domain logic |
| ICLIB-F-006 | The package shall provide an opt-in `$client->entrance()` sub-client returning an `EntranceClient` with methods `validate`, `confirmCheckin`, `verifyCheckin`, `confirmPayment`, `submitOverride`, `searchAttendees`, `stats`, `events`, `fetchSigningKeys`; the `fetchSigningKeys` method shall cache results under key `lancore.jwks` in the configured cache store with TTL from `config('lancore.entrance.signing_keys_cache_ttl')`; the sub-client shall raise a `LanCoreDisabledException` variant when `config('lancore.entrance.enabled') === false` |
| ICLIB-F-007 | The package shall ship a `LanCoreServiceProvider` that: merges and publishes `config/lancore.php`, binds `LanCoreClient` as a singleton resolving config at first use (Octane-safe — no request state captured at bind time), registers the `VerifyLanCoreWebhook` middleware under alias `lancore.webhook`, and throws `LanCoreDisabledException` from the client early when `config('lancore.enabled') === false` |
| ICLIB-F-008 | The package shall retry transient HTTP failures using Laravel's `Http::retry($retries, $delayMs)` with values from `config('lancore.http.retries')` and `config('lancore.http.retry_delay')`, defaulting to 2 retries and 100 ms |
| ICLIB-F-009 | The package shall expose a `LanCoreClient::fake()` factory for use in satellite test suites that registers `Http::fake()` responses and returns an assertion API sufficient to verify outbound request shape without a live LanCore instance |
| ICLIB-F-010 | Satellite applications consuming the `LanCoreUser` DTO via `exchangeCode()`, `resolveUserById()`, `resolveUserByEmail()`, `currentUser()`, or webhook payload deserialization SHALL use `username` as the sole public-facing player display field on every public-facing surface (tournament brackets, leaderboards, player cards, scoreboards, OBS overlays, public team rosters, etc.). Satellites SHALL NOT display `name` (real name) or `email` publicly. When `username` is `null` (transitional state for users registered before USR-F-022), satellites SHALL render a generic placeholder of the form `Player #{id}` rather than substituting the real name. Satellites MAY use `name` and `email` for purely internal-facing operator screens (admin lists, audit logs) where access is restricted to authorized staff |

### 3.3 CSCI External Interface Requirements

See [IRS](IRS.md) for detailed external interface requirements.

### 3.4 CSCI Internal Interface Requirements

| Interface | From | To | Mechanism |
|-----------|------|----|-----------|
| Domain Events | Domain Actions | Event Listeners | Laravel Event Dispatcher |
| Inertia Props | Controllers | Vue Pages | Inertia.js JSON serialization |
| Policy Checks | Controllers | Policies | Laravel Gate/Authorization |
| Cache Access | Models/Services | Redis | ModelCacheService with tags |
| Queue Dispatch | Listeners | Queue Workers | Laravel Queue via Database |

### 3.5 CSCI Internal Data Requirements

- All Eloquent models use auto-incrementing integer primary keys
- Timestamps (created_at, updated_at) on all models
- Soft deletes where applicable
- JSONB columns for flexible structured data (seat plans, banner images)
- Polymorphic relationships for purchasable items

### 3.6 Adaptation and Internationalization Requirements

#### 3.6.1 Adaptation

- The system shall be configurable via `.env` environment variables
- The system shall support timezone configuration per installation
- The system shall support configurable bcrypt rounds for password hashing

#### 3.6.2 Internationalization (CSCI-I18N)

**Models:** User (locale column)
**Middleware:** SetLocale
**Actions:** UpdateUserAttributes (locale field)
**Shared Props:** HandleInertiaRequests (locale, availableLocales)
**Bug Fix:** ResolveIntegrationUser (§INT)

| Req ID | Requirement |
|--------|------------|
| I18N-F-001 | The software shall support four display locales: `en` (English), `de` (German), `fr` (French), `es` (Spanish); `en` shall be the default locale |
| I18N-F-002 | The software shall provide a `SetLocale` middleware that: (a) for authenticated requests, calls `app()->setLocale($user->locale ?? config('app.locale'))` using the locale stored on the authenticated user; (b) for unauthenticated requests, parses the `Accept-Language` header and maps it to the nearest supported locale, falling back to `en`; the middleware shall be registered in the web middleware group after session initialization |
| I18N-F-003 | The software's `HandleInertiaRequests` middleware shall share two Inertia props on every response: `locale` (the active locale string) and `availableLocales` (the array `['en', 'de', 'fr', 'es']`) so that the Vue frontend can configure `vue-i18n` and display the locale switcher without an additional API call |
| I18N-F-004 | The software shall provide backend translation files under `lang/{en,de,fr,es}/` via `php artisan lang:publish`; all user-facing string output from Laravel (validation messages, email subjects, notification titles) shall use the `__()` / `trans()` helper referencing these files |
| I18N-F-005 | The software's Vue frontend shall use `vue-i18n@9+` initialized with the `locale` Inertia shared prop; translation strings shall reside in `resources/js/locales/{en,de,fr,es}.json`; all UI text shall use the `t()` composable rather than hard-coded strings |
| I18N-F-006 | The software shall receive translation strings from the Weblate component at `https://weblate.sxcs.de/projects/lan-software/lancore/`; Weblate shall push translator commits directly to the `weblate` branch of the repository via an SSH deploy key with write access; the `.github/workflows/weblate-merge.yml` workflow shall fast-forward the `weblate` branch onto `main`; no separate CLI export or nightly pull step is required |
| I18N-F-007 | The `ResolveIntegrationUser` action (`app/Domain/Integration/Actions/ResolveIntegrationUser.php`, line ~40) shall pass `$user->locale` — the value stored on the `User` model — when constructing the `LanCoreUser` DTO; it shall NOT pass `app()->getLocale()` (the current request locale), which was the prior behaviour and constitutes a bug causing satellites to receive the server's request locale instead of the user's preference |

### 3.7 Safety Requirements

Not applicable — LanCore is not a safety-critical system.

### 3.8 Security and Privacy Requirements

See SSS Section 3.6 for system-level security requirements.

Additional CSCI-level requirements:

| Req ID | Requirement |
|--------|------------|
| SEC-CSCI-001 | Form Request classes shall validate all user input before processing |
| SEC-CSCI-002 | Eloquent models shall define $fillable or $guarded attributes for mass assignment protection |
| SEC-CSCI-003 | File uploads shall be stored in private S3 buckets with signed URL access |
| SEC-CSCI-004 | The software shall use parameterized queries (via Eloquent) to prevent SQL injection |
| SEC-CSCI-005 | Vue components shall use v-text or template interpolation to prevent XSS |
| SEC-CSCI-006 | Ed25519 private key files (`storage/keys/ticket_signing/{kid}.key`) shall have filesystem permissions restricted to the application process user only (mode 0600) and shall not be readable by web-accessible paths |
| SEC-CSCI-007 | The HMAC pepper for nonce hashing shall be supplied via the `TICKET_TOKEN_PEPPER` environment variable; it shall never be hardcoded, logged, or included in version control |

### 3.9 Computer Resource Requirements

| Resource | Requirement |
|----------|------------|
| PHP Memory | 256 MB per worker (configurable via php.ini) |
| Database Connections | Connection pooling via persistent connections |
| Redis Memory | Dependent on cache usage; minimum 256 MB recommended |
| Storage | S3-compatible bucket for file uploads |
| Queue Workers | Minimum 3 workers via Horizon (configurable) |

### 3.10 Software Quality Factors

| Factor | Requirement |
|--------|------------|
| Correctness | All business rules enforced via Actions and Form Requests |
| Reliability | Failed queue jobs logged and retryable |
| Efficiency | Eager loading to prevent N+1 queries; Redis caching |
| Integrity | Audit trails via laravel-auditing on critical models |
| Usability | Responsive UI via Tailwind CSS; mobile-friendly |
| Maintainability | Domain-driven design; isolated bounded contexts |
| Testability | Every domain testable via Pest; factories for all models |
| Portability | Docker-based deployment; no OS-specific dependencies |

---

## 4. Qualification Provisions

| Req Category | Verification Method | Tool |
|-------------|-------------------|------|
| Functional Requirements | Automated feature tests | Pest PHP |
| Interface Requirements | Integration tests | Pest PHP, manual |
| Security Requirements | Security audit, automated checks | Pest PHP, manual review |
| Quality Factors | Code review, linting, architecture tests | Pint, ESLint, Pest Architecture |
| UI Requirements | E2E tests | Playwright |
| Frontend Components | Component tests | Vitest |

---

## 5. Requirements Traceability

| System Requirement (SSS) | Software Requirement (SRS) |
|--------------------------|---------------------------|
| CAP-EVT-* | EVT-F-* |
| CAP-TKT-001..012 | TKT-F-001..016 |
| CAP-TKT-013 | TKT-F-017..020, TKT-F-022, TKT-F-023, TKT-F-026, TKT-F-028 |
| CAP-TKT-014 | TKT-F-021 |
| CAP-TKT-015 | TKT-F-024, TKT-F-025 |
| CAP-TKT-016 | TKT-F-027, TKT-F-029 |
| SEC-014..020 | TKT-F-017..029 |
| CAP-SHP-* | SHP-F-* |
| CAP-PRG-* | PRG-F-* |
| CAP-SET-* | SET-F-* |
| CAP-SET-005 | SET-F-011 |
| CAP-SET-006 | SET-F-012, SET-F-013, SET-F-014 |
| CAP-SPO-* | SPO-F-* |
| CAP-NWS-* | NWS-F-* |
| CAP-ANN-* | ANN-F-* |
| CAP-ACH-* | ACH-F-* |
| CAP-NTF-* | NTF-F-* |
| CAP-INT-* | INT-F-* |
| CAP-WHK-* | WHK-F-* |
| CAP-GAM-* | GAM-F-* |
| CAP-USR-* | USR-F-* |
| CAP-COMP-* | COMP-F-* |
| CAP-ORC-* | ORC-F-* |
| CAP-ORG-* | ORG-F-* |
| CAP-ICLIB-001..005 | ICLIB-F-001..009 |
| CAP-USR-010 | USR-F-021 |
| CAP-USR-011 | USR-F-022 |
| CAP-USR-012 | USR-F-023 |
| CAP-USR-013 | USR-F-024 |
| CAP-USR-014 | USR-F-025, USR-F-026 |
| CAP-USR-015 | ICLIB-F-002 (amended), ICLIB-F-010 |
| CAP-ACH-005 | ACH-F-008 |
| SEC-021 | USR-F-012 (carve-out), USR-F-023 |
| SEC-022 | USR-F-024 |
| CAP-I18N-001..007 | I18N-F-001..007 |

---

## 6. Notes

### 6.1 Acronyms

| Term | Definition |
|------|-----------|
| CSCI | Computer Software Configuration Item |
| CRUD | Create, Read, Update, Delete |
| HMAC | Hash-based Message Authentication Code |
| JSONB | JSON Binary (PostgreSQL) |
| ORM | Object-Relational Mapping |
| SPA | Single Page Application |
| SSO | Single Sign-On |
| XSS | Cross-Site Scripting |

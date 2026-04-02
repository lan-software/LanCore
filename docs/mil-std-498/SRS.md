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

LanCore is a monolithic web application comprising a Laravel 13 backend and a Vue.js 3 frontend connected via Inertia.js v2. It is organized into 14 domain modules, each encapsulating a bounded context of the event management domain.

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

### 3.2 CSCI Capability Requirements

#### 3.2.1 Event Domain (CSCI-EVT)

**Models:** Event, Venue, VenueImage, Address
**Controllers:** EventController, PublicEventController, EventAuditController, VenueController
**Actions:** CreateEvent, UpdateEvent, PublishEvent, UnpublishEvent, DeleteEvent, CreateVenue, UpdateVenue, DeleteVenue

| Req ID | Requirement |
|--------|------------|
| EVT-F-001 | The software shall provide CRUD operations for events via EventController |
| EVT-F-002 | The software shall store events with: name, description, start_date, end_date, status, banner_images, seat_capacity |
| EVT-F-003 | The software shall enforce EventPolicy authorization on all event operations |
| EVT-F-004 | The software shall support event status transitions: draft → published, published → draft |
| EVT-F-005 | The software shall emit EventPublished event on publication, triggering HandleEventPublishedWebhooks listener |
| EVT-F-006 | The software shall support venues with: name, description, and associated Address (street, city, state, country) |
| EVT-F-007 | The software shall support venue images with alt text and sort ordering |
| EVT-F-008 | The software shall provide audit trail views for events via EventAuditController |
| EVT-F-009 | The software shall provide public event listing via PublicEventController |
| EVT-F-010 | The software shall support a primary program assignment per event |

#### 3.2.2 Ticketing Domain (CSCI-TKT)

**Models:** Ticket, TicketType, TicketCategory, TicketGroup, Addon
**Controllers:** TicketController, AdminTicketController, TicketTypeController, TicketCategoryController, AddonController
**Actions:** CreateTicketType, UpdateTicketType, DeleteTicketType, CreateTicketCategory, CreateAddon, UpdateTicketAssignments

| Req ID | Requirement |
|--------|------------|
| TKT-F-001 | The software shall support ticket types with: name, description, price, quota, max_per_user, sale_start, sale_end |
| TKT-F-002 | The software shall support ticket categories belonging to events for logical grouping |
| TKT-F-003 | The software shall support ticket groups as an alternative grouping mechanism |
| TKT-F-004 | The software shall track individual tickets with statuses: available, assigned, used, checked_in |
| TKT-F-005 | The software shall generate unique validation IDs per ticket (TicketValidationId) |
| TKT-F-006 | The software shall support three ticket assignment roles: owner, manager, user |
| TKT-F-007 | The software shall support ticket add-ons with pricing history tracked via pivot table |
| TKT-F-008 | The software shall enforce TicketTypePolicy, TicketCategoryPolicy, and AddonPolicy |
| TKT-F-009 | The software shall support seating/row ticket types linked to seat plans |
| TKT-F-010 | The software shall provide admin ticket management views (AdminTicketController) |
| TKT-F-011 | The software shall support ticket locking to prevent concurrent modifications |
| TKT-F-012 | The software shall maintain audit trails for ticket types, categories, and add-ons |

#### 3.2.3 Shop Domain (CSCI-SHP)

**Models:** Order, OrderLine, Cart, CartItem, Voucher, PurchaseRequirement, GlobalPurchaseCondition, PaymentProviderCondition, CheckoutAcknowledgement
**Controllers:** ShopController, CartController, OrderController, VoucherController, PurchaseRequirementController, GlobalPurchaseConditionController, PaymentProviderConditionController
**Actions:** CreateCheckoutSession, FulfillOrder, CreateOrder, CreateVoucher, UpdateVoucher, DeleteVoucher
**Payment Providers:** StripePaymentProvider, OnSitePaymentProvider, PaymentProviderManager

| Req ID | Requirement |
|--------|------------|
| SHP-F-001 | The software shall maintain one cart per user per event |
| SHP-F-002 | The software shall support polymorphic cart items (tickets, add-ons via Purchasable interface) |
| SHP-F-003 | The software shall create Stripe Checkout sessions via StripePaymentProvider |
| SHP-F-004 | The software shall support on-site payment via OnSitePaymentProvider |
| SHP-F-005 | The software shall select payment provider via PaymentProviderManager factory |
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

**Models:** SeatPlan
**Controllers:** SeatPlanController, SeatPlanAuditController
**Actions:** CreateSeatPlan, UpdateSeatPlan, DeleteSeatPlan

| Req ID | Requirement |
|--------|------------|
| SET-F-001 | The software shall provide a canvas-based seat plan editor using seatmap-canvas library |
| SET-F-002 | The software shall store seat plan data as JSONB in PostgreSQL |
| SET-F-003 | The software shall associate seat plans with events |
| SET-F-004 | The software shall enforce SeatPlanPolicy authorization |
| SET-F-005 | The software shall maintain audit trails for seat plan modifications |

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

**Models:** Announcement, AnnouncementDismissal
**Controllers:** AnnouncementController, PublicAnnouncementController, AnnouncementDismissalController
**Actions:** CreateAnnouncement, UpdateAnnouncement, DeleteAnnouncement

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

#### 3.2.10 Notification Domain (CSCI-NTF)

**Models:** NotificationPreference, ProgramNotificationSubscription, PushSubscription
**Controllers:** NotificationController, NotificationSettingsController, ProgramSubscriptionController, PushSubscriptionController

| Req ID | Requirement |
|--------|------------|
| NTF-F-001 | The software shall store per-user notification preferences with mail and push toggles per category |
| NTF-F-002 | The software shall support notification categories: news, events, comments, programs, announcements |
| NTF-F-003 | The software shall support Web Push subscriptions with endpoint, public key, and auth token |
| NTF-F-004 | The software shall support program-specific notification subscriptions |
| NTF-F-005 | The software shall support notification archiving with archived_at timestamp |
| NTF-F-006 | The software shall deliver notifications via: database, mail, web push channels |

#### 3.2.11 Integration Domain (CSCI-INT)

**Models:** IntegrationApp, IntegrationToken
**Controllers:** IntegrationAppController, IntegrationTokenController, IntegrationSsoController, IntegrationUserController
**Actions:** CreateIntegrationApp, UpdateIntegrationApp, DeleteIntegrationApp, CreateIntegrationToken, RotateIntegrationToken, RevokeIntegrationToken, GenerateSsoAuthorizationCode, ExchangeSsoAuthorizationCode, ResolveIntegrationUser, SyncIntegrationWebhooks
**Middleware:** AuthenticateIntegration

| Req ID | Requirement |
|--------|------------|
| INT-F-001 | The software shall support integration app registration with: name, description, callback URL, webhook subscriptions |
| INT-F-002 | The software shall generate API tokens with support for rotation and revocation |
| INT-F-003 | The software shall hash API tokens before database storage |
| INT-F-004 | The software shall provide OAuth2-like SSO with authorization code flow |
| INT-F-005 | The software shall authenticate integration API requests via Bearer token (AuthenticateIntegration middleware) |
| INT-F-006 | The software shall support navigation hints (nav_title, nav_url, nav_icon) for integrations |
| INT-F-007 | The software shall emit IntegrationAccessed events and dispatch webhooks |
| INT-F-008 | The software shall provide stateless API routes under `/api/integration/` |
| INT-F-009 | The software shall enforce IntegrationAppPolicy |
| INT-F-010 | The software shall provide Artisan commands for integration management (create, list, revoke tokens) |

#### 3.2.12 Webhook Domain (CSCI-WHK)

**Models:** Webhook, WebhookDelivery
**Controllers:** WebhookController
**Actions:** CreateWebhook, UpdateWebhook, DeleteWebhook, DispatchWebhooks
**Events:** WebhookDispatched
**Listeners:** SendWebhookPayload

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

#### 3.2.14 User Management (CSCI-USR)

**Models:** User, Role
**Controllers:** ProfileController, SecurityController, UserController, SidebarFavoriteController, TicketDiscoveryController, UserAchievementsController
**Actions:** CreateNewUser, ResetUserPassword, UpdateUserAttributes, DeleteUser, ChangeRoles

| Req ID | Requirement |
|--------|------------|
| USR-F-001 | The software shall support user registration via Laravel Fortify with email verification |
| USR-F-002 | The software shall support login with email and password |
| USR-F-003 | The software shall support TOTP-based two-factor authentication with recovery codes |
| USR-F-004 | The software shall support password reset via email link |
| USR-F-005 | The software shall support password confirmation for sensitive operations |
| USR-F-006 | The software shall support role-based access with roles: Admin, Superadmin, SponsorManager |
| USR-F-007 | The software shall emit UserRolesChanged and UserAttributesUpdated events on changes |
| USR-F-008 | The software shall support ticket discovery settings (user visibility to other users) |
| USR-F-009 | The software shall support sidebar favorites for navigation customization |
| USR-F-010 | The software shall support appearance/theme settings |
| USR-F-011 | The software shall integrate with Stripe Cashier for billing customer management |

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

### 3.6 Adaptation Requirements

- The system shall be configurable via `.env` environment variables
- The system shall support timezone configuration per installation
- The system shall support locale configuration for internationalization
- The system shall support configurable bcrypt rounds for password hashing

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
| CAP-TKT-* | TKT-F-* |
| CAP-SHP-* | SHP-F-* |
| CAP-PRG-* | PRG-F-* |
| CAP-SET-* | SET-F-* |
| CAP-SPO-* | SPO-F-* |
| CAP-NWS-* | NWS-F-* |
| CAP-ANN-* | ANN-F-* |
| CAP-ACH-* | ACH-F-* |
| CAP-NTF-* | NTF-F-* |
| CAP-INT-* | INT-F-* |
| CAP-WHK-* | WHK-F-* |
| CAP-GAM-* | GAM-F-* |
| CAP-USR-* | USR-F-* |

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

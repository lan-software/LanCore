# Requirements Traceability Matrix (RTM)

**Document Identifier:** LanCore-RTM-001
**Version:** 0.1.0
**Date:** 2026-04-02
**Status:** Draft
**Classification:** Unclassified

### Author

| Role | Name |
|------|------|
| Project Lead | Markus Kohn |

---

## 1. Overview

This Requirements Traceability Matrix maps every SRS requirement to its implementing source code, test file(s), and coverage status. It enables impact analysis when requirements change and identifies gaps where tests are needed.

**Legend:**
- **Covered** — Test(s) exist that exercise the requirement
- **Partial** — Some aspects tested but key scenarios missing
- **Gap** — No test coverage exists for this requirement

**Statistics:**
- Total SRS Requirements: 128
- Total Test Files: 121
- Total Test Cases: 834

---

## 2. Event Domain (CSCI-EVT)

| Req ID | Requirement | Source | Test File(s) | Status |
|--------|-------------|--------|--------------|--------|
| EVT-F-001 | Event CRUD via EventController | `Domain/Event/Http/Controllers/EventController.php` | `Events/EventCrudTest.php` (13 tests) | Covered |
| EVT-F-002 | Event attributes (name, dates, status, banners, capacity) | `Domain/Event/Models/Event.php` | `Events/EventCrudTest.php` | Covered |
| EVT-F-003 | EventPolicy authorization | `Domain/Event/Policies/EventPolicy.php` | `Events/AccessTest.php` (4 tests) | Covered |
| EVT-F-004 | Event status transitions (draft ↔ published) | `Domain/Event/Actions/PublishEvent.php`, `UnpublishEvent.php` | `Events/PublishEventTest.php` (7 tests) | Covered |
| EVT-F-005 | EventPublished event + webhook dispatch | `Domain/Event/Events/EventPublished.php`, `Domain/Event/Listeners/HandleEventPublishedWebhooks.php` | `Webhook/WebhookTest.php` | Covered |
| EVT-F-006 | Venue CRUD with address | `Domain/Venue/Actions/CreateVenue.php` | `Venues/VenueCrudTest.php` (12 tests) | Covered |
| EVT-F-007 | Venue images with alt text and sort order | `Domain/Venue/Models/VenueImage.php` | `Venues/VenueCrudTest.php` | Covered |
| EVT-F-008 | Event audit trails | `Domain/Event/Http/Controllers/EventAuditController.php` | `Domain/Event/EventAuditTest.php` (5 tests) | Covered |
| EVT-F-009 | Public event listing | `Domain/Event/Http/Controllers/PublicEventController.php` | `Events/PublicEventListTest.php` (3 tests) | Covered |
| EVT-F-010 | Primary program assignment | `Domain/Event/Models/Event.php` (primaryProgram relation) | `Programs/ProgramCrudTest.php` | Covered |

---

## 3. Ticketing Domain (CSCI-TKT)

| Req ID | Requirement | Source | Test File(s) | Status |
|--------|-------------|--------|--------------|--------|
| TKT-F-001 | Ticket types with pricing, quota, limits, sale windows | `Domain/Ticketing/Actions/CreateTicketType.php` | `Ticketing/TicketTypeCrudTest.php` (13 tests) | Covered |
| TKT-F-002 | Ticket categories per event | `Domain/Ticketing/Actions/CreateTicketCategory.php` | `Ticketing/TicketTypeCrudTest.php` | Covered |
| TKT-F-003 | Ticket groups | `Domain/Ticketing/Models/TicketGroup.php` | — | **Gap** |
| TKT-F-004 | Ticket status tracking (active, checked_in, cancelled) | `Domain/Ticketing/Models/Ticket.php` | `Ticketing/TicketManagementTest.php` (6 tests) | Partial |
| TKT-F-005 | Unique validation IDs | `Domain/Ticketing/Models/Ticket.php` (generateValidationId) | `Ticketing/TicketValidationIdTest.php` (3 tests), `Unit/TicketValidationIdTest.php` (3 tests) | Covered |
| TKT-F-006 | Three assignment roles (owner, manager, user) | `Domain/Ticketing/Actions/UpdateTicketAssignments.php` | `Ticketing/TicketManagementTest.php` | Covered |
| TKT-F-007 | Ticket add-ons with pricing history | `Domain/Ticketing/Models/Addon.php` | `Ticketing/AddonCrudTest.php`, `Ticketing/TicketTypeCrudTest.php` | Covered |
| TKT-F-008 | Policy enforcement (Ticket, TicketType, Category, Addon) | Policies under `Domain/Ticketing/Policies/` | `Events/AccessTest.php`, `Ticketing/TicketTypeCrudTest.php`, `Ticketing/TicketCategoryCrudTest.php`, `Ticketing/AddonCrudTest.php` | Covered |
| TKT-F-009 | ~~Seating/row ticket types linked to seat plans~~ **(Deprecated — superseded by TKT-F-013..016)** | — | — | Deprecated |
| TKT-F-013 | Group ticket types with max_users_per_ticket | `Domain/Ticketing/Models/TicketType.php` | `Ticketing/GroupTicketTest.php` (4 tests) | Covered |
| TKT-F-014 | Multi-user assignment via ticket_user pivot | `Domain/Ticketing/Models/Ticket.php` | `Ticketing/GroupTicketTest.php` (3 tests) | Covered |
| TKT-F-015 | Individual vs group check-in modes | `Domain/Ticketing/Enums/CheckInMode.php`, `Domain/Ticketing/Actions/UpdateTicketAssignments.php` | `Ticketing/GroupTicketTest.php` (3 tests) | Covered |
| TKT-F-016 | Seat capacity = seats_per_user × max_users_per_ticket | `Domain/Event/Models/Event.php`, `Domain/Shop/Actions/` | `Ticketing/GroupTicketTest.php` (2 tests) | Covered |
| TKT-F-010 | Admin ticket management views | `Domain/Ticketing/Http/Controllers/AdminTicketController.php` | `Ticketing/AdminTicketControllerTest.php` (6 tests) | Covered |
| TKT-F-011 | Ticket locking | `Domain/Ticketing/Actions/UpdateTicketType.php` (locked field handling) | `Ticketing/TicketTypeCrudTest.php` (locked fields test) | Covered |
| TKT-F-012 | Audit trails for types, categories, add-ons | Audit controllers | `Domain/Ticketing/TicketTypeAuditTest.php` (3), `TicketCategoryAuditTest.php` (3), `AddonAuditTest.php` (3) | Covered |

### Ticketing Gaps — Proposed Tests

**TKT-F-003: Ticket Groups**
```
File: tests/Feature/Ticketing/TicketGroupTest.php
- it creates a ticket group for an event
- it updates a ticket group
- it deletes a ticket group
- it associates ticket types with a group
- it lists ticket groups for an event
```

**TKT-F-004: Ticket Status Tracking (expand)**
```
File: tests/Feature/Ticketing/TicketStatusTest.php
- it transitions ticket from active to checked_in
- it transitions ticket from active to cancelled
- it prevents invalid status transitions
- it records checked_in_at timestamp on check-in
```

**TKT-F-007: Add-on Pricing History (expand)**
```
File: tests/Feature/Ticketing/AddonPricingTest.php
- it records add-on price at time of purchase in pivot table
- it preserves historical price when add-on price changes
```

**TKT-F-009: ~~Seating/Row Ticket Types~~ (Deprecated — superseded by TKT-F-013..016)**

**TKT-F-013..016: Group Tickets**
```
File: tests/Feature/Ticketing/GroupTicketTest.php
- it creates a group ticket type with max_users_per_ticket and check_in_mode
- it defaults max_users_per_ticket to 1 (backward compatibility)
- it rejects max_users_per_ticket below 1
- it assigns multiple users to a group ticket up to the limit
- it rejects exceeding max_users_per_ticket
- it removes an assigned user from a group ticket
- it calculates seat consumption as seats_per_user × max_users_per_ticket
- it validates seat capacity during checkout for group tickets
- it performs individual check-in for one user on a group ticket
- it marks ticket as CheckedIn when all users individually checked in
- it performs group check-in marking all users at once
- it allows owner to add users to their group ticket
- it allows manager to add users to a group ticket
- it denies non-owner/manager from adding users
```

---

## 4. Shop Domain (CSCI-SHP)

| Req ID | Requirement | Source | Test File(s) | Status |
|--------|-------------|--------|--------------|--------|
| SHP-F-001 | One cart per user per event | `Domain/Shop/Models/Cart.php` | `Cart/CartTest.php` (21 tests) | Covered |
| SHP-F-002 | Polymorphic cart items (Purchasable) | `Domain/Shop/Models/CartItem.php` | `Cart/CartTest.php` | Covered |
| SHP-F-003 | Stripe Checkout sessions | `Domain/Shop/PaymentProviders/StripePaymentProvider.php` | `Shop/StripeCheckoutTest.php` (4 tests) | Covered |
| SHP-F-004 | On-site payment with admin confirmation | `Domain/Shop/PaymentProviders/OnSitePaymentProvider.php`, `Domain/Shop/Http/Controllers/OrderController.php` | `Shop/OnSitePaymentTest.php` (6 tests) | Covered |
| SHP-F-005 | PaymentProviderManager factory | `Domain/Shop/PaymentProviders/PaymentProviderManager.php` | — | **Gap** |
| SHP-F-006 | Order status tracking | `Domain/Shop/Models/Order.php` | `Shop/OrderControllerTest.php` (6 tests) | Covered |
| SHP-F-007 | Vouchers (fixed_amount, percentage) | `Domain/Shop/Actions/CreateVoucher.php` | `Ticketing/VoucherCrudTest.php` (7 tests) | Covered |
| SHP-F-008 | Voucher applicability per event | `Domain/Shop/Models/Voucher.php` (isValid) | `Cart/CartTest.php` | Partial |
| SHP-F-009 | Purchase requirements | `Domain/Shop/Actions/CreatePurchaseRequirement.php` | `Shop/PurchaseRequirementCrudTest.php` (6 tests) | Covered |
| SHP-F-010 | Global + payment-provider conditions | `Domain/Shop/Models/GlobalPurchaseCondition.php` | `Shop/GlobalPurchaseConditionCrudTest.php` (5), `Shop/PaymentProviderConditionCrudTest.php` (6) | Covered |
| SHP-F-011 | Checkout acknowledgements | `Domain/Shop/Models/CheckoutAcknowledgement.php` | `Shop/CheckoutAcknowledgementTest.php` (7 tests) | Covered |
| SHP-F-012 | TicketPurchased event on fulfillment | `Domain/Shop/Events/TicketPurchased.php` | `Shop/StripeWebhookTest.php` | Covered |
| SHP-F-013 | CartItemAdded event | `Domain/Shop/Events/CartItemAdded.php` | — | **Gap** |
| SHP-F-014 | Policy enforcement (Order, Voucher, etc.) | Policies under `Domain/Shop/Policies/` | `Shop/OrderControllerTest.php` | Partial |
| SHP-F-015 | Stripe webhook fulfillment | `Domain/Shop/Listeners/HandleStripeCheckoutCompleted.php` | `Shop/StripeWebhookTest.php` (4 tests) | Covered |
| SHP-F-016 | Idempotent order fulfillment | `Domain/Shop/Actions/FulfillOrder.php` | `Shop/StripeWebhookTest.php` (duplicate test) | Covered |
| SHP-F-017 | User-facing "My Orders" views | `Domain/Shop/Http/Controllers/UserOrderController.php` | `Shop/UserOrderControllerTest.php` (5 tests) | Covered |

### Shop Gaps — Proposed Tests

**SHP-F-005: PaymentProviderManager**
```
File: tests/Feature/Shop/PaymentProviderManagerTest.php
- it resolves StripePaymentProvider for stripe method
- it resolves OnSitePaymentProvider for on_site method
- it throws exception for unknown payment method
- it lists available payment methods
```

**SHP-F-008: Voucher Validation (expand)**
```
File: tests/Feature/Shop/VoucherValidationTest.php
- it rejects expired vouchers
- it rejects vouchers that exceeded max_uses
- it rejects inactive vouchers
- it calculates fixed_amount discount correctly
- it calculates percentage discount correctly
- it caps percentage discount at subtotal
- it validates voucher belongs to the correct event
```

**SHP-F-013: CartItemAdded Event**
```
File: tests/Feature/Shop/CartItemEventTest.php
- it dispatches CartItemAdded event when item added to cart
- it does not dispatch event when cart update fails validation
```

**SHP-F-014: Policy Enforcement (expand)**
```
File: tests/Feature/Shop/ShopAccessTest.php
- it denies non-admin access to voucher management
- it denies non-admin access to purchase requirement management
- it denies non-admin access to global condition management
- it denies non-admin access to payment provider condition management
- it allows users to view only their own orders
```

---

## 5. Program Domain (CSCI-PRG)

| Req ID | Requirement | Source | Test File(s) | Status |
|--------|-------------|--------|--------------|--------|
| PRG-F-001 | Programs with name, description, event | `Domain/Program/Actions/CreateProgram.php` | `Programs/ProgramCrudTest.php` (16 tests) | Covered |
| PRG-F-002 | Program visibility (public, registered_only, hidden) | `Domain/Program/Models/Program.php` | `Programs/ProgramCrudTest.php` | Covered |
| PRG-F-003 | Time slots with ordering and visibility | `Domain/Program/Actions/CreateProgram.php` (time slots) | `Programs/ProgramCrudTest.php` | Covered |
| PRG-F-004 | ProgramTimeSlotApproaching event | `Domain/Program/Events/ProgramTimeSlotApproaching.php` | — | **Gap** |
| PRG-F-005 | Program notification subscriptions | `Domain/Notification/Http/Controllers/ProgramSubscriptionController.php` | `Notification/ProgramSubscriptionTest.php` (4 tests) | Covered |
| PRG-F-006 | Sponsor assignment to programs and time slots | `Domain/Program/Actions/UpdateProgram.php` | `Programs/ProgramCrudTest.php` | Covered |
| PRG-F-007 | Program audit trails | `Domain/Program/Http/Controllers/ProgramAuditController.php` | `Domain/Program/ProgramAuditTest.php` (3 tests) | Covered |

### Program Gaps — Proposed Tests

**PRG-F-004: Time Slot Approaching Notification**
```
File: tests/Feature/Programs/ProgramTimeSlotNotificationTest.php
- it dispatches ProgramTimeSlotApproaching event for upcoming time slots
- it sends notification to subscribed users when time slot approaches
- it does not notify unsubscribed users
- it respects user push notification preferences
```

---

## 6. Seating Domain (CSCI-SET)

| Req ID | Requirement | Source | Test File(s) | Status |
|--------|-------------|--------|--------------|--------|
| SET-F-001 | Canvas-based seat plan editor | `Domain/Seating/Actions/CreateSeatPlan.php` | `Seating/SeatPlanCrudTest.php` (13 tests), `Seating/SeatPlanTest.php` (7 tests) | Covered |
| SET-F-002 | JSONB storage | `Domain/Seating/Models/SeatPlan.php` | `Seating/SeatPlanTest.php` | Covered |
| SET-F-003 | Event association | `Domain/Seating/Models/SeatPlan.php` | `Seating/SeatPlanTest.php` | Covered |
| SET-F-004 | SeatPlanPolicy authorization | `Domain/Seating/Policies/SeatPlanPolicy.php` | `Seating/SeatPlanCrudTest.php` | Covered |
| SET-F-005 | Audit trails | `Domain/Seating/Http/Controllers/SeatPlanAuditController.php` | `Domain/Seating/SeatPlanAuditTest.php` (3 tests) | Covered |

No gaps identified.

---

## 7. Sponsoring Domain (CSCI-SPO)

| Req ID | Requirement | Source | Test File(s) | Status |
|--------|-------------|--------|--------------|--------|
| SPO-F-001 | Sponsors with name, logo, link | `Domain/Sponsoring/Actions/CreateSponsor.php` | `Sponsors/SponsorCrudTest.php` (12 tests) | Covered |
| SPO-F-002 | Sponsor levels with ordering | `Domain/Sponsoring/Actions/CreateSponsorLevel.php` | `Sponsors/SponsorLevelCrudTest.php` (9 tests) | Covered |
| SPO-F-003 | Many-to-many assignments (events, programs, time slots, users) | `Domain/Sponsoring/Actions/CreateSponsor.php` | `Sponsors/SponsorCrudTest.php` | Covered |
| SPO-F-004 | SponsorPolicy and SponsorLevelPolicy | Policies | `Sponsors/AccessTest.php` (7 tests) | Covered |
| SPO-F-005 | Audit trails | Audit controllers | `Domain/Sponsoring/SponsorAuditTest.php` (3), `SponsorLevelAuditTest.php` (3) | Covered |

No gaps identified.

---

## 8. News Domain (CSCI-NWS)

| Req ID | Requirement | Source | Test File(s) | Status |
|--------|-------------|--------|--------------|--------|
| NWS-F-001 | Articles with rich text, SEO metadata, visibility | `Domain/News/Actions/CreateNewsArticle.php` | `News/NewsArticleCrudTest.php` (14 tests) | Covered |
| NWS-F-002 | Visibility: public, draft, members_only, archived | `Domain/News/Models/NewsArticle.php` | `News/PublicNewsTest.php` (9 tests) | Covered |
| NWS-F-003 | User comments on articles | `Domain/News/Http/Controllers/NewsCommentController.php` | `News/NewsCommentTest.php` (14 tests) | Covered |
| NWS-F-004 | Comment voting (upvote/downvote) | `Domain/News/Http/Controllers/NewsCommentController.php` (vote) | `News/NewsCommentTest.php` | Covered |
| NWS-F-005 | NewsArticlePublished event + notifications + webhooks | `Domain/News/Events/NewsArticlePublished.php` | `Notification/NewsArticleNotificationTest.php` (10 tests), `Webhook/WebhookTest.php` | Covered |
| NWS-F-006 | NewsArticleRead event for analytics | `Domain/News/Events/NewsArticleRead.php` | — | **Gap** |
| NWS-F-007 | NewsArticlePolicy and NewsCommentPolicy | Policies | `News/AccessTest.php` (4 tests), `News/NewsCommentAdminTest.php` (9 tests) | Covered |
| NWS-F-008 | Audit trails for articles and comments | Audit controllers | `Domain/News/NewsArticleAuditTest.php` (3), `NewsCommentAuditTest.php` (3) | Covered |

### News Gaps — Proposed Tests

**NWS-F-006: NewsArticleRead Analytics Event**
```
File: tests/Feature/News/NewsArticleReadTest.php
- it dispatches NewsArticleRead event when public article is viewed
- it does not dispatch event for draft articles
- it includes the authenticated user in the event (if logged in)
```

---

## 9. Announcement Domain (CSCI-ANN)

| Req ID | Requirement | Source | Test File(s) | Status |
|--------|-------------|--------|--------------|--------|
| ANN-F-001 | Announcements with title, body, priority, event | `Domain/Announcement/Actions/CreateAnnouncement.php` | `Announcement/AnnouncementTest.php` (19 tests) | Covered |
| ANN-F-002 | Priority levels: low, normal, high, critical | `Domain/Announcement/Enums/AnnouncementPriority.php` | `Announcement/AnnouncementTest.php` | Covered |
| ANN-F-003 | Per-user dismissals | `Domain/Announcement/Http/Controllers/AnnouncementDismissalController.php` | `Announcement/AnnouncementTest.php` | Covered |
| ANN-F-004 | AnnouncementPublished event + notifications + webhooks | `Domain/Announcement/Events/AnnouncementPublished.php` | `Announcement/AnnouncementTest.php`, `Integrations/IntegrationAnnouncementWebhookTest.php` (29 tests) | Covered |
| ANN-F-005 | AnnouncementPolicy | `Domain/Announcement/Policies/AnnouncementPolicy.php` | `Announcement/AnnouncementTest.php` | Covered |

No gaps identified.

---

## 10. Achievement Domain (CSCI-ACH)

| Req ID | Requirement | Source | Test File(s) | Status |
|--------|-------------|--------|--------------|--------|
| ACH-F-001 | Achievement definitions with name, description, icon | `Domain/Achievements/Actions/CreateAchievement.php` | `Achievements/AchievementCrudTest.php` (9 tests) | Covered |
| ACH-F-002 | Event-based triggers via AchievementEvent | `Domain/Achievements/Models/AchievementEvent.php` | `Achievements/AchievementGrantingTest.php` (7 tests) | Covered |
| ACH-F-003 | ProcessAchievements listener | `Domain/Achievements/Listeners/ProcessAchievements.php` | `Achievements/AchievementGrantingTest.php` | Covered |
| ACH-F-004 | Achievement-to-user with earned_at | `Domain/Achievements/Actions/GrantAchievement.php` | `Achievements/AchievementGrantingTest.php` | Covered |
| ACH-F-005 | AchievementPolicy | `Domain/Achievements/Policies/AchievementPolicy.php` | `Achievements/AchievementCrudTest.php` | Covered |
| ACH-F-006 | AchievementEarnedNotification with achievement details | `Notifications/AchievementEarnedNotification.php` | `Achievements/AchievementGrantingTest.php` (7 tests) | Covered |
| ACH-F-007 | Achievement notification display in frontend | `components/NotificationBell.vue`, `pages/notifications/Index.vue`, `pages/notifications/Archive.vue` | `Achievements/AchievementGrantingTest.php` | Covered |

No gaps identified.

---

## 11. Notification Domain (CSCI-NTF)

| Req ID | Requirement | Source | Test File(s) | Status |
|--------|-------------|--------|--------------|--------|
| NTF-F-001 | Per-user preferences with mail/push toggles | `Domain/Notification/Models/NotificationPreference.php` | `Notification/NotificationPreferencesTest.php` (5 tests) | Covered |
| NTF-F-002 | Categories: news, events, comments, programs, announcements | `Domain/Notification/Models/NotificationPreference.php` | `Notification/NotificationPreferencesTest.php` | Covered |
| NTF-F-003 | Web Push subscriptions | `Domain/Notification/Http/Controllers/PushSubscriptionController.php` | `Commands/TestPushNotificationCommandTest.php` (10 tests) | Partial |
| NTF-F-004 | Program notification subscriptions | `Domain/Notification/Http/Controllers/ProgramSubscriptionController.php` | `Notification/ProgramSubscriptionTest.php` (4 tests), `Notification/ProgramSubscriptionExtendedTest.php` | Covered |
| NTF-F-005 | Notification archiving | `Domain/Notification/Http/Controllers/NotificationController.php` | `Notification/NotificationControllerTest.php` (12 tests) | Covered |
| NTF-F-006 | Delivery via database, mail, web push | Notification classes | `Notification/NewsArticleNotificationTest.php` | Partial |
| NTF-F-007 | Session-scoped push prompt dismissal | `Domain/Notification/Http/Controllers/PushSubscriptionController.php`, `Http/Middleware/HandleInertiaRequests.php` | `Notification/PushSubscriptionCrudTest.php` (4 tests) | Covered |

### Notification Gaps — Proposed Tests

**NTF-F-003: Web Push Subscriptions (expand)**
```
File: tests/Feature/Notification/PushSubscriptionTest.php
- it stores a new push subscription
- it replaces existing subscription for same endpoint
- it removes a push subscription
- it rejects invalid endpoint URLs
- it rejects missing keys
```

**NTF-F-006: Multi-Channel Delivery (expand)**
```
File: tests/Feature/Notification/NotificationDeliveryTest.php
- it sends database notification for all notification types
- it sends mail notification when mail preference enabled
- it sends web push when push preference enabled
- it skips mail when preference disabled
- it skips push when preference disabled
```

---

## 12. Integration Domain (CSCI-INT)

| Req ID | Requirement | Source | Test File(s) | Status |
|--------|-------------|--------|--------------|--------|
| INT-F-001 | Integration app registration | `Domain/Integration/Actions/CreateIntegrationApp.php` | `Integrations/IntegrationAppCrudTest.php` (10 tests) | Covered |
| INT-F-002 | API tokens with rotation and revocation | `Domain/Integration/Actions/CreateIntegrationToken.php` | `Integrations/IntegrationTokenTest.php` (11 tests) | Covered |
| INT-F-003 | Token hashing before storage | `Domain/Integration/Actions/CreateIntegrationToken.php` | `Integrations/IntegrationTokenTest.php` | Covered |
| INT-F-004 | SSO with authorization code flow | `Domain/Integration/Actions/GenerateSsoAuthorizationCode.php` | `Integrations/IntegrationSsoTest.php` (15 tests) | Covered |
| INT-F-005 | Bearer token authentication | `Domain/Integration/Http/Middleware/AuthenticateIntegration.php` | `Integrations/IntegrationApiTest.php` (16 tests) | Covered |
| INT-F-006 | Navigation hints | `Domain/Integration/Models/IntegrationApp.php` | `Integrations/IntegrationAppCrudTest.php` | Covered |
| INT-F-007 | IntegrationAccessed events + webhooks | `Domain/Integration/Events/IntegrationAccessed.php` | `Integrations/IntegrationApiTest.php` | Covered |
| INT-F-008 | Stateless API routes | `routes/api-integrations.php` | `Integrations/IntegrationApiTest.php` | Covered |
| INT-F-009 | IntegrationAppPolicy | `Domain/Integration/Policies/IntegrationAppPolicy.php` | `Integrations/AccessTest.php` (6 tests) | Covered |
| INT-F-010 | Artisan commands | Console commands | `Integrations/IntegrationCommandTest.php` (16 tests) | Covered |

No gaps identified.

---

## 13. Webhook Domain (CSCI-WHK)

| Req ID | Requirement | Source | Test File(s) | Status |
|--------|-------------|--------|--------------|--------|
| WHK-F-001 | Webhook registration with URL, events, secret | `Domain/Webhook/Actions/CreateWebhook.php` | `Webhook/WebhookTest.php` (27 tests) | Covered |
| WHK-F-002 | 8 webhook event types | `Domain/Webhook/Enums/WebhookEvent.php` | `Webhook/WebhookTest.php` | Covered |
| WHK-F-003 | HMAC-SHA256 payload signing | `Domain/Webhook/Listeners/SendWebhookPayload.php` | `Webhook/WebhookSignatureTest.php` | Covered |
| WHK-F-004 | Delivery tracking (status, duration, body) | `Domain/Webhook/Models/WebhookDelivery.php` | `Webhook/WebhookTest.php` | Covered |
| WHK-F-005 | Async dispatch via queued listener | `Domain/Webhook/Listeners/SendWebhookPayload.php` | `Webhook/WebhookTest.php` | Covered |
| WHK-F-006 | Sent count per webhook | `Domain/Webhook/Models/Webhook.php` | `Webhook/WebhookTest.php` | Covered |
| WHK-F-007 | WebhookPolicy | `Domain/Webhook/Policies/WebhookPolicy.php` | `Webhook/WebhookTest.php` | Covered |

No gaps identified.

---

## 14. Games Domain (CSCI-GAM)

| Req ID | Requirement | Source | Test File(s) | Status |
|--------|-------------|--------|--------------|--------|
| GAM-F-001 | Game catalog with name, description | `Domain/Games/Actions/CreateGame.php` | `Games/GameCrudTest.php` (15 tests) | Covered |
| GAM-F-002 | Game modes with team size, parameters | `Domain/Games/Actions/CreateGameMode.php` | `Games/GameCrudTest.php` | Covered |
| GAM-F-003 | GamePolicy and GameModePolicy | Policies | `Games/AccessTest.php` (4 tests) | Covered |

No gaps identified.

---

## 15. Venue Domain (CSCI-VEN)

| Req ID | Requirement | Source | Test File(s) | Status |
|--------|-------------|--------|--------------|--------|
| VEN-F-001 | Venue CRUD with address | `Domain/Venue/Actions/CreateVenue.php`, `UpdateVenue.php`, `DeleteVenue.php` | `Venues/VenueCrudTest.php` (12 tests) | Covered |
| VEN-F-002 | Venue images with alt text and sort order | `Domain/Venue/Models/VenueImage.php` | `Venues/VenueCrudTest.php` | Covered |
| VEN-F-003 | VenuePolicy authorization | `Domain/Venue/Policies/VenuePolicy.php` | `Venues/AccessTest.php` (4 tests) | Covered |

No gaps identified.

---

## 16. User Management (CSCI-USR)

| Req ID | Requirement | Source | Test File(s) | Status |
|--------|-------------|--------|--------------|--------|
| USR-F-001 | Registration with email verification | Fortify | `Auth/RegistrationTest.php` (2), `Auth/EmailVerificationTest.php` (6) | Covered |
| USR-F-002 | Login with email/password | Fortify | `Auth/AuthenticationTest.php` (6 tests) | Covered |
| USR-F-003 | TOTP 2FA with recovery codes | Fortify | `Auth/TwoFactorChallengeTest.php` (2 tests) | Covered |
| USR-F-004 | Password reset via email | Fortify | `Auth/PasswordResetTest.php` (5 tests) | Covered |
| USR-F-005 | Password confirmation | Fortify | `Auth/PasswordConfirmationTest.php` (2 tests) | Covered |
| USR-F-006 | Role-based access (User, Moderator, Admin, Superadmin, SponsorManager) | `App/Enums/RoleName.php`, `App/Models/User.php` | `Actions/User/ChangeRolesTest.php` (5 tests) | Covered |
| USR-F-007 | UserRolesChanged + UserAttributesUpdated events | Events | `Actions/User/UpdateUserAttributesTest.php` (2), `Webhook/WebhookTest.php` | Covered |
| USR-F-008 | Ticket discovery settings | `Settings/TicketDiscoveryController.php` | `Settings/TicketDiscoveryTest.php` (9 tests) | Covered |
| USR-F-009 | Sidebar favorites | `Settings/SidebarFavoriteController.php` | `Settings/SidebarFavoriteTest.php` (8 tests) | Covered |
| USR-F-010 | Appearance/theme settings | HandleAppearance middleware | — | **Gap** |
| USR-F-011 | Stripe Cashier billing customer | `App/Models/User.php` (Billable trait) | — | **Gap** |
| USR-F-014 | Domain-specific Permission enums (24 cases across 14 enums) | `App/Contracts/PermissionEnum.php`, `App/Enums/Permission.php`, `App/Enums/AuditPermission.php`, `App/Domain/*/Enums/Permission.php` | `Unit/PermissionEnumTest.php` (7 tests), `Unit/PermissionArchitectureTest.php` (2 tests) | Covered |
| USR-F-015 | Static role-to-permission mapping via `RolePermissionMap::forRole()` | `App/Enums/RolePermissionMap.php` | `Unit/PermissionEnumTest.php` (7 tests), `Unit/PermissionArchitectureTest.php` (2 tests) | Covered |
| USR-F-016 | `HasPermissions` trait with `hasPermission()`, `hasAnyPermission()`, `allPermissions()` | `App/Concerns/HasPermissions.php` | `Unit/HasPermissionsTraitTest.php` (5 tests) | Covered |
| USR-F-017 | Centralized `Gate::before()` superadmin bypass | `App/Providers/AppServiceProvider.php` | `Policies/RoleBasedPolicyAccessTest.php` (68 tests) | Covered |
| USR-F-018 | Permissions shared via Inertia shared props | `App/Http/Middleware/HandleInertiaRequests.php` | `Policies/RoleBasedPolicyAccessTest.php` | Covered |
| USR-F-019 | `usePermissions()` Vue composable | `resources/js/composables/usePermissions.ts` | — | **Gap** |
| USR-F-020 | Permission-based sidebar navigation rendering | `resources/js/components/AppSidebar.vue` | — | **Gap** |

### User Management Gaps — Proposed Tests

**USR-F-010: Appearance Settings**
```
File: tests/Feature/Settings/AppearanceTest.php
- it renders the appearance settings page
- it stores appearance preference in cookie
- it persists preference across requests
```

**USR-F-011: Stripe Customer Management**
```
File: tests/Feature/Shop/StripeCustomerTest.php
- it creates a Stripe customer on first checkout
- it reuses existing Stripe customer on subsequent checkouts
```

---

## 17. Gap Summary

| Req ID | Domain | Gap Description | Priority | Proposed Test File |
|--------|--------|-----------------|----------|--------------------|
| TKT-F-003 | Ticketing | Ticket group CRUD | Medium | `Ticketing/TicketGroupTest.php` |
| ~~SHP-F-004~~ | ~~Shop~~ | ~~On-site payment flow~~ | — | ~~Covered~~ |
| SHP-F-005 | Shop | PaymentProviderManager | Medium | `Shop/PaymentProviderManagerTest.php` |
| SHP-F-013 | Shop | CartItemAdded event | Low | `Shop/CartItemEventTest.php` |
| PRG-F-004 | Program | Time slot approaching notification | Medium | `Programs/ProgramTimeSlotNotificationTest.php` |
| NWS-F-006 | News | NewsArticleRead analytics event | Low | `News/NewsArticleReadTest.php` |
| NTF-F-003 | Notification | Push subscription CRUD | Medium | `Notification/PushSubscriptionTest.php` |
| USR-F-010 | User | Appearance settings | Low | `Settings/AppearanceTest.php` |
| USR-F-011 | User | Stripe customer management | Low | `Shop/StripeCustomerTest.php` |
| USR-F-019 | User | `usePermissions()` composable (frontend) | Low | `composables/usePermissions.test.ts` |
| USR-F-020 | User | Permission-based sidebar rendering (frontend) | Low | `components/AppSidebar.test.ts` |

**Total: 10 gaps out of 128 requirements (92.2% coverage by requirement count)**

*Note: TKT-F-009 deprecated and replaced by TKT-F-013..016 (4 new requirements, all now covered).*

### Priority Order for Implementation

1. **SHP-F-005** — PaymentProviderManager (architecture)
2. **PRG-F-004** — Time slot notifications (user-facing)
3. **NTF-F-003** — Push subscriptions (user-facing)
4. **TKT-F-003** — Ticket groups (feature completeness)
5. **NWS-F-006** — Article read analytics (analytics)
6. **SHP-F-013** — CartItemAdded event (event architecture)
7. **USR-F-010** — Appearance settings (UI preference)
8. **USR-F-011** — Stripe customer (payment infrastructure)
9. **USR-F-019** — usePermissions composable (frontend unit test)
10. **USR-F-020** — Permission-based sidebar rendering (frontend component test)

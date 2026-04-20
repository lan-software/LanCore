# Software Test Description (STD)

**Document Identifier:** LanCore-STD-001
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

This Software Test Description (STD) describes the test cases and test procedures for the **LanCore** CSCI.

### 1.2 System Overview

Tests are implemented using Pest PHP v4 for backend tests and organized under `tests/Feature/` and `tests/Unit/`. Each test file uses Pest's `it()` / `test()` syntax with `expect()` assertions.

### 1.3 Document Overview

This document describes the test preparations, individual test cases, expected results, and evaluation criteria for LanCore qualification testing.

---

## 2. Referenced Documents

- [STP](STP.md) — Software Test Plan
- [STR](STR.md) — Software Test Report
- [SRS](SRS.md) — Software Requirements Specification

---

## 3. Test Preparations

### 3.1 Hardware Preparation

No special hardware preparation required. Tests run on standard development machines or CI runners with Docker support.

### 3.2 Software Preparation

1. Clone repository and install dependencies:
   ```bash
   composer install
   npm install
   ```

2. Configure test environment:
   - Copy `.env.example` to `.env.testing`
   - Set `DB_CONNECTION=sqlite` and `DB_DATABASE=:memory:`

3. Start Sail environment:
   ```bash
   vendor/bin/sail up -d
   ```

#### 3.2.1 CI Environment Preparation

In GitHub Actions CI, the following steps replace the local Sail environment:

1. Setup PHP 8.5 via `shivammathur/setup-php@v2` with Xdebug coverage
2. Setup Node.js 22 via `actions/setup-node@v4`
3. Install dependencies:
   ```bash
   composer install --no-interaction --prefer-dist --optimize-autoloader
   npm ci
   ```
4. Configure environment:
   ```bash
   cp .env.example .env
   php artisan key:generate
   touch database/database.sqlite
   php artisan migrate --force
   ```
5. Build frontend assets:
   ```bash
   npm run build
   ```
6. Environment overrides applied by `phpunit.xml`:
   - `DB_DATABASE=testing` (SQLite in-memory)
   - `CACHE_STORE=array`
   - `SESSION_DRIVER=array`

For Playwright E2E tests, a Laravel development server is started on port 8000 with `SESSION_DRIVER=file` since E2E tests run outside the PHPUnit process.

### 3.3 Test Database

Tests use the `RefreshDatabase` trait which:
1. Runs all migrations before the first test
2. Wraps each test in a database transaction
3. Rolls back the transaction after each test

This ensures complete isolation between test cases.

---

## 4. Test Descriptions

### 4.1 Authentication Tests

**File:** `tests/Feature/Auth/`

#### 4.1.1 Registration

| Test | Preconditions | Input | Expected Result |
|------|--------------|-------|-----------------|
| registration screen can be rendered | None | GET /register | 200 OK |
| new users can register | No existing user | POST /register {name, email, password, password_confirmation} | 302 redirect, user created, authenticated |
| registration fails with invalid email | None | POST /register {invalid email} | 422 validation error |
| registration fails with short password | None | POST /register {password < 8 chars} | 422 validation error |

#### 4.1.2 Login

| Test | Preconditions | Input | Expected Result |
|------|--------------|-------|-----------------|
| login screen can be rendered | None | GET /login | 200 OK |
| users can authenticate | User exists | POST /login {email, password} | 302 redirect, authenticated |
| users cannot authenticate with invalid password | User exists | POST /login {wrong password} | 422 error |

#### 4.1.3 Password Reset

| Test | Preconditions | Input | Expected Result |
|------|--------------|-------|-----------------|
| reset password link screen can be rendered | None | GET /forgot-password | 200 OK |
| reset password link can be requested | User exists | POST /forgot-password {email} | 302, reset email sent |
| password can be reset with valid token | Reset token exists | POST /reset-password {token, email, password} | 302, password changed |

#### 4.1.4 Email Verification

| Test | Preconditions | Input | Expected Result |
|------|--------------|-------|-----------------|
| email verification screen can be rendered | Unverified user logged in | GET /verify-email | 200 OK |
| email can be verified | Valid verification URL | GET /verify-email/{id}/{hash} | 302, email_verified_at set |

#### 4.1.5 Two-Factor Authentication

| Test | Preconditions | Input | Expected Result |
|------|--------------|-------|-----------------|
| 2FA can be enabled | Authenticated user | POST /user/two-factor-authentication | 200, secret generated |
| 2FA challenge screen rendered | 2FA enabled, session requires confirmation | GET /two-factor-challenge | 200 OK |
| 2FA can be confirmed with valid code | 2FA enabled | POST /two-factor-challenge {code} | 302, authenticated |
| 2FA recovery codes work | 2FA enabled | POST /two-factor-challenge {recovery_code} | 302, authenticated |

#### 4.1.6 Password Confirmation

| Test | Preconditions | Input | Expected Result |
|------|--------------|-------|-----------------|
| confirm password screen can be rendered | Authenticated | GET /confirm-password | 200 OK |
| password can be confirmed | Authenticated | POST /confirm-password {password} | 302 redirect |
| incorrect password is rejected | Authenticated | POST /confirm-password {wrong} | 422 error |

### 4.2 Settings Tests

**File:** `tests/Feature/Settings/`

#### 4.2.1 Profile

| Test | Preconditions | Input | Expected Result |
|------|--------------|-------|-----------------|
| profile page is displayed | Authenticated | GET /settings/profile | 200 OK |
| profile information can be updated | Authenticated | PATCH /settings/profile {name, email, phone, street, city, zip_code, country} | 302, user updated |
| email verification reset on email change | Authenticated, verified | PATCH /settings/profile {new email} | email_verified_at = null |
| redirects to profile when incomplete | Authenticated, no address fields | POST /cart/items | 302 to /settings/profile, session error "profile" |

#### 4.2.2 Security

| Test | Preconditions | Input | Expected Result |
|------|--------------|-------|-----------------|
| password can be updated | Authenticated | PUT /settings/security/password {current, new, confirm} | 302, password changed |
| correct current password required | Authenticated | PUT /settings/security/password {wrong current} | 422 error |

#### 4.2.3 Notifications

| Test | Preconditions | Input | Expected Result |
|------|--------------|-------|-----------------|
| notification preferences page displayed | Authenticated | GET /settings/notifications | 200 OK |
| preferences can be updated | Authenticated | PATCH /settings/notifications {channels} | 302, preferences saved |

#### 4.2.4 Ticket Discovery

| Test | Preconditions | Input | Expected Result |
|------|--------------|-------|-----------------|
| ticket discovery settings page displayed | Authenticated | GET /settings/ticket-discovery | 200 OK |
| discovery settings can be updated | Authenticated | PATCH /settings/ticket-discovery {settings} | 302, settings saved |

#### 4.2.5 Sidebar Favorites

| Test | Preconditions | Input | Expected Result |
|------|--------------|-------|-----------------|
| favorites can be added | Authenticated | POST /settings/sidebar-favorites {item} | 200, favorite saved |
| favorites can be removed | Authenticated, has favorites | DELETE /settings/sidebar-favorites/{id} | 200, favorite removed |

### 4.3 Integration Tests

**File:** `tests/Feature/Integration/`

#### 4.3.1 Integration App Management

| Test | Preconditions | Input | Expected Result |
|------|--------------|-------|-----------------|
| index page lists apps | Admin | GET /integrations | 200, apps listed |
| app can be created | Admin | POST /integrations {name, description, callback_url} | 302, app created |
| app can be updated | Admin, app exists | PUT /integrations/{id} {name} | 302, app updated |
| app can be deleted | Admin, app exists | DELETE /integrations/{id} | 302, app deleted |
| non-admin cannot manage apps | Regular user | GET /integrations | 403 forbidden |

#### 4.3.2 Integration Tokens

| Test | Preconditions | Input | Expected Result |
|------|--------------|-------|-----------------|
| token can be created | Admin, app exists | POST /integrations/{id}/tokens {name} | 302, plain token returned |
| token can be rotated | Token exists | POST /integrations/{id}/tokens/{tid}/rotate | 302, new token |
| token can be revoked | Token exists | DELETE /integrations/{id}/tokens/{tid} | 302, token deleted |

#### 4.3.3 SSO Flow

| Test | Preconditions | Input | Expected Result |
|------|--------------|-------|-----------------|
| SSO authorization redirects to callback | Authenticated, app exists | GET /integrations/{id}/sso/authorize | 302 to callback with code |
| SSO code can be exchanged | Valid code | POST /api/integration/sso/exchange {code} | 200, user data |
| expired code rejected | Expired code | POST /api/integration/sso/exchange {code} | 401 or 422 |

#### 4.3.4 API Authentication

| Test | Preconditions | Input | Expected Result |
|------|--------------|-------|-----------------|
| valid token authenticates | Token exists | GET /api/integration/user (Bearer token) | 200, user data |
| invalid token rejected | None | GET /api/integration/user (bad token) | 401 |
| expired token rejected | Expired token | GET /api/integration/user (expired) | 401 |

#### 4.3.5 Webhook Subscriptions

| Test | Preconditions | Input | Expected Result |
|------|--------------|-------|-----------------|
| webhook events synced on app create | Admin | POST /integrations {webhook_events} | Events stored |
| webhook events updated | App exists | PUT /integrations/{id} {webhook_events} | Events updated |
| webhook secret managed | Admin | POST/PUT /integrations {webhook_secret} | Secret stored |

### 4.4 Notification Tests

**File:** `tests/Feature/Notifications/`

| Test | Preconditions | Input | Expected Result |
|------|--------------|-------|-----------------|
| notification preferences created on registration | None | Register new user | Default preferences created |
| mail notification sent when enabled | User with mail enabled | Publish news article | Mail queued |
| mail notification not sent when disabled | User with mail disabled | Publish news article | No mail queued |
| push subscription stored | Authenticated | POST push subscription | Subscription saved |
| push prompt dismissed and session flag stored | Authenticated | POST /push-subscriptions/dismiss | 200, `dismissed: true`, session flag set |
| pushPromptDismissed shared as false by default | Authenticated, no session flag | GET /dashboard | Inertia prop `pushPromptDismissed` = false |
| pushPromptDismissed shared as true when session flag set | Authenticated, session flag set | GET /dashboard | Inertia prop `pushPromptDismissed` = true |
| dismiss requires authentication | Unauthenticated | POST /push-subscriptions/dismiss | 401 Unauthorized |
| program subscription toggled | Authenticated, program exists | POST program subscription | Subscription created/removed |

### 4.4.1 Achievement Notification Tests

**File:** `tests/Feature/Achievements/AchievementGrantingTest.php`

| Test | Preconditions | Input | Expected Result |
|------|--------------|-------|-----------------|
| sends a notification with achievement details | User, active achievement | GrantAchievement action | Notification data contains achievement_id, name, description, color, icon |
| falls back to description when notification_text is null | User, achievement with null notification_text | GrantAchievement action | Notification description equals achievement description |

### 4.5 Ticketing Tests

**File:** `tests/Feature/Ticketing/`

| Test | Preconditions | Input | Expected Result |
|------|--------------|-------|-----------------|
| ticket type audit trail recorded | Admin, ticket type exists | Update ticket type | Audit record created |
| voucher can be created | Admin | POST voucher {code, type, value} | 302, voucher created |
| voucher can be updated | Admin, voucher exists | PUT voucher {value} | 302, voucher updated |

#### 4.5.1 Group Ticket Tests

**File:** `tests/Feature/Ticketing/GroupTicketTest.php`

| Test | Preconditions | Input | Expected Result |
|------|--------------|-------|-----------------|
| creates group ticket type | Admin | POST ticket-type {max_users_per_ticket: 4, check_in_mode: individual} | Ticket type created with group fields |
| defaults max_users_per_ticket to 1 | Admin | POST ticket-type {} | max_users_per_ticket = 1 |
| rejects max_users_per_ticket < 1 | Admin | POST ticket-type {max_users_per_ticket: 0} | Validation error |
| assigns multiple users to group ticket | Owner, group ticket | POST ticket/{id}/users {user_email} | User added to pivot |
| rejects exceeding max_users_per_ticket | Owner, group ticket at capacity | POST ticket/{id}/users | Validation error |
| removes assigned user | Owner, group ticket with users | DELETE ticket/{id}/users/{user} | User removed from pivot |
| calculates group ticket seat consumption | Event with seat_capacity | Purchase group ticket (2 seats × 4 users) | 8 seats consumed |
| individual check-in for one user | Admin, group ticket (individual mode) | POST ticket/{id}/check-in {user_id} | User's checked_in_at set |
| ticket checked in when all users done | Admin, all users checked in | POST ticket/{id}/check-in {last_user_id} | Ticket status = CheckedIn |
| group check-in marks all users | Admin, group ticket (group mode) | POST ticket/{id}/check-in | All users' checked_in_at set, ticket status = CheckedIn |
| owner can add users | Owner | POST ticket/{id}/users | 302, user added |
| non-owner denied adding users | Non-owner, non-manager | POST ticket/{id}/users | 403 |

### 4.6 Shop and Payment Tests

#### 4.6.1 On-Site Payment Flow

**File:** `tests/Feature/Shop/OnSitePaymentTest.php`

| Test | Preconditions | Input | Expected Result |
|------|--------------|-------|-----------------|
| creates on-site order that stays pending | User, event, ticket type | Order::factory()->pending()->onSite() | Order status = Pending, payment_method = on_site, 0 tickets |
| shows pending message on checkout success | User, pending on-site order | GET /cart/checkout/{order}/success | 200, Inertia 'shop/CheckoutSuccess', status = pending |
| admin confirms payment on pending on-site order | Admin, pending on-site order with metadata + order lines | PATCH /orders/{order}/confirm-payment | 302, order Completed, tickets created |
| prevents non-admin from confirming payment | Regular user, pending on-site order | PATCH /orders/{order}/confirm-payment | 403 Forbidden |
| prevents confirming on non-on-site orders | Admin, pending Stripe order | PATCH /orders/{order}/confirm-payment | 302, session error |
| prevents confirming already completed orders | Admin, completed on-site order | PATCH /orders/{order}/confirm-payment | 302, session error |

#### 4.6.2 User Order Views

**File:** `tests/Feature/Shop/UserOrderControllerTest.php`

| Test | Preconditions | Input | Expected Result |
|------|--------------|-------|-----------------|
| shows the user their own orders | User with 3 orders, other user has 2 | GET /my-orders | 200, Inertia 'my-orders/Index', 3 orders |
| does not show other users orders | User with 0 orders, other user has 2 | GET /my-orders | 200, 0 orders |
| shows order detail for own order | User owns order | GET /my-orders/{order} | 200, Inertia 'my-orders/Show', correct order |
| denies viewing another users order | User does not own order | GET /my-orders/{order} | 403 Forbidden |
| requires authentication | Unauthenticated | GET /my-orders | 302 redirect to login |

#### 4.6.3 Stripe Webhook Processing

**File:** `tests/Feature/Shop/StripeWebhookTest.php`

| Test | Preconditions | Input | Expected Result |
|------|--------------|-------|-----------------|
| fulfills pending order on checkout.session.completed | Order pending, ticket type exists, order has metadata and order lines | `WebhookReceived` with `checkout.session.completed` payload containing `order_id` | Order status = Completed, provider fields populated, ticket(s) created |
| does not duplicate tickets for completed orders | Order already Completed | `WebhookReceived` with `checkout.session.completed` payload (duplicate) | Order status unchanged, no new tickets created |
| ignores webhooks without order_id | None | `checkout.session.completed` payload with empty metadata | No order modified |
| ignores non-checkout webhook types | Order pending | `customer.subscription.created` event | Order status unchanged (Pending) |

#### 4.6.2 Stripe Checkout Flow

**File:** `tests/Feature/Shop/StripeCheckoutTest.php`

| Test | Preconditions | Input | Expected Result |
|------|--------------|-------|-----------------|
| creates order and redirects to Stripe | User authenticated, cart populated with ticket type | POST `/cart/checkout` `{payment_method: stripe}` | Order created (status = Pending), redirect to Stripe, cart cleared |
| fulfills order on success URL return | Order pending, mock provider returns success | GET `/cart/checkout/{order}/success?session_id=...` | Order status = Completed, provider fields populated, ticket(s) created |
| does not fulfill on unpaid session | Order pending, mock provider returns failure | GET `/cart/checkout/{order}/success?session_id=...` | Order status remains Pending, no tickets created |
| marks order as failed on cancel | Order pending | GET `/cart/checkout/{order}/cancel` | Order status = Failed, redirect to `/cart` |

### 4.7 Unit Tests

**File:** `tests/Unit/`

| Test | Input | Expected Result |
|------|-------|-----------------|
| ticket validation ID generates unique string | None | Non-empty unique string |
| ticket validation ID format is valid | None | Matches expected format |
| user discoverability returns correct value | User with settings | Boolean based on settings |

### 4.8 Venue Tests

**File:** `tests/Feature/Venues/`

| Test | Preconditions | Input | Expected Result |
|------|--------------|-------|-----------------|
| allows admins to store a new venue | Admin | POST /venues {name, address} | 302, venue created |
| allows admins to update a venue | Admin, venue exists | PATCH /venues/{id} | 302, venue updated |
| allows admins to delete a venue | Admin, venue exists | DELETE /venues/{id} | 302, venue deleted |
| stores venue images when creating | Admin | POST /venues with images | Images stored in S3 |
| deletes removed images from storage | Admin, venue with images | PATCH /venues/{id} (remove image) | Image deleted from S3 |
| forbids users from creating venues | Regular user | POST /venues | 403 Forbidden |
| returns paginated venues for admins | Admin | GET /venues | 200, paginated list |
| filters venues by search term | Admin | GET /venues?search=arena | Filtered results |

### 4.9 Game Tests

**File:** `tests/Feature/Games/`

| Test | Preconditions | Input | Expected Result |
|------|--------------|-------|-----------------|
| allows admins to store a new game | Admin | POST /games {name, slug} | 302, game created |
| allows admins to update a game | Admin, game exists | PATCH /games/{id} | 302, game updated |
| allows admins to delete a game | Admin, game exists | DELETE /games/{id} | 302, game deleted, modes cascaded |
| allows admins to store a new game mode | Admin, game exists | POST /games/{id}/modes | 302, mode created |
| allows admins to update a game mode | Admin, mode exists | PATCH /games/{id}/modes/{modeId} | 302, mode updated |
| allows admins to delete a game mode | Admin, mode exists | DELETE /games/{id}/modes/{modeId} | 302, mode deleted |
| validates slug uniqueness | Admin | POST /games {duplicate slug} | 422 validation error |
| forbids users from creating games | Regular user | POST /games | 403 Forbidden |

### 4.10 Sponsor Tests

**File:** `tests/Feature/Sponsors/`

| Test | Preconditions | Input | Expected Result |
|------|--------------|-------|-----------------|
| allows admins to store a new sponsor | Admin | POST /sponsors {name} | 302, sponsor created |
| allows admins to store a sponsor with events | Admin | POST /sponsors {name, event_ids} | Sponsor with event associations |
| allows admins to update a sponsor | Admin, sponsor exists | PATCH /sponsors/{id} | 302, sponsor updated |
| allows admins to delete a sponsor | Admin, sponsor exists | DELETE /sponsors/{id} | 302, sponsor deleted |
| allows sponsor managers to edit their own | SponsorManager, assigned | GET /sponsors/{id} | 200, edit page |
| forbids sponsor managers from editing unassigned | SponsorManager, not assigned | GET /sponsors/{id} | 403 Forbidden |
| allows admins to store a sponsor level | Admin | POST /sponsor-levels | 302, level created |
| auto-increments sort order on store | Admin | POST /sponsor-levels | sort_order auto-incremented |
| allows admins to update a sponsor level | Admin, level exists | PATCH /sponsor-levels/{id} | 302, level updated |
| allows admins to delete a sponsor level | Admin, level exists | DELETE /sponsor-levels/{id} | 302, level deleted |

### 4.11 Seating Tests

**File:** `tests/Feature/Seating/`

| Test | Preconditions | Input | Expected Result |
|------|--------------|-------|-----------------|
| allows admins to store a new seat plan | Admin, event exists | POST /seat-plans {name, event_id, data} | 302, seat plan created |
| validates data is valid JSON | Admin | POST /seat-plans {invalid data} | 422 validation error |
| allows admins to update a seat plan | Admin, seat plan exists | PATCH /seat-plans/{id} | 302, updated |
| allows admins to delete a seat plan | Admin, seat plan exists | DELETE /seat-plans/{id} | 302, deleted |
| stores blocks with seats and labels as JSON | — | Factory create | JSONB data stored correctly |
| belongs to an event | — | SeatPlan model | Relationship correct |
| cascades deletion when event is deleted | Event with seat plans | DELETE event | Seat plans cascade deleted |
| allows searching seat plans by name | Admin | GET /seat-plans?search=Main | Filtered results |

### 4.12 Achievement CRUD Tests

**File:** `tests/Feature/Achievements/AchievementCrudTest.php`

| Test | Preconditions | Input | Expected Result |
|------|--------------|-------|-----------------|
| allows admins to view achievements index | Admin | GET /achievements-admin | 200, list displayed |
| prevents regular users from viewing | Regular user | GET /achievements-admin | 403 Forbidden |
| allows admins to store a new achievement | Admin | POST /achievements-admin {name, description} | 302, achievement created |
| validates required fields when storing | Admin | POST /achievements-admin {} | 422 validation error |
| allows admins to update an achievement | Admin, achievement exists | PATCH /achievements-admin/{id} | 302, updated |
| allows admins to delete an achievement | Admin, achievement exists | DELETE /achievements-admin/{id} | 302, deleted |
| validates color format | Admin | POST /achievements-admin {color: invalid} | 422 validation error |

### 4.13 Permission System Tests

#### 4.13.1 Permission Enum Unit Tests

**File:** `tests/Unit/PermissionEnumTest.php`

| Test | Input | Expected Result |
|------|-------|-----------------|
| superadmin gets all permissions | `RolePermissionMap::forRole(Superadmin)` | Equals `RolePermissionMap::all()` |
| admin excludes SyncUserRoles and DeleteUsers | `RolePermissionMap::forRole(Admin)` | Does not contain `SyncUserRoles`, `DeleteUsers` |
| admin gets all other permissions | `RolePermissionMap::forRole(Admin)` | Contains all 21 admin-level permissions |
| moderator gets content moderation only | `RolePermissionMap::forRole(Moderator)` | Exactly `[ModerateNewsComments, ManageAnnouncements]` |
| sponsor manager gets assigned sponsors only | `RolePermissionMap::forRole(SponsorManager)` | Exactly `[ManageAssignedSponsors]` |
| regular user gets no permissions | `RolePermissionMap::forRole(User)` | Empty array |
| every RoleName case is covered | All `RoleName::cases()` | Each returns a valid array |

#### 4.13.2 HasPermissions Trait Unit Tests

**File:** `tests/Unit/HasPermissionsTraitTest.php`

| Test | Preconditions | Expected Result |
|------|--------------|-----------------|
| returns true for granted permission | Admin role | `hasPermission(ManageUsers)` is true |
| returns false for non-granted permission | User role | `hasPermission(ManageUsers)` is false |
| hasAnyPermission checks correctly | Moderator role | True for `(ManageUsers, ModerateNewsComments)`, false for `(ManageUsers, ManageVenues)` |
| collects permissions from multiple roles | Moderator + SponsorManager | `allPermissions()` contains 3 permissions |
| deduplicates overlapping role permissions | Admin + Moderator | No duplicate values in `allPermissions()` |

#### 4.13.3 Role-Based Policy Access Tests

**File:** `tests/Feature/Policies/RoleBasedPolicyAccessTest.php`

| Test | Role | Routes | Expected Result |
|------|------|--------|-----------------|
| superadmin accesses all admin routes | Superadmin | 16 admin index routes | 200 OK (16 datasets) |
| admin accesses all admin routes | Admin | 16 admin index routes | 200 OK (16 datasets) |
| moderator accesses content routes | Moderator | `/announcements-admin`, `/news-admin/comments` | 200 OK (2 datasets) |
| moderator blocked from non-content routes | Moderator | `/achievements-admin`, `/events`, `/venues`, etc. | 403 Forbidden (10 datasets) |
| regular user blocked from all admin routes | User | 16 admin index routes | 403 Forbidden (16 datasets) |
| sponsor manager views sponsors list | SponsorManager | `/sponsors` | 200 OK |
| sponsor manager blocked from other routes | SponsorManager | `/achievements-admin`, `/events`, `/venues`, etc. | 403 Forbidden (7 datasets) |

#### 4.13.4 Permission Architecture Tests

**File:** `tests/Unit/PermissionArchitectureTest.php`

| Test | Assertion |
|------|-----------|
| every permission enum case is registered | All cases from `app/Domain/*/Enums/Permission.php`, `app/Enums/Permission.php`, and `app/Enums/AuditPermission.php` appear in `RolePermissionMap::all()` |
| no duplicate permission values | No two enum cases across all permission enums share the same string value |

### 4.14 Architecture Tests

**File:** `tests/Architecture/`

| Test | Assertion |
|------|-----------|
| Domain structure | Domain modules follow expected directory structure |
| No direct DB:: usage | Models use Eloquent, not DB facade |
| Controller returns | Controllers return Inertia or redirect responses |

### 4.16 Competition Tests

**Files:** `tests/Feature/Competition/LeaveTeamTest.php`, `tests/Unit/Domain/Competition/LeaveTeamActionTest.php`

#### 4.16.1 Feature Tests — LeaveTeamTest

| Test | Preconditions | Input | Expected Result |
|------|--------------|-------|-----------------|
| non-captain member leaves (happy path) | Authenticated member, captain exists | DELETE /teams/{team}/leave | 302 to my-competitions.show, flash success = "You have left the team.", team NOT deleted |
| captain leaves with members remaining | Captain, at least one other member | DELETE /teams/{team}/leave | Captaincy rotated to oldest-joined active member, 302 to my-competitions.show, team NOT deleted |
| last member leaves (team deleted) | Sole remaining member | DELETE /teams/{team}/leave | Team record deleted, 302 to my-competitions.show, flash success = "You left the team. As the last member, the team has been disbanded." |
| non-member receives 403 | Authenticated user, not on team | DELETE /teams/{team}/leave | 403 Forbidden |
| captain destroys team | Captain | DELETE /teams/{team} | 302 redirect, flash "Team deleted.", team record deleted |
| non-captain cannot destroy team | Non-captain member | DELETE /teams/{team} | 403 Forbidden |

#### 4.16.2 Unit Tests — LeaveTeamActionTest

| Test | Input | Expected Result |
|------|-------|-----------------|
| returns false when members remain | Team with 2+ members | `LeaveTeam::execute()` returns `false`, team not deleted |
| returns true when last member leaves | Team with 1 member | `LeaveTeam::execute()` returns `true`, team deleted |
| rotates captain to oldest joined_at | Team with multiple members, explicit `joined_at` values on factory | Captaincy assigned to member with earliest `joined_at` |

### 4.17 My Pages Event Selector Tests

**Files:** `tests/Feature/EventContextTest.php`, `tests/Feature/MyPagesEventFilterTest.php`, `tests/Unit/Domain/Event/EventScopeForUserTest.php`

#### 4.17.1 Feature Tests — EventContextTest

| Test | Preconditions | Input | Expected Result |
|------|--------------|-------|-----------------|
| storeMy happy path (participant) | User has ticket in event | POST /my-event-context {event_id} | session('my_selected_event_id') set, 302 |
| storeMy rejects non-participant | User has no participation in event | POST /my-event-context {event_id} | 422 validation error |
| storeMy validation (missing event_id) | Authenticated | POST /my-event-context {} | 422 |
| destroyMy clears session key | Session has my_selected_event_id | DELETE /my-event-context | my_selected_event_id removed from session, 302 |
| stale session key auto-cleared | User had participation, now lost | GET /dashboard (or any Inertia page) | myEventContext prop is null, session key cleared |
| guest blocked on POST | Unauthenticated | POST /my-event-context | 302 to login |
| guest blocked on DELETE | Unauthenticated | DELETE /my-event-context | 302 to login |
| myEventContext.events shape and ordering | User participates in multiple events | GET /dashboard | events ordered by start_date descending |

#### 4.17.2 Feature Tests — MyPagesEventFilterTest

| Test | Preconditions | Input | Expected Result |
|------|--------------|-------|-----------------|
| UserCompetitionController index filtered by session event | User in competitions across 2 events | GET /my-competitions (session set) | Only competitions for selected event returned |
| UserCompetitionController index unfiltered without session | No session key | GET /my-competitions | All user competitions returned |
| UserTeamController index filtered | Teams across 2 events | GET /my-teams (session set) | Only teams in selected event's competitions returned |
| UserOrderController index filtered | Orders across 2 events | GET /my-orders (session set) | Only orders for selected event returned |
| TicketController index filtered | Tickets (owned/manager/assignee) across 2 events | GET /my-tickets (session set) | Only tickets for selected event returned |

#### 4.17.3 Unit Tests — EventScopeForUserTest

| Test | Input | Expected Result |
|------|-------|-----------------|
| ticket owner sees event | User owns a ticket in event | `Event::scopeForUser($user)` includes event |
| ticket manager sees event | User is manager on a ticket | `Event::scopeForUser($user)` includes event |
| ticket pivot user sees event | User assigned via ticket_user pivot | `Event::scopeForUser($user)` includes event |
| active team member sees event | User has active CompetitionTeamMember, competition has event_id | `Event::scopeForUser($user)` includes event |
| order with event_id grants access | User has order with event_id | `Event::scopeForUser($user)` includes event |
| left team member excluded | User has left_at set on CompetitionTeamMember | event not returned |
| competition without event_id excluded | competition.event_id IS NULL | event not returned |
| order without event_id excluded | order.event_id IS NULL | event not returned |
| no participation — nothing returned | User has no tickets, teams, or orders | empty result |

### 4.18 Organization Settings Tests

**File:** `tests/Feature/Settings/OrganizationSettingsTest.php`

| Test | Preconditions | Input | Expected Result |
|------|--------------|-------|-----------------|
| organization prop shape without logo | No logo configured | GET any Inertia page | `organization = { name, logoUrl: null }` |
| organization prop shape with logo | Logo uploaded to public disk | GET any Inertia page | `organization.logoUrl` is a non-null URL |
| prop is cached after first request | Cache empty | GET /dashboard | `Cache::has('inertia.organization')` is true after response |
| cache invalidated on update | Cache populated | PATCH /settings/organization {name} | `Cache::has('inertia.organization')` is false after response |
| cache invalidated on uploadLogo | Cache populated | POST /settings/organization/logo | Cache cleared |
| cache invalidated on removeLogo | Cache populated, logo exists | DELETE /settings/organization/logo | Cache cleared, logo file deleted from public disk |
| uploadLogo writes file to public role disk | Admin authenticated | POST /settings/organization/logo {logo file} | File exists on StorageRole::public() (disk configured by filesystems.public_disk) |
| non-admin blocked on update | Regular user | PATCH /settings/organization | 403 Forbidden |
| non-admin blocked on uploadLogo | Regular user | POST /settings/organization/logo | 403 Forbidden |
| non-admin blocked on removeLogo | Regular user | DELETE /settings/organization/logo | 403 Forbidden |

### 4.20 Signed Ticket Token Tests

**File:** `tests/Feature/Ticketing/TicketTokenTest.php` and `tests/Unit/Ticketing/TicketTokenServiceTest.php`

#### 4.20.1 Happy Path Tests

| Test | Preconditions | Input | Expected Result |
|------|--------------|-------|-----------------|
| valid token passes signature check | Issued LCT1 token, active key pair | Token string submitted to validate endpoint | `decision: "valid"`, ticket located by nonce hash |
| token embedded in QR after fulfillment | Order fulfilled | Order fulfillment triggers PDF dispatch | `GenerateTicketPdf` dispatched with `qrPayload` matching LCT1 format; `validation_nonce_hash` set on ticket |
| validate locates ticket by nonce hash only | Token present, ticket has nonce_hash | Validate called with valid token | Ticket found via HMAC lookup (not via validation_id) |

#### 4.20.2 Signature Tamper Tests

| Test | Preconditions | Input | Expected Result |
|------|--------------|-------|-----------------|
| tampered body returns invalid_signature | Valid token | Modify body segment, resubmit | `decision: "invalid_signature"` |
| tampered sig returns invalid_signature | Valid token | Replace sig with random base64url | `decision: "invalid_signature"` |
| wrong kid returns unknown_kid | Valid token | Replace kid with non-existent kid value | `decision: "unknown_kid"` |
| truncated token (missing segment) returns invalid | Any | Submit "LCT1.kid.body" (3 segments only) | `decision: "invalid"` |
| non-LCT1 prefix returns invalid | Any | Submit old-style UUID validation_id | `decision: "invalid"` |

#### 4.20.3 Expiry and Revocation Tests

| Test | Preconditions | Input | Expected Result |
|------|--------------|-------|-----------------|
| expired token returns expired | Token with exp in the past | Submit valid but expired token | `decision: "expired"` |
| cancelled ticket returns revoked | Ticket cancelled (nonce_hash cleared) | Submit token that was valid before cancellation | `decision: "revoked"` |
| token from before regeneration returns revoked | Token reissued (nonce rotated) | Submit old token after assignment change | `decision: "revoked"` (old nonce_hash no longer in DB) |
| unknown ticket ID in body returns revoked | Body references non-existent tid | Submit structurally valid token, tid does not exist | `decision: "revoked"` |

#### 4.20.4 Key Rotation Tests

| Test | Preconditions | Input | Expected Result |
|------|--------------|-------|-----------------|
| rotation generates new key pair | No keys present | Run `tickets:keys:rotate` | New .key file created, TicketKeyRing returns new kid as active |
| new tokens use new kid after rotation | Key rotated | Issue new token | Token kid matches new active kid |
| old tokens still validate after rotation | Token issued before rotation, old key retained | Submit pre-rotation token | `decision: "valid"` (retired key still in verify set) |
| JWKS includes both active and retired keys | One rotation performed | GET /api/entrance/signing-keys | Response contains 2 JWKS entries |
| JWKS requires authentication | Unauthenticated request | GET /api/entrance/signing-keys (no Bearer) | 401 Unauthorized |
| JWKS returns 200 with active key | Active key present | GET /api/entrance/signing-keys (valid Bearer) | 200, `keys` array has at least one entry with `kty: "OKP"`, `crv: "Ed25519"` |

#### 4.20.5 Admin Token Rotation and Cancel Tests

| Test | Preconditions | Input | Expected Result |
|------|--------------|-------|-----------------|
| admin rotateToken issues new LCT1 token | Admin, ticket with existing token | POST /admin/tickets/{id}/rotate-token | `validation_nonce_hash` changed, `validation_kid` updated, `GenerateTicketPdf` dispatched |
| admin cancel clears token fields | Admin, ticket with active token | Ticket cancellation action | `validation_nonce_hash` NULL, `validation_kid` NULL, `validation_issued_at` NULL, `validation_expires_at` NULL |

#### 4.20.6 Threat Model Simulation Tests

| Test | Threat Scenario | Input | Expected Result |
|------|----------------|-------|-----------------|
| DB dump cannot produce valid token | Attacker has full DB dump | Attempt to construct token using validation_nonce_hash only (no private key) | `decision: "invalid_signature"` — signature cannot be reconstructed without private key |
| QR photo leak cannot query DB record | Attacker has scanned QR string | Submit token but attempt to derive nonce_hash without pepper | Cannot derive matching nonce_hash — pepper not in token or DB |

### 4.15 CI Pipeline Verification

**Scope:** GitHub Actions workflows (`.github/workflows/`)

| Test | Assertion |
|------|-----------|
| Backend pipeline executes | `tests.yml` completes Pest test suite with coverage |
| Frontend pipeline executes | `frontend-tests.yml` completes Vitest and Playwright tests |
| Lint pipeline executes | `lint.yml` passes Pint, ESLint, and Prettier checks with zero errors |
| Docker pipeline executes | `docker-publish.yml` builds multi-platform image and pushes to GHCR |
| All pipelines gate merges | Pull requests cannot merge with failing required status checks |

### 4.21 Integration Client Library Tests

**Package:** `lan-software/lancore-client` (separate repository; tests executed in the package's own CI pipeline using Pest + `Http::fake()`)

These qualification tests trace to CAP-ICLIB-001..005 and ICLIB-F-001..006. They exercise the consumer-side contract that every Lan\* satellite relies on. They run in the package repository rather than in LanCore, but are enumerated here because the package is a system-level CSCI of the Lan Software ecosystem.

#### 4.21.1 LanCoreClient Transport Tests

| Test | Preconditions | Input | Expected Result |
|------|--------------|-------|-----------------|
| exchange code returns LanCoreUser DTO | `Http::fake` returns 200 with valid user payload | `$client->exchangeCode('abc')` | Returns `LanCoreUser` with populated id, username, email, roles, locale, avatar |
| exchange code throws InvalidLanCoreUserException on malformed payload | `Http::fake` returns 200 with missing `id` field | `$client->exchangeCode('abc')` | `InvalidLanCoreUserException` thrown |
| exchange code throws LanCoreRequestException on 4xx | `Http::fake` returns 400 | `$client->exchangeCode('bad')` | `LanCoreRequestException` with `statusCode === 400` |
| exchange code throws LanCoreUnavailableException on 5xx | `Http::fake` returns 503 | `$client->exchangeCode('abc')` | `LanCoreUnavailableException` thrown |
| exchange code throws LanCoreUnavailableException on connection failure | `Http::fake` throws ConnectionException | `$client->exchangeCode('abc')` | `LanCoreUnavailableException` thrown |
| client throws LanCoreDisabledException when disabled | `config('lancore.enabled') === false` | any client call | `LanCoreDisabledException` thrown before any HTTP attempt |
| client applies configured retries | `Http::fake` returns 500 twice then 200 | `$client->exchangeCode('abc')` | Succeeds on third attempt; exactly three outbound requests recorded |
| client sends Bearer token from config | `config('lancore.token') === 'xyz'` | any client call | Outbound request has `Authorization: Bearer xyz` |
| resolveUser by id returns DTO | `Http::fake` returns 200 | `$client->resolveUser(id: 42)` | Returns `LanCoreUser` with `id === 42` |
| resolveUser by email returns DTO | `Http::fake` returns 200 | `$client->resolveUser(email: 'a@b')` | Returns `LanCoreUser` with `email === 'a@b'` |

#### 4.21.2 Webhook Verification Tests

| Test | Preconditions | Input | Expected Result |
|------|--------------|-------|-----------------|
| middleware accepts valid HMAC signature | `LANCORE_WEBHOOK_SECRET` set; request body HMAC-SHA256 matches `X-Webhook-Signature` header | POST with correct signature | Middleware passes request through |
| middleware rejects invalid HMAC signature | Valid secret; tampered body | POST with stale signature | 401 response; request does not reach controller |
| middleware rejects missing signature header | Valid secret | POST with no signature | 401 response |
| middleware rejects disallowed event header | Signature valid; `X-Webhook-Event` not in controller's allowlist | POST | 400 response |
| middleware accepts signature-less requests when secret empty | `LANCORE_WEBHOOK_SECRET` is empty string | POST with no signature | Middleware passes request through (local dev bypass) |

#### 4.21.3 Abstract Webhook Controller Tests

Parameterised across all eight webhook event types (`user.registered`, `user.roles_updated`, `user.profile_updated`, `announcement.published`, `news_article.published`, `event.published`, `ticket.purchased`, `integration.accessed`):

| Test | Preconditions | Input | Expected Result |
|------|--------------|-------|-----------------|
| abstract controller dispatches typed payload DTO | Subclass implements `handle()` hook | POST with valid signed payload for event X | Subclass receives typed payload DTO matching event X's schema |
| abstract controller returns 202 when resolveUser returns null | Subclass's `resolveUser` returns `null` (UserRolesUpdated only) | POST with valid payload | 202 `{status: user_not_found}` |
| abstract controller returns 200 on successful handle | Subclass's `handle` completes without exception | POST with valid payload | 200 `{status: ok}` |
| abstract controller propagates handler exceptions | Subclass's `handle` throws | POST with valid payload | Exception propagates to Laravel exception handler |

#### 4.21.4 Entrance Sub-Client Tests

| Test | Preconditions | Input | Expected Result |
|------|--------------|-------|-----------------|
| validate ticket returns decision DTO | `Http::fake` returns 200 valid decision | `$client->entrance()->validate($token)` | Returns `CheckinResult` with `decision === 'valid'` |
| validate ticket propagates LanCoreUnavailableException on 5xx | `Http::fake` returns 503 | `$client->entrance()->validate($token)` | `LanCoreUnavailableException` thrown |
| JWKS is cached within TTL | First call fetches and caches; second call within TTL | Two consecutive `fetchSigningKeys()` | Exactly one outbound HTTP request recorded |
| JWKS cache miss after TTL expiry | First call caches; TTL elapses; second call | Two consecutive `fetchSigningKeys()` spanning TTL | Two outbound HTTP requests recorded |
| entrance sub-client unavailable when not enabled | `config('lancore.entrance.enabled') === false` | `$client->entrance()` | Throws or returns guard object that rejects all calls |

### 4.22 Internationalization (i18n) Tests

**Files:** `tests/Feature/I18n/LocaleSettingsTest.php`, `tests/Feature/I18n/SetLocaleMiddlewareTest.php`, `tests/Feature/Integration/ResolveIntegrationUserLocaleTest.php`, `tests/Unit/I18n/LocaleResolutionTest.php`

#### 4.22.1 Locale Storage and Profile Update Tests

| Test | Preconditions | Input | Expected Result |
|------|--------------|-------|-----------------|
| profile page exposes locale selector | Authenticated user | GET /settings/profile | Response contains `availableLocales` Inertia prop with `['en', 'de', 'fr', 'es']` |
| locale can be updated via profile settings | Authenticated user, current locale = `en` | PATCH /settings/profile `{locale: 'de'}` | 302, `users.locale` = `de` |
| locale update rejects unsupported locale | Authenticated user | PATCH /settings/profile `{locale: 'zh'}` | 422 validation error |
| locale defaults to null on registration | New user registration | POST /register | `users.locale` IS NULL |

#### 4.22.2 SetLocale Middleware Tests

| Test | Preconditions | Input | Expected Result |
|------|--------------|-------|-----------------|
| authenticated user with stored locale gets correct app locale | User with `locale = 'de'` | GET /dashboard | `app()->getLocale()` = `de` during request handling |
| authenticated user with null locale falls back to app default | User with `locale = null` | GET /dashboard | `app()->getLocale()` = `en` (config default) |
| unauthenticated request uses Accept-Language header | No session, `Accept-Language: fr` | GET / | `app()->getLocale()` = `fr` |
| unauthenticated request with unsupported Accept-Language falls back | No session, `Accept-Language: zh-CN` | GET / | `app()->getLocale()` = `en` |
| unauthenticated request with no Accept-Language falls back to default | No session, no header | GET / | `app()->getLocale()` = `en` |

#### 4.22.3 Inertia Shared Props Tests

| Test | Preconditions | Input | Expected Result |
|------|--------------|-------|-----------------|
| locale prop shared on every Inertia response | Authenticated user, `locale = 'fr'` | GET /dashboard | Inertia shared prop `locale` = `fr` |
| availableLocales prop shared on every Inertia response | Any authenticated request | GET /dashboard | Inertia shared prop `availableLocales` = `['en', 'de', 'fr', 'es']` |
| locale prop reflects null-locale fallback | User with `locale = null` | GET /dashboard | Inertia shared prop `locale` = `en` |

#### 4.22.4 ResolveIntegrationUser Locale Bug-Fix Tests

| Test | Preconditions | Input | Expected Result |
|------|--------------|-------|-----------------|
| SSO exchange returns user-stored locale, not request locale | User with `locale = 'de'`, request locale = `en` | POST /api/integration/sso/exchange `{code}` | Returned user payload contains `locale = 'de'` |
| SSO exchange returns null when user has no locale preference | User with `locale = null` | POST /api/integration/sso/exchange `{code}` | Returned user payload contains `locale = null` |
| resolveUser endpoint returns user-stored locale | User with `locale = 'fr'`, request in `en` context | GET /api/integration/user (Bearer token) | Response `locale` field = `fr`, not `en` |

### 4.19 Demo Mode Tests

**File:** `tests/Feature/Demo/`

| Test | Preconditions | Input | Expected Result |
|------|--------------|-------|-----------------|
| demo seed produces expected event | Demo mode active | Run `artisan db:seed --class=DemoSeeder` | At least one published event with tickets and users exists in the database |
| payment completes via Stripe test mode | Demo mode active (`APP_DEMO=true`, `STRIPE_SECRET=sk_test_...`), demo event with ticket | POST `/cart/checkout` with Stripe test card `4242 4242 4242 4242` | Order created with status Completed, ticket issued, Stripe sandbox API called, no real charge created |
| Stripe test-mode keys prevent real charges | Demo mode active, live Stripe card used | POST `/cart/checkout` with a real card number | Stripe sandbox rejects or ignores charge; no financial transaction occurs |
| admin can exit demo mode | Superadmin, demo mode active | Artisan `app:demo --disable` or equivalent env toggle | Application returns to Normal mode, demo data remains until manually purged |

---

## 5. Requirements Traceability

| SRS Requirement | Test Cases |
|----------------|-----------|
| USR-F-001..005 | AUTH-*, SET-* |
| USR-F-014..020 | Permission system tests (4.13) |
| INT-F-001..010 | Integration tests (4.3) |
| NTF-F-001..006 | Notification tests (4.4) |
| ACH-F-001..007 | Achievement CRUD tests (4.12), notification tests (4.4.1) |
| TKT-F-001..016 | Ticketing tests (4.5, 4.5.1) |
| TKT-F-017..023 | Signed ticket token tests (4.20) |
| SEC-014..020 | Signed ticket token tests (4.20) |
| CAP-TKT-013..014 | Signed ticket token tests (4.20) |
| SHP-F-003, SHP-F-015, SHP-F-016 | Shop/Payment tests (4.6) |
| SHP-F-017 | User order views (4.6.2) |
| VEN-F-001..003 | Venue tests (4.8) |
| GAM-F-001..003 | Game tests (4.9) |
| SPO-F-001..005 | Sponsor tests (4.10) |
| SET-F-001..005 | Seating tests (4.11) |
| All domains | Architecture tests (4.13) |
| COMP-F-007, COMP-F-013..015 | Competition tests (4.16) |
| EVT-F-011 | My Pages event selector tests (4.17) |
| ORG-F-001..005 | Organization settings tests (4.18) |
| SSS 3.1 Required States (Demo) | Demo mode tests (4.19) |
| CAP-ICLIB-001..005, ICLIB-F-001..006 | Integration Client Library tests (4.21) |
| CAP-USR-010, USR-F-021 | Locale settings tests (4.22.1) |
| CAP-I18N-001..004, I18N-F-001..004 | SetLocale middleware tests (4.22.2), Inertia shared props tests (4.22.3) |
| CAP-I18N-002, CAP-I18N-007, I18N-F-007 | ResolveIntegrationUser locale bug-fix tests (4.22.4) |
| CAP-I18N-005 | Locale asset presence verified by CI pipeline (4.15) |

---

## 6. Notes

### 6.1 Test Naming Convention

Tests use Pest's descriptive `it()` syntax:
```php
it('can create a new integration app', function () {
    // ...
});
```

### 6.2 Test Utilities

- `actingAs($user)` — Authenticate as a specific user
- `assertRedirect()` — Verify redirect response
- `assertInertia()` — Verify Inertia page component and props
- `assertDatabaseHas()` — Verify database state
- `Mail::fake()`, `Event::fake()`, `Notification::fake()` — Mock side effects

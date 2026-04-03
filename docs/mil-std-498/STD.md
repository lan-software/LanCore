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

### 4.13 Architecture Tests

**File:** `tests/Architecture/`

| Test | Assertion |
|------|-----------|
| Domain structure | Domain modules follow expected directory structure |
| No direct DB:: usage | Models use Eloquent, not DB facade |
| Controller returns | Controllers return Inertia or redirect responses |

### 4.14 CI Pipeline Verification

**Scope:** GitHub Actions workflows (`.github/workflows/`)

| Test | Assertion |
|------|-----------|
| Backend pipeline executes | `tests.yml` completes Pest test suite with coverage |
| Frontend pipeline executes | `frontend-tests.yml` completes Vitest and Playwright tests |
| Lint pipeline executes | `lint.yml` passes Pint, ESLint, and Prettier checks with zero errors |
| Docker pipeline executes | `docker-publish.yml` builds multi-platform image and pushes to GHCR |
| All pipelines gate merges | Pull requests cannot merge with failing required status checks |

---

## 5. Requirements Traceability

| SRS Requirement | Test Cases |
|----------------|-----------|
| USR-F-001..005 | AUTH-*, SET-* |
| INT-F-001..010 | Integration tests (4.3) |
| NTF-F-001..006 | Notification tests (4.4) |
| ACH-F-001..007 | Achievement CRUD tests (4.12), notification tests (4.4.1) |
| TKT-F-001..016 | Ticketing tests (4.5, 4.5.1) |
| SHP-F-003, SHP-F-015, SHP-F-016 | Shop/Payment tests (4.6) |
| VEN-F-001..003 | Venue tests (4.8) |
| GAM-F-001..003 | Game tests (4.9) |
| SPO-F-001..005 | Sponsor tests (4.10) |
| SET-F-001..005 | Seating tests (4.11) |
| All domains | Architecture tests (4.13) |

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

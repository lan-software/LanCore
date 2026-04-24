# Software Test Plan (STP)

**Document Identifier:** LanCore-STP-001
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

This Software Test Plan (STP) describes the plan for conducting qualification testing of the **LanCore** CSCI.

### 1.2 System Overview

LanCore employs a multi-layered testing strategy using Pest PHP (backend), Vitest (frontend components), and Playwright (end-to-end browser tests), automated via GitHub Actions CI/CD pipelines.

### 1.3 Document Overview

This document describes the test environment, test approach, planned tests, and schedules for LanCore qualification testing.

---

## 2. Referenced Documents

- [SRS](SRS.md) — Software Requirements Specification
- [STD](STD.md) — Software Test Description
- [STR](STR.md) — Software Test Report

---

## 3. Software Test Environment

### 3.1 Software Items

| Item | Version | Purpose |
|------|---------|---------|
| Pest PHP | v4.4.3 | Backend feature and unit testing |
| PHPUnit | v12.5.14 | Test runner engine (via Pest) |
| Vitest | Latest | Frontend Vue component testing |
| Playwright | v1.58 | End-to-end browser testing |
| Xdebug | Latest | Code coverage collection |
| Codecov | SaaS | Coverage reporting and tracking |

### 3.2 Hardware and Infrastructure

| Component | CI Environment | Local Development |
|-----------|---------------|-------------------|
| Runtime | GitHub Actions Ubuntu runner | Docker via Laravel Sail |
| PHP | 8.5 | 8.5 (Sail container) |
| Database | SQLite (in-memory) | SQLite or PostgreSQL |
| Node.js | Latest LTS | Latest LTS (Sail container) |
| Browser | Chromium (Playwright) | Chromium (Playwright) |

#### 3.2.1 CI Pipeline Configuration

| Configuration | Value |
|---------------|-------|
| CI Platform | GitHub Actions |
| Runner | `ubuntu-latest` |
| PHP Setup | `shivammathur/setup-php@v2` with Xdebug coverage |
| Node.js Setup | `actions/setup-node@v4`, Node 22 |
| Database (CI) | SQLite file (`database/database.sqlite`) with migrations |
| Database (Tests) | SQLite in-memory via `phpunit.xml` override (`DB_DATABASE=testing`) |
| Cache/Session (CI) | Overridden to `array` via `phpunit.xml` (no Redis in CI) |
| Coverage Tool | Xdebug (collection) + Codecov (reporting) |
| Secrets Required | `CODECOV_TOKEN` (coverage upload), `GITHUB_TOKEN` (automatic, for Docker/GHCR) |
| Asset Build | `npm run build` (Vite production build before tests) |

### 3.3 Test Data

- **Factories:** Each Eloquent model has a corresponding factory for generating test data
- **Seeders:** `SeedDemoCommand` provides comprehensive demo data
- **Faker:** Used within factories for realistic random data generation
- **Database Reset:** `RefreshDatabase` trait resets database state between tests

### 3.4 Participating Organizations

- **Development Team:** Open-source contributors run tests locally
- **CI/CD:** GitHub Actions runs tests automatically on push/PR
- **Coverage:** Codecov tracks coverage trends over time

---

## 4. Test Identification

### 4.1 General Test Approach

LanCore uses a three-tier testing strategy:

| Tier | Tool | Scope | Execution |
|------|------|-------|-----------|
| 1 — Unit Tests | Pest PHP | Individual functions, value objects, utilities | Fast, isolated, no I/O |
| 2 — Feature Tests | Pest PHP | HTTP requests through full Laravel stack | Database, middleware, auth |
| 3 — E2E Tests | Playwright | Full browser interaction flows | Real browser, compiled frontend |

Additional:
| Type | Tool | Scope |
|------|------|-------|
| Architecture Tests | Pest (PHPUnit) | Enforces structural constraints on codebase |
| Component Tests | Vitest | Vue component rendering and behavior |
| Lint/Format | Pint, ESLint, Prettier | Code style consistency |
| Type Checking | vue-tsc | TypeScript type correctness |

### 4.2 Test Execution Commands

```bash
# Run all backend tests
vendor/bin/sail artisan test --compact

# Run specific test file
vendor/bin/sail artisan test --compact tests/Feature/Auth/LoginTest.php

# Run with filter
vendor/bin/sail artisan test --compact --filter=testName

# Run with coverage
vendor/bin/sail artisan test --coverage

# Run frontend component tests
vendor/bin/sail npm run test

# Run E2E tests
vendor/bin/sail npm run test:e2e

# Run linting
vendor/bin/sail bin pint --dirty --format agent
vendor/bin/sail npm run lint
vendor/bin/sail npm run types:check
```

### 4.3 Planned Test Areas

#### 4.3.1 Authentication Tests (TGRP-AUTH)

| Test ID | Description | Type |
|---------|-------------|------|
| AUTH-001 | User registration with valid data | Feature |
| AUTH-002 | User registration with invalid data (validation) | Feature |
| AUTH-003 | User login with correct credentials | Feature |
| AUTH-004 | User login with incorrect credentials | Feature |
| AUTH-005 | Password reset request | Feature |
| AUTH-006 | Password reset completion | Feature |
| AUTH-007 | Email verification flow | Feature |
| AUTH-008 | Two-factor authentication setup | Feature |
| AUTH-009 | Two-factor authentication challenge | Feature |
| AUTH-010 | Password confirmation | Feature |

#### 4.3.2 Settings Tests (TGRP-SET)

| Test ID | Description | Type |
|---------|-------------|------|
| SET-001 | Profile update (name, email) | Feature |
| SET-002 | Password change | Feature |
| SET-003 | Notification preferences update | Feature |
| SET-004 | Ticket discovery settings | Feature |
| SET-005 | Sidebar favorites management | Feature |
| SET-006 | Account deletion | Feature |
| SET-007 | Privacy toggle (`is_seat_visible_publicly`) — render & update | Feature |

#### 4.3.3 Integration Tests (TGRP-INT)

| Test ID | Description | Type |
|---------|-------------|------|
| INT-001 | Integration app CRUD | Feature |
| INT-002 | Integration token creation and rotation | Feature |
| INT-003 | Integration token revocation | Feature |
| INT-004 | SSO authorization code generation | Feature |
| INT-005 | SSO code exchange | Feature |
| INT-006 | API Bearer token authentication | Feature |
| INT-007 | Webhook subscription management | Feature |
| INT-008 | Integration access logging | Feature |

#### 4.3.4 Notification Tests (TGRP-NTF)

| Test ID | Description | Type |
|---------|-------------|------|
| NTF-001 | Notification preference management | Feature |
| NTF-002 | Push subscription registration | Feature |
| NTF-003 | News article notification dispatch | Feature |
| NTF-004 | Program subscription management | Feature |
| NTF-005 | Announcement notification dispatch | Feature |

#### 4.3.5 Ticketing Tests (TGRP-TKT)

| Test ID | Description | Type |
|---------|-------------|------|
| TKT-001 | Ticket type audit trail | Feature |
| TKT-002 | Voucher CRUD operations | Feature |
| TKT-003 | Ticket validation ID generation | Unit |
| TKT-004 | User discoverability logic | Unit |

#### 4.3.6 Dashboard Tests (TGRP-DSH)

| Test ID | Description | Type |
|---------|-------------|------|
| DSH-001 | Dashboard rendering | Feature |
| DSH-002 | Event context switching | Feature |

#### 4.3.7 Cache Tests (TGRP-CCH)

| Test ID | Description | Type |
|---------|-------------|------|
| CCH-001 | ModelCacheService tag operations | Feature |
| CCH-002 | Cache invalidation | Feature |

#### 4.3.7a Seating Picker Tests (TGRP-SEAT)

Covers SET-F-006..010.

| Test ID | Description | Type |
|---------|-------------|------|
| SEAT-001 | AssignSeat: owner picks own seat (no users added) | Unit |
| SEAT-002 | AssignSeat: re-assignment moves the seat instead of duplicating | Unit |
| SEAT-003 | AssignSeat: double-booking same seat across tickets is rejected | Unit |
| SEAT-004 | AssignSeat: non-salable / non-existent seat / cross-event seat plan rejected | Unit |
| SEAT-005 | Picker: owner picks for self via HTTP | Feature |
| SEAT-006 | Picker: manager picks for assigned user on group ticket | Feature |
| SEAT-007 | Picker: ticket-user picks own seat but cannot pick for others on the ticket | Feature |
| SEAT-008 | Picker: third-party stranger is forbidden | Feature |
| SEAT-009 | Lifecycle: cancelling a ticket releases all its seat assignments | Feature |
| SEAT-010 | Lifecycle: removing a user from a group ticket releases that user's seat | Feature |
| SEAT-011 | Welcome / picker: privacy override reveals name to same-event ticket holder | Feature |
| SEAT-012 | Category restriction: Standard-category ticket cannot assign into a VIP-only block (returns 422) | Feature |
| SEAT-013 | Category restriction: VIP-category ticket CAN assign into a VIP-only block | Feature |
| SEAT-014 | Category restriction: any ticket can assign when block has no allowlist (permissive default) | Feature |
| SEAT-015 | Invalidation detection: PATCH removing an occupied seat without `confirm_invalidations` returns 302 + `invalidations` flash; no DB write | Feature |
| SEAT-016 | Invalidation detection: PATCH narrowing a category allowlist that rejects an existing assignment is detected | Feature |
| SEAT-017 | Confirmed invalidation: PATCH with `confirm_invalidations=true` deletes `seat_assignments`, dispatches `SeatAssignmentInvalidated`, mail + database channels fire | Feature |
| SEAT-018 | Notification via() branches: mail fires iff `mail_on_seating=true`; push fires iff `push_on_seating=true`; database always fires | Unit |
| SEAT-U-001 | SeatingCategoryRules rule matrix (permissive / restricted / unknown block / allowed_category_ids normalisation) | Unit |
| SEAT-U-002 | UpdateSeatPlan diff logic: no-change, seat removed, category narrowed, confirm→delete+dispatch | Unit |
| SEAT-019 | Resource shape: `SeatPlanResource` preserves the `{blocks:[{seats,labels,allowed_ticket_category_ids}]}` wire shape with integer IDs (SET-F-016) | Unit |
| SEAT-020 | Backfill idempotency: `LegacySeatPlanConverter::backfillAll` skips plans that already have normalized blocks | Feature |
| SEAT-021 | Backfill: plain `{blocks:[…]}` JSONB is migrated with seats, labels, and category-restriction pivot preserved (permissive orphan handling) | Feature |
| SEAT-022 | Seat plan tree syncer: full-state replace creates blocks/rows/seats/labels for `new-*` placeholders and returns an `id_map` covering every newly-created entity | Unit |
| SEAT-023 | Editor → save round-trip: admin edits a plan, first-save reports invalidations, confirmed save commits the tree and swaps placeholders for PKs | Feature |

#### 4.3.8 Architecture Tests (TGRP-ARC)

| Test ID | Description | Type |
|---------|-------------|------|
| ARC-001 | Domain structure constraints | Architecture |

---

## 5. Test Schedules

### 5.1 Continuous Integration

#### 5.1.1 Pipeline Overview

| Workflow | File | Triggers | Scope |
|----------|------|----------|-------|
| Backend Tests | `tests.yml` | Push/PR to `develop`, `main`, `master`, `workos` | Pest PHP tests with Xdebug coverage, uploaded to Codecov |
| Frontend Tests | `frontend-tests.yml` | Push/PR to `develop`, `main`, `master` | Vitest component tests + Playwright E2E browser tests |
| Linting | `lint.yml` | Push/PR to `develop`, `main`, `master`, `workos` | PHP Pint formatting + ESLint/Prettier frontend checks |
| Docker Publish | `docker-publish.yml` | Push to `main`/`master`, version tags | Multi-platform Docker image (amd64/arm64) to GitHub Container Registry |

#### 5.1.2 Backend Tests (`tests.yml`)

1. Checkout code
2. Setup PHP 8.5 with Xdebug coverage
3. Setup Node.js 22
4. Install PHP and Node dependencies
5. Copy `.env.example` to `.env`, generate application key
6. Create SQLite database and run migrations
7. Build frontend assets (`npm run build`)
8. Run Pest tests with coverage output (`./vendor/bin/pest --coverage-clover coverage.xml`)
9. Upload coverage to Codecov

**Environment overrides:** `CACHE_STORE=array`, `SESSION_DRIVER=array`, `QUEUE_CONNECTION=sync`

#### 5.1.3 Frontend Tests (`frontend-tests.yml`)

**Vitest Job (Component Tests):**
1. Checkout code, setup Node.js 22
2. Install dependencies (`npm ci`)
3. Run Vitest (`npm test`) with `LARAVEL_BYPASS_ENV_CHECK=1`

**Playwright Job (E2E Tests):**
1. Checkout code, setup PHP 8.5 and Node.js 22
2. Install PHP and Node dependencies
3. Copy `.env.example`, generate key, create SQLite DB, run migrations
4. Build assets (`npm run build`)
5. Install Playwright Chromium browser
6. Start Laravel dev server on port 8000
7. Wait for server readiness (`wait-on http://localhost:8000`)
8. Run Playwright tests (`npm run test:e2e`)
9. Upload Playwright report artifact on failure

#### 5.1.4 Linting (`lint.yml`)

1. Checkout code, setup PHP 8.5
2. Install PHP and Node dependencies
3. Run PHP Pint (`composer lint`)
4. Run Prettier formatting check (`npm run format`)
5. Run ESLint check (`npm run lint:check`)

#### 5.1.5 Docker Publish (`docker-publish.yml`)

1. Checkout code
2. Setup Docker Buildx for multi-platform builds
3. Log in to GitHub Container Registry
4. Extract metadata (tags, labels)
5. Build and push multi-platform image (linux/amd64, linux/arm64)
6. Generate artifact attestation for supply chain security

#### 5.1.6 Merge Requirements

All applicable pipelines must pass before a pull request can be merged. Pipeline failures block merge to protected branches (`main`, `develop`).

### 5.2 Development Workflow

1. Developer writes/modifies code
2. Developer runs relevant tests locally: `vendor/bin/sail artisan test --compact --filter=...`
3. Developer runs Pint: `vendor/bin/sail bin pint --dirty --format agent`
4. Developer pushes; CI runs full suite
5. PR merged only if all checks pass

---

## 6. Requirements Traceability

| SRS Domain | Test Group |
|-----------|-----------|
| CSCI-USR (User Management) | TGRP-AUTH, TGRP-SET |
| CSCI-INT (Integration) | TGRP-INT |
| CSCI-NTF (Notification) | TGRP-NTF |
| CSCI-TKT (Ticketing) | TGRP-TKT |
| CSCI-EVT (Event) | TGRP-DSH |
| Cross-cutting | TGRP-CCH, TGRP-ARC |

---

## 7. Notes

### 7.1 Test Gaps (Current PoC)

The following areas do not yet have comprehensive test coverage and are planned for pre-v1.0:

- Shop/checkout flow (Stripe integration) — **partially addressed**: `StripeWebhookTest.php` covers webhook-based fulfillment (checkout.session.completed, idempotency, edge cases); `StripeCheckoutTest.php` covers the redirect-based checkout flow (order creation, success/cancel URL handling). Integration tests with live Stripe test mode remain manual.
- News article CRUD
- Announcement CRUD
- Event CRUD
- Venue CRUD
- Program CRUD
- Sponsor CRUD
- Game CRUD
- Seating plan operations
- Achievement granting
- Webhook delivery
- E2E browser flows

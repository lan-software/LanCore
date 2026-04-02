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

#### 4.3.8 Architecture Tests (TGRP-ARC)

| Test ID | Description | Type |
|---------|-------------|------|
| ARC-001 | Domain structure constraints | Architecture |

---

## 5. Test Schedules

### 5.1 Continuous Integration

| Trigger | Tests Run | Pipeline |
|---------|-----------|----------|
| Push to any branch | All Pest tests, lint, type check | `tests.yml`, `lint.yml` |
| Pull request | All Pest tests, frontend tests, lint | `tests.yml`, `frontend-tests.yml`, `lint.yml` |
| Release tag | All tests + Docker image build | All workflows |

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

- Shop/checkout flow (Stripe integration)
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

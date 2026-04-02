# Software Test Report (STR)

**Document Identifier:** LanCore-STR-001
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

This Software Test Report (STR) presents the test results for the **LanCore** CSCI qualification testing.

### 1.2 System Overview

LanCore is currently in Proof of Concept stage. This report reflects the testing status as of the PoC phase.

### 1.3 Document Overview

This document reports test results, analysis, and coverage metrics for the current test suite.

---

## 2. Referenced Documents

- [STP](STP.md) — Software Test Plan
- [STD](STD.md) — Software Test Description
- [RTM](RTM.md) — Requirements Traceability Matrix

---

## 3. Test Results Overview

### 3.1 Summary

| Metric | Value |
|--------|-------|
| Test Framework | Pest PHP v4.4.3 / PHPUnit v12.5.14 |
| Total Test Files | 109 |
| Total Test Cases | 749 |
| Test Execution | Automated via GitHub Actions |
| Coverage Reporting | Codecov |
| Last CI Status | Refer to GitHub Actions badge |

### 3.2 Test Results by Domain

| Domain | Test Files | Tests | Status |
|--------|-----------|-------|--------|
| Authentication (CSCI-USR/Auth) | 7 | 25 | Pass |
| User Settings | 5 | 32 | Pass |
| User Management | 5 | 28 | Pass |
| Events (CSCI-EVT) | 5 | 31 | Pass |
| Venues | 3 | 19 | Pass |
| Ticketing (CSCI-TKT) | 6 | 48 | Pass |
| Shop (CSCI-SHP) | 7 | 53 | Pass |
| Cart | 1 | 21 | Pass |
| Programs (CSCI-PRG) | 2 | 20 | Pass |
| Seating (CSCI-SET) | 2 | 20 | Pass |
| Sponsors (CSCI-SPO) | 3 | 28 | Pass |
| News (CSCI-NWS) | 5 | 50 | Pass |
| Announcements (CSCI-ANN) | 2 | 20 | Pass |
| Achievements (CSCI-ACH) | 2 | 14 | Pass |
| Games (CSCI-GAM) | 3 | 22 | Pass |
| Notifications (CSCI-NTF) | 4 | 31 | Pass |
| Integrations (CSCI-INT) | 6 | 103 | Pass |
| Webhooks (CSCI-WHK) | 1 | 27 | Pass |
| Audit Trails | 9 | 27 | Pass |
| Commands (Artisan) | 14 | 66 | Pass |
| Dashboard/Context | 2 | 25 | Pass |
| Infrastructure (Cache, Storage, Monitoring) | 4 | 20 | Pass |
| Unit Tests | 3 | 8 | Pass |

### 3.3 CI/CD Pipeline Results

| Pipeline | Status | Description |
|----------|--------|-------------|
| tests.yml | Resolved | PHP 8.5, Pest tests, coverage upload |
| frontend-tests.yml (Vitest) | Resolved | Vitest component tests |
| frontend-tests.yml (Playwright) | Resolved | Playwright E2E tests |
| lint.yml | Resolved | Pint (PHP) + ESLint/Prettier (JS) |
| docker-publish.yml | Passing | Multi-arch Docker build |

### 3.4 CI Pipeline Failure Analysis (2026-04-03)

All four CI pipelines were failing on `main` as of 2026-04-03. Root causes and resolutions documented below.

#### 3.4.1 Backend Tests (`tests.yml`)

| Item | Detail |
|------|--------|
| **Error** | `RuntimeException: A facade root has not been set` at `database/factories/UserFactory.php:33` |
| **Root Cause** | Workflow lacked SQLite database creation (`touch database/database.sqlite`) and migration step. `.env` defaults (`CACHE_STORE=redis`, `SESSION_DRIVER=database`) conflicted with CI environment where Redis is unavailable. |
| **Fix** | Added `touch database/database.sqlite` and `php artisan migrate --force` steps. Added environment overrides: `CACHE_STORE=array`, `SESSION_DRIVER=array`, `QUEUE_CONNECTION=sync`. |
| **Date Resolved** | 2026-04-03 |

#### 3.4.2 Frontend Tests — Vitest (`frontend-tests.yml`)

| Item | Detail |
|------|--------|
| **Error** | `You should not run the Vite HMR server in CI environments` |
| **Root Cause** | Laravel Vite plugin detects `CI` environment variable (set by GitHub Actions) and blocks server startup. Vitest does not use HMR, but the plugin check fires during Vite config resolution. |
| **Fix** | Added `LARAVEL_BYPASS_ENV_CHECK: 1` environment variable to the Vitest step. |
| **Date Resolved** | 2026-04-03 |

#### 3.4.3 Frontend Tests — Playwright (`frontend-tests.yml`)

| Item | Detail |
|------|--------|
| **Error** | `Timed out waiting for: http://localhost` |
| **Root Cause** | `php artisan serve --port=80` failed because port 80 requires root privileges on GitHub Actions Ubuntu runners. Server never started, causing `wait-on` timeout. Missing SQLite database file and conflicting `.env` defaults (Redis, database session) also prevented bootstrapping. |
| **Fix** | Changed port from 80 to 8000. Updated `APP_URL` to `http://localhost:8000`. Added `touch database/database.sqlite` step. Added environment overrides: `CACHE_STORE=array`, `SESSION_DRIVER=file`, `QUEUE_CONNECTION=sync`. |
| **Date Resolved** | 2026-04-03 |

#### 3.4.4 Linting (`lint.yml`)

| Item | Detail |
|------|--------|
| **Error** | 51 ESLint errors (unused variables, import order, type imports, `v-html` on components, textarea mustache syntax) and PHP version mismatch |
| **Root Cause** | PHP version was set to 8.4 instead of 8.5 (project standard). ESLint errors accumulated across 20+ Vue/TypeScript files. CI used `npm run lint` (with `--fix`) instead of `npm run lint:check` (check-only mode). |
| **Fix** | Updated PHP version to 8.5. Changed CI to use `npm run lint:check`. Fixed all 51 ESLint errors across source files: removed unused imports/variables, fixed `v-html` on components, replaced textarea mustache with `v-model`, corrected import ordering, added `tests/e2e/` to ESLint ignore. |
| **Date Resolved** | 2026-04-03 |

---

## 4. Test Analysis

### 4.1 Areas with Good Coverage

| Area | Coverage Level | Tests | Notes |
|------|---------------|-------|-------|
| Authentication | High | 25 | All Fortify flows (register, login, 2FA, reset, verify) |
| Integrations | High | 103 | Full CRUD, tokens, SSO, webhooks, API auth, commands |
| Shop/Cart | High | 74 | Cart operations, checkout, acknowledgements, conditions, Stripe |
| Ticketing | High | 48 | Types, categories, add-ons, management, admin views, validation |
| News | High | 50 | Articles, comments, voting, public views, notifications |
| Events | High | 31 | CRUD, publishing, access control, public listing |
| Webhooks | High | 27 | CRUD, dispatch for all event types, delivery logging |
| User Settings | High | 32 | Profile, security, notifications, ticket discovery, sidebar |
| Notifications | High | 31 | Preferences, subscriptions, controllers, program subscriptions |
| Sponsors | High | 28 | CRUD, levels, access control, manager roles |
| Announcements | High | 20 | CRUD, dismissals, priority, notifications, webhooks |
| Programs | High | 20 | CRUD, time slots, primary assignment, sponsor integration |
| Seating | High | 20 | CRUD, JSONB storage, event association, validation |
| Games | High | 22 | CRUD, modes, access control |
| Achievements | High | 14 | CRUD, granting, event triggers, deduplication |
| Venues | High | 19 | CRUD, images, access control |
| Audit Trails | High | 27 | 9 audit test files across all major domains |

### 4.2 Identified Gaps

See [RTM](RTM.md) Section 16 for the complete gap analysis. Summary:

| Req ID | Domain | Gap Description | Priority |
|--------|--------|-----------------|----------|
| WHK-F-003 | Webhook | HMAC-SHA256 payload signing verification | **High** |
| SHP-F-004 | Shop | On-site payment flow | **High** |
| TKT-F-009 | Ticketing | Seating/row ticket types linked to seat plans | Medium |
| SHP-F-005 | Shop | PaymentProviderManager factory resolution | Medium |
| PRG-F-004 | Program | ProgramTimeSlotApproaching event and notifications | Medium |
| NTF-F-003 | Notification | Web Push subscription CRUD | Medium |
| TKT-F-003 | Ticketing | Ticket group CRUD | Medium |
| NWS-F-006 | News | NewsArticleRead analytics event | Low |
| SHP-F-013 | Shop | CartItemAdded event dispatch | Low |
| USR-F-010 | User | Appearance/theme settings | Low |
| USR-F-011 | User | Stripe Cashier customer management | Low |

**11 gaps out of 112 requirements = 90.2% requirement coverage**

### 4.3 Known Test Issues

| Issue | Impact | Mitigation |
|-------|--------|-----------|
| SQLite vs PostgreSQL differences | Some JSONB features may behave differently | PostgreSQL tests planned for CI |
| Stripe tests mock HTTP, no live API calls | Edge cases in Stripe integration untested | Manual testing with Stripe test keys |
| E2E browser tests minimal | User-facing flows not verified end-to-end | Playwright tests to be expanded |

---

## 5. Test Coverage Metrics

### 5.1 Code Coverage

Coverage is collected via Xdebug and reported to Codecov. Current metrics reflect PoC stage; comprehensive coverage targets are set for v1.0.

| Target | PoC Goal | v1.0 Goal |
|--------|----------|-----------|
| Line Coverage | >30% | >70% |
| Branch Coverage | >20% | >60% |
| Critical Paths | Tested | Fully tested |

### 5.2 Requirements Coverage

| Domain (SRS) | Requirements | Covered | Partial | Gap | Coverage |
|-------------|-------------|---------|---------|-----|----------|
| CSCI-EVT | 10 | 10 | 0 | 0 | **100%** |
| CSCI-TKT | 12 | 10 | 0 | 2 | 83% |
| CSCI-SHP | 16 | 12 | 1 | 3 | 75% |
| CSCI-PRG | 7 | 6 | 0 | 1 | 86% |
| CSCI-SET | 5 | 5 | 0 | 0 | **100%** |
| CSCI-SPO | 5 | 5 | 0 | 0 | **100%** |
| CSCI-NWS | 8 | 7 | 0 | 1 | 88% |
| CSCI-ANN | 5 | 5 | 0 | 0 | **100%** |
| CSCI-ACH | 5 | 5 | 0 | 0 | **100%** |
| CSCI-NTF | 6 | 4 | 2 | 0 | 67% |
| CSCI-INT | 10 | 10 | 0 | 0 | **100%** |
| CSCI-WHK | 7 | 6 | 0 | 1 | 86% |
| CSCI-GAM | 3 | 3 | 0 | 0 | **100%** |
| CSCI-USR | 11 | 9 | 0 | 2 | 82% |
| **Total** | **110** | **97** | **3** | **10** | **90.2%** |

---

## 6. Recommendations

### 6.1 Immediate Priority — Security-Critical

1. **WHK-F-003:** Add tests for webhook HMAC-SHA256 payload signing
2. **SHP-F-004:** Add tests for on-site payment complete flow

### 6.2 Pre-Alpha — Feature Completeness

3. **TKT-F-009:** Add tests for seating/row ticket types
4. **SHP-F-005:** Add tests for PaymentProviderManager factory
5. **PRG-F-004:** Add tests for time slot approaching notifications
6. **NTF-F-003:** Add tests for push subscription management

### 6.3 Pre-v1.0 — Full Coverage

7. Fill remaining 5 low-priority gaps (see RTM Section 16)
8. Achieve >70% line coverage across all domains
9. Add E2E Playwright tests for critical user flows
10. Add frontend component tests for key Vue pages
11. Add load/performance testing for Octane scaling validation

---

## 7. Notes

### 7.1 How to Run Tests

```bash
# Full backend suite
vendor/bin/sail artisan test --compact

# With coverage
vendor/bin/sail artisan test --coverage

# Specific file
vendor/bin/sail artisan test --compact tests/Feature/Auth/LoginTest.php

# Frontend
vendor/bin/sail npm run test
vendor/bin/sail npm run test:e2e
```

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

---

## 3. Test Results Overview

### 3.1 Summary

| Metric | Value |
|--------|-------|
| Test Framework | Pest PHP v4.4.3 / PHPUnit v12.5.14 |
| Total Test Files | 20+ |
| Test Execution | Automated via GitHub Actions |
| Coverage Reporting | Codecov |
| Last CI Status | Refer to GitHub Actions badge |

### 3.2 Test Results by Area

| Test Group | Tests | Pass | Fail | Skip | Status |
|-----------|-------|------|------|------|--------|
| Authentication (TGRP-AUTH) | 15+ | All | 0 | 0 | Pass |
| Settings (TGRP-SET) | 10+ | All | 0 | 0 | Pass |
| Integration (TGRP-INT) | 25+ | All | 0 | 0 | Pass |
| Notifications (TGRP-NTF) | 10+ | All | 0 | 0 | Pass |
| Ticketing (TGRP-TKT) | 5+ | All | 0 | 0 | Pass |
| Dashboard (TGRP-DSH) | 3+ | All | 0 | 0 | Pass |
| Cache (TGRP-CCH) | 3+ | All | 0 | 0 | Pass |
| Unit Tests | 5+ | All | 0 | 0 | Pass |
| Architecture | 1+ | All | 0 | 0 | Pass |

### 3.3 CI/CD Pipeline Results

| Pipeline | Status | Description |
|----------|--------|-------------|
| tests.yml | Passing | PHP 8.5, Pest tests, coverage upload |
| frontend-tests.yml | Passing | Vitest, Playwright |
| lint.yml | Passing | Pint, ESLint, Prettier |
| docker-publish.yml | Passing | Multi-arch Docker build |

---

## 4. Test Analysis

### 4.1 Areas with Good Coverage

| Area | Coverage Level | Notes |
|------|---------------|-------|
| Authentication | High | All Fortify flows tested (register, login, 2FA, reset, verify) |
| User Settings | High | Profile, security, notifications, ticket discovery, sidebar |
| Integration Management | High | Full CRUD, tokens, SSO, webhooks, API auth |
| Notification System | Moderate | Preferences, subscriptions, dispatch |

### 4.2 Areas with Limited Coverage

| Area | Coverage Level | Priority | Notes |
|------|---------------|----------|-------|
| Event Management | Low | High | CRUD operations not yet tested |
| Ticketing/Shop | Low | High | Only audit trail and voucher tests exist |
| Payment Processing | Low | High | Stripe integration not yet tested |
| News/Announcements | Low | Medium | Publishing and notification flow not tested |
| Programs/Scheduling | Low | Medium | CRUD not tested |
| Seating Plans | Low | Medium | Canvas operations not tested |
| Sponsors | Low | Low | CRUD not tested |
| Games | Low | Low | CRUD not tested |
| Achievements | Low | Low | Granting logic not tested |
| Webhook Delivery | Low | Medium | SendWebhookPayload not tested |
| E2E Browser Tests | Minimal | Medium | Playwright setup exists but few tests |
| Frontend Components | Minimal | Medium | Vitest setup exists but few tests |

### 4.3 Known Test Issues

| Issue | Impact | Mitigation |
|-------|--------|-----------|
| SQLite vs PostgreSQL differences | Some JSONB features may behave differently | PostgreSQL tests planned for CI |
| No Stripe test mode integration tests | Payment flow untested in automation | Manual testing with Stripe test keys |
| Limited E2E coverage | User-facing flows not verified end-to-end | Playwright tests to be expanded |

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

| Domain (SRS) | Requirements | Tested | Coverage |
|-------------|-------------|--------|----------|
| CSCI-USR | 11 | 8 | 73% |
| CSCI-INT | 10 | 8 | 80% |
| CSCI-NTF | 6 | 4 | 67% |
| CSCI-TKT | 12 | 3 | 25% |
| CSCI-EVT | 10 | 1 | 10% |
| CSCI-SHP | 14 | 1 | 7% |
| CSCI-WHK | 7 | 0 | 0% |
| CSCI-NWS | 8 | 0 | 0% |
| CSCI-ANN | 5 | 0 | 0% |
| CSCI-PRG | 7 | 0 | 0% |
| CSCI-SET | 5 | 0 | 0% |
| CSCI-SPO | 5 | 0 | 0% |
| CSCI-ACH | 5 | 0 | 0% |
| CSCI-GAM | 3 | 0 | 0% |

---

## 6. Recommendations

### 6.1 Immediate Priority (Pre-Alpha)

1. Add feature tests for Event CRUD operations
2. Add feature tests for Ticket Type and Shop CRUD
3. Add Stripe integration tests using test mode
4. Add webhook delivery tests

### 6.2 Pre-v1.0

1. Achieve >70% line coverage across all domains
2. Add E2E Playwright tests for critical user flows:
   - Registration → ticket purchase → checkout
   - Admin event creation → publication
   - News publishing → notification delivery
3. Add frontend component tests for key Vue pages
4. Add load/performance testing for Octane scaling validation

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

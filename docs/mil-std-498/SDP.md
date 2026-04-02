# Software Development Plan (SDP)

**Document Identifier:** LanCore-SDP-001
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

This Software Development Plan (SDP) applies to the **LanCore** system — a LAN Party & BYOD Event Management Platform, version 0.x (Proof of Concept).

### 1.2 System Overview

LanCore is a web-based platform for organizing and managing LAN parties and Bring-Your-Own-Device (BYOD) events. It provides event management, ticketing, seating, scheduling, news, announcements, sponsor management, achievements, notifications, and third-party integration capabilities. LanCore is a modern rewrite of the eventula-manager project.

### 1.3 Document Overview

This SDP describes the plans for developing the LanCore software system, including the project organization, development processes, tools, schedules, and standards to be followed.

### 1.4 Relationship to Other Plans

- [STP](STP.md) — Software Test Plan
- [SIP](SIP.md) — Software Installation Plan

---

## 2. Referenced Documents

- MIL-STD-498, Software Development and Documentation, 8 November 1994
- [LanCore README](../../README.md)
- [LanCore CLAUDE.md](../../CLAUDE.md)

---

## 3. Overview of Required Work

### 3.1 Purpose and Scope of the Software

LanCore shall provide a comprehensive, self-hostable platform for LAN party organizers to:

- Create and publish events with venues, schedules, and sponsorships
- Sell tickets with flexible pricing, quotas, categories, and add-ons
- Manage seating plans via canvas-based editors
- Publish news articles and announcements with real-time notifications
- Track user achievements and badges
- Integrate with third-party applications via SSO, API tokens, and webhooks
- Process payments via Stripe and on-site collection

### 3.2 Type of Software and Classification

- **Type:** Web Application (Server-Side Rendered SPA)
- **Classification:** Open-source, community-driven
- **Criticality:** Non-safety-critical; data integrity and availability are primary concerns

### 3.3 Requirements and Constraints

- Must run within Docker containers (Laravel Sail)
- Must support PostgreSQL as the primary database
- Must use PHP 8.5 and Laravel 13 framework
- Must provide a responsive, mobile-friendly user interface
- Must support horizontal scaling via Laravel Octane (FrankenPHP)

---

## 4. Plans for Performing General Software Development Activities

### 4.1 Software Development Process

LanCore follows an iterative, domain-driven development process:

1. **Requirements Analysis** — Feature requirements defined via GitHub Issues
2. **Domain Design** — Business logic organized into domain modules under `app/Domain/`
3. **Implementation** — Code developed following Laravel conventions and domain-driven design
4. **Testing** — Automated tests written alongside or before implementation (Pest PHP)
5. **Code Review** — Pull request review before merge to `main`
6. **Integration** — CI/CD pipeline validates all changes automatically

### 4.2 General Standards and Procedures

#### Coding Standards
- **PHP:** PSR-12 code style enforced by Laravel Pint v1
- **JavaScript/Vue:** ESLint v9 + Prettier v3
- **TypeScript:** Strict type checking via vue-tsc
- **Architecture:** Domain-Driven Design with Actions pattern

#### Documentation Standards
- MIL-STD-498 DID structure for project documentation — adopted on the recommendation of Theodor "Bawz", whose advocacy for applying MIL-STD-498 to the LAN-Software/LanCore project inspired this documentation approach
- PHPDoc blocks for PHP classes and methods
- Inline comments only for exceptionally complex logic

#### Version Control
- **System:** Git with GitHub hosting
- **Branching:** Feature branches from `main`, merged via pull request
- **Commit Style:** Conventional commits with co-authorship attribution

### 4.3 Reusable Software

| Component | Source | Purpose |
|-----------|--------|---------|
| Laravel Framework 13 | Open Source | Core MVC framework |
| Vue.js 3 | Open Source | Frontend UI framework |
| Inertia.js v2 | Open Source | Server-driven SPA bridge |
| Tailwind CSS v4 | Open Source | Utility-first CSS framework |
| Laravel Fortify v1 | Open Source | Headless authentication |
| Laravel Cashier v16 | Open Source | Stripe billing integration |
| Laravel Horizon v5 | Open Source | Queue management dashboard |
| Laravel Pulse v1 | Open Source | Application monitoring |
| Laravel Telescope v5 | Open Source | Debug/development assistant |
| Pest PHP v4 | Open Source | Testing framework |
| TipTap | Open Source | Rich text editor |
| TanStack Vue Table | Open Source | Data table components |
| reka-ui | Open Source | Headless UI components |
| seatmap-canvas | Open Source | Canvas seating visualization |

---

## 5. Project Organization and Resources

### 5.1 Organizational Structure

- **Project Lead:** Open-source maintainer(s)
- **Contributors:** Community contributors via GitHub
- **Roles:** Admin, Superadmin, Sponsor Manager (within the application)

### 5.2 Personnel

Development is community-driven. Contributors should be familiar with:

- PHP 8.5 and Laravel 13
- Vue.js 3 with Composition API
- PostgreSQL database design
- Domain-Driven Design principles

### 5.3 Development Environment

#### Hardware Requirements
- Development machine with Docker support
- Minimum 4 GB RAM, 10 GB disk space recommended

#### Software Environment

| Component | Technology |
|-----------|-----------|
| Runtime | PHP 8.5 with FrankenPHP (Laravel Octane) |
| Container | Docker via Laravel Sail |
| Database | PostgreSQL (production), SQLite (development/testing) |
| Cache | Redis |
| Storage | S3-compatible (Minio for development, Garage/S3 for production) |
| Mail | SMTP (MailHog for development) |
| Queue | Database-backed with Horizon supervision |
| OS | Linux (container), cross-platform development via Docker |

#### Development Tools

| Tool | Version | Purpose |
|------|---------|---------|
| Laravel Sail | v1 | Docker container management |
| Composer | Latest | PHP dependency management |
| npm | Latest | JavaScript dependency management |
| Vite | Latest | Frontend asset bundling |
| Laravel Pint | v1 | PHP code formatting |
| ESLint | v9 | JavaScript linting |
| Prettier | v3 | JavaScript/Vue formatting |
| Pest | v4 | PHP testing |
| Vitest | Latest | Frontend component testing |
| Playwright | v1.58 | End-to-end browser testing |

---

## 6. Schedules and Activity Network

### 6.1 Development Phases

| Phase | Status | Description |
|-------|--------|-------------|
| Proof of Concept | **Current** | Core features implemented, architecture validated |
| Alpha | Planned | Feature-complete for core domains, internal testing |
| Beta | Planned | Public testing, bug fixing, performance tuning |
| v1.0 Release | Planned | Production-ready, documentation complete |

### 6.2 Milestones

| Milestone | Criteria |
|-----------|----------|
| PoC Complete | Core domains functional: events, ticketing, seating, news, programs |
| Payment Integration | Stripe checkout and on-site payment fully operational |
| Integration API Stable | SSO, API tokens, and webhooks production-ready |
| Tournament Support | Competition/orchestration domains implemented |
| v1.0 Release Candidate | All domains complete, test coverage adequate, documentation finalized |

### 6.3 Planned Companion Applications

| Application | Purpose | Status |
|-------------|---------|--------|
| LanBrackets | Tournament/bracket management | Planned |
| LanShout | Event shoutbox/chat | Planned |
| LanEntrance | Check-in and door management | Planned |
| LanHelp | Help desk and FAQ | Planned |
| LanVote | Event voting | Planned |
| LanOrder | Group food ordering | Planned |
| LanDisplay | OBS-compatible web display | Planned |

---

## 7. Notes

### 7.1 Acronyms and Abbreviations

| Term | Definition |
|------|-----------|
| BYOD | Bring Your Own Device |
| CSCI | Computer Software Configuration Item |
| DID | Data Item Description |
| DDD | Domain-Driven Design |
| LAN | Local Area Network |
| MVC | Model-View-Controller |
| SPA | Single Page Application |
| SSO | Single Sign-On |
| 2FA | Two-Factor Authentication |
| TOTP | Time-based One-Time Password |

---

## Appendix A: CI/CD Pipeline

### A.1 Pipeline Architecture

LanCore uses four independent GitHub Actions workflows. All run in parallel on push/PR events with no cross-workflow dependencies.

| Workflow | File | Purpose | Artifacts |
|----------|------|---------|-----------|
| `tests.yml` | `.github/workflows/tests.yml` | Backend qualification testing | `coverage.xml` (Codecov) |
| `frontend-tests.yml` | `.github/workflows/frontend-tests.yml` | Frontend component and E2E testing | `playwright-report/` (on failure) |
| `lint.yml` | `.github/workflows/lint.yml` | Code style and quality enforcement | None |
| `docker-publish.yml` | `.github/workflows/docker-publish.yml` | Container image build and publish | Docker image on GHCR |

### A.2 CI Environment Configuration

The CI environment differs from local development in several key areas:

| Aspect | Local (Sail) | CI (GitHub Actions) |
|--------|-------------|-------------------|
| Runtime | Docker containers via Sail | Ubuntu runner, native PHP/Node |
| Database | PostgreSQL (Sail service) | SQLite file + in-memory (tests) |
| Cache | Redis (Sail service) | Array driver (no Redis) |
| Session | Database driver | Array driver (tests), File driver (E2E) |
| Queue | Database/Redis driver | Sync driver |
| Assets | Vite dev server (HMR) | Pre-built via `npm run build` |
| PHP Version | 8.5 (Sail container) | 8.5 (`shivammathur/setup-php`) |

**Key design decision:** CI uses SQLite and array/sync drivers to eliminate external service dependencies (Redis, PostgreSQL) from the test runner. The `phpunit.xml` file overrides `.env` values for the test process. Playwright E2E tests use a file session driver because they run against a real Laravel server process (not the PHPUnit test harness).

### A.3 Secrets Management

| Secret | Purpose | Provisioned Via |
|--------|---------|-----------------|
| `CODECOV_TOKEN` | Upload coverage reports to Codecov | GitHub repository secret |
| `GITHUB_TOKEN` | Authenticate to GHCR for Docker push, artifact attestation | Automatic (GitHub Actions) |

No application secrets (Stripe keys, SMTP credentials, etc.) are used in CI. All external integrations are either mocked in tests or skipped.

### A.4 Pipeline Maintenance

- **PHP/Node version updates:** Update the `php-version` and `node-version` values in all workflow files simultaneously. The project standard PHP version is defined in `composer.json` (`require.php`).
- **Dependency caching:** Node dependencies use `actions/setup-node` built-in caching (`cache: 'npm'`). Composer dependencies are not cached.
- **Debugging CI failures:** Reproduce locally by running the same commands outside Sail (matching the CI environment). Check the "Actions" tab on GitHub for detailed step logs. For Playwright failures, download the `playwright-report` artifact.
- **Adding new pipelines:** Follow the existing pattern in `.github/workflows/`. Ensure new pipelines are added to branch protection required status checks.

### A.5 Merge Requirements

All pipelines triggered by a pull request must pass before merge is permitted. Branch protection rules enforce this for `main` and `develop`. The required status checks are:

- `ci` (from `tests.yml`)
- `Vitest (Component Tests)` (from `frontend-tests.yml`)
- `Playwright (E2E Tests)` (from `frontend-tests.yml`)
- `quality` (from `lint.yml`)

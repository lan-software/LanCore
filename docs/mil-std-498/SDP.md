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

### GitHub Actions Workflows

| Workflow | Trigger | Description |
|----------|---------|-------------|
| `tests.yml` | Push/PR | PHP 8.5, Pest tests, coverage to Codecov |
| `frontend-tests.yml` | Push/PR | Node.js, Vitest, Playwright E2E |
| `lint.yml` | Push/PR | Pint (PHP) + ESLint/Prettier (JS) |
| `docker-publish.yml` | Release | Multi-platform Docker image build (amd64, arm64) to GHCR |

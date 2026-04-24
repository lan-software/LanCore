# Software Version Description (SVD)

**Document Identifier:** LanCore-SVD-001
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

This Software Version Description (SVD) identifies and describes the current version of the **LanCore** software.

### 1.2 System Overview

LanCore is a LAN Party & BYOD Event Management Platform. This SVD covers the current Proof of Concept release.

### 1.3 Document Overview

This document identifies the delivered software components, changes from previous versions, and known problems.

---

## 2. Referenced Documents

- [SPS](SPS.md) — Software Product Specification
- [SRS](SRS.md) — Software Requirements Specification

---

## 3. Version Identification

### 3.1 Current Version

| Property | Value |
|----------|-------|
| Version | 0.x (Proof of Concept) |
| Status | Pre-release, NOT production-ready |
| Branch | `main` (development), feature branches for active work |
| Release Artifacts | Docker images published to GitHub Container Registry (GHCR) |
| Platforms | linux/amd64, linux/arm64 |

### 3.2 Version History

| Version | Date | Description |
|---------|------|-------------|
| 0.x (PoC) | 2026-03 to present | Initial proof of concept with core domains |

### 3.3 Predecessor

LanCore is a ground-up rewrite of **eventula-manager**. There is no direct migration path from eventula-manager to LanCore.

---

## 4. Software Contents

### 4.1 Source Code Components

| Component | Path | Description |
|-----------|------|-------------|
| PHP Backend | `app/` | Laravel 13 application code |
| Domain Modules (14) | `app/Domain/` | Business logic organized by bounded context |
| Eloquent Models (40) | `app/Models/` | Database model definitions |
| Database Migrations (77) | `database/migrations/` | Schema evolution |
| Factories & Seeders | `database/factories/`, `database/seeders/` | Test data generation |
| Route Definitions (20) | `routes/` | HTTP route registration |
| Configuration | `config/` | Application configuration files |
| Vue.js Pages (95+) | `resources/js/pages/` | Frontend page components |
| Vue.js Components | `resources/js/components/` | Reusable UI components |
| CSS Styles | `resources/css/` | Tailwind CSS configuration |
| Test Suite | `tests/` | Pest PHP and architecture tests |
| Docker Config | `docker/` | Container build and deployment files |
| CI/CD Pipelines | `.github/workflows/` | GitHub Actions automation |

### 4.2 Runtime Dependencies

#### PHP Dependencies (composer.json)

| Package | Version | Purpose |
|---------|---------|---------|
| laravel/framework | ^13.0 | Core framework |
| inertiajs/inertia-laravel | ^2.0 | SPA bridge |
| laravel/cashier | ^16.5 | Stripe billing |
| laravel/fortify | ^1.34 | Authentication |
| laravel/horizon | ^5.45 | Queue dashboard |
| laravel/octane | ^2.17 | High-performance HTTP |
| laravel/pulse | ^1.7 | Monitoring |
| laravel/telescope | ^5.19 | Debug assistant |
| laravel/wayfinder | ^0.1.14 | TypeScript route generation |
| owen-it/laravel-auditing | ^14.0 | Change tracking |
| minishlink/web-push | ^10.0 | Web push notifications |
| spatie/laravel-prometheus | ^1.5 | Metrics |

#### JavaScript Dependencies (package.json)

| Package | Version | Purpose |
|---------|---------|---------|
| vue | ^3 | UI framework |
| @inertiajs/vue3 | ^2.3 | Inertia Vue adapter |
| tailwindcss | ^4.2 | CSS framework |
| @tiptap/vue-3 | Latest | Rich text editor |
| @tanstack/vue-table | ^8.21 | Data tables |
| reka-ui | ^2.9 | Headless UI |
| @alisaitteke/seatmap-canvas | ^2.7.1 | Seating canvas |

### 4.3 Docker Image

| Property | Value |
|----------|-------|
| Base Image | laravelsail/php85-composer + FrankenPHP |
| Registry | GitHub Container Registry (GHCR) |
| Architectures | linux/amd64, linux/arm64 |
| Build Workflow | `.github/workflows/docker-publish.yml` |

---

## 5. Changes from Previous Version

As this is the initial PoC version, there is no previous version. Key capabilities delivered:

### 5.1 Implemented Features

| Feature | Status |
|---------|--------|
| Event Management (CRUD, publish/unpublish) | Complete |
| Venue Management with addresses and images | Complete |
| Ticketing (types, categories, groups, add-ons) | Complete |
| Seating Plans (normalized schema + drag-to-place admin editor with row/grid wizard, mass-edit, undo/redo, background images) | Complete |
| Sponsor Management with tier levels | Complete |
| Program Scheduling with time slots | Complete |
| News Articles with rich text, comments, voting | Complete |
| Announcements with priority levels | Complete |
| Notifications (email, web push, database) | Complete |
| User Authentication (Fortify, 2FA, email verify) | Complete |
| Role-based Access Control (Admin, Superadmin, SponsorManager) | Complete |
| Integration Apps with SSO and API tokens | Complete |
| Webhook System with 8 event types and signed payloads | Complete |
| Games Catalog with modes | Complete |
| Achievements with event-based triggers | Complete |
| Shopping Cart and Checkout framework | Complete |
| Stripe Payment Integration | In progress |
| On-site Payment Collection | Complete |
| Voucher System (fixed and percentage) | Complete |
| Purchase Requirements and Conditions | Complete |
| Audit Logging (laravel-auditing) | Complete |
| Prometheus Metrics | Complete |
| User Settings (profile, security, notifications, appearance) | Complete |
| Sidebar Favorites | Complete |
| Ticket Discovery / Privacy | Complete |

### 5.2 Planned but Not Yet Implemented

| Feature | Target |
|---------|--------|
| Tournament/Competition Management | Post-PoC |
| Game Server Management (Pelican Panel) | Post-PoC |
| PayPal Payment Provider | Post-PoC |
| Companion App Ecosystem (LanBrackets, etc.) | Post-PoC |

---

## 6. Known Problems and Limitations

### 6.1 Known Issues

| ID | Severity | Description | Workaround |
|----|----------|-------------|-----------|
| KP-001 | Medium | Stripe payment flow not fully end-to-end tested | Manual testing with Stripe test mode |
| KP-002 | Low | SQLite test DB may not catch PostgreSQL-specific issues | Run PostgreSQL tests separately |
| KP-003 | Medium | Limited E2E browser test coverage | Manual testing of user flows |
| KP-004 | Info | Competition/Orchestration domain is empty placeholder | Feature planned for future release |

### 6.2 Limitations

| Limitation | Description |
|-----------|------------|
| Not production-ready | PoC stage; not recommended for production events until v1.0 |
| Single-tenant | Each installation serves one organization |
| No offline support | Requires continuous internet connection |

---

## 7. Notes

### 7.1 Upgrade Instructions

As this is a pre-release version, there is no formal upgrade path. Each deployment should be a fresh installation.

### 7.2 Obtaining the Software

```bash
# Clone the repository
git clone https://github.com/<org>/LanCore.git

# Or pull the Docker image
docker pull ghcr.io/<org>/lancore:latest
```

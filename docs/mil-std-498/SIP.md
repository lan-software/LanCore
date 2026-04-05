# Software Installation Plan (SIP)

**Document Identifier:** LanCore-SIP-001
**Version:** 0.1.0
**Date:** 2026-04-02
**Status:** Draft — Scaffolded
**Classification:** Unclassified

### Author

| Role | Name |
|------|------|
| Project Lead | Markus Kohn |

---

## 1. Scope

### 1.1 Identification

This Software Installation Plan (SIP) describes the plan for installing the **LanCore** software at user sites.

### 1.2 System Overview

LanCore is deployed as a Docker container stack, making installation consistent across environments.

---

## 2. Referenced Documents

- [SPS](SPS.md) — Software Product Specification
- [SSDD](SSDD.md) — System/Subsystem Design Description
- [SCOM](SCOM.md) — Software Center Operator Manual

---

## 3. Installation Overview

### 3.1 Prerequisites

| Requirement | Specification |
|-------------|--------------|
| Operating System | Linux (Ubuntu 22.04+ recommended) |
| Docker | Docker Engine 20+ with Docker Compose v2 |
| Memory | Minimum 4 GB RAM |
| Storage | Minimum 10 GB (more for file uploads) |
| Network | Public IP or domain with HTTPS (for production) |

### 3.2 Installation Methods

| Method | Description | Status |
|--------|-------------|--------|
| Docker Compose | Deploy using provided `docker-compose.yml` | Available |
| Docker Image (GHCR) | Pull pre-built image from GitHub Container Registry | Available |
| Source Build | Clone repo and build with Sail | Available |

### 3.3 Quick Start (Development with Shared Infrastructure)

```bash
# 1. Clone the monorepo
git clone <repository-url> lan-software && cd lan-software

# 2. Start shared infrastructure (PostgreSQL, Redis, Mailpit, Mockserver)
./platform/dev/setup.sh

# 3. Set up LanCore
cd LanCore
cp .env.example .env
vendor/bin/sail up -d
vendor/bin/sail artisan key:generate
vendor/bin/sail artisan webpush:vapid
vendor/bin/sail artisan migrate
vendor/bin/sail artisan db:seed

# 4. (Optional) Start additional apps
cd ../LanBrackets && cp .env.example .env && vendor/bin/sail up -d && vendor/bin/sail artisan key:generate && vendor/bin/sail artisan migrate
# etc.
```

The `.env.example` is pre-configured for the shared infrastructure — no manual editing of database, Redis, or mail settings is needed.

### 3.4 Quick Start (Production Docker Compose)

```bash
# 1. Create deployment directory
mkdir lancore && cd lancore

# 2. Download deployment files
# (docker-compose.yml and .env.example from docs/deployment/)

# 3. Configure environment
cp .env.example .env
# Edit .env with your settings (APP_URL, DB credentials, etc.)

# 4. Start services
docker compose up -d

# 5. Run migrations
docker compose exec app php artisan migrate --force

# 6. Create admin user
docker compose exec app php artisan make:admin
```

---

## 4. Detailed Installation Procedures

### 4.1 Database Setup

TBD — To be detailed with PostgreSQL configuration, user creation, and backup setup.

### 4.2 Storage Configuration

TBD — To be detailed with S3/Minio bucket creation, CORS configuration, and access policies.

### 4.3 Mail Configuration

TBD — To be detailed with SMTP provider setup and SPF/DKIM records.

### 4.4 SSL/TLS Configuration

TBD — To be detailed with certificate management (Let's Encrypt, custom certificates).

### 4.5 Stripe Configuration

TBD — To be detailed with Stripe account setup, API key configuration, and webhook endpoint registration.

---

## 5. Verification Procedures

### 5.1 Post-Installation Checks

| Check | Command/Action | Expected Result |
|-------|---------------|-----------------|
| Application responds | Visit APP_URL in browser | Welcome page loads |
| Database connected | `php artisan migrate:status` | All migrations ran |
| Cache operational | `php artisan cache:clear` | No errors |
| Queue processing | Check Horizon dashboard | Workers active |
| Storage accessible | Upload a test file | File stored and retrievable |
| Mail configured | `php artisan tinker --execute "Mail::raw(...)"` | Email received |

---

## 6. Rollback Procedures

TBD — To be detailed with database rollback steps, container version pinning, and data restore procedures.

---

## 7. Notes

Detailed production deployment documentation is in development. See `docs/deployment/` for current deployment files.

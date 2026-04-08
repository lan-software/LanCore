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

The production image built from `LanCore/Dockerfile` is a single artifact that serves all three runtime **roles** (`web`, `worker`, `all`) selected via the `ROLE` environment variable. See [SSDD §3.1.1](SSDD.md#311-deployment-architecture-production) for the full topology.

#### 3.4.1 Single-container deployment (role=all)

Suitable for small sites and demo instances:

```bash
docker run -d \
  --name lancore \
  -e ROLE=all \
  -e SKIP_MIGRATE=0 \
  -e APP_KEY="base64:..." \
  -e APP_URL=https://lan.example.org \
  -e DB_CONNECTION=pgsql \
  -e DB_HOST=postgres -e DB_DATABASE=lancore -e DB_USERNAME=lancore -e DB_PASSWORD=... \
  -e REDIS_HOST=redis \
  -p 80:80 -p 443:443 \
  ghcr.io/lan-software/lancore:latest
```

#### 3.4.2 Split-container deployment (role=web + role=worker)

Suitable for any serious production deployment. The key rule: **exactly one container** sets `SKIP_MIGRATE=0`; all others set `SKIP_MIGRATE=1` to avoid migration races.

```yaml
# docker-compose.yml (excerpt)
services:
  lancore-web-migrator:
    image: ghcr.io/lan-software/lancore:latest
    environment:
      ROLE: web
      SKIP_MIGRATE: "0"            # designated migrator — only ONE container
      OCTANE_WORKERS: "16"
      OCTANE_MAX_REQUESTS: "500"
    env_file: [.env]
    ports: ["80:80"]

  lancore-web:
    image: ghcr.io/lan-software/lancore:latest
    environment:
      ROLE: web
      SKIP_MIGRATE: "1"            # every replica
      OCTANE_WORKERS: "16"
      OCTANE_MAX_REQUESTS: "500"
    env_file: [.env]
    deploy:
      replicas: 3

  lancore-worker:
    image: ghcr.io/lan-software/lancore:latest
    environment:
      ROLE: worker
      SKIP_MIGRATE: "1"
    env_file: [.env]
```

After the stack comes up, create the first admin user:

```bash
docker compose exec lancore-web-migrator php artisan make:admin
```

#### 3.4.3 Required runtime environment variables

| Variable | Description |
|----------|-------------|
| `APP_KEY` | Laravel encryption key (`base64:...`). **Never** bake into the image — inject via secret manager. |
| `APP_URL` | Public HTTPS URL of the application. |
| `DB_*` | PostgreSQL host, database, user, password. |
| `REDIS_HOST` / `REDIS_PASSWORD` | Redis connection. |
| `ROLE` | `web`, `worker`, or `all`. |
| `SKIP_MIGRATE` | `0` for the designated migrator, `1` for everything else. |
| `OCTANE_WORKERS` | Octane worker count (default 80). Tunable without rebuilding. |
| `OCTANE_MAX_REQUESTS` | Worker recycle threshold (default 500). |
| `TICKET_TOKEN_PEPPER` | HMAC pepper for ticket nonce hashing (see [SSDD §5a.1](SSDD.md#5a1-trust-boundary)). |

#### 3.4.4 Satellite apps

LanBrackets, LanShout, LanHelp, and LanEntrance each publish their own image built from an adapted version of this Dockerfile and follow the identical `ROLE` / `SKIP_MIGRATE` contract. LanBrackets runs Octane like LanCore; LanShout/LanHelp/LanEntrance use `frankenphp php-server` without Octane and neither ships Horizon — their `worker` role runs `queue:work --tries=3 --max-time=3600` plus the scheduler. See [SSDD §3.1.1.5](SSDD.md#31115-satellite-app-topology) for the per-app matrix.

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

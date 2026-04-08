# System/Subsystem Design Description (SSDD)

**Document Identifier:** LanCore-SSDD-001
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

This System/Subsystem Design Description (SSDD) describes the system-level design of the **LanCore** system.

### 1.2 System Overview

LanCore is a monolithic web application with a Docker-based deployment model. At the system level, it comprises the application container, database, cache, storage, and queue processing subsystems.

### 1.3 Document Overview

This document describes the system-level design, including subsystem decomposition, inter-subsystem communication, and deployment topology.

---

## 2. Referenced Documents

- [SSS](SSS.md) — System/Subsystem Specification
- [SDD](SDD.md) — Software Design Description
- [SIP](SIP.md) — Software Installation Plan

---

## 3. System-Wide Design Decisions

### 3.1 Deployment Architecture (Development)

In development, all Lan-Software ecosystem apps share infrastructure services via the `platform/dev/` Docker Compose stack, connected through a single `lanparty` Docker network.

```
┌──────────────────────────────────────────────────────────────────┐
│                         Docker Host                               │
│                                                                   │
│  ══════════════════ lanparty network ═══════════════════════════  │
│  ║                                                             ║  │
│  ║  ┌───────────────────────────────────────────────────────┐  ║  │
│  ║  │  platform/dev  (shared infrastructure)                │  ║  │
│  ║  │                                                       │  ║  │
│  ║  │  infrastructure-pgsql     PostgreSQL 18  :5430        │  ║  │
│  ║  │  infrastructure-redis     Redis          :6370        │  ║  │
│  ║  │  infrastructure-mailpit   Mailpit        :1025/:8021  │  ║  │
│  ║  │  infrastructure-mockserver               :1080        │  ║  │
│  ║  └───────────────────────────────────────────────────────┘  ║  │
│  ║                                                             ║  │
│  ║  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      ║  │
│  ║  │ lancore.test  │  │ lanbrackets  │  │ lanshout     │      ║  │
│  ║  │ :80  + queue  │  │ .test  :81   │  │ .test  :82   │      ║  │
│  ║  │ + garage      │  └──────────────┘  └──────────────┘      ║  │
│  ║  └──────────────┘                                           ║  │
│  ║  ┌──────────────┐  ┌──────────────┐                         ║  │
│  ║  │ lanhelp      │  │ lanentrance  │                         ║  │
│  ║  │ .test  :83   │  │ .test  :84   │                         ║  │
│  ║  └──────────────┘  └──────────────┘                         ║  │
│  ║                                                             ║  │
│  ═══════════════════════════════════════════════════════════════  │
└──────────────────────────────────────────────────────────────────┘
```

### 3.1.1 Deployment Architecture (Production)

In production, LanCore is deployed as a Docker container stack with its own PostgreSQL database, Redis cache, object storage, and SMTP relay. The application image is built from a reproducible three-stage Dockerfile and can be launched in one of three **roles** selected at runtime via the `ROLE` environment variable.

#### 3.1.1.1 Build Stages

| Stage | Base image | Purpose |
|-------|-----------|---------|
| 1. `deps` | `composer:2` (digest-pinned) | `composer install --no-dev`, autoload optimisation, Wayfinder TypeScript generation (`actions/`, `routes/`, `wayfinder/`) |
| 2. `frontend` | `node:22-alpine` (digest-pinned) | `npm ci` + `npm run build`; overlays the Wayfinder TS produced in stage 1 |
| 3. `production` | `dunglas/frankenphp:php8.5-alpine` (digest-pinned) | FrankenPHP + Laravel Octane, PHP extensions (pdo_pgsql, bcmath, pcntl, zip, gd, opcache, intl, redis), supervisor, entrypoint |

Build-time secrets (`BUILD_APP_KEY`) are supplied only as a throwaway `ARG` for Wayfinder generation; the runtime `APP_KEY` **must** be injected by the orchestrator — never baked into the image (see [SSS](SSS.md) ENV-DEP-012).

#### 3.1.1.2 Runtime Roles

| `ROLE` | Supervisor config | Processes | Intended use |
|--------|-------------------|-----------|--------------|
| `web` | `supervisord-web.conf` | Octane (FrankenPHP workers) | Horizontally scalable HTTP tier |
| `worker` | `supervisord-worker.conf` | Horizon + scheduler (`schedule:run` loop) | Dedicated queue / cron tier |
| `all` | `supervisord.conf` | Octane + Horizon + scheduler | Single-container deployments (small sites, demo) |

Octane worker count and max-request recycling are parametrised via `OCTANE_WORKERS` and `OCTANE_MAX_REQUESTS` environment variables so operators can tune without rebuilding the image.

#### 3.1.1.3 Migration Ownership

The entrypoint runs `php artisan migrate --force` unless `SKIP_MIGRATE=1`. In multi-container deployments **exactly one** container (typically a dedicated `web` or init container) must run with `SKIP_MIGRATE=0`; all others must set `SKIP_MIGRATE=1` to prevent schema races. See [SIP §3.4](SIP.md#34-quick-start-production-docker-compose) for the deployment pattern.

#### 3.1.1.4 Topology Diagram

```
┌───────────────────────────────────────────────────────────────┐
│                        Docker Host                             │
│                                                                 │
│  ┌──────────────────┐  ┌──────────────────┐  ┌──────────────┐ │
│  │ LanCore (ROLE=   │  │ LanCore (ROLE=   │  │  PostgreSQL  │ │
│  │  web, migrator)  │  │  web, replica)   │  │              │ │
│  │ FrankenPHP+Octane│  │ FrankenPHP+Octane│  └──────────────┘ │
│  │ SKIP_MIGRATE=0   │  │ SKIP_MIGRATE=1   │  ┌──────────────┐ │
│  └────────┬─────────┘  └────────┬─────────┘  │    Redis     │ │
│           │                     │             └──────────────┘ │
│  ┌────────┴─────────────────────┴─────┐     ┌──────────────┐ │
│  │    LanCore (ROLE=worker)           │     │  S3 / Garage │ │
│  │    Horizon + scheduler             │     └──────────────┘ │
│  │    SKIP_MIGRATE=1                  │     ┌──────────────┐ │
│  └────────────────────────────────────┘     │     SMTP     │ │
│                                               └──────────────┘ │
└───────────────────────────────────────────────────────────────┘
```

#### 3.1.1.5 Satellite App Topology

LanBrackets, LanShout, LanHelp, and LanEntrance each ship their **own** Dockerfile that follows the same three-stage pattern and `ROLE`/`SKIP_MIGRATE` contract. Differences:

| App | Base image | Octane | Horizon | Typical roles |
|-----|-----------|--------|---------|---------------|
| LanCore | `frankenphp:php8.5-alpine` | Yes | Yes | `web` + `worker` (split) or `all` |
| LanBrackets | `frankenphp:php8.5-alpine` | Yes | No (plain `queue:work` + scheduler) | `web` + optional `worker` |
| LanHelp | `frankenphp:php8.5-alpine` | No (`frankenphp php-server`) | No | `web` + optional `worker` |
| LanEntrance | `frankenphp:php8.5-alpine` | No (`frankenphp php-server`) | No | `web` + optional `worker` |
| LanShout | `frankenphp:php8.5-alpine` (after Laravel 13 upgrade prerequisite) | No | No | `web` + optional `worker` |

All five images: non-root (`www-data`), pinned base image digests, healthcheck on `/up`, runtime secrets via env only.

### 3.2 Subsystem Inventory

| Subsystem | Technology | Purpose | Shared in Dev |
|-----------|-----------|---------|---------------|
| Application Server | FrankenPHP + Laravel Octane | HTTP request handling | No (per-app) |
| Queue Workers | Horizon + Supervisor | Background job processing | No (LanCore only) |
| Database | PostgreSQL 18 | Persistent data storage | Yes (one instance, per-app databases) |
| Cache | Redis 7+ | Caching, sessions, rate limiting | Yes (shared instance) |
| Object Storage | S3-compatible (Garage) | File and image storage | No (LanCore only) |
| Mail | SMTP (Mailpit in dev) | Transactional email delivery | Yes (shared Mailpit) |

---

## 4. System Architectural Design

### 4.1 Subsystem Communication

In development, all app containers and shared infrastructure services communicate over a single Docker network (`lanparty`). Service discovery uses Docker DNS with container names as hostnames.

| From | To | Hostname | Port | Protocol |
|------|----|----------|------|----------|
| Any app | PostgreSQL | `infrastructure-pgsql` | 5432 | TCP (libpq) |
| Any app | Redis | `infrastructure-redis` | 6379 | TCP (RESP) |
| Any app | Mailpit SMTP | `infrastructure-mailpit` | 1025 | TCP (SMTP) |
| Any app | LanCore | `lancore.test` | 80 | HTTP |
| LanCore | Garage S3 | `garage` | 3900 | HTTP (S3 API) |

### 4.2 Scaling Considerations

TBD — To be detailed with horizontal scaling patterns (multiple Octane workers, Redis Cluster, read replicas).

### 4.3 High Availability

TBD — To be detailed with health check endpoints, container orchestration (Kubernetes/Swarm), and backup strategies.

---

## 5. System Detailed Design

### 5.1 Network Architecture

Development uses a single external Docker bridge network (`lanparty`) for all inter-service communication. The `infrastructure` network has been eliminated to reduce complexity. Each app may maintain an internal `sail` network for app-specific services (e.g., LanCore's Garage).

Production network architecture (TLS termination, reverse proxy) is TBD.

### 5.3 Container Security Design

Production container images for LanCore and all satellite apps share a common security posture:

- All supervised application processes (Octane/FrankenPHP, Horizon / `queue:work`, the scheduler loop) are declared with `user=www-data` in the supervisor configs and therefore run as the unprivileged user. supervisord itself runs as PID 1 under root — this is required so it can open `/dev/stdout` and `/dev/stderr` for its child programs — but it has no network exposure and no external attack surface.
- Base images are pinned by `@sha256:` digest to guarantee reproducible builds and to prevent silent upstream drift.
- Runtime secrets (`APP_KEY`, database credentials, pepper for ticket token HMAC — see §5a.1, Stripe keys, S3 credentials) are injected exclusively through environment variables at container start; no secret is ever baked into a layer.
- `expose_php=Off`, OPcache with `validate_timestamps=0`, and a tuned `memory_limit` are enforced via `docker/php/*.ini`.
- Only the single designated migrator container runs with `SKIP_MIGRATE=0`; all other containers ship schema migrations disabled to prevent races.
- The `/up` healthcheck endpoint is wired to the Docker `HEALTHCHECK` directive with a 60 s start period to allow Octane cold boot + migrations on the migrator container.

These rules trace to SSS requirements ENV-DEP-010, ENV-DEP-011, and ENV-DEP-012.

### 5.2 Monitoring Integration

| Component | Tool | Status |
|-----------|------|--------|
| Application Metrics | Prometheus (spatie/laravel-prometheus) | Implemented |
| Application Monitoring | Laravel Pulse | Implemented |
| Debug/Development | Laravel Telescope | Implemented |
| Log Aggregation | TBD | Planned |
| Alerting | TBD | Planned |

---

## 5a. Ticket Token Security Architecture

### 5a.1 Trust Boundary

LanCore is the sole token **issuer**. LanEntrance is a token **verifier** only. The trust boundary is enforced as follows:

| Capability | LanCore | LanEntrance |
|-----------|---------|-------------|
| Holds Ed25519 private keys | Yes | Never |
| Holds HMAC pepper | Yes | Never |
| Issues new LCT1 tokens | Yes | Never |
| Verifies Ed25519 signatures | Yes (authoritative) | Yes (fast pre-check only) |
| Can query ticket database | Yes | No (calls `/api/entrance/validate`) |
| Can forge new tokens | Yes (by design — issuer) | No |

A compromised LanEntrance instance can replay scanned tokens within their validity window but cannot generate new valid tokens or query the nonce hash directly, because it holds no private key and no pepper.

### 5a.2 Sequence Diagram — Token Issuance on Assignment Change

```
UpdateTicketAssignments (addUser / removeUser / updateManager)
  │
  ├── TicketTokenService::issue(ticket)
  │     ├── Generate 128-bit CSPRNG nonce
  │     ├── Derive nonce_hash = HMAC-SHA256(nonce, pepper)
  │     ├── Build body = base64url(json({tid, nonce, iat, exp, evt}))
  │     ├── sig = sodium_crypto_sign_detached("LCT1." + kid + "." + body, sk[kid])
  │     ├── token = "LCT1." + kid + "." + body + "." + base64url(sig)
  │     └── Persist: nonce_hash, kid, issued_at, expires_at to tickets row
  │
  ├── dispatch(GenerateTicketPdf(ticket, qrPayload: token))
  │     └── PDF job embeds token in QR code; token not persisted
  │
  └── Return (caller proceeds)
```

The `qrPayload` is passed as a constructor argument to `GenerateTicketPdf`. The job never persists it beyond its own execution; it is rendered into the QR image and discarded.

### 5a.3 Sequence Diagram — Validation Scan

```
LanEntrance                       LanCore
     │                                │
     │── POST /api/entrance/validate ──►
     │   { token: "LCT1.kid.body.sig" }
     │                                │
     │                    Parse token segments
     │                    Lookup kid in TicketKeyRing
     │                    Verify sig via sodium_crypto_sign_verify_detached
     │                    Decode body → extract nonce, tid, exp, evt
     │                    Derive nonce_hash = HMAC-SHA256(nonce, pepper)
     │                    SELECT ticket WHERE validation_nonce_hash = nonce_hash
     │                    Check exp > now()
     │                    Check ticket.status
     │                                │
     │◄── 200 { decision: "valid" ... }
     │
```

Failure paths return one of: `invalid_signature`, `unknown_kid`, `expired`, `revoked`, `already_checked_in`, `invalid` — all as HTTP 200 with a structured `decision` field.

### 5a.4 Sequence Diagram — Key Rotation

```
Admin
  │
  ├── php artisan tickets:keys:rotate
  │     ├── Generate new Ed25519 key pair
  │     ├── Assign kid = "key-YYYY-MM" (or custom label)
  │     ├── Write private key → storage/keys/ticket_signing/{kid}.key (mode 0600)
  │     ├── Register new kid as active in TicketKeyRing config/DB record
  │     └── Print new kid to console
  │
  └── LanEntrance (on next JWKS cache refresh)
        ├── GET /api/entrance/signing-keys
        │     └── Returns [ new_kid_public_key, ...retired_unexpired_public_keys ]
        └── Updates local key cache
```

Previously issued tokens signed by the retired `kid` remain verifiable because retired public keys stay in the JWKS response until all tokens signed by them have expired (`validation_expires_at < now()`).

---

## 6. Requirements Traceability

| SSS Requirement | SSDD Section |
|----------------|-------------|
| SSS 3.7 Environment | Section 3 |
| SSS 3.4 Internal Interfaces | Section 4.1 |
| SSS 3.9 Quality Factors (Scalability) | Section 4.2 |
| SSS 3.1 Required States and Modes (Demo) | Section 3.1 — Demo mode uses the same Docker deployment topology as Normal mode; the distinction is data content (seeded via `SeedDemoCommand`) and payment provider configuration (simulated), not infrastructure topology |
| CAP-TKT-013, CAP-TKT-014 | Section 5a |
| SEC-014..020 | Section 5a.1 |
| ENV-DEP-010 (pinned base images) | Section 3.1.1.1, Section 5.3 |
| ENV-DEP-011 (non-root runtime) | Section 3.1.1, Section 5.3 |
| ENV-DEP-012 (runtime secrets via env) | Section 3.1.1.1, Section 5.3 |

---

## 7. Notes

This document will be expanded as LanCore moves from PoC to production deployment.

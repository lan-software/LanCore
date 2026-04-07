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

In production, LanCore is deployed as a standalone Docker container stack with its own database, cache, and storage.

```
┌─────────────────────────────────────────┐
│              Docker Host                 │
│                                          │
│  ┌──────────────────┐  ┌──────────────┐ │
│  │  LanCore App     │  │  PostgreSQL  │ │
│  │  (FrankenPHP +   │  │  Database    │ │
│  │   Laravel Octane) │  │             │ │
│  └────────┬─────────┘  └──────────────┘ │
│           │                              │
│  ┌────────┴─────────┐  ┌──────────────┐ │
│  │  Horizon Workers  │  │    Redis     │ │
│  │  (Queue Processing│  │  (Cache)     │ │
│  │   via Supervisor) │  │             │ │
│  └──────────────────┘  └──────────────┘ │
│                                          │
│  ┌──────────────────┐  ┌──────────────┐ │
│  │  S3/Garage       │  │  SMTP        │ │
│  │  (Object Storage) │  │  (Mail)      │ │
│  └──────────────────┘  └──────────────┘ │
└─────────────────────────────────────────┘
```

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

### 5.2 Monitoring Integration

| Component | Tool | Status |
|-----------|------|--------|
| Application Metrics | Prometheus (spatie/laravel-prometheus) | Implemented |
| Application Monitoring | Laravel Pulse | Implemented |
| Debug/Development | Laravel Telescope | Implemented |
| Log Aggregation | TBD | Planned |
| Alerting | TBD | Planned |

---

## 6. Requirements Traceability

| SSS Requirement | SSDD Section |
|----------------|-------------|
| SSS 3.7 Environment | Section 3 |
| SSS 3.4 Internal Interfaces | Section 4.1 |
| SSS 3.9 Quality Factors (Scalability) | Section 4.2 |
| SSS 3.1 Required States and Modes (Demo) | Section 3.1 — Demo mode uses the same Docker deployment topology as Normal mode; the distinction is data content (seeded via `SeedDemoCommand`) and payment provider configuration (simulated), not infrastructure topology |

---

## 7. Notes

This document will be expanded as LanCore moves from PoC to production deployment.

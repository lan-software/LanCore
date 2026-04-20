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
| 3. `production` | `dunglas/frankenphp:php8.3-alpine` (digest-pinned) | FrankenPHP + Laravel Octane, PHP extensions (pdo_pgsql, bcmath, pcntl, zip, gd, opcache, intl, redis), supervisor, entrypoint |

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
| LanCore | `frankenphp:php8.3-alpine` | Yes | Yes | `web` + `worker` (split) or `all` |
| LanBrackets | `frankenphp:php8.3-alpine` | Yes | No (plain `queue:work` + scheduler) | `web` + optional `worker` |
| LanHelp | `frankenphp:php8.3-alpine` | No (`frankenphp php-server`) | No | `web` + optional `worker` |
| LanEntrance | `frankenphp:php8.3-alpine` | No (`frankenphp php-server`) | No | `web` + optional `worker` |
| LanShout | `frankenphp:php8.3-alpine` | No (`frankenphp php-server`) | No | `web` + optional `worker` |

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
| Integration Client Library | Composer package `lan-software/lancore-client` | Shared HTTP client, webhook verification, SSO exchange, and entrance sub-client consumed by all Lan\* satellites in place of per-app implementations | Published on Packagist; consumed via `composer require` in each satellite |

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

Satellite-to-LanCore HTTP communication (SSO exchange, user resolution, entrance operations, webhook receipt) is not implemented independently in each satellite. All five satellites (LanBrackets, LanEntrance, LanShout, LanHelp, and the forthcoming LanChart / LanBase) route their LanCore interactions through the shared `lan-software/lancore-client` Composer package, which provides the `LanCoreClient` service, `VerifyLanCoreWebhook` middleware, and abstract webhook controllers. See §5.4 for the client library architecture.

### 4.2 Scaling Considerations

TBD — To be detailed with horizontal scaling patterns (multiple Octane workers, Redis Cluster, read replicas).

### 4.3 High Availability

Production Kubernetes deployments (via the `lan-software` Helm umbrella chart) achieve HA through the following baseline topology:

- **Liveness / readiness probes** — every application pod exposes `GET /up`. Readiness probe period 5 s gates Service traffic; liveness probe period 30 s triggers container restart. Octane-flavoured pods (LanCore, LanBrackets) additionally carry a startup probe with a 60 s failure threshold to absorb cold-boot time before liveness evaluation begins.
- **Pod disruption budget** — each Deployment with ≥ 2 replicas carries a `PodDisruptionBudget` with `minAvailable: 1` so voluntary evictions cannot take the service offline.
- **PostgreSQL HA** — the default configuration provisions a CloudNativePG `Cluster` with three instances (one primary, two streaming replicas). Scheduled base backups + WAL archiving to S3 (`barmanObjectStore`) provide point-in-time recovery. Bumping the operator-managed `Cluster` triggers a zero-downtime rolling restart.
- **In-memory store HA** — the default configuration provisions a Dragonfly Operator `Dragonfly` CR. Dragonfly speaks the Redis wire protocol so Laravel's phpredis client (Horizon, sessions, cache, queue) is transparent. Persistence is enabled by default to survive pod restarts; snapshots are written to a PVC backed by the cluster's default StorageClass.
- **Stateless web tier** — the LanCore web Deployment and each satellite's web/server Deployment are horizontally scalable. The `HorizontalPodAutoscaler` targets 70 % CPU utilisation on the web tier. The worker tier does **not** use HPA: Horizon (LanCore) and `queue:work` (satellites) manage their own concurrency via `config/horizon.php` or worker-process counts.
- **Migration ownership** — exactly one release-scoped `Job` runs `php artisan migrate --force` as a `helm.sh/hook: pre-install,pre-upgrade` hook with `hook-weight: -5`. All web and worker pods set `SKIP_MIGRATE=1` so the "exactly one migrator per release" invariant from §3.1.1.3 is preserved in Kubernetes.

Backup strategy is detailed in the SCOM §4.x Kubernetes operations section.

### 4.3.1 Topology

The Kubernetes topology mirrors §3.1.1.4 with the following adaptations:

- The Docker network becomes a Kubernetes Namespace (default: `lan-software`) with a default-deny `NetworkPolicy` plus explicit allow rules (see §5.1).
- Each app has one `Service` (ClusterIP) per component (web/worker). Satellite web Services are addressable by other pods as `{app}-web.{ns}.svc.cluster.local`. LanCore's internal Service DNS is used by satellites to populate `LANCORE_INTERNAL_URL`, separating east-west pod-to-pod traffic from the external ingress URL (`LANCORE_BASE_URL`).
- One Ingress per app terminates TLS via cert-manager; the default issuer is `letsencrypt-prod` (production) or a self-signed `ClusterIssuer` (dev).
- Stateful infrastructure runs as operator-managed Custom Resources: `postgresql.cnpg.io/v1 Cluster` (CloudNativePG), `dragonflydb.io/v1alpha1 Dragonfly` (Dragonfly Operator), optional `minio.min.io/v2 Tenant` (MinIO Operator).

---

## 5. System Detailed Design

### 5.1 Network Architecture

#### 5.1.1 Development (Docker)

Development uses a single external Docker bridge network (`lanparty`) for all inter-service communication. The `infrastructure` network has been eliminated to reduce complexity. Each app may maintain an internal `sail` network for app-specific services (e.g., LanCore's Garage).

#### 5.1.2 Production (Kubernetes)

Production Kubernetes deployments use:

- **Ingress**: a cluster-wide Ingress controller (default: `ingress-nginx`) terminates external TLS and routes by host. Each app ships a `networking.k8s.io/v1 Ingress` resource; LanCore additionally carries admin paths (`/horizon`, `/pulse`) that are either annotation-gated (Phase 1 basic-auth) or SSO-protected (Phase 2+ oauth2-proxy sidecar / OIDC annotations).
- **TLS**: `cert-manager` `Certificate` resources are emitted per Ingress host, referencing a cluster-wide `ClusterIssuer` (default `letsencrypt-prod`). The Helm chart does not create issuers in production mode; a self-signed `ClusterIssuer` is optionally emitted for development clusters.
- **Internal DNS**: East-west pod traffic uses the standard `{service}.{namespace}.svc.cluster.local` DNS. Satellites resolve LanCore's internal URL via `LANCORE_INTERNAL_URL=http://lancore-web.lan-software.svc.cluster.local`, distinct from the external `LANCORE_BASE_URL` used for browser redirects and public API calls.
- **NetworkPolicy (default-deny)**: every app's namespace runs with a default-deny ingress and egress `NetworkPolicy`, amended by explicit allow rules:
  - Ingress from the `ingress-nginx` namespace to the web/server Service ports
  - Ingress from the Prometheus Operator namespace to `/metrics` scrape ports
  - Ingress from the OTel collector namespace to application ports (where applicable)
  - Inter-app ingress from satellite namespaces to `lancore-web` (for `LANCORE_INTERNAL_URL` calls and webhook callbacks)
  - Egress to kube-dns (53/UDP)
  - Egress to the CloudNativePG `-rw` service (5432/TCP)
  - Egress to the Dragonfly Service (6379/TCP)
  - Egress to the S3 endpoint (443/TCP or the Tenant Service port)
  - Egress to the internet for SMTP (587/TCP), Stripe (443/TCP), WebPush (443/TCP), and TMT2 (operator-configurable)
- **Reverse proxy**: TLS is terminated at the Ingress controller; pods receive plaintext HTTP on their service ports (FrankenPHP 8080 or as configured by `image.port`). No sidecar reverse proxy runs inside the pod.

The Helm umbrella chart ships `NetworkPolicy` templates for every sub-chart; the allow-lists are parametrised via `networkPolicy.allowFrom` and `networkPolicy.allowToEgress` values.

#### 5.1.3 Hostname Derivation

The Helm umbrella chart computes every external hostname from three global values so operators flip the entire stack's DNS with a single edit:

| Global value                    | Role                                                          | Default                            |
|---------------------------------|---------------------------------------------------------------|------------------------------------|
| `global.domain`                 | Canonical deployment domain (required)                        | —                                  |
| `global.lancoreHost`            | Hostname for LanCore itself                                   | `lancore.{{ .global.domain }}`     |
| `global.satelliteHostStyle`     | `flat` \| `prefixed` \| `custom`                              | `flat`                             |
| `global.integrations.<slug>.host` | Per-satellite override (required when `style: custom`)       | computed                           |

- `flat`: satellites live at `<slug>.<domain>` (e.g. `lanhelp.lanparty.de`).
- `prefixed`: satellites live at `<slug>.lancore.<domain>` (e.g. `lanhelp.lancore.lanparty.de`). Common when LanCore runs at the apex (`global.lancoreHost: lanparty.de`).
- `custom`: the chart fails-fast unless the operator supplies `global.integrations.<slug>.host` for every enabled satellite.

Derived URLs consumed by applications:

- `LANCORE_BASE_URL = <scheme>://<lancoreHost>`
- `LANCORE_INTERNAL_URL = http://lancore-web.<namespace>.svc.cluster.local` (in-cluster; bypasses external DNS)
- Per satellite `s`: `LANCORE_CALLBACK_URL = <scheme>://<satellite-host>/auth/callback`

The identical host table is fed into LanCore's `config/integrations.php` (via env vars `LANCORE_DOMAIN`, `LANCORE_HOST`, `LANCORE_SATELLITE_HOST_STYLE`, `<SLUG>_HOST`) so the reconciler and the Ingress rules always agree. See §5.4.5 for the reconciler.

### 5.3 Container Security Design

Production container images for LanCore and all satellite apps share a common security posture:

- All supervised application processes (Octane/FrankenPHP, Horizon / `queue:work`, the scheduler loop) are declared with `user=www-data` in the supervisor configs and therefore run as the unprivileged user. supervisord itself runs as PID 1 under root — this is required so it can open `/dev/stdout` and `/dev/stderr` for its child programs — but it has no network exposure and no external attack surface.
- Base images are pinned by `@sha256:` digest to guarantee reproducible builds and to prevent silent upstream drift.
- Runtime secrets (`APP_KEY`, database credentials, pepper for ticket token HMAC — see §5a.1, Stripe keys, S3 credentials) are injected exclusively through environment variables at container start; no secret is ever baked into a layer.
- `expose_php=Off`, OPcache with `validate_timestamps=0`, and a tuned `memory_limit` are enforced via `docker/php/*.ini`.
- Only the single designated migrator container runs with `SKIP_MIGRATE=0`; all other containers ship schema migrations disabled to prevent races.
- The `/up` healthcheck endpoint is wired to the Docker `HEALTHCHECK` directive with a 60 s start period to allow Octane cold boot + migrations on the migrator container.

These rules trace to SSS requirements ENV-DEP-010, ENV-DEP-011, and ENV-DEP-012.

### 5.4 Integration Client Library Architecture

The `lan-software/lancore-client` Composer package is the canonical implementation of the LanCore Integration API consumer side. It exists to eliminate the per-satellite duplication of HTTP client code, webhook signature verification, SSO exchange, exception handling, and (for LanEntrance) JWKS caching that previously lived as independently-maintained copies inside each satellite's `app/Services/LanCoreClient.php`.

#### 5.4.1 Ownership and Distribution

- **Source of truth (documentation):** this repository, under `docs/mil-std-498/`. No duplicate MIL-STD-498 documentation is maintained in the package repo.
- **Source of truth (code):** `https://github.com/lan-software/lancore-client`, a standalone public repository published on Packagist as `lan-software/lancore-client`.
- **Versioning:** independent SemVer. Satellites adopt new capabilities by bumping the Composer constraint.
- **Release coupling:** the package is versioned independently from LanCore itself. Breaking changes to the Integration API in LanCore require a coordinated major-version bump of the package.

#### 5.4.2 Package Boundary

The package owns **transport and protocol** concerns:

| Concern | Component |
|---------|-----------|
| HTTP transport, retries, timeouts, Bearer authentication | `LanCoreClient` |
| Typed response shapes | `LanCoreUser` DTO, entrance DTOs (`AttendeeTicket`, `CheckinResult`, `EntranceStats`, `SigningKey`) |
| Error taxonomy | `LanCoreException` base + `LanCoreDisabled`, `LanCoreUnavailable`, `LanCoreRequest`, `InvalidLanCoreUser` |
| Webhook signature verification | `VerifyLanCoreWebhook` middleware (HMAC-SHA256 over request body, event header allowlist) |
| Webhook payload parsing | One typed payload DTO per `WebhookEvent` enum case |
| Abstract webhook controllers | One base controller per event type with abstract hooks for user resolution and event handling |
| SSO authorization URL construction and code exchange | `LanCoreClient::ssoAuthorizeUrl()`, `LanCoreClient::exchangeCode()` |
| Opt-in entrance sub-client | `LanCoreClient->entrance()` returning `EntranceClient` with JWKS caching |
| Laravel integration | `LanCoreServiceProvider` — publishes config, binds singleton, registers middleware alias |
| Test ergonomics | `LanCoreClient::fake()` factory for Pest/PHPUnit |

Satellites retain ownership of all **domain and policy** concerns: how roles map to local user models (enum vs. pivot table), how users are resolved from LanCore IDs (dedicated `lancore_user_id` column vs. `external_provider` + `external_id` pair), display-name preservation rules, shadow-user creation, and per-app business response to webhook events. The abstract webhook controllers expose these as `resolveUser()` and `syncRoles()` (or equivalent) template methods.

#### 5.4.3 Dependency Topology

```
        ┌─────────────────────────────────────────────────┐
        │               LanCore (server)                   │
        │  routes/api-integrations.php                     │
        │  app/Domain/Integration/*                        │
        │  app/Domain/Webhook/Actions/DispatchWebhooks     │
        └───────────────┬─────────────────────────────────┘
                        │ HTTPS (Bearer token)
                        │ + outbound webhooks (HMAC-signed)
                        │
        ┌───────────────┴─────────────────────────────────┐
        │      lan-software/lancore-client (package)       │
        │                                                  │
        │   LanCoreClient  ──  EntranceClient (opt-in)     │
        │   VerifyLanCoreWebhook middleware                │
        │   HandlesLanCore*Webhook abstract controllers    │
        │   LanCoreUser DTO, Exception hierarchy           │
        └───────────────┬──────────────────────────────────┘
                        │ composer require
                        │
   ┌──────┬──────┬──────┼──────┬──────┬──────┐
   │      │      │      │      │      │      │
LanBrackets LanEntrance LanShout LanHelp LanChart LanBase
(satellite domain code: role models, user sync services,
 webhook event handlers, UI, per-app policies)
```

#### 5.4.4 Configuration Contract

Every satellite consuming the package resolves the following environment variables into a unified `config/lancore.php`:

| Variable | Purpose |
|----------|---------|
| `LANCORE_ENABLED` | Master kill-switch; when `false` the client throws `LanCoreDisabledException` |
| `LANCORE_BASE_URL` | Browser-facing LanCore base URL (used for SSO redirects) |
| `LANCORE_INTERNAL_URL` | Server-to-server LanCore base URL (falls back to `LANCORE_BASE_URL`) |
| `LANCORE_TOKEN` | Bearer token issued by LanCore integration registration |
| `LANCORE_APP_SLUG` | Satellite identity slug (e.g. `lanbrackets`) |
| `LANCORE_CALLBACK_URL` | SSO callback URL on the satellite |
| `LANCORE_WEBHOOK_SECRET` | HMAC-SHA256 secret for webhook signature verification (single secret covers all event types; replaces the legacy per-event `LANCORE_ROLES_WEBHOOK_SECRET`) |
| `LANCORE_TIMEOUT`, `LANCORE_RETRIES`, `LANCORE_RETRY_DELAY` | HTTP tuning |
| `LANCORE_ENTRANCE_ENABLED` and `LANCORE_SIGNING_KEYS_*` | Opt-in entrance sub-client + JWKS cache configuration (LanEntrance only) |

#### 5.4.5 Integration Declarative Config Reconciler

LanCore supports declarative integration-app registration via `config/integrations.php`. This file describes the full set of integration apps that the operator wants to exist in the database, including their scopes, navigation hints, webhook subscriptions, pre-shared tokens, and pre-shared webhook secrets. It is the canonical source of truth for Kubernetes (Helm-driven) deployments and, optionally, for Docker Compose deployments that enable `LANCORE_INTEGRATIONS_RECONCILE_ON_BOOT`.

**File shape.** Every value in `config/integrations.php` reads from an environment variable with a sensible default, so operators configure the whole subsystem through env vars (provided by the umbrella Helm chart) rather than by editing the file:

```php
return [
    'domain'                => env('LANCORE_DOMAIN'),
    'lancore_host'          => env('LANCORE_HOST'),
    'satellite_host_style'  => env('LANCORE_SATELLITE_HOST_STYLE', 'flat'),
    'scheme'                => env('LANCORE_SATELLITE_SCHEME', 'https'),
    'reconcile_on_boot'     => env('LANCORE_INTEGRATIONS_RECONCILE_ON_BOOT', false),
    'apps' => [
        '<slug>' => [
            'name', 'description', 'host', 'callback_path', 'scopes',
            'nav_url', 'nav_icon', 'nav_label',
            'send_announcements', 'announcement_path',
            'send_role_updates',  'roles_path',
            'token', 'announcement_webhook_secret', 'roles_webhook_secret',
        ],
    ],
];
```

**Reconciliation flow.** `php artisan integrations:sync` (typically invoked by a Helm `pre-install,pre-upgrade` hook Job on the LanCore sub-chart) performs, for each configured slug, within a single DB transaction:

1. `IntegrationApp::updateOrCreate([slug], <config-derived fields>)` — populates `name`, `description`, `callback_url`, `allowed_scopes`, `is_active=true`, `nav_*`, `send_announcements`, `announcement_endpoint`, `send_role_updates`, `roles_endpoint`. Matches SSS ENV-DEP-018 (secrets injection).
2. **Deletes** all existing `IntegrationToken` rows for this app. If the config supplies a non-empty `token`, writes a fresh token row with `name='config-seeded'`, `token=hash('sha256', $plaintext)`, `plain_text_prefix=substr($plaintext, 0, 8)`, `expires_at=null`.
3. **Deletes** the app's existing `Webhook` rows and invokes `SyncIntegrationWebhooks` with the config-supplied announcement and roles webhook secrets.

**Scope policy.** Slugs listed in `config('integrations.apps')` are *config-managed*. Slugs NOT listed are *UI/Artisan-managed* and the reconciler does not touch them. This permits both operating models to coexist: the Helm-shipped satellites are config-managed; bespoke operator integrations created through the admin UI remain UI-managed.

**Host derivation.** When `host` is empty on an app entry, the reconciler computes it from `domain` + `satellite_host_style`:

| Style      | Computed host                        |
|-----------|----------------------------------------|
| `flat`    | `<slug>.<domain>`                      |
| `prefixed`| `<slug>.lancore.<domain>`              |
| `custom`  | app entry MUST supply `host` or the reconciler fails |

`lancore_host` defaults to `lancore.<domain>` when unset. `callback_url` is computed as `<scheme>://<host><callback_path>`. `nav_url` defaults to `<scheme>://<host>`.

**Destructive-by-design.** Because tokens are delete-then-insert for every managed slug, each `integrations:sync` run invalidates any bearer tokens that satellites currently hold *unless* the Helm-seeded Secret value is stable (which it is across upgrades, thanks to Helm's `lookup` idiom on the umbrella seed Secret). Operators coordinating a manual token rotation must restart the affected satellite Deployments; the Helm chart does this automatically because the seed Secret hash-checksum is part of the Deployment template annotation.

**Logging.** Per INT-F-014, the reconciler logs each row change at INFO level, tagged with `LANCORE_RELEASE_NAME`, `LANCORE_RELEASE_REVISION` (when set by the Helm chart), and a UTC timestamp, so operators can correlate token invalidations with a specific release.

### 5.6 Key Storage Design (Kubernetes)

In Docker Compose deployments the Ed25519 ticket-signing keyring lives at `storage/keys/ticket_signing/{kid}.key` on a host-mounted volume, and `php artisan tickets:keys:rotate` writes new keys directly to the same path (see §5a.4 for the rotation sequence).

In Kubernetes deployments this contract is preserved but the backing store is different:

- **Secret-backed, read-only mount**: the keyring is held in a Kubernetes `Secret` named `lancore-ticket-signing-keys` in the release namespace. The Secret contains one data key per `kid` with filename `{kid}.key`. The Secret is mounted into every LanCore web and worker pod as a `projected` volume at `/app/storage/keys/ticket_signing/` with `defaultMode: 0400` (owner-read only). The volume is read-only; application code reads the key files at Octane boot via `TicketKeyRing` (§5a.1) exactly as in the Docker deployment — no application changes are required.
- **Rotation via Job, not in-container**: key rotation does not run inside a web or worker pod (their mount is read-only). Instead, `php artisan tickets:keys:rotate` runs as a dedicated Kubernetes `Job` with a writable `Secret` mount (achieved by mounting via `secret` volume with a ServiceAccount that has `patch secrets` RBAC in the release namespace). The Job:
  1. Generates the new Ed25519 keypair.
  2. Patches the `lancore-ticket-signing-keys` Secret with a new `{kid}.key` entry.
  3. Publishes the new public key into the JWKS endpoint's backing store (database row, per §5a.1).
  4. Bumps a pod-template annotation (e.g. `lan-software.mawiguko.dev/key-rotation: <timestamp>`) on the LanCore web Deployment, triggering a Kubernetes rolling restart so all pods pick up the new key material into the in-memory `TicketKeyRing` cache.
- **Retired keys retained**: retired `kid` entries remain in the Secret (and in the JWKS verify set) until all tokens signed with them have expired. This matches the Docker-side rotation policy and preserves the §5a.4 invariant that previously-issued tokens remain valid after rotation.
- **Dev escape hatch**: for local `kind`-cluster development, the Helm chart supports an optional `ticketing.signingKeys.inlineKeys` values map (`{kid: base64-encoded-key}`) which is templated into the Secret by the chart itself. This is explicitly opt-in and disabled by default; production deployments use an out-of-band Secret (`kubectl create secret` or External Secrets Operator).
- **RBAC**: only the rotation Job's ServiceAccount has `patch secrets` on `lancore-ticket-signing-keys`. The LanCore web/worker ServiceAccount has no Secret-write permissions. The Helm chart emits scoped Role + RoleBinding resources, no ClusterRole.

This design satisfies SSS requirement ENV-DEP-020 (readonly keyring mount, rotation via Job, rolling restart) and preserves the §5a.4 operator flow; the only observable difference from Docker is the medium (Kubernetes Secret vs. host-mounted filesystem) and the rotation execution environment (Job vs. `docker exec`).

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
| CAP-INT-001..006 (server-side integration registration, API tokens, SSO, webhook subscriptions, navigation hints, access logging) | Section 4.1 (network topology), Section 5.4 (consumer-side client library) |
| CAP-WHK-001..004 (webhook registration, signing, delivery tracking, event types) | Section 4.1 (outbound delivery path), Section 5.4.2 (consumer-side verification + abstract controllers) |
| CAP-ICLIB-001..005 (shared Integration Client Library) | Section 3.2 (subsystem inventory), Section 5.4 (architecture) |

---

## 7. Notes

This document will be expanded as LanCore moves from PoC to production deployment.

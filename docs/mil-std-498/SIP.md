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
| Helm Chart (Kubernetes) | `lan-software` umbrella chart at `oci://ghcr.io/lan-software/charts/lan-software` — deploys LanCore plus all satellites with operator-managed Postgres, Dragonfly, and S3 | Available |

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

### 3.5 Quick Start (Production Kubernetes via Helm)

The `lan-software` Helm umbrella chart packages LanCore plus all satellite apps into a single `helm install` on Kubernetes 1.29+. The chart is published as an OCI artifact at `oci://ghcr.io/lan-software/charts/lan-software` and depends on the following cluster-scoped prerequisites, which are NOT installed by the chart:

| Prerequisite | Purpose | Install reference |
|--------------|---------|-------------------|
| Kubernetes 1.29+ with RBAC enabled | Target cluster | Cluster-provider-specific |
| `ingress-nginx` (or any Ingress controller) | External traffic + TLS termination | `helm install ingress-nginx ingress-nginx/ingress-nginx -n ingress-nginx --create-namespace` |
| `cert-manager` | TLS certificate lifecycle | `helm install cert-manager jetstack/cert-manager -n cert-manager --create-namespace --set crds.enabled=true` |
| CloudNativePG operator | PostgreSQL CR controller | `helm install cnpg cnpg/cloudnative-pg -n cnpg-system --create-namespace` |
| Dragonfly Operator | Dragonfly CR controller (Redis-compatible) | `kubectl apply -f https://raw.githubusercontent.com/dragonflydb/dragonfly-operator/main/manifests/dragonfly-operator.yaml` |
| MinIO Operator (optional, required only if `storage.mode: minio-tenant`) | S3 Tenant CR controller | `helm install minio-operator minio-operator/operator -n minio-operator --create-namespace` |
| `prometheus-operator` (optional, required only if `monitoring.enabled: true`) | `ServiceMonitor` + `PrometheusRule` CRDs | `helm install kube-prometheus-stack prometheus-community/kube-prometheus-stack -n monitoring --create-namespace` |

Happy-path install:

```bash
# 1. Create the release namespace and apply PSA restricted profile.
kubectl create namespace lan-software
kubectl label namespace lan-software pod-security.kubernetes.io/enforce=restricted

# 2. Create a values file (see examples/values-prod.yaml in the LanChart repo).
#    At minimum, override:
#      global.domain: your-event.example.com
#      global.tls.issuer.name: letsencrypt-prod
#    and provide credentials Secrets (mail, Stripe, WebPush, S3) out-of-band or
#    via an ExternalSecret if using External Secrets Operator.

# 3. Install.
helm install lan-software \
    oci://ghcr.io/lan-software/charts/lan-software \
    --version 0.1.0 \
    -n lan-software \
    -f values-prod.yaml

# 4. Watch pods come up. Two pre-install/pre-upgrade Helm hook Jobs per
#    release run BEFORE any Deployment rolls:
#      - `*-migrate-<ts>` per app — runs `php artisan migrate --force`
#      - `lan-software-lancore-integrations-sync` — runs
#        `php artisan integrations:sync` against LanCore to reconcile the
#        declarative `config/integrations.php` (see SSDD §5.4.5 +
#        IRS §3.5a) into the database: upserts the satellite IntegrationApp
#        rows, writes the config-seeded tokens, refreshes the webhooks.
#    Satellites boot already knowing their LANCORE_TOKEN because the shared
#    `lan-software-integrations-seed` Secret is mounted at startup. No
#    post-install Job, no `kubectl exec` dance.
kubectl -n lan-software get pods --watch
kubectl -n lan-software logs job/lan-software-lancore-integrations-sync
```

**First-adoption note:** The reconciler is destructive by design for slugs listed in `config/integrations.php`. On first adoption (or after editing the config), any pre-existing tokens for those slugs are deleted and replaced with the config-seeded ones. The Helm chart keeps token values stable across upgrades via its seed-Secret `lookup` idiom, so ordinary upgrades do not invalidate tokens. Operators can preview with `helm upgrade --dry-run` + manually running `kubectl exec deploy/lancore-web -- php artisan integrations:sync --dry-run` before flipping the release.

**Docker Compose deployments** can opt into the same reconciler at container boot by setting `LANCORE_INTEGRATIONS_RECONCILE_ON_BOOT=true` on the LanCore service — see §3.4.

**Ticket token deterministic-nonce migration (one-shot):** After the migration that adds `validation_rotation_epoch` to the `tickets` table, every ticket with a non-null `validation_nonce_hash` must be rotated once so the stored hash aligns with the new deterministic derivation. Run:

```
vendor/bin/sail artisan tickets:rotate-all --only-legacy
```

(Or `kubectl exec deploy/lancore-web -- php artisan tickets:rotate-all --only-legacy` on Kubernetes.) The command is idempotent; the `--only-legacy` flag limits it to tickets with `validation_rotation_epoch = 0`. Every previously-issued printed QR becomes invalid after this run; a `TicketTokenRotatedNotification` is *not* dispatched by the command itself, so announce the change through operator channels. Affected users can re-download the PDF or use the live QR at "My Tickets". See TKT-F-026..028.

Verification:

```bash
# Each app exposes /up for readiness.
for app in lancore lanbrackets lanentrance lanshout lanhelp; do
  kubectl -n lan-software run --rm -it curl --image=curlimages/curl --restart=Never -- \
    curl -fsSL http://${app}-web.lan-software.svc.cluster.local/up || echo "FAIL: $app"
done
```

See [SCOM](SCOM.md) for ongoing operations (pod restart, log access, backup/restore, key rotation) and [SSDD §4.3](SSDD.md#43-high-availability) for the HA topology.

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

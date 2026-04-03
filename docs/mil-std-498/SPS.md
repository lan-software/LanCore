# Software Product Specification (SPS)

**Document Identifier:** LanCore-SPS-001
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

This Software Product Specification (SPS) specifies the delivered software product for the **LanCore** CSCI.

### 1.2 System Overview

LanCore is delivered as a Docker container image or as source code for self-hosting. This document describes the source tree structure, build process, and delivered artifacts.

### 1.3 Document Overview

This document encompasses the executable software, source files, and support information needed to build, deploy, and maintain LanCore.

---

## 2. Referenced Documents

- [SDD](SDD.md) — Software Design Description
- [SVD](SVD.md) — Software Version Description
- [SIP](SIP.md) — Software Installation Plan

---

## 3. Source Tree Structure

```
LanCore/
├── app/                          # PHP application source
│   ├── Domain/                   # 14 domain modules
│   │   ├── Achievements/         # Achievement definitions and granting
│   │   ├── Announcement/         # Real-time announcements
│   │   ├── Competition/          # Planned: tournament management
│   │   ├── Event/                # Event lifecycle management
│   │   ├── Games/                # Game catalog
│   │   ├── Integration/          # Third-party app integration
│   │   ├── News/                 # News articles and comments
│   │   ├── Notification/         # Notification preferences and delivery
│   │   ├── Orchestration/        # Planned: match management
│   │   ├── Program/              # Event scheduling
│   │   ├── Seating/              # Canvas seat plans
│   │   ├── Shop/                 # Cart, checkout, payments
│   │   ├── Sponsoring/           # Sponsor management
│   │   ├── Ticketing/            # Ticket types and assignments
│   │   ├── Venue/                # Venue management
│   │   └── Webhook/              # Webhook delivery
│   ├── Enums/                    # Application enums (11)
│   ├── Http/
│   │   ├── Controllers/          # Cross-cutting controllers
│   │   └── Middleware/           # HTTP middleware (7)
│   ├── Models/                   # Eloquent models (40)
│   ├── Policies/                 # Authorization policies (24)
│   ├── Providers/                # Service providers
│   └── Services/                 # Cross-cutting services
├── bootstrap/                    # Application bootstrap
│   └── app.php                   # Middleware, providers, routing
├── config/                       # Configuration files
│   ├── app.php                   # Application settings
│   ├── auth.php                  # Authentication guards
│   ├── cashier.php               # Stripe billing
│   ├── database.php              # Database connections
│   ├── filesystems.php           # Storage disks
│   ├── fortify.php               # Auth features
│   ├── horizon.php               # Queue management
│   ├── inertia.php               # Inertia.js settings
│   ├── pulse.php                 # Monitoring
│   └── ...                       # Additional config files
├── database/
│   ├── factories/                # Model factories for testing
│   ├── migrations/               # Schema migrations (77+)
│   └── seeders/                  # Data seeders
├── docker/                       # Docker build files
│   ├── frankenphp/
│   │   └── Dockerfile            # Production container image
│   ├── supervisor/               # Queue worker supervision
│   ├── php/                      # PHP configuration overrides
│   └── entrypoint.sh             # Container startup script
├── docs/                         # Documentation
│   └── mil-std-498/              # MIL-STD-498 documents
├── public/                       # Web root
│   └── index.php                 # Application entry point
├── resources/
│   ├── css/                      # Tailwind CSS
│   ├── js/
│   │   ├── pages/                # Vue page components (95+)
│   │   ├── components/           # Shared Vue components
│   │   ├── layouts/              # Page layouts
│   │   ├── composables/          # Vue composables
│   │   └── types/                # TypeScript type definitions
│   └── views/                    # Blade templates (minimal, Inertia root)
├── routes/                       # Route definitions (20 files)
│   ├── web.php                   # Main web routes
│   ├── api-integrations.php      # Stateless API routes
│   ├── events.php                # Event routes
│   ├── ticketing.php             # Ticketing routes
│   ├── shop.php                  # Shop routes
│   └── ...                       # Domain-specific route files
├── storage/                      # Runtime storage
├── tests/
│   ├── Feature/                  # Feature tests (Pest)
│   ├── Unit/                     # Unit tests (Pest)
│   └── Architecture/             # Architecture tests
├── .github/
│   └── workflows/                # CI/CD pipelines
│       ├── tests.yml             # Backend tests + coverage
│       ├── frontend-tests.yml    # Frontend tests + coverage + E2E
│       ├── lint.yml              # Code linting
│       └── docker-publish.yml    # Docker image build
├── composer.json                 # PHP dependencies
├── package.json                  # JavaScript dependencies
├── vite.config.ts                # Vite bundler configuration
├── tailwind.config.ts            # Tailwind CSS configuration
├── tsconfig.json                 # TypeScript configuration
├── eslint.config.js              # ESLint configuration
├── phpunit.xml                   # PHPUnit/Pest configuration
├── docker-compose.yml            # Local development services
└── .env.example                  # Environment variable template
```

---

## 4. Build Process

### 4.1 Prerequisites

| Tool | Version | Purpose |
|------|---------|---------|
| Docker | 20+ | Container runtime |
| Docker Compose | v2+ | Multi-container orchestration |
| Git | 2.30+ | Source control |

### 4.2 Development Build

```bash
# 1. Clone repository
git clone <repository-url>
cd LanCore

# 2. Copy environment configuration
cp .env.example .env

# 3. Install PHP dependencies
docker run --rm -v $(pwd):/app composer install

# 4. Start Sail environment
./vendor/bin/sail up -d

# 5. Generate application key
vendor/bin/sail artisan key:generate

# 6. Run database migrations
vendor/bin/sail artisan migrate --no-interaction

# 7. Install JavaScript dependencies
vendor/bin/sail npm install

# 8. Build frontend assets
vendor/bin/sail npm run build

# 9. (Optional) Seed demo data
vendor/bin/sail artisan db:seed --class=SeedDemoCommand
```

### 4.3 Production Docker Build

```bash
# Build multi-architecture image
docker buildx build \
  --platform linux/amd64,linux/arm64 \
  -f Dockerfile \
  -t ghcr.io/<org>/lancore:latest \
  --push .
```

### 4.4 CI/CD Build Pipeline

#### 4.4.1 Backend Tests (`tests.yml`)

| Step | Command | Purpose |
|------|---------|---------|
| PHP Setup | `shivammathur/setup-php@v2` (PHP 8.5, Xdebug) | Runtime and coverage |
| Node Setup | `actions/setup-node@v4` (Node 22) | Frontend asset compilation |
| Dependencies | `composer install`, `npm i` | Install PHP and JS packages |
| Environment | `cp .env.example .env`, `php artisan key:generate` | Application configuration |
| Database | `touch database/database.sqlite`, `php artisan migrate --force` | Test database |
| Assets | `npm run build` | Compile frontend for integration tests |
| Tests | `./vendor/bin/pest --coverage-clover coverage.xml` | Execute test suite |
| Coverage | `codecov/codecov-action@v5` | Upload to Codecov |

**Artifacts:** `coverage.xml` (uploaded to Codecov)

#### 4.4.2 Frontend Tests (`frontend-tests.yml`)

**Vitest Job:**

| Step | Command | Purpose |
|------|---------|---------|
| Node Setup | `actions/setup-node@v4` (Node 22) | Runtime |
| Dependencies | `npm ci` | Install packages |
| Tests | `npm run test:coverage` (with `LARAVEL_BYPASS_ENV_CHECK=1`) | Vue component tests with coverage |
| Coverage | `codecov/codecov-action@v5` | Upload frontend coverage to Codecov |

**Playwright Job:**

| Step | Command | Purpose |
|------|---------|---------|
| Full Setup | PHP 8.5, Node 22, Composer, npm | Full-stack runtime |
| Server | `php artisan serve --port=8000` | Laravel dev server for E2E |
| Browser | `npx playwright install chromium --with-deps` | Test browser |
| Tests | `npm run test:e2e` | End-to-end browser tests |

**Artifacts:** `playwright-report/` (uploaded on failure, 7-day retention)

#### 4.4.3 Linting (`lint.yml`)

| Step | Command | Purpose |
|------|---------|---------|
| PHP Setup | `shivammathur/setup-php@v2` (PHP 8.5) | Pint runtime |
| Dependencies | `composer install`, `npm install` | Install packages |
| PHP Style | `composer lint` (Pint) | PHP code formatting |
| JS Format | `npm run format` (Prettier) | Frontend formatting |
| JS Lint | `npm run lint:check` (ESLint) | Frontend code quality |

**Artifacts:** None

#### 4.4.4 Docker Publish (`docker-publish.yml`)

| Step | Command | Purpose |
|------|---------|---------|
| Buildx | `docker/setup-buildx-action` | Multi-platform build support |
| Auth | `docker/login-action` (GHCR) | Container registry authentication |
| Metadata | `docker/metadata-action` | Tag and label generation |
| Build | `docker/build-push-action` (linux/amd64, linux/arm64) | Multi-arch image build and push |
| Attestation | `actions/attest-build-provenance` | Supply chain security |

**Artifacts:** Docker image published to `ghcr.io` with build provenance attestation

#### 4.4.5 External Service Dependencies

| Service | Used By | Purpose |
|---------|---------|---------|
| Codecov | `tests.yml`, `frontend-tests.yml` | Coverage trend tracking (backend + frontend) |
| GitHub Container Registry | `docker-publish.yml` | Docker image hosting |
| GitHub Actions | All workflows | CI/CD execution platform |

### 4.5 Asset Compilation

| Command | Output | Description |
|---------|--------|-------------|
| `npm run build` | `public/build/` | Production-optimized frontend assets |
| `npm run dev` | In-memory | Development server with hot module replacement |
| `composer run dev` | N/A | Starts both PHP and Vite dev servers |

---

## 5. Deployment Artifacts

### 5.1 Docker Image Contents

| Component | Description |
|-----------|-------------|
| PHP 8.5 Runtime | FrankenPHP with Laravel Octane |
| Application Code | Full `app/`, `config/`, `routes/`, `resources/` |
| Compiled Assets | Pre-built `public/build/` |
| Vendor Dependencies | `vendor/` (Composer) and `node_modules/` (npm) |
| Supervisor Config | Queue worker supervision |
| Entrypoint Script | Database migration and cache warming on start |

### 5.2 Required External Services

| Service | Purpose | Required |
|---------|---------|----------|
| PostgreSQL 15+ | Primary database | Yes |
| Redis 7+ | Cache, rate limiting | Yes |
| S3-compatible storage | File uploads | Yes (or local disk) |
| SMTP server | Email delivery | Yes (or log driver for dev) |
| Stripe API | Payment processing | No (optional) |

### 5.3 Configuration

All configuration via environment variables. See `.env.example` for the complete list. Key variables:

| Variable | Description | Default |
|----------|-------------|---------|
| `APP_NAME` | Application name | Laravel |
| `APP_ENV` | Environment (local, production) | local |
| `APP_URL` | Base URL | http://localhost |
| `DB_CONNECTION` | Database driver | sqlite |
| `CACHE_STORE` | Cache backend | redis |
| `QUEUE_CONNECTION` | Queue backend | database |
| `MAIL_MAILER` | Mail driver | log |

---

## 6. Quality Assurance

### 6.1 Code Quality Tools

| Tool | Command | Purpose |
|------|---------|---------|
| Laravel Pint | `vendor/bin/sail bin pint` | PHP code formatting (PSR-12) |
| ESLint | `vendor/bin/sail npm run lint` | JavaScript linting |
| Prettier | `vendor/bin/sail npm run format` | JavaScript/Vue formatting |
| vue-tsc | `vendor/bin/sail npm run types:check` | TypeScript type checking |

### 6.2 Pre-Commit Checks

Before committing, developers should run:

```bash
vendor/bin/sail bin pint --dirty --format agent
vendor/bin/sail npm run lint
vendor/bin/sail npm run types:check
vendor/bin/sail artisan test --compact
```

---

## 7. Notes

### 7.1 File Counts Summary

| Category | Count |
|----------|-------|
| PHP Source Files | 300+ |
| Vue Components | 120+ |
| Database Migrations | 77+ |
| Test Files | 20+ |
| Configuration Files | 15+ |
| Route Files | 20 |
| Eloquent Models | 40 |
| Domain Modules | 14 |

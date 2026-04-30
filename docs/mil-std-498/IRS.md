# Interface Requirements Specification (IRS)

**Document Identifier:** LanCore-IRS-001
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

This Interface Requirements Specification (IRS) specifies the interface requirements for the **LanCore** system's external and internal interfaces.

### 1.2 System Overview

LanCore interacts with several external services and provides its own API interfaces for third-party integration. This document specifies the requirements for each interface boundary.

### 1.3 Document Overview

This document describes the requirements for all external interfaces of the LanCore system, organized by interface type.

---

## 2. Referenced Documents

- [SSS](SSS.md) — System/Subsystem Specification
- [SRS](SRS.md) — Software Requirements Specification
- [IDD](IDD.md) — Interface Design Description

---

## 3. Interface Requirements

### 3.1 Stripe Payment API (IF-STRIPE)

| Req ID | Requirement |
|--------|------------|
| IF-STRIPE-001 | The system shall integrate with Stripe API via Laravel Cashier v16 |
| IF-STRIPE-002 | The system shall create Stripe Checkout Sessions for ticket and add-on purchases |
| IF-STRIPE-003 | The system shall handle Stripe webhook callbacks for payment confirmation |
| IF-STRIPE-004 | The system shall store Stripe customer IDs in the `customers` table |
| IF-STRIPE-005 | The system shall support Stripe subscriptions via `subscriptions` and `subscription_items` tables |
| IF-STRIPE-006 | The system shall use HTTPS for all Stripe API communication |
| IF-STRIPE-007 | The system shall store Stripe API keys as environment variables, never in code |
| IF-STRIPE-008 | The system shall handle `checkout.session.completed` webhook events to fulfill orders when the user does not return to the checkout success URL |
| IF-STRIPE-009 | The system shall verify Stripe webhook signatures using `STRIPE_WEBHOOK_SECRET` to prevent unauthorized webhook payloads |

**Configuration:**
- `STRIPE_KEY` — Stripe publishable key
- `STRIPE_SECRET` — Stripe secret key
- `STRIPE_WEBHOOK_SECRET` — Webhook endpoint signing secret (from Stripe Dashboard or CLI)
- `CASHIER_CURRENCY` — Payment currency (e.g., `eur`)
- `CASHIER_CURRENCY_LOCALE` — Locale for currency formatting (e.g., `de_DE`)
- Config file: `config/cashier.php`

### 3.2 PayPal Payment API (IF-PAYPAL)

| Req ID | Requirement |
|--------|------------|
| IF-PAYPAL-001 | The system shall integrate with the PayPal REST API via the `srmklive/paypal` package (the upstream of `blendbyte/laravel-paypal`) using PHP namespace `Srmklive\PayPal\Services\PayPal`. |
| IF-PAYPAL-002 | The system shall create PayPal Orders v2 via `createOrder` / `capturePaymentOrder` for ticket and add-on purchases; each order's `purchase_unit` shall carry the LanCore order id in `custom_id` and the invoice number in `invoice_id`. |
| IF-PAYPAL-003 | The system shall handle PayPal webhooks at `POST /webhooks/paypal`, verifying signatures locally via `verifyWebHookLocally()` using `PAYPAL_WEBHOOK_ID` before processing any payload. |
| IF-PAYPAL-004 | The system shall fulfill orders on `PAYMENT.CAPTURE.COMPLETED` via the same idempotent `FulfillOrder` path used by the return-URL flow; duplicate webhooks shall be detected as `already_fulfilled` and take no action. |
| IF-PAYPAL-005 | The system shall mark orders as `Failed` on `PAYMENT.CAPTURE.DENIED` / `PAYMENT.CAPTURE.REVERSED` events. |
| IF-PAYPAL-006 | The system shall use HTTPS for all PayPal API communication, relying on the package default (`validate_ssl=true`). |
| IF-PAYPAL-007 | The system shall store PayPal API credentials as environment variables, never in code. |
| IF-PAYPAL-008 | The system shall provide `php artisan paypal:webhook:register {url?}` to create or update the webhook subscription with PayPal and persist the returned id to `.env` as `PAYPAL_WEBHOOK_ID`. |
| IF-PAYPAL-009 | The system shall construct the `PayPalClient` per call (factory closure), never as a long-lived singleton, to remain Octane-safe. |

**Configuration:**
- `PAYPAL_MODE` — `sandbox` or `live` (default `sandbox`)
- `PAYPAL_SANDBOX_CLIENT_ID` / `PAYPAL_SANDBOX_CLIENT_SECRET` — sandbox credentials
- `PAYPAL_LIVE_CLIENT_ID` / `PAYPAL_LIVE_CLIENT_SECRET` / `PAYPAL_LIVE_APP_ID` — live credentials
- `PAYPAL_WEBHOOK_ID` — populated by the artisan command above
- `PAYPAL_TIMEOUT`, `PAYPAL_CONNECT_TIMEOUT`, `PAYPAL_MAX_RETRIES` — HTTP client tuning
- Config file: `config/paypal.php`

### 3.3 S3-Compatible Object Storage (IF-S3)

| Req ID | Requirement |
|--------|------------|
| IF-S3-001 | The system shall store uploaded files (images, logos, banners) in S3-compatible storage |
| IF-S3-002 | The system shall support AWS S3, Minio, and Garage as storage backends |
| IF-S3-003 | The system shall use path-style endpoint addressing (configurable) |
| IF-S3-004 | The system shall generate signed URLs for private file access |
| IF-S3-005 | The system shall support anonymous bucket access (configurable) |
| IF-S3-006 | The system shall provide a StorageFileController for file serving |
| IF-S3-007 | The system shall expose two semantic storage roles — `public` and `private` — each independently routable to a distinct disk/bucket |
| IF-S3-008 | The system shall keep sensitive artifacts (invoices, receipts, ticket PDFs) on the `private` role and never on a publicly readable bucket |
| IF-S3-009 | The system shall fall back from per-role S3 credentials (`AWS_PUBLIC_*` / `AWS_PRIVATE_*`) to the legacy single-bucket `AWS_*` credentials when role-specific values are unset |

**Configuration:**
- `FILESYSTEM_PUBLIC_DISK` — Disk that backs the public role (default: `public`; e.g. `s3_public`)
- `FILESYSTEM_PRIVATE_DISK` — Disk that backs the private role (default: `local`; e.g. `s3_private`)
- `AWS_ACCESS_KEY_ID` / `AWS_SECRET_ACCESS_KEY` / `AWS_DEFAULT_REGION` / `AWS_BUCKET` / `AWS_ENDPOINT` / `AWS_URL` / `AWS_USE_PATH_STYLE_ENDPOINT` — Legacy single-bucket S3 settings; also act as fallback for the per-role variants below
- `AWS_PUBLIC_BUCKET`, `AWS_PUBLIC_URL`, `AWS_PUBLIC_ENDPOINT`, `AWS_PUBLIC_ACCESS_KEY_ID`, `AWS_PUBLIC_SECRET_ACCESS_KEY`, `AWS_PUBLIC_DEFAULT_REGION`, `AWS_PUBLIC_USE_PATH_STYLE_ENDPOINT`, `AWS_PUBLIC_ANONYMOUS_BUCKET_ACCESS` — Public-role S3 overrides
- `AWS_PRIVATE_BUCKET`, `AWS_PRIVATE_URL`, `AWS_PRIVATE_ENDPOINT`, `AWS_PRIVATE_ACCESS_KEY_ID`, `AWS_PRIVATE_SECRET_ACCESS_KEY`, `AWS_PRIVATE_DEFAULT_REGION`, `AWS_PRIVATE_USE_PATH_STYLE_ENDPOINT` — Private-role S3 overrides
- Config file: `config/filesystems.php`
- Helper: `App\Support\StorageRole::public()` / `::private()`

### 3.4 SMTP Mail Service (IF-SMTP)

| Req ID | Requirement |
|--------|------------|
| IF-SMTP-001 | The system shall send transactional emails via SMTP |
| IF-SMTP-002 | The system shall support configurable SMTP host, port, encryption, and credentials |
| IF-SMTP-003 | The system shall support TLS/STARTTLS encryption for mail transport |
| IF-SMTP-004 | The system shall queue email delivery via Laravel's mail queue |
| IF-SMTP-005 | The system shall support a log driver for development (no actual mail sent) |

**Email types sent:**
- Email verification
- Password reset
- News article notifications
- Announcement notifications
- Program time slot approaching
- User role change notifications
- Achievement earned notifications

**Configuration:**
- `MAIL_MAILER` — Mail driver (smtp, log, mailgun, etc.)
- `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`
- `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`
- Config file: `config/mail.php`

### 3.5 Web Push Service (IF-PUSH)

| Req ID | Requirement |
|--------|------------|
| IF-PUSH-001 | The system shall support Web Push notifications per RFC 8030 |
| IF-PUSH-002 | The system shall use the minishlink/web-push library (v10) for push delivery |
| IF-PUSH-003 | The system shall store push subscriptions with: endpoint, public_key, auth_token |
| IF-PUSH-004 | The system shall use VAPID authentication for push services |
| IF-PUSH-005 | The system shall deliver push notifications for: news, announcements, programs, achievements |
| IF-PUSH-006 | The system shall register a service worker for push reception in the browser |

**Data model:** `push_subscriptions` table with user association

### 3.6 LanCore Integration API (IF-INTAPI)

| Req ID | Requirement |
|--------|------------|
| IF-INTAPI-001 | The system shall provide a stateless REST API at `/api/integration/*` |
| IF-INTAPI-002 | The system shall authenticate API requests via Bearer token in Authorization header |
| IF-INTAPI-003 | The system shall validate tokens via AuthenticateIntegration middleware |
| IF-INTAPI-004 | The system shall return JSON responses for all API endpoints |
| IF-INTAPI-005 | The system shall provide user resolution endpoint for integration apps |
| IF-INTAPI-006 | The system shall rate-limit API requests (configurable) |

**Endpoints defined in:** `routes/api-integrations.php`

### 3.6a Integration Declarative Configuration (IF-INTCFG)

A file-based interface that lets operators declare the full set of integration apps (slug, host, callback, scopes, nav, webhooks, pre-shared token + webhook secrets) in one place, rather than creating them through the UI or the imperative Artisan commands.

| Req ID | Requirement |
|--------|------------|
| IF-INTCFG-001 | The system shall read integration-app declarations from `config/integrations.php` at boot |
| IF-INTCFG-002 | The system shall resolve every config value from an environment variable (with a sensible default) so operators configure the subsystem via env vars rather than by editing the source file |
| IF-INTCFG-003 | The system shall support a `satellite_host_style` selector with values `flat`, `prefixed`, or `custom` to derive satellite hostnames from a single `domain` value |
| IF-INTCFG-004 | The system shall provide an `integrations:sync` Artisan command that reconciles `config/integrations.php` against the database (upsert app rows, delete and recreate tokens, delete and recreate webhooks — scoped to the slugs listed in config) |
| IF-INTCFG-005 | The system shall optionally reconcile at application boot when `LANCORE_INTEGRATIONS_RECONCILE_ON_BOOT=true`, using a process-global lock to prevent duplicate reconciliations under Octane |
| IF-INTCFG-006 | The system shall accept a caller-supplied plaintext token (via the `token` config field) and persist only its SHA-256 hash plus an 8-character display prefix |
| IF-INTCFG-007 | The system shall accept caller-supplied webhook secrets (via `announcement_webhook_secret` and `roles_webhook_secret`) and persist them verbatim in the corresponding `webhooks.secret` columns |
| IF-INTCFG-008 | The system shall leave integration-app rows whose slug is NOT listed in `config/integrations.php` untouched by the reconciler |

**Config file shape (abbreviated):**

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

**Primary consumer:** the `lan-software` Helm umbrella chart populates every env var from operator `values.yaml` plus a shared auto-generated seed Secret. See SSDD §5.4.5 for the reconciler flow.

### 3.7 LanCore SSO Interface (IF-SSO)

| Req ID | Requirement |
|--------|------------|
| IF-SSO-001 | The system shall provide an OAuth2-like SSO flow with authorization codes |
| IF-SSO-002 | The system shall generate time-limited authorization codes via GenerateSsoAuthorizationCode |
| IF-SSO-003 | The system shall exchange authorization codes for user data via ExchangeSsoAuthorizationCode |
| IF-SSO-004 | The system shall redirect users to the integration's callback URL with the authorization code |
| IF-SSO-005 | The system shall resolve authenticated users via ResolveIntegrationUser |
| IF-SSO-006 | The user payload returned by SSO exchange and user-resolution endpoints shall conform to the `LanCoreUser` DTO defined in SRS ICLIB-F-002 (amended), comprising the following fields: `id` (int), `username` (?string; null until the user has chosen one — see CAP-USR-011), `name` (string, real name; satellite-internal use only — never publicly displayed), `email` (?string, only when scope `user:email` granted), `roles` (string[], only when scope `user:roles` granted), `locale` (?string, BCP 47), `avatar_url` (string, never null — resolved server-side per CAP-USR-013 source chain), `avatar_source` (string, one of `default`/`gravatar`/`custom`/`steam`), `profile_url` (?string, absolute URL to LanCore public profile; null when `username` is null), `created_at` (ISO 8601 string) |

**Flow:**
1. Integration redirects user to LanCore SSO authorization endpoint
2. User authenticates (if not already) and approves
3. LanCore redirects to integration callback URL with authorization code
4. Integration exchanges code for user information via API

### 3.8 Webhook Delivery Interface (IF-WHK)

| Req ID | Requirement |
|--------|------------|
| IF-WHK-001 | The system shall deliver webhook payloads via HTTP POST to registered URLs |
| IF-WHK-002 | The system shall include HMAC-SHA256 signature in webhook headers |
| IF-WHK-003 | The system shall serialize payloads as JSON |
| IF-WHK-004 | The system shall record delivery response: status code, duration, body |
| IF-WHK-005 | The system shall dispatch webhooks asynchronously via queue |
| IF-WHK-006 | The system shall support per-integration-app webhook secrets |

**Supported events:**
| Event | Trigger |
|-------|---------|
| `NewsArticlePublished` | News article published |
| `AnnouncementPublished` | Announcement created |
| `EventPublished` | Event published |
| `UserRegistered` | New user registration |
| `UserRolesUpdated` | User role change |
| `ProfileUpdated` | User profile update |
| `TicketPurchased` | Order fulfilled |
| `IntegrationAccessed` | Integration API accessed |

**Payload field requirements for user-related events:**

| Event | Required payload fields |
|-------|------------------------|
| `UserRegistered` | `user.id`, `user.username` (?string), `user.email`, `user.locale`, `user.avatar_url`, `user.avatar_source`, `user.profile_url` (?string), `user.profile_visibility` (`public` / `logged_in` / `private`), `user.created_at` |
| `UserRolesUpdated` | `user.id`, `user.username` (?string), `user.roles` (string[]) |
| `ProfileUpdated` | `user.id`, `user.username` (?string), `user.locale`, `user.avatar_url`, `user.avatar_source`, `user.short_bio`, `user.profile_emoji`, `user.profile_visibility`, `user.profile_url` (?string), `user.profile_updated_at` (ISO 8601 string). The webhook is dispatched whenever the user changes any field consumed by these payload keys; satellites that cache user data should refresh their copy on receipt |

### 3.9 Redis Cache Interface (IF-REDIS)

| Req ID | Requirement |
|--------|------------|
| IF-REDIS-001 | The system shall use Redis as the primary cache store |
| IF-REDIS-002 | The system shall support tag-based cache invalidation via ModelCacheService |
| IF-REDIS-003 | The system shall use Redis for rate limiting |
| IF-REDIS-004 | The system shall support a fallback registry pattern for non-tag cache stores |

**Configuration:**
- `REDIS_HOST`, `REDIS_PORT`, `REDIS_PASSWORD`
- `CACHE_STORE=redis`
- Config file: `config/database.php` (redis section), `config/cache.php`

### 3.10 PostgreSQL Database Interface (IF-DB)

| Req ID | Requirement |
|--------|------------|
| IF-DB-001 | The system shall use PostgreSQL as the primary database in production |
| IF-DB-002 | The system shall support SQLite as an alternative for development/testing |
| IF-DB-003 | The system shall manage schema via Laravel migrations |
| IF-DB-004 | The system shall access data exclusively via Eloquent ORM (no raw DB:: calls) |
| IF-DB-005 | The system shall use JSONB columns for flexible structured data |
| IF-DB-006 | The system shall store sessions in the database |

**Configuration:**
- `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- Config file: `config/database.php`

### 3.12 LanCore Entrance API — Ticket Signing Keys (IF-JWKS)

| Req ID | Requirement |
|--------|------------|
| IF-JWKS-001 | The system shall expose a `GET /api/entrance/signing-keys` endpoint accessible to registered integration consumers authenticated via Bearer token |
| IF-JWKS-002 | The response shall be a JWKS-format JSON object containing one entry per active or retired-but-unexpired Ed25519 public key |
| IF-JWKS-003 | Each JWKS entry shall carry at minimum: `kty` ("OKP"), `crv` ("Ed25519"), `use` ("sig"), `kid` (string, max 16 chars), `x` (base64url-encoded public key bytes) |
| IF-JWKS-004 | The endpoint shall include a `Cache-Control: max-age=<ttl>` header; consumers shall treat the response as cacheable for the stated TTL and must not call the endpoint more frequently |
| IF-JWKS-005 | The endpoint shall return HTTP 401 when the Bearer token is invalid or missing |
| IF-JWKS-006 | The endpoint shall return HTTP 200 with the JWKS object even when only retired (unexpired) keys remain; an empty `keys` array is returned only when no keys have ever been generated |

### 3.13 LanCore Entrance API — Validate Endpoint Error Codes (IF-VALIDATE)

| Req ID | Requirement |
|--------|------------|
| IF-VALIDATE-001 | The `POST /api/entrance/validate` endpoint shall accept the opaque LCT1 token string in the `token` field and perform: (a) structural parse, (b) `kid` lookup in JWKS, (c) Ed25519 signature verification, (d) nonce hash lookup, (e) expiry check |
| IF-VALIDATE-002 | The endpoint shall return `decision: "invalid_signature"` when the Ed25519 signature does not verify against the public key identified by `kid` |
| IF-VALIDATE-003 | The endpoint shall return `decision: "unknown_kid"` when the `kid` field in the token does not correspond to any known public key |
| IF-VALIDATE-004 | The endpoint shall return `decision: "expired"` when the current time is past the token's `exp` claim |
| IF-VALIDATE-005 | The endpoint shall return `decision: "revoked"` when no `tickets` row matches the HMAC-derived nonce hash (ticket cancelled or nonce rotated) |
| IF-VALIDATE-006 | The existing decision values (`valid`, `already_checked_in`, `invalid`, `denied_by_policy`, `override_possible`, `verification_required`, `payment_required`) shall remain unchanged |

### 3.11 Prometheus Metrics Interface (IF-PROM)

| Req ID | Requirement |
|--------|------------|
| IF-PROM-001 | The system shall expose Prometheus metrics via spatie/laravel-prometheus |
| IF-PROM-002 | The system shall track HTTP request metrics via TrackHttpMetrics middleware |
| IF-PROM-003 | The system shall expose metrics in Prometheus exposition format |

---

## 4. Qualification Provisions

| Interface | Verification Method |
|-----------|-------------------|
| IF-STRIPE | Integration tests with Stripe test mode |
| IF-S3 | Integration tests with Minio (local S3) |
| IF-SMTP | Feature tests with log driver; manual SMTP testing |
| IF-PUSH | Feature tests with mock push service |
| IF-INTAPI | Feature tests with test tokens |
| IF-SSO | Feature tests with mock integration apps |
| IF-WHK | Feature tests verifying payload dispatch and signature |
| IF-REDIS | Feature tests with Redis test instance |
| IF-DB | All test suites (SQLite for speed) |
| IF-JWKS | Feature tests with test integration token; verify JWKS structure and cacheability |
| IF-VALIDATE | Feature tests covering all six error code paths (invalid_signature, unknown_kid, expired, revoked, valid, already_checked_in) |

---

## 5. Requirements Traceability

| System Requirement (SSS) | Interface Requirement (IRS) |
|--------------------------|---------------------------|
| SSS 3.3.1 External Interfaces | IF-STRIPE, IF-S3, IF-SMTP, IF-PUSH, IF-INTAPI, IF-WHK |
| SSS 3.4 Internal Interfaces | IF-REDIS, IF-DB |
| CAP-INT-* | IF-INTAPI, IF-SSO |
| CAP-WHK-* | IF-WHK |
| CAP-NTF-* | IF-SMTP, IF-PUSH |
| CAP-SHP-* | IF-STRIPE |
| CAP-TKT-014 | IF-JWKS |
| CAP-TKT-013 | IF-VALIDATE |
| SEC-019, SEC-020 | IF-JWKS |
| SEC-014..018 | IF-VALIDATE |
| CAP-DL-007, CAP-DL-004..005 | IF-DL-001, IF-DL-002, IF-DL-003 |

---

### 5.X Data Lifecycle Internal Interfaces

These are internal (in-process) interfaces. The Data Lifecycle domain does not expose any external interfaces in this iteration; satellite-app integration via `lancore-client` is explicitly out of scope.

| Interface ID | Contract | Path | Notes |
|--------------|----------|------|-------|
| IF-DL-001 | `EmailHasher::hash(string $email): string` | `app/Domain/DataLifecycle/Services/EmailHasher.php` | HMAC-SHA256 over lower-cased, trimmed email. Salt is HKDF-derived from APP_KEY with versioned context `data-lifecycle-email-v1`. Returns 64-char hex. |
| IF-DL-002 | `DomainAnonymizer::dataClass(): RetentionDataClass; anonymize(User, AnonymizationMode): AnonymizationResult` | `app/Domain/DataLifecycle/Anonymizers/Contracts/DomainAnonymizer.php` | Implementations are registered into `DomainAnonymizerRegistry` at boot in `DataLifecycleServiceProvider`. Implementations MUST be idempotent. |
| IF-DL-003 | `RetentionEvaluator::dataClass(): RetentionDataClass; evaluate(User): RetentionVerdict` | `app/Domain/DataLifecycle/RetentionEvaluators/Contracts/RetentionEvaluator.php` | Implementations are registered into `RetentionEvaluatorRegistry` at boot. Verdicts inform `PurgeExpiredData`. |

> **Out of scope** (deferred): exposing the Data Lifecycle deletion API to satellite apps via `lancore-client` / Integration API. Captured for a follow-up iteration.

---

## 6. Notes

### 6.1 Acronyms

| Term | Definition |
|------|-----------|
| API | Application Programming Interface |
| HMAC | Hash-based Message Authentication Code |
| REST | Representational State Transfer |
| SMTP | Simple Mail Transfer Protocol |
| SSO | Single Sign-On |
| VAPID | Voluntary Application Server Identification |
| RFC | Request for Comments |

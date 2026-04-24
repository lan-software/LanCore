# Software Design Description (SDD)

**Document Identifier:** LanCore-SDD-001
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

This Software Design Description (SDD) describes the design of the **LanCore** CSCI — a LAN Party & BYOD Event Management Platform.

### 1.2 System Overview

LanCore is a monolithic web application using Laravel 13 (backend), Vue.js 3 (frontend), and Inertia.js v2 (SPA bridge). It follows domain-driven design principles with an Actions pattern for business logic.

### 1.3 Document Overview

This document describes the architectural design, component structure, concept of execution, and detailed design of each domain module.

---

## 2. Referenced Documents

- [SRS](SRS.md) — Software Requirements Specification
- [DBDD](DBDD.md) — Database Design Description
- [IDD](IDD.md) — Interface Design Description

---

## 3. CSCI-Wide Design Decisions

### 3.1 Architectural Pattern

**Domain-Driven Design with Actions Pattern:**

LanCore departs from traditional Laravel "fat controller" patterns by organizing code into domain modules with explicit Action classes for business logic.

```
app/
├── Domain/                    # Business domains (17 modules)
│   ├── Event/
│   │   ├── Actions/           # Business logic (CreateEvent, PublishEvent, etc.)
│   │   ├── Controllers/       # HTTP layer (thin, delegates to Actions)
│   │   ├── Events/            # Domain events (EventPublished)
│   │   ├── Listeners/         # Event handlers
│   │   ├── Requests/          # Form validation (CreateEventRequest)
│   │   └── Policies/          # Authorization rules (EventPolicy)
│   ├── Ticketing/
│   ├── Shop/
│   ├── Program/
│   ├── Seating/
│   ├── Sponsoring/
│   ├── News/
│   ├── Announcement/
│   ├── Achievements/
│   ├── Notification/
│   ├── Integration/
│   ├── Webhook/
│   ├── Games/
│   └── Competition/           # LanBrackets integration, team management
├── Models/                    # Eloquent models (shared)
├── Enums/                     # Application enums
├── Http/
│   ├── Controllers/           # Cross-cutting controllers (Dashboard, Welcome)
│   └── Middleware/             # HTTP middleware
├── Providers/                 # Service providers
└── Services/                  # Cross-cutting services
```

### 3.2 Key Design Decisions

| Decision | Rationale |
|----------|-----------|
| Domain modules under `app/Domain/` | Isolates bounded contexts for independent evolution |
| Actions pattern over service classes | Each action has a single responsibility; easier to test and trace |
| Inertia.js over REST API + SPA | Eliminates API serialization layer; server-driven routing |
| Eloquent over raw SQL | Type-safe, relationship-aware data access; N+1 prevention via eager loading |
| Form Request validation | Decouples validation from controllers; reusable across routes |
| Laravel Policies | Declarative authorization co-located with domain concerns |
| Event/Listener pattern | Decouples side effects (notifications, webhooks) from primary operations |
| JSONB for flexible data | Banner images and per-seat `custom_data` benefit from schemaless storage (seat plans themselves are fully normalized — see SET-F-002) |
| Laravel Octane/FrankenPHP | Application boot once, reuse across requests for performance |
| Redis for caching | Tag-based invalidation, high throughput, shared cache across workers |
| Demo mode via `SeedDemoCommand` + Stripe test-mode keys | Enables end-to-end showcases using Stripe's sandbox; activated via environment flag `APP_DEMO=true` paired with `STRIPE_KEY=pk_test_...` / `STRIPE_SECRET=sk_test_...`; `PaymentProviderManager` resolves `StripePaymentProvider` as normal — no custom provider is needed |
| LanCore as authoritative locale store | `users.locale` column on LanCore is the single source of truth for the user's display language; satellites receive the locale via `LanCoreUser` DTO and persist it locally; this avoids duplicated preference UIs across apps and ensures the user changes their language in one place |
| `SetLocale` middleware for per-request locale application | A dedicated middleware (registered in the web group after session init) calls `app()->setLocale()` based on the authenticated user's stored locale; this keeps locale resolution out of controllers and ensures every translation call in a request cycle uses the correct locale |
| `vue-i18n` + Weblate for frontend i18n | `vue-i18n@9+` initialized from the `locale` Inertia shared prop; JSON locale files in `resources/js/locales/`; Weblate (self-hosted, `https://weblate.sxcs.de`) reads source strings directly from these files and pushes translator commits to the `weblate` branch, which is fast-forwarded onto `main` by `.github/workflows/weblate-merge.yml` |

### 3.3 Security Design

- All user input validated via Form Request classes before reaching Actions
- Authorization enforced at controller level via `$this->authorize()` and Policy classes
- 29 Policy classes cover all domain entities, each checking granular permissions via `$user->hasPermission()`
- CSRF protection on all state-changing web routes
- Stateless Bearer token auth for integration API (no session/cookies)
- Passwords hashed with bcrypt (configurable rounds)
- API tokens hashed before storage
- Webhook payloads signed with HMAC-SHA256

#### 3.3.1 Permission Architecture

Authorization uses a static enum-based permission system with no database tables for permissions. The architecture has four layers:

```
RoleName Enum (5 cases)
  └── RolePermissionMap::forRole() mapping
       └── HasPermissions trait (on User model, with request-scoped cache)
            └── Policy classes (check $user->hasPermission())
```

**Domain Permission Enums**: Each domain defines its own `Permission` enum implementing `App\Contracts\PermissionEnum`. Cross-cutting user management permissions (`ManageUsers`, `SyncUserRoles`, `DeleteUsers`) remain in `app/Enums/Permission.php`. Audit permissions (`ViewAuditLogs`) live in `app/Enums/AuditPermission.php`. Domain-specific permissions live inside their domain (e.g., `App\Domain\Ticketing\Enums\Permission` defines `ManageTicketing` and `CheckInTickets`). This keeps permissions co-located with their domain, following the project's domain-driven design structure. Targeted splits exist where a domain requires finer control (e.g., `ViewOrders` vs `ManageOrders`, `CheckInTickets` vs `ManageTicketing`).

**Static Role Mapping** (`app/Enums/RolePermissionMap.php`): Each `RoleName` maps to a fixed set of `PermissionEnum` cases via `RolePermissionMap::forRole()`. Superadmin receives `RolePermissionMap::all()` (every permission across all domains). Admin receives all except `SyncUserRoles` and `DeleteUsers`. Moderator receives content moderation permissions only. User receives none (authorization relies on ownership checks).

**HasPermissions Trait** (`app/Concerns/HasPermissions.php`): Provides `hasPermission(PermissionEnum)`, `hasAnyPermission(PermissionEnum...)`, and `allPermissions()` methods on the User model. Resolves permissions by iterating loaded roles and collecting from `RolePermissionMap::forRole()`. Accepts any enum implementing the `PermissionEnum` interface. Resolved permissions are cached on the user instance within a request lifecycle via a `$resolvedPermissions` property, avoiding repeated array rebuilds.

**Centralized Superadmin Bypass** (`AppServiceProvider::configurePolicies()`): A single `Gate::before()` callback grants superadmin access to all policy checks, eliminating the repeated `before()` method previously present in every policy class.

**Frontend Integration**: `HandleInertiaRequests` shares the user's resolved permissions as a flat string array via Inertia shared props. The `usePermissions()` composable (`resources/js/composables/usePermissions.ts`) provides typed `can(PermissionValue)` and `canAny(PermissionValue...)` helpers backed by a `Permission` TypeScript constant object (`resources/js/types/permissions.ts`) for compile-time safety against typos.

#### 3.3.2 Ticket Token Security Design

The ticket signing subsystem is implemented as two service classes in `app/Domain/Ticketing/Security/`:

**`TicketTokenService`** — Orchestrates token lifecycle with a clear rotate/render split:
- `rotate(Ticket $ticket): IssuedToken` — Acquires a `lockForUpdate` row lock, increments `validation_rotation_epoch` by 1, derives the deterministic nonce as `HMAC_SHA256(pepper, ticket_id_le64 || epoch_le64)` truncated to 16 bytes, builds the LCT1 body, signs with the active kid, and persists the new `validation_nonce_hash / validation_kid / validation_issued_at / validation_expires_at / validation_rotation_epoch`. Returns the envelope so callers can dispatch PDF regeneration.
- `render(Ticket $ticket): string` — Pure read: recomputes the deterministic nonce from the stored epoch, rebuilds the identical LCT1 body using the stored `validation_issued_at / validation_expires_at / validation_kid`, re-signs, and returns the QR payload. **No DB writes.** Throws if the ticket has never been rotated.
- `verify(string $token): TokenVerification` — Parses the token, verifies the Ed25519 signature via `TicketKeyRing`, checks `exp`, returns the decoded claims.
- `locate(TokenVerification $verification): ?Ticket` — Looks up a ticket by `tid + validation_nonce_hash`; returns `null` if the nonce hash no longer matches (i.e. the ticket has been rotated since the token was printed).
- `isVerifiable(string $kid): bool` — Thin accessor that wraps `TicketKeyRing::allVerifyKids()`.

**`TicketKeyRing`** — Key management:
- `sign(string $message, string $kid = null): string` — Signs `message` with the active (or specified) private key; returns base64url-encoded 64-byte signature
- `publicKey(string $kid): string` — Returns base64url-encoded public key bytes for a given kid; throws `UnknownKidException` if not found
- `activeKid(): string` — Returns the currently active key identifier
- `allVerifyKids(): array` — Returns all kids whose public keys are retained (active + retired-but-unexpired)
- `toJwks(): array` — Builds the `{ keys: [...] }` JWKS array for the signing-keys endpoint

Private key files reside at `storage/keys/ticket_signing/{kid}.key` with mode 0600. `TicketKeyRing` reads them on boot and caches in memory for the Octane process lifetime. On `tickets:keys:rotate`, a new key pair is written to disk and `TicketKeyRing` is re-initialized.

**`GenerateTicketPdf` job:** The job takes a required `string $qrPayload` constructor argument carrying the signed LCT1 token payload to render into the QR code. The `qrPayload` is supplied by the caller (either freshly from `rotate()` during an intentional rotation, or from `render()` on a cache-miss regeneration); the job itself never mutates the stored nonce. The job also pulls the event banner (via `StorageRole::public()` → base64), the currently-active `GlobalPurchaseCondition` rows, and a personalised forensic watermark PNG for the tri-fold PDF template.

**Forensic watermark:** `GenerateTicketPdf::generateWatermarkBase64()` produces an A4-sized transparent PNG (1240 × 1754 px at 150 DPI) with the single line `"{owner} · {event} · {venue}, {city} · {orgName} · {ticketType} · #{ticketId}"` drawn repeatedly using DejaVu Sans TTF at ~12% opacity. The repeated text is rotated by an angle seeded deterministically from the ticket ID (45° ± 5°) and offset by `(ticket_id * 7) mod 180` px horizontally / `(ticket_id * 13) mod 180` px vertically. For each instance the rotated bounding box is computed via `imagettfbbox`; if it overlaps the QR safe rectangle (5 mm..80 mm horizontal, 105 mm..190 mm vertical on the page), the draw is skipped so the scan area stays clean. The result is embedded as a `data:image/png;base64,…` overlay as the first element of the PDF body, under the three tri-fold panels in DomPDF paint order. Purpose: any leaked photo, screenshot, or photocopy of a ticket carries the owner's identity and ticket ID dispersed across the page, making source attribution possible without DB access. Gracefully skipped when GD TTF support or the DejaVu font file is unavailable.

**Deterministic nonce derivation:** The plaintext nonce is never stored. It is recomputed on every render as `substr(hash_hmac('sha256', pack('J', $ticketId).pack('J', $epoch), $pepper, true), 0, 16)`. The pepper lives in config (`tickets.pepper`), not in the database, so a DB-only attacker cannot reconstruct the nonce even though the epoch counter is stored alongside the ticket row.

#### 3.3.4 Role-Permission Matrix

| Permission | User | Moderator | SponsorManager | Admin | Superadmin |
|------------|:----:|:---------:|:--------------:|:-----:|:----------:|
| ManageAchievements | | | | X | X |
| ManageAnnouncements | | X | | X | X |
| ManageNewsArticles | | | | X | X |
| ModerateNewsComments | | X | | X | X |
| ManageEvents | | | | X | X |
| ManagePrograms | | | | X | X |
| ManageGames | | | | X | X |
| ManageVenues | | | | X | X |
| ManageSeatPlans | | | | X | X |
| ManageTicketing | | | | X | X |
| CheckInTickets | | | | X | X |
| ViewOrders | | | | X | X |
| ManageOrders | | | | X | X |
| ManageVouchers | | | | X | X |
| ManageShopConditions | | | | X | X |
| ManageSponsors | | | | X | X |
| ManageSponsorLevels | | | | X | X |
| ManageAssignedSponsors | | | X | | X |
| ManageIntegrations | | | | X | X |
| ManageWebhooks | | | | X | X |
| ManageUsers | | | | X | X |
| ViewAuditLogs | | | | X | X |
| SyncUserRoles | | | | | X |
| DeleteUsers | | | | | X |
| ManageCompetitions | | | | X | X |
| ManageGameServers | | | | X | X |
| ViewOrchestration | | | | X | X |

---

## 4. CSCI Architectural Design

### 4.1 Component Overview

```
┌─────────────────────────────────────────────────────────┐
│                    Vue.js 3 Frontend                     │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌────────────┐ │
│  │  Pages   │ │Components│ │  Layouts │ │  Composables│ │
│  │ (95+)    │ │ (shared) │ │          │ │             │ │
│  └──────────┘ └──────────┘ └──────────┘ └────────────┘ │
├─────────────────────────────────────────────────────────┤
│                  Inertia.js v2 Bridge                    │
├─────────────────────────────────────────────────────────┤
│              Laravel 13 Backend (Octane)                  │
│  ┌─────────────────────────────────────────────────────┐ │
│  │                    HTTP Layer                        │ │
│  │  Middleware → Routes → Controllers → Form Requests   │ │
│  └───────────────────────┬─────────────────────────────┘ │
│  ┌───────────────────────▼─────────────────────────────┐ │
│  │                  Domain Layer                        │ │
│  │  Actions → Models → Events → Listeners → Policies   │ │
│  └───────────────────────┬─────────────────────────────┘ │
│  ┌───────────────────────▼─────────────────────────────┐ │
│  │              Infrastructure Layer                    │ │
│  │  Eloquent ORM → Database │ Redis │ S3 │ Mail │ Queue│ │
│  └─────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────┘
```

### 4.2 Domain Modules (17)

Each domain module follows a consistent internal structure:

| Component | Responsibility |
|-----------|---------------|
| **Actions/** | Business logic operations (one class per operation) |
| **Controllers/** | HTTP request handling, delegates to Actions, returns Inertia responses |
| **Events/** | Domain events emitted by Actions |
| **Listeners/** | Side-effect handlers (notifications, webhooks, achievement processing) |
| **Requests/** | Form Request validation classes |
| **Policies/** | Authorization rules per model |

#### 4.2.1 Domain Module Inventory

| Domain | Models | Actions | Controllers | Events | Listeners |
|--------|--------|---------|-------------|--------|-----------|
| Event | 1 | 5 | 3 | 1 | 1 |
| Venue | 3 | 3 | 1 | 0 | 0 |
| Ticketing | 5 | 10+2 services | 8+1 | 0 | 0 |
| Shop | 9 | 15 | 9 | 2 | 2 |
| Program | 2 | 6 | 2 | 1 | 1 |
| Seating | 2 | 5 | 3 | 0 | 0 |
| Sponsoring | 2 | 6 | 4 | 0 | 0 |
| News | 3 | 3 | 5 | 2 | 2 |
| Announcement | 1 | 3 | 3 | 2 | 2 |
| Achievements | 2 | 4 | 1 | 0 | 1 |
| Notification | 3 | 0 | 4 | 6 | 4 |
| Integration | 2 | 10 | 4 | 1 | 1 |
| Webhook | 2 | 4 | 1 | 1 | 2 |
| Games | 2 | 6 | 2 | 0 | 0 |
| Competition | 4 | 8 | 5 | 2 | 0 |
| Orchestration | 3 | 12 | 3 | 0 | 2 |
| Api | 0 | 0 | 0 | 0 | 0 |

#### 4.2.2 Orchestration Architecture

The Orchestration domain manages automated game server assignment and match config deployment. It uses a **Strategy pattern** via `MatchHandlerContract` — each game engine (CS2/Source 2 via TMT2, future engines) has a concrete handler implementation.

**Server Selection Algorithm:** When processing a job, `SelectServerForMatch` queries available servers for the required game, ordered by allocation priority (Competition=1 > Flexible=2 > Casual=3), with pessimistic locking (`lockForUpdate()`) to prevent race conditions in concurrent processing.

**Cross-Domain Event Integration:** The Competition domain emits `MatchReadyForOrchestration` events (triggered by LanBrackets `bracket.generated` and `match.result_reported` webhooks). The Orchestration domain listens and creates queued jobs. Match readiness is determined by checking that all participant slots are filled in the LanBrackets match data.

**Automated Pipeline:** TMT2 `MATCH_END` webhook → LanCore auto-reports to LanBrackets → LanBrackets progresses bracket → LanBrackets sends `match.result_reported` webhook → LanCore orchestrates next-round matches. Zero manual intervention for multi-round tournaments.

**MatchHandler Feature System:** Handlers can implement optional feature interfaces (e.g., `SupportsChatFeature`) to provide capabilities beyond core match handling. Features are detected via `instanceof` checks at runtime.

### 4.3 Concept of Execution

#### 4.3.1 Request Lifecycle

1. **FrankenPHP** receives HTTP request (Octane keeps application booted)
2. **Middleware pipeline** processes request: AddRequestId → TrackHttpMetrics → HandleAppearance → Authentication → CSRF
3. **Router** matches request to controller method
4. **Controller** invokes Form Request validation, then calls Action
5. **Action** executes business logic, dispatches domain events
6. **Listeners** handle side effects asynchronously (queued) or synchronously
7. **Controller** returns Inertia response (renders Vue page with props)
8. **Inertia.js** client receives JSON, renders Vue component without full page reload

#### 4.3.2 Event/Listener Flow

```
Action (e.g., CreateNewsArticle)
  │
  ├── dispatch(NewsArticlePublished)
  │     │
  │     ├── SendNewsNotification (queued)
  │     │     ├── Check user notification preferences
  │     │     ├── Send email to subscribed users
  │     │     └── Send web push to subscribed users
  │     │
  │     └── HandleNewsArticlePublishedWebhooks (queued)
  │           ├── Find webhooks subscribed to this event
  │           ├── Build payload
  │           └── dispatch(DispatchWebhooks)
  │                 └── SendWebhookPayload (per webhook, queued)
  │                       ├── Sign payload with HMAC
  │                       ├── POST to webhook URL
  │                       └── Record WebhookDelivery
  │
  └── return result to Controller
```

#### 4.3.3 Payment Flow

```
User → CartController (add items)
  │
  ├── ShopController → CreateCheckoutSession
  │     │
  │     ├── PaymentProviderManager → resolve provider
  │     │
  │     ├── StripePaymentProvider
  │     │     ├── Create Stripe Checkout Session
  │     │     └── Redirect to Stripe
  │     │
  │     └── OnSitePaymentProvider
  │           └── Create pending order
  │
  ├── Stripe Webhook → FulfillOrder
  │     ├── CreateOrder → create tickets
  │     ├── dispatch(TicketPurchased)
  │     └── HandleTicketPurchasedWebhooks
  │
  └── Admin manual fulfillment → FulfillOrder (same flow)
```

#### 4.3.3a Group Ticket Design

**Data Model:**
- `TicketType` gains `max_users_per_ticket` (int, default 1) and `check_in_mode` (CheckInMode enum: `individual`|`group`)
- `Ticket` no longer has a singular `user_id`; instead uses a `ticket_user` pivot table (BelongsToMany)
- Each pivot row tracks `checked_in_at` for per-user check-in status
- `is_row_ticket` is deprecated (column retained, removed from UI)

**Seat Capacity Formula:**
```
total_seats_consumed = seats_per_user × max_users_per_ticket × quantity
```
Reserved at purchase time. Remaining capacity = `event.seat_capacity - Σ(total_seats_consumed) - Σ(addon_seats)`.

**On-Site Payment Flow:**
```
User Checkout (on_site) → Order created (Pending) → User sees "Pay at venue"
Admin → orders/Show → "Confirm Payment Received" → FulfillOrder → Completed + Tickets issued
```
On-site orders are NOT immediately fulfilled. They remain `Pending` until an admin confirms payment via `OrderController.confirmPayment()`, which calls `FulfillOrder.execute()` to create tickets and send the order confirmation notification.

**Check-In Flow:**
```
Individual Mode:
  POST /tickets/{ticket}/check-in {user_id: N}
    → Set ticket_user.checked_in_at for user N
    → If ALL users now checked in → set ticket.status = CheckedIn

Group Mode:
  POST /tickets/{ticket}/check-in
    → Set ticket_user.checked_in_at for ALL assigned users
    → Set ticket.status = CheckedIn
```

**Enum:** `App\Domain\Ticketing\Enums\CheckInMode` (Individual, Group)

**Token Rotation Triggers:**

| Trigger | Source | Token Action | Notification |
|---------|--------|--------------|--------------|
| Initial issuance | `FulfillOrder::execute` | Bump epoch 0 → 1, derive nonce, re-sign, dispatch PDF | No (purchase confirmation mail covers this) |
| `updateManager` | `UpdateTicketAssignments` | Bump epoch, derive nonce, re-sign, dispatch PDF | Owner + assigned users + previous manager |
| `addUser` | `UpdateTicketAssignments` | Bump epoch, derive nonce, re-sign, dispatch PDF | Owner + assigned users (incl. new) |
| `removeUser` | `UpdateTicketAssignments` | Bump epoch, derive nonce, re-sign, dispatch PDF | Owner + assigned users + removed user |
| `rotateToken` (admin) | `AdminTicketController::rotateToken` → `UpdateTicketAssignments` | Bump epoch, derive nonce, re-sign, dispatch PDF | Owner + assigned users |
| `rotateTokenUser` | `TicketController::rotateTokenUser` → `UpdateTicketAssignments::rotateToken` | Bump epoch, derive nonce, re-sign, dispatch PDF | Owner + assigned users |
| Ticket cancel | Cancellation action | Clear nonce hash, kid, issued_at, expires_at | (existing flow) |
| QR render / PDF regen | `TicketController::qrCode`, `download` | **No rotation.** `render()` pure-function, reads stored epoch, re-derives nonce, re-signs | — |

Every rotation call sequence is `TicketTokenService::rotate($ticket)` → persists new nonce hash, kid, and timestamps → `dispatchPdf()` with the freshly signed envelope → `notifyRotation()` fans the `TicketTokenRotatedNotification` out to owner + assigned users + any removed/previous-manager context. Old QRs become unresolvable immediately because the stored nonce hash no longer matches their embedded nonce.

#### 4.3.4 SSO Authorization Flow

```
Integration App → redirect user to LanCore SSO endpoint
  │
  ├── User authenticates (if needed)
  ├── GenerateSsoAuthorizationCode
  │     └── Create time-limited code
  ├── Redirect to integration callback URL with code
  │
  └── Integration → ExchangeSsoAuthorizationCode (API call)
        ├── Validate code
        ├── ResolveIntegrationUser
        └── Return user data as JSON
```

### 4.4 Interface Design

#### 4.4.1 Inertia.js Bridge

Controllers return Inertia responses that serialize PHP data to JSON props consumed by Vue pages:

```php
// Controller
return Inertia::render('events/Edit', [
    'event' => $event->load('venue', 'programs'),
    'venues' => Venue::all(),
]);
```

```vue
<!-- Vue Page -->
<script setup>
defineProps<{ event: Event; venues: Venue[] }>()
</script>
```

#### 4.4.2 Wayfinder Route Bindings

Laravel Wayfinder generates TypeScript functions for backend routes:

```typescript
import { show } from '@/actions/Domain/Event/Controllers/EventController'
// Generates: /events/{event}
show({ id: 1 })
```

---

## 5. CSCI Detailed Design

### 5.1 Middleware Pipeline

| Order | Middleware | Purpose |
|-------|-----------|---------|
| 1 | AddRequestId | Assigns X-Request-ID header for tracing |
| 2 | TrackHttpMetrics | Records Prometheus metrics |
| 3 | HandleAppearance | Reads appearance/theme preference |
| 4 | SetLocale | Applies the authenticated user's stored `locale` (from `users.locale`) via `app()->setLocale()`; for unauthenticated requests, parses `Accept-Language` and maps to the nearest supported locale (`en`, `de`, `fr`, `es`), falling back to `en`. Must run after `StartSession` so the authenticated user is available; runs before `HandleInertiaRequests` so the active locale is set when Inertia builds its shared props. Traces to I18N-F-002. |
| 5 | HandleInertiaRequests | Shares global data with Inertia (auth, flash, push prompt dismissal, permissions, `organization`, `myEventContext`, `locale`, `availableLocales`, etc.). The `organization` prop is read from cache key `inertia.organization` (1h TTL, invalidated by `OrganizationSettingsController::update/uploadLogo/removeLogo`). The `myEventContext` prop resolves the user's currently selected event from session key `my_selected_event_id`, validates participation via `Event::scopeForUser`, and auto-clears a stale selection. The `locale` prop is `app()->getLocale()` after `SetLocale` has run; `availableLocales` is `['en', 'de', 'fr', 'es']`. Traces to I18N-F-003. |
| 6 | EncryptCookies | Cookie encryption |
| 7 | StartSession | Session initialization |
| 8 | VerifyCsrfToken | CSRF protection |
| 9 | EnsureUserHasRole | Role-based route protection (alias: `role`) |
| 10 | AuthenticateIntegration | Bearer token validation for API routes |

### 5.2 Service Layer

#### 5.2.1 ModelCacheService

Provides distributed caching with Redis tag support:

- Tag-based cache groups for efficient invalidation
- Fallback registry pattern for cache stores without tag support
- Group-based invalidation (e.g., flush all event caches)
- Handles stale serialization gracefully

#### 5.2.2 PaymentProviderManager

Factory pattern for payment provider selection:

- Resolves `StripePaymentProvider` or `OnSitePaymentProvider` based on configuration
- Implements `PaymentProvider` contract
- Returns `PaymentResult` objects

#### 5.2.3 Webhook Listeners

Event-driven listeners handle side effects from external systems:

| Listener | Event | Responsibility |
|----------|-------|---------------|
| HandleStripeCheckoutCompleted | `WebhookReceived` (Cashier) | Fulfills orders on `checkout.session.completed` webhook |
| HandleTicketPurchasedWebhooks | `TicketPurchased` | Dispatches webhook notifications to integration apps |

**HandleStripeCheckoutCompleted** design:
- Filters `WebhookReceived` events for `checkout.session.completed` type
- Extracts `order_id` from Stripe session metadata
- Validates order exists and has `Pending` status
- Updates order with `provider_session_id` and `provider_transaction_id`
- Delegates to `FulfillOrder` action (idempotent — skips if already `Completed`)
- Gracefully ignores malformed or irrelevant webhooks

#### 5.2.4 Contracts

| Contract | Purpose |
|----------|---------|
| Purchasable | Defines purchasable items (tickets, add-ons) |
| PurchasableDependency | Defines purchase dependencies |
| PaymentProvider | Payment processing abstraction |
| PaymentResult | Payment outcome encapsulation |

### 5.3 Frontend Architecture

#### 5.3.1 Page Components (95+)

Organized under `resources/js/pages/`:

| Area | Pages | Description |
|------|-------|-------------|
| Auth | 7 | Login, Register, ForgotPassword, ResetPassword, VerifyEmail, ConfirmPassword, TwoFactorChallenge |
| Dashboard | 1 | Main dashboard |
| Events | 5 | Index, Create, Edit, Public, Audit |
| Venues | 3 | Index, Create, Edit |
| Programs | 4 | Index, Create, Edit, Audit |
| Ticket Types | 4 | Index, Create, Edit, Audit |
| Ticket Categories | 4 | Index, Create, Edit, Audit |
| Ticket Add-ons | 4 | Index, Create, Edit, Audit |
| Tickets | 2 | Index, Show |
| Admin Tickets | 2 | Index, Show |
| Shop | 2 | Index, CheckoutSuccess |
| Cart | 2 | Index, Checkout |
| Orders | 2 | Index, Show |
| Vouchers | 4 | Index, Create, Edit, Audit |
| Purchase Requirements | 3 | Index, Create, Edit |
| Global Conditions | 3 | Index, Create, Edit |
| Payment Conditions | 3 | Index, Create, Edit |
| Games | 3 | Index, Create, Edit |
| Game Modes | 2 | Create, Edit |
| Seating | 6 | Index, Create, Edit, Audit, partials/BlockCategoryEditor, partials/InvalidationConfirmDialog |
| Seating Picker | 1 | Picker (end-user seat selection) |
| Sponsors | 4 | Index, Create, Edit, Audit |
| Sponsor Levels | 4 | Index, Create, Edit, Audit |
| News | 5 | Index, Create, Edit, Show, Audit |
| News Comments | 2 | Index, Audit |
| Announcements | 4 | Index, Create, Edit, Public |
| Achievements | 3 | Index, Create, Edit |
| Integrations | 3 | Index, Create, Edit |
| Webhooks | 4 | Index, Create, Edit, Show |
| Users | 2 | Index, Show |
| Settings | 6 | Profile, Security, Notifications, TicketDiscovery, Achievements, Appearance |

#### 5.3.2 UI Component Library

Built on **reka-ui** (headless) + **Tailwind CSS v4**:

| Component | Purpose |
|-----------|---------|
| Card, CardHeader, CardContent, CardFooter | Content containers |
| Button, Badge, Switch | Interactive elements |
| Dialog, Sheet, Alert | Overlays and feedback |
| DataTable (TanStack) | Sortable, filterable tables |
| RichTextEditor (TipTap) | Rich text editing for news/announcements |
| BannerCarousel | Event banner rotation |
| Skeleton, Spinner | Loading states |
| Breadcrumb | Navigation context |
| NavUser | User menu with account actions |
| NotificationBell | Real-time notification indicator and dropdown |
| PushNotificationPrompt | Web push subscription prompt |
| SeatMapCanvas | Canvas-based seat plan rendering |
| TicketCard | Ticket display with validation and status |
| AppLogo | Renders the organization logo (or a text fallback) sourced from the `organization` Inertia shared prop |
| MailLetterAnimation | Animated envelope illustration used in email-confirmation and verification screens |

#### 5.3.3 Frontend Libraries

| Library | Version | Purpose |
|---------|---------|---------|
| @inertiajs/vue3 | ^2.3 | Server-driven SPA |
| @tiptap/vue-3 | Latest | Rich text editor |
| @tanstack/vue-table | ^8.21 | Advanced data tables |
| reka-ui | ^2.9 | Headless UI components |
| lucide-vue-next | Latest | Icon library |
| vue-input-otp | Latest | OTP input for 2FA |
| @alisaitteke/seatmap-canvas | ^2.7.1 | Seating visualization |
| tailwindcss | ^4.2 | Utility CSS |

### 5.3a Design Notes — Competition Domain

#### LeaveTeam Action Contract

`LeaveTeam::execute(): bool` implements a two-branch departure flow:

- **Non-last-member departure:** Removes the member from `CompetitionTeamMember`. If the departing user was captain, captaincy is transferred to the active member with the earliest `joined_at`. Returns `false`.
- **Last-member departure:** Deletes the `CompetitionTeam` record entirely. Returns `true` (team deleted).

On both branches the controller redirects to `my-competitions.show` and sets a flash `success` message appropriate to the branch (standard leave vs. team disbanded).

Non-members are rejected at the policy layer (`CompetitionTeamPolicy::leave()` → 403) before the action is reached.

### 5.3b Design Notes — Event Context Controller

`EventContextController::storeMy(Request $request)` accepts a validated `event_id`, queries `Event::forUser($user)` to confirm participation (ownership of a ticket, active team membership in a competition linked to the event, or an order with `event_id`), stores the result in `session('my_selected_event_id')`, and returns a redirect.

`EventContextController::destroyMy()` simply removes the `my_selected_event_id` session key.

The `myEventContext` Inertia shared prop (set by `HandleInertiaRequests`) re-resolves the selected event on every request. If the stored ID no longer appears in `Event::forUser($user)` (participation lost), the session key is cleared automatically and the prop returns `null`, ensuring stale context never filters "My Pages" views incorrectly.

### 5.3c Design Notes — Seating Domain

#### Seat Assignment Data Model (SET-F-002, SET-F-006)

Seating state lives in six relational tables rooted at `seat_plans`:

| Table | Purpose |
|---|---|
| `seat_plans` | Named plan belonging to an `event`; carries optional `background_image_url`. |
| `seat_plan_blocks` | Visual grouping within a plan (title, color, optional background image, sort order). |
| `seat_plan_rows` | Ordered row within a block (unique name per block). Drives incremental per-row seat numbering. |
| `seat_plan_seats` | Individual seat with denormalized `seat_plan_id` (hot-path lookup), nullable `seat_plan_row_id`, optional `number`, x/y coordinates, salable flag, color/note/custom JSONB. Unique `(block, row, number)`. |
| `seat_plan_labels` | Free-form text annotations on a block (e.g., row letters). |
| `seat_plan_block_category_restrictions` | Pivot between a block and the `ticket_categories` it accepts — SET-F-011. |

`SeatAssignment` (`App\Domain\Seating\Models`) joins `Ticket`, `User`, `SeatPlan`, and a `seat_plan_seat_id` foreign key. Two database-enforced unique constraints keep the model honest:

1. `UNIQUE (seat_plan_id, seat_plan_seat_id)` — a single seat can never be double-booked.
2. `UNIQUE (ticket_id, user_id)` — each user holds at most one seat per ticket.

The FK on `seat_plan_seat_id` is `RESTRICT ON DELETE` so the two-phase save flow (below) is the only code path that releases assignments — Postgres will not silently cascade away an active assignment when a seat is removed.

All seating models (`SeatPlan`, `SeatPlanBlock`, `SeatPlanRow`, `SeatPlanSeat`, `SeatPlanLabel`, `SeatAssignment`) implement `Auditable` (laravel-auditing), so every entity-level mutation leaves an audit row.

#### Authorization (SET-F-007)

`TicketPolicy::pickSeat(User $actor, Ticket $ticket, User $assignee)` is the single gate. It checks (in order): not checked-in → ManageTicketing permission → owner → manager → self-on-pivot. The "self" branch only fires when `actor.id === assignee.id` AND the actor is on the `ticket_user` pivot. This codifies the "roles non-exclusive" rule: an owner who is also on the user pivot keeps owner rights.

#### Picker Page (SET-F-006/007)

`SeatPickerController@show` (`GET /events/{event}/seats`) renders the Inertia page `seating/Picker.vue`. It hydrates four props:

- `event`, `seatPlans` — the event's seat plans projected via `SeatPlanResource`. The resource emits the pre-normalization wire shape `{id, name, event_id, background_image_url, blocks:[{id, title, color, background_image_url, seats:[…], labels:[…], allowed_ticket_category_ids:[…]}]}`, so the `@alisaitteke/seatmap-canvas` wrapper continues to consume it unchanged (SET-F-016).
- `taken[]` — every existing assignment on this event's seat plans, with `name` set per `User::isSeatNameVisibleTo` (privacy + same-event override). The `seat_id` field now carries the integer `seat_plan_seat_id` PK.
- `myTickets[]` — tickets the viewer can act on, decorated per assignee with `can_pick` (the `Gate::pickSeat` outcome) and the existing `assignment` if any.
- `context` — the optional `?ticket=&user=` query, used to preselect a context.

The Vue side decorates the active seat plan's blocks before passing them to `SeatMapCanvas`: occupied seats become `salable: false` and have their `title` overwritten with `getInitials(name)` so the canvas renders the assignee's initials. Seat clicks come back through the wrapper's `seat-click` emit (the wrapper subscribes to the library's `seat:click` event).

`@store` runs `Gate::authorize('pickSeat', [$ticket, $assignee])`, then delegates to `AssignSeat::execute`. `@destroy` does the same for `ReleaseSeat`.

#### Concurrency

`AssignSeat::execute` wraps the upsert in `DB::transaction` and catches `QueryException` for unique-violation (SQLSTATE 23000/23505), surfacing a friendly "That seat was just taken" `ValidationException`. No advisory lock is needed beyond the unique index.

#### Auto-release Lifecycle (SET-F-008)

Two release paths, both Eloquent so audit rows are emitted:

- **Cancellation** — `Ticket::booted()` registers a `static::updated` listener that, when `wasChanged('status')` and the new status is `Cancelled`, calls `$ticket->seatAssignments()->delete()`.
- **User removed from group ticket** — `UpdateTicketAssignments::removeUser()` calls `ReleaseSeat::execute($ticket, $user)` inside its existing transaction, before detaching from the `ticket_user` pivot.

A hard-delete of the ticket itself is also covered by the migration's `cascadeOnDelete()` foreign keys.

#### Privacy Override (SET-F-009/010)

`User::isSeatNameVisibleTo(?User $viewer, Event $event)` returns `true` when:

- the user has `is_seat_visible_publicly = true`, OR
- viewer is the user themselves, OR
- viewer holds at least one ticket for the event as owner, manager, or pivot user.

Both `WelcomeController::getTakenSeats` and `SeatPickerController::show` use this accessor when assembling their `taken[]` overlays — emitting `null` for the name when the viewer is not allowed to see it. Initials are derived client-side via the existing `getInitials()` composable so no server-side initials helper is needed.

`/settings/privacy` (`PrivacyController` mirroring the existing `TicketDiscoveryController` shape) is the toggle UI. Default for new users is `true` (visible).

#### 5.3c.2 Category Rules Helper (SET-F-011)

`App\Domain\Seating\Support\SeatingCategoryRules` is the single source of truth for per-block category gating:

- `blockAccepts(SeatPlanBlock $block, ?int $categoryId): bool`
- `allowedCategoryIds(SeatPlanBlock $block): int[]`

The allowlist now lives in the `seat_plan_block_category_restrictions(seat_plan_block_id, ticket_category_id)` pivot. An empty pivot for a block ⇒ accept all — this permissive default keeps plans working without retroactive edits and makes restriction strictly opt-in. Callers should eager-load `block.categoryRestrictions` to avoid N+1.

Enforcement happens in three places, all of which call `SeatingCategoryRules`:
1. `AssignSeat::ensureBlockAcceptsCategory` on the server — throws `ValidationException` with key `seat_id` and message `seating.errors.block_category_forbidden`.
2. `Picker.vue::decoratedPlanData` on the client — marks rejected seats `salable: false` so they render greyed out.
3. `Picker.vue::onSeatClick` — surfaces the amber `seating.picker.hint.blockNotForCategory` banner when the user clicks a rejected seat, distinguishing category-forbidden from taken-by-another-user.

Category-mismatch is deliberately NOT modelled as a `TicketPolicy::pickSeat` rejection: the viewer has every right to act on the ticket; they've just aimed at the wrong block. A 422 validation error with a clear message is correct UX; a 403 would be misleading.

#### 5.3c.3 Edit-Safety / Two-Phase Save (SET-F-012, SET-F-013)

`UpdateSeatPlan::execute(SeatPlan, array $attributes, bool $confirmInvalidations = false): UpdateSeatPlanResult`.

The `UpdateSeatPlanResult` value object (`App\Domain\Seating\Support`) carries three named constructors: `pending($invalidations)`, `saved(int $count, array $idMap)`, and `emptyIdMap()`. Public props `invalidations`, `releasedCount`, and `idMap` let the controller branch on `back()->with('invalidations', ...)` vs `back()->with('status', 'seat-plan-updated')->with('id_map', ...)`. The `id_map` flash lets the editor reconcile client-generated `new-*` placeholders with the persisted PKs without a full page reload.

Diff logic (now O(n) over PK sets, since seats have stable IDs):
1. Index the proposed payload's seats by numeric PK (`payload.blocks[].rows[].seats[].id`). Non-numeric IDs indicate client-generated `new-*` placeholders and are skipped for diff purposes.
2. Load all `SeatAssignment`s for the plan (eager-loaded with `ticket.ticketType`, `user`, and `seat.block` for the confirmation dialog).
3. For each existing assignment:
   - assignment's `seat_plan_seat_id` not in the incoming index ⇒ `reason: 'seat_removed'`
   - seat still present but the incoming block's `allowed_ticket_category_ids` excludes the assignment's ticket-type category ⇒ `reason: 'category_mismatch'`

Persistence is delegated to `App\Domain\Seating\Support\SeatPlanTreeSyncer`, which performs a full-state replace: entities with numeric IDs are updated in place, new-* placeholders are inserted (and their PK recorded in `idMap`), and any existing row not referenced in the payload is deleted. The whole `UpdateSeatPlan::execute` body runs inside a single `DB::transaction` when there is work to do; assignment deletes dispatch `SeatAssignmentInvalidated` before the syncer deletes the underlying seats (ordering matters — the FK is `restrictOnDelete`).

`SeatPlanController::update` reads `confirm_invalidations` from the validated request and passes it through. On success it also flashes `id_map` for the editor. The Inertia form flashes invalidation rows back to `Edit.vue`'s `<InvalidationConfirmDialog>` when needed; confirming re-submits with the flag set.

#### 5.3c.4 Notification & Preferences (SET-F-014)

`App\Domain\Notification\Notifications\SeatAssignmentInvalidatedNotification` mirrors the `NewsPublishedNotification` shape.

`via()` contract:
- `'database'` — always (in-app bell requires it; no preference can disable).
- `'mail'` — when `preferences.mail_on_seating === true` (default true — email is opt-out).
- `'push'` — when `preferences.push_on_seating === true` (default false — push is opt-in).

`toArray()` schema, used for both the bell and the push payload:
```json
{
  "ticket_id": int,
  "user_id": int,
  "event_id": int,
  "seat_plan_id": int,
  "previous_seat_id": int,
  "previous_seat_title": "string|null",
  "previous_block_id": "int|null",
  "reason": "seat_removed" | "category_mismatch"
}
```

The listener `App\Domain\Seating\Listeners\NotifyAffectedAssignees` is queued (`ShouldQueue`) and dispatches to the deduped set of ticket owner + manager + assignee. Registered in `AppServiceProvider` alongside the other `EventFacade::listen(...)` calls.

#### 5.3c.5 Admin Editor Architecture (SET-F-001, SET-F-015)

The admin-facing seat-plan editor is a lightweight SVG-based Vue component tree hosted in `resources/js/pages/seating/Edit.vue`, which exposes a four-tab layout (Editor / Preview / Categories / JSON). The editor tree lives under `resources/js/components/seating-editor/`.

| Component | Role |
|---|---|
| `EditorShell.vue` | Orchestrator. Owns the working-copy store, wires tool changes, routes canvas clicks into seat/label creation, mounts `AddBlockDialog`, registers keyboard shortcuts (V/S/L/Space tools, Ctrl+Z/Y undo/redo, Delete, Esc, Ctrl+S). |
| `EditorCanvas.vue` | Pure Vue SVG canvas. Renders the plan-level and per-block `<image>` backgrounds, optional grid, every block → seats/labels. Pointer handlers implement drag-to-move (on selected seats/labels), marquee-select (in Select mode), pan (middle-mouse or Pan tool), wheel-zoom. Uses `@vueuse` primitives minimally; no external canvas library. |
| `EditorToolbar.vue` | Tool switcher, snap-to-grid toggle, zoom +/−/reset, undo/redo, unsaved-changes pill, Save button. |
| `PropertiesPanel.vue` | Context-sensitive right sidebar. Switches on selection count: plan-level fields (name, global background upload) when empty; seat / block / label fields for one selection; mass-edit controls (toggle salable, bulk delete) for many. |
| `AddBlockDialog.vue` | "Empty" and "NxM grid" modes. The grid wizard synthesizes rows and seats at a configurable pitch + origin and writes them into a fresh block — the fastest path from "empty plan" to a realistic venue layout. |
| `BackgroundImageUpload.vue` | Thin wrapper around the plan- and block-level background upload endpoints (§3.17 IDD). Plain multipart POST; emits the persisted URL back to the properties panel. |
| `useEditorStore.ts` | Reactive working-copy state plus a 50-snapshot undo/redo ring. Exposes `applyMutation(label, fn)` as the sole entry point for edits, so every change is undoable. `reconcileIds(map)` rewrites client `new-*` placeholders to persisted PKs after save, then clears the undo stack. |
| `geometry.ts` | Pure helpers (`snapToGrid`, `rectContainsPoint`, `newClientId`, `generateGridSeats`), trivially unit-testable. |

**New-ID reconciliation.** While a block, row, seat, or label exists only in the working copy, it carries a client-generated `new-<base36>` string ID (`geometry.ts::newClientId`). `SeatPlanTreeSyncer` on the server treats non-numeric IDs as "insert", records the mapping `{'new-abc' → 42, …}` in `UpdateSeatPlanResult::idMap`, and the controller flashes it back as `id_map`. `useEditorStore::reconcileIds` walks the working copy and rewrites IDs in place, then calls `markSaved()`. The admin sees no reload flicker; subsequent edits already reference the real PKs.

**Preview tab** renders the same working copy through the existing read-only `SeatMapCanvas.vue` so the admin can sanity-check the editor output without leaving the page.

**JSON tab** retains a raw `{blocks:[…]}` textarea escape hatch — valid on-type JSON replaces the working copy; invalid input is ignored without clobbering state.

**Save contract.** `EditorShell` emits `save(plan)` into `Edit.vue`, which serialises only the `{blocks: [...]}` subset into `form.data`, then PATCHes `SeatPlanController::update`. The two-phase flow (SET-F-012/013) is unchanged from the user's perspective — the existing `InvalidationConfirmDialog` fires from `flash.invalidations` exactly as before.

**Interaction bindings and save feedback.** Plain left-drag on empty space starts a rubber-band marquee selection (Select tool); panning is triggered by right-click or middle-click drag, regardless of the active tool (the SVG has `@contextmenu.prevent` to suppress the browser context menu). Holding Space also switches to the Pan tool for left-drag panning. Wheel zoom is centred on the cursor via a linear-mapping solve that keeps the world point under the pointer fixed as the viewBox width shrinks / grows. The Delete tool (keyboard `D`) turns clicks on seats and labels into single-entity removes. Creating a block through `AddBlockDialog` selects every seat in the new block (or the block itself for the empty mode) so the admin can move or mass-edit without a second selection pass. A Move-to-block `<Select>` in `PropertiesPanel` splices the selected seats out of their source blocks, nulls their `seat_plan_row_id`, and pushes them onto the target block, updating the selection refs so the panel stays stable. Blocks may carry a `seat_title_prefix` — `EditorCanvas` renders `prefix + seat.title` on the SVG label while `PropertiesPanel` edits the raw title; `SeatPlanResource` bakes the prefix into the wire `seats[].title` so the `seatmap-canvas` library shows the combined string without any client-side join. Save feedback lives in the toolbar: `idle | saving | saved | error` state; `saved` triggers a palette-aware `canvas-confetti` burst (`celebrate.ts::celebrateSave`) that honours `prefers-reduced-motion: reduce`, and `error` surfaces a dismissible red banner populated from the Inertia `onError` payload.

### 5.4 Console Commands (21)

| Category | Commands |
|----------|----------|
| Integration | CreateIntegrationAppCommand, CreateIntegrationTokenCommand, ListIntegrationAppsCommand, ListIntegrationTokensCommand, RevokeIntegrationTokenCommand |
| Data Listing | ListEvents, ListVenues, ListPrograms, ListSponsors, ListSponsorLevels, ListTickets, ListTicketTypes, ListAddons, ListNews, ListGames, ListSeatPlans, ListUsers |
| Administration | PromoteUserToAdmin, SeedDemoCommand |
| Utilities | MigrateStorageCommand, TestPushNotificationCommand |
| Ticket Security | `tickets:keys:rotate` — generates a new Ed25519 key pair, assigns a `kid`, writes the private key to `storage/keys/ticket_signing/{kid}.key` (mode 0600), and sets the new key as active for subsequent token issuance |

#### 5.4.1 Demo Mode Design

When `APP_DEMO=true` is set in the environment, the system enters Demo mode:

1. **Data seeding:** `SeedDemoCommand` (existing) populates the database with a synthetic published event, ticket types, a demo admin user, and sample attendees. It is idempotent — running it multiple times does not duplicate records.
2. **Payment provider:** No custom payment provider is used in demo mode. `PaymentProviderManager` resolves `StripePaymentProvider` as it does in normal mode. The demo environment is configured with Stripe test-mode keys (`STRIPE_KEY=pk_test_...`, `STRIPE_SECRET=sk_test_...`). All checkout flows run end-to-end against Stripe's sandbox API using test card numbers (e.g., `4242 4242 4242 4242`). No real charges are created.
3. **Stripe Checkout and webhook routes:** Both routes function normally and hit Stripe's sandbox API. A webhook endpoint must be registered in the Stripe test-mode dashboard and forwarded to the application (e.g., via the Stripe CLI `stripe listen --forward-to`). Stripe test-mode keys prevent any real charges regardless of the card number used.
4. **Normal mode restoration:** Replacing the Stripe test-mode keys with live keys (`pk_live_...` / `sk_live_...`) and setting `APP_DEMO=false` (then clearing application cache) returns the system to normal operation. Demo data is not automatically purged — admins must run a migration rollback or a dedicated cleanup seeder if clean-slate state is desired.

### 5.5 Integration Client Library Design (CSCI-ICLIB)

The `lan-software/lancore-client` package is designed as a thin, Laravel-native Composer library. Its source of truth lives in a separate public repository (`https://github.com/lan-software/lancore-client`) but its documentation is maintained here as part of the LanCore MIL-STD-498 set (see SSDD §5.4 for the ownership rationale).

#### 5.5.1 Package Structure

```
lan-software/lancore-client/
├── composer.json                    # php ^8.3, laravel/framework ^13.0
├── config/
│   └── lancore.php                  # publishable template, all env bindings
├── src/
│   ├── LanCoreServiceProvider.php
│   ├── LanCoreClient.php            # core: SSO + user resolution
│   ├── Entrance/
│   │   ├── EntranceClient.php       # opt-in sub-client
│   │   └── JwksCache.php            # Laravel Cache facade wrapper
│   ├── DTOs/
│   │   ├── LanCoreUser.php          # readonly class
│   │   ├── AttendeeTicket.php
│   │   ├── CheckinResult.php
│   │   ├── EntranceStats.php
│   │   └── SigningKey.php
│   ├── Exceptions/
│   │   ├── LanCoreException.php                # base
│   │   ├── LanCoreDisabledException.php
│   │   ├── LanCoreUnavailableException.php
│   │   ├── LanCoreRequestException.php         # exposes statusCode
│   │   └── InvalidLanCoreUserException.php
│   ├── Webhooks/
│   │   ├── Middleware/
│   │   │   └── VerifyLanCoreWebhook.php
│   │   ├── Payloads/
│   │   │   ├── UserRegisteredPayload.php
│   │   │   ├── UserRolesUpdatedPayload.php
│   │   │   ├── UserProfileUpdatedPayload.php
│   │   │   ├── AnnouncementPublishedPayload.php
│   │   │   ├── NewsArticlePublishedPayload.php
│   │   │   ├── EventPublishedPayload.php
│   │   │   ├── TicketPurchasedPayload.php
│   │   │   └── IntegrationAccessedPayload.php
│   │   └── Controllers/
│   │       ├── HandlesLanCoreWebhook.php              # abstract base
│   │       ├── HandlesLanCoreUserRegisteredWebhook.php
│   │       ├── HandlesLanCoreUserRolesUpdatedWebhook.php
│   │       ├── HandlesLanCoreUserProfileUpdatedWebhook.php
│   │       ├── HandlesLanCoreAnnouncementPublishedWebhook.php
│   │       ├── HandlesLanCoreNewsArticlePublishedWebhook.php
│   │       ├── HandlesLanCoreEventPublishedWebhook.php
│   │       ├── HandlesLanCoreTicketPurchasedWebhook.php
│   │       └── HandlesLanCoreIntegrationAccessedWebhook.php
│   └── Testing/
│       └── LanCoreClientFake.php    # ::fake() helper
└── tests/                            # Pest, Http::fake()
```

#### 5.5.2 LanCoreClient Class Design

```php
final class LanCoreClient
{
    public function __construct(
        private readonly LanCoreConfig $config,
    ) {}

    public function ssoAuthorizeUrl(): string;
    public function exchangeCode(string $code): LanCoreUser;
    public function currentUser(): LanCoreUser;
    public function resolveUserById(int $id): LanCoreUser;
    public function resolveUserByEmail(string $email): LanCoreUser;
    public function entrance(): EntranceClient;  // opt-in
}
```

The client is bound as an Octane-safe singleton via `LanCoreServiceProvider`. It reads configuration lazily at each method call through the injected `LanCoreConfig` value object, so a change to `config('lancore.*')` between requests does not require container re-binding. No request state is captured at construction.

All HTTP calls go through `Http::withToken($this->config->token)->retry($this->config->retries, $this->config->retryDelay)->timeout($this->config->timeout)`. Server-to-server calls use `$this->config->internalUrl`; browser-facing URL construction uses `$this->config->baseUrl`. 4xx and 5xx responses are distinguished after `->throw()` is called: `Illuminate\Http\Client\RequestException` with `$e->response->status() >= 500` (or underlying `ConnectionException`) becomes `LanCoreUnavailableException`; 4xx becomes `LanCoreRequestException`. Response bodies are validated against `LanCoreUser::fromArray()`, which throws `InvalidLanCoreUserException` on schema mismatch.

#### 5.5.3 LanCoreUser DTO

```php
final readonly class LanCoreUser
{
    public function __construct(
        public int $id,
        public string $username,
        public ?string $email,
        public ?string $locale,
        public ?string $avatar,
        public array $roles,               // list<string>
        public ?CarbonImmutable $createdAt,
    ) {}

    public static function fromArray(array $data): self;
    public function toArray(): array;
}
```

Readonly to match PHP 8.3 capability and to guarantee immutability across Octane workers.

#### 5.5.4 Webhook Verification Middleware

`VerifyLanCoreWebhook` accepts an event-name argument: `->middleware('lancore.webhook:user.roles_updated')`. Behavior:

1. Reject if `X-Webhook-Event` header is absent or not equal to the argument — 400.
2. Compute `expected = 'sha256=' . hash_hmac('sha256', $request->getContent(), config('lancore.webhooks.secret'))`.
3. Compare against `X-Webhook-Signature` header using `hash_equals()` — 401 on mismatch.
4. If `config('lancore.webhooks.secret')` is an empty string, skip verification (local dev only; the config template documents this).
5. Pass request through.

#### 5.5.5 Abstract Webhook Controller Contract

Each concrete abstract controller corresponds to one webhook event. Example for the roles-updated variant:

```php
abstract class HandlesLanCoreUserRolesUpdatedWebhook
{
    public function __invoke(Request $request): JsonResponse
    {
        $payload = UserRolesUpdatedPayload::fromRequest($request);

        $user = $this->resolveUser($payload->lancoreUserId);
        if ($user === null) {
            return response()->json(['status' => 'user_not_found'], 202);
        }

        $this->handle($user, $payload);

        return response()->json(['status' => 'ok']);
    }

    abstract protected function resolveUser(int $lancoreUserId): ?Model;
    abstract protected function handle(Model $user, UserRolesUpdatedPayload $payload): void;
}
```

Satellites subclass the relevant controllers, implement `resolveUser()` using their preferred lookup strategy (dedicated `lancore_user_id` column or `external_provider` + `external_id` pair), and implement `handle()` using their preferred role model (single `UserRole` enum or pivot-table role sync). The payload DTO normalises inputs so satellites never hand-parse request arrays.

#### 5.5.6 Entrance Sub-Client

`$client->entrance()` returns an `EntranceClient` only when `config('lancore.entrance.enabled') === true`; otherwise it throws `LanCoreDisabledException`. The sub-client wraps `/api/entrance/*` endpoints with the same retry/exception semantics as the core client and provides JWKS caching via `JwksCache`, which writes to the cache store named in `config('lancore.entrance.signing_keys_cache_store')` under key `lancore.jwks` with TTL `config('lancore.entrance.signing_keys_cache_ttl')`. A bootstrap JWKS (`config('lancore.entrance.signing_keys_bootstrap')`) is parsed from a comma-separated `kid:x` list and used when the cache is cold and the upstream endpoint is unreachable.

#### 5.5.7 Satellite Integration Pattern

A typical satellite consumes the package as follows:

1. `composer require lan-software/lancore-client`
2. `php artisan vendor:publish --tag=lancore-config` (publishes `config/lancore.php`)
3. Populate `.env` with `LANCORE_*` variables (see SSDD §5.4.4)
4. Inject `LanCoreClient` into SSO controllers (replaces the satellite's own `app/Services/LanCoreClient.php`)
5. Subclass the abstract webhook controllers the satellite cares about; register routes with `->middleware('lancore.webhook:<event>')`
6. Delete the satellite's legacy `LanCoreClient`, middleware, and inline webhook signature code
7. Update satellite tests to use `LanCoreClient::fake()` from the package's Testing namespace

The migration PR for each satellite is expected to be net-negative in lines of code.

### 5.6 Internationalization (i18n) Design

#### 5.6.1 Database Schema Change

A single new nullable column is added to the `users` table:

```
users.locale  VARCHAR(10)  nullable  default: null
```

Valid values: `en`, `de`, `fr`, `es`, or NULL. NULL means "use the application default locale (`en`)". Validation is enforced at the Form Request layer before the value is written.

#### 5.6.2 SetLocale Middleware

`app/Http/Middleware/SetLocale.php` — registered in the `web` middleware group after `StartSession` and before `HandleInertiaRequests`:

```php
public function handle(Request $request, Closure $next): Response
{
    $supportedLocales = ['en', 'de', 'fr', 'es'];
    $defaultLocale    = config('app.locale', 'en');

    if ($request->user()?->locale && in_array($request->user()->locale, $supportedLocales, true)) {
        app()->setLocale($request->user()->locale);
    } else {
        // Unauthenticated or null locale: negotiate from Accept-Language
        $preferred = $request->getPreferredLanguage($supportedLocales);
        app()->setLocale($preferred ?? $defaultLocale);
    }

    return $next($request);
}
```

This is the only place in the codebase where the active locale is set. All downstream code — views, mail, notifications, validation messages — calls `app()->getLocale()` implicitly through Laravel's `__()` helper.

#### 5.6.3 UpdateUserAttributes Action Change

`app/Domain/Notification/Actions/UpdateUserAttributes.php` (or equivalent profile action) gains a `locale` field in its update payload. The Form Request adds:

```php
'locale' => ['nullable', 'string', Rule::in(['en', 'de', 'fr', 'es'])],
```

#### 5.6.4 ResolveIntegrationUser Bug Fix

`app/Domain/Integration/Actions/ResolveIntegrationUser.php`, approximately line 40, currently constructs the `LanCoreUser` DTO with:

```php
// BEFORE (bug): passes request locale, not user preference
'locale' => app()->getLocale(),
```

The fix replaces this with:

```php
// AFTER (correct): passes the user's stored locale preference
'locale' => $user->locale,
```

This satisfies I18N-F-007 and CAP-I18N-007. The `locale` field is already declared on the `LanCoreUser` DTO (see ICLIB-F-002 / SRS §3.2.19) — no DTO changes are required.

#### 5.6.5 HandleInertiaRequests Shared Props Addition

`app/Http/Middleware/HandleInertiaRequests.php` — the `share()` method gains two new entries:

```php
'locale'           => fn () => app()->getLocale(),
'availableLocales' => fn () => ['en', 'de', 'fr', 'es'],
```

`locale` is a lazy closure so it reads the locale after `SetLocale` has already called `app()->setLocale()`. `availableLocales` is static data used by the language-switcher UI and by `vue-i18n` initialization.

#### 5.6.6 Vue Frontend i18n Initialization

In `resources/js/app.ts` (or the Inertia initialization file):

```typescript
import { createI18n } from 'vue-i18n'
import en from './locales/en.json'
import de from './locales/de.json'
import fr from './locales/fr.json'
import es from './locales/es.json'

// locale is injected from Inertia shared props via usePage()
const i18n = createI18n({
    locale: usePage().props.locale as string,
    fallbackLocale: 'en',
    messages: { en, de, fr, es },
})
app.use(i18n)
```

Translation files live at `resources/js/locales/{en,de,fr,es}.json`. Vue components use the `t()` composable from `vue-i18n`.

#### 5.6.7 Weblate Sync Workflow

Weblate pushes translator commits directly to the `weblate` branch of each app repository via an SSH deploy key (write access). No CLI export or nightly pull step is required — Weblate reads source strings from the existing `resources/js/locales/{en,de,fr,es}.json` files in place. The `.github/workflows/weblate-merge.yml` workflow in each app repository fast-forwards the `weblate` branch onto `main`:

```yaml
name: Merge Weblate translations

on:
  push:
    branches:
      - weblate

jobs:
  merge:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
        with:
          fetch-depth: 0
          token: ${{ secrets.GITHUB_TOKEN }}

      - name: Fast-forward main with weblate branch
        run: |
          git config user.name "weblate-bot"
          git config user.email "bot@lan-software.dev"
          git checkout main
          git merge --ff-only weblate
          git push origin main
```

Each app has its own Weblate component under the `lan-software` project (`lancore`, `lanbrackets`, `lanshout`, `lanentrance`, `lanhelp`). The former `shared` namespace (carried over from the previous TMS plan) is not present — it was defined but never consumed by any application and has been deliberately dropped.

---

## 6. Requirements Traceability

| SRS Requirement | Design Component |
|----------------|-----------------|
| EVT-F-* | app/Domain/Event/ |
| TKT-F-001..016 | app/Domain/Ticketing/ |
| TKT-F-017..023 | app/Domain/Ticketing/Security/TicketTokenService.php, app/Domain/Ticketing/Security/TicketKeyRing.php, app/Console/Commands/RotateTicketSigningKeyCommand.php, app/Domain/Ticketing/Http/Controllers/AdminTicketController.php (rotateToken action), app/Domain/Ticketing/Actions/UpdateTicketAssignments.php |
| TKT-F-024 | app/Domain/Ticketing/Http/Controllers/TicketController.php (rotateTokenUser action), routes/ticketing.php (`tickets.rotate-token` with `throttle:10,1`) |
| TKT-F-025 | app/Domain/Ticketing/Notifications/TicketTokenRotatedNotification.php, app/Domain/Ticketing/Actions/UpdateTicketAssignments.php (`notifyRotation`) |
| TKT-F-026 | app/Domain/Ticketing/Security/TicketTokenService.php (`render`), app/Domain/Ticketing/Models/Ticket.php (`renderSignedToken`), app/Domain/Ticketing/Http/Controllers/TicketController.php (qrCode, download) |
| TKT-F-027 | resources/views/pdf/ticket.blade.php, app/Domain/Ticketing/Jobs/GenerateTicketPdf.php |
| TKT-F-029 | app/Domain/Ticketing/Jobs/GenerateTicketPdf.php (generateWatermarkBase64), resources/views/pdf/ticket.blade.php (`.watermark`), tests/Feature/Ticketing/TicketPdfWatermarkTest.php |
| TKT-F-028 | app/Console/Commands/RotateLegacyTicketTokensCommand.php |
| SHP-F-* | app/Domain/Shop/ |
| PRG-F-* | app/Domain/Program/ |
| SET-F-* | app/Domain/Seating/ |
| SPO-F-* | app/Domain/Sponsoring/ |
| NWS-F-* | app/Domain/News/ |
| ANN-F-* | app/Domain/Announcement/ |
| ACH-F-* | app/Domain/Achievements/ |
| NTF-F-* | app/Domain/Notification/ |
| INT-F-* | app/Domain/Integration/ |
| WHK-F-* | app/Domain/Webhook/ |
| GAM-F-* | app/Domain/Games/ |
| USR-F-001..013 | app/Models/User, app/Domain/ controllers |
| USR-F-014..020 | app/Contracts/PermissionEnum.php, app/Enums/Permission.php, app/Enums/RolePermissionMap.php, app/Domain/*/Enums/Permission.php, app/Concerns/HasPermissions.php, app/Providers/AppServiceProvider.php, resources/js/composables/usePermissions.ts |
| COMP-F-001..015 | app/Domain/Competition/ |
| ORC-F-001..015 | app/Domain/Orchestration/ |
| EVT-F-011 | app/Domain/Event/Http/Controllers/EventContextController.php, app/Http/Middleware/HandleInertiaRequests.php, app/Domain/Event/Models/Event.php (scopeForUser) |
| ORG-F-001..005 | app/Domain/Settings/Http/Controllers/OrganizationSettingsController.php, app/Http/Middleware/HandleInertiaRequests.php, resources/js/components/AppLogo.vue |
| SRS 3.1 Required States (Demo) | app/Console/Commands/SeedDemoCommand.php (data seeding), app/Domain/Shop/Services/PaymentProviderManager.php (resolves StripePaymentProvider in all modes), environment configuration: STRIPE_KEY / STRIPE_SECRET set to Stripe test-mode values when APP_DEMO=true |
| ICLIB-F-001..009 | `lan-software/lancore-client` package (separate repository); see §5.5 for class-level design |
| USR-F-021 | `users` table migration (new `locale` VARCHAR column); `app/Domain/Notification/Actions/UpdateUserAttributes.php` or equivalent profile action; profile Form Request (`locale` validation rule); see §5.6.1, §5.6.3 |
| I18N-F-001 | `config/app.php` supported locales config; `SetLocale` middleware validation set; Vue `availableLocales` shared prop; see §5.6.2, §5.6.5 |
| I18N-F-002 | `app/Http/Middleware/SetLocale.php`; see §5.6.2 |
| I18N-F-003 | `app/Http/Middleware/HandleInertiaRequests.php` (`locale`, `availableLocales` props); see §5.6.5 |
| I18N-F-004 | `lang/{en,de,fr,es}/` directories (via `artisan lang:publish`); `__()` / `trans()` usage throughout backend |
| I18N-F-005 | `resources/js/locales/{en,de,fr,es}.json`; `vue-i18n` initialization in `resources/js/app.ts`; see §5.6.6 |
| I18N-F-006 | `.github/workflows/pull-translations.yml`; see §5.6.7 |
| I18N-F-007 | `app/Domain/Integration/Actions/ResolveIntegrationUser.php` line ~40: `$user->locale` replaces `app()->getLocale()`; see §5.6.4 |

---

## 7. Notes

### 7.1 Acronyms

| Term | Definition |
|------|-----------|
| DDD | Domain-Driven Design |
| MVC | Model-View-Controller |
| ORM | Object-Relational Mapping |
| SPA | Single Page Application |
| SSR | Server-Side Rendering |
| CSCI | Computer Software Configuration Item |

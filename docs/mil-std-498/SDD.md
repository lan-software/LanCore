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
| JSONB for flexible data | Seat plans and banner images benefit from schemaless storage |
| Laravel Octane/FrankenPHP | Application boot once, reuse across requests for performance |
| Redis for caching | Tag-based invalidation, high throughput, shared cache across workers |
| Demo mode via `SeedDemoCommand` + Stripe test-mode keys | Enables end-to-end showcases using Stripe's sandbox; activated via environment flag `APP_DEMO=true` paired with `STRIPE_KEY=pk_test_...` / `STRIPE_SECRET=sk_test_...`; `PaymentProviderManager` resolves `StripePaymentProvider` as normal — no custom provider is needed |
| LanCore as authoritative locale store | `users.locale` column on LanCore is the single source of truth for the user's display language; satellites receive the locale via `LanCoreUser` DTO and persist it locally; this avoids duplicated preference UIs across apps and ensures the user changes their language in one place |
| `SetLocale` middleware for per-request locale application | A dedicated middleware (registered in the web group after session init) calls `app()->setLocale()` based on the authenticated user's stored locale; this keeps locale resolution out of controllers and ensures every translation call in a request cycle uses the correct locale |
| `vue-i18n` + Tolgee for frontend i18n | `vue-i18n@9+` initialized from the `locale` Inertia shared prop; JSON locale files in `resources/js/locales/`; Tolgee Cloud nightly pull ensures translations stay current without manual file management |

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

**`TicketTokenService`** — Orchestrates token lifecycle:
- `issue(Ticket $ticket): string` — Generates nonce (CSPRNG), builds LCT1 body, calls `TicketKeyRing::sign()`, persists nonce hash + metadata to ticket row, returns the full token string for PDF generation
- `verify(string $token): array` — Parses the token, delegates to `TicketKeyRing::verify()`, derives nonce hash from body nonce, queries ticket by nonce hash; returns structured result including the `Ticket` model on success
- `locateByNonceHash(string $nonceHash): ?Ticket` — Direct lookup used by the validate endpoint

**`TicketKeyRing`** — Key management:
- `sign(string $message, string $kid = null): string` — Signs `message` with the active (or specified) private key; returns base64url-encoded 64-byte signature
- `publicKey(string $kid): string` — Returns base64url-encoded public key bytes for a given kid; throws `UnknownKidException` if not found
- `activeKid(): string` — Returns the currently active key identifier
- `allVerifyKids(): array` — Returns all kids whose public keys are retained (active + retired-but-unexpired)
- `toJwks(): array` — Builds the `{ keys: [...] }` JWKS array for the signing-keys endpoint

Private key files reside at `storage/keys/ticket_signing/{kid}.key` with mode 0600. `TicketKeyRing` reads them on boot and caches in memory for the Octane process lifetime. On `tickets:keys:rotate`, a new key pair is written to disk and `TicketKeyRing` is re-initialized.

**`GenerateTicketPdf` job:** The job takes a required `string $qrPayload` constructor argument carrying the signed LCT1 token payload to render into the QR code. The `qrPayload` is never persisted beyond the job execution.

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
| Seating | 1 | 3 | 2 | 0 | 0 |
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

**Token Regeneration Triggers (within UpdateTicketAssignments):**

| Method | Trigger Condition | Token Action |
|--------|------------------|--------------|
| `updateManager` | Manager user changes | Rotate nonce, re-sign, dispatch PDF regen |
| `addUser` | User added to pivot | Rotate nonce, re-sign, dispatch PDF regen |
| `removeUser` | User removed from pivot | Rotate nonce, re-sign, dispatch PDF regen |
| `rotateToken` (admin action) | Manual admin request | Rotate nonce, re-sign, dispatch PDF regen |
| Ticket cancel | Status set to cancelled | Clear nonce hash, kid, issued_at, expires_at |

The regeneration sequence calls `TicketTokenService::issue($ticket)`, stores the new nonce hash and metadata, and passes the new `qrPayload` to a freshly dispatched `GenerateTicketPdf` job. The old QR codes become unresolvable immediately (nonce hash changes).

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
| Seating | 4 | Index, Create, Edit, Audit |
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

#### 5.6.7 Tolgee Pull Workflow

A nightly GitHub Actions workflow (`.github/workflows/pull-translations.yml`) runs in each app repository:

```yaml
- name: Pull translations from Tolgee
  run: |
    npx tolgee pull \
      --api-url https://app.tolgee.io \
      --api-key ${{ secrets.TOLGEE_API_KEY }} \
      --namespace lancore \
      --namespace shared \
      --path resources/js/locales/{locale}.json \
      --backend-path lang/{locale}
- name: Commit updated locale files
  run: |
    git config user.name "tolgee-bot"
    git config user.email "bot@lan-software.dev"
    git add lang/ resources/js/locales/
    git diff --staged --quiet || git commit -m "chore(i18n): pull translations from Tolgee [skip ci]"
    git push
```

The `shared` namespace is pulled by all six apps; each app additionally pulls its own namespace (`lancore`, `lanbrackets`, `lanshout`, `lanentrance`, `lanhelp`).

---

## 6. Requirements Traceability

| SRS Requirement | Design Component |
|----------------|-----------------|
| EVT-F-* | app/Domain/Event/ |
| TKT-F-001..016 | app/Domain/Ticketing/ |
| TKT-F-017..023 | app/Domain/Ticketing/Security/TicketTokenService.php, app/Domain/Ticketing/Security/TicketKeyRing.php, app/Console/Commands/RotateTicketKeysCommand.php, app/Domain/Ticketing/Http/Controllers/AdminTicketController.php (rotateToken action) |
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

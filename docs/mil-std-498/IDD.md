# Interface Design Description (IDD)

**Document Identifier:** LanCore-IDD-001
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

This Interface Design Description (IDD) describes the design of the **LanCore** system's external and internal interfaces.

### 1.2 System Overview

LanCore exposes several interface boundaries: a web-based user interface via Inertia.js, a stateless Integration API, an SSO authorization flow, webhook delivery, and connections to external services (Stripe, S3, SMTP, Web Push).

### 1.3 Document Overview

This document describes the detailed design of each interface, including data formats, protocols, authentication mechanisms, and message flows.

---

## 2. Referenced Documents

- [IRS](IRS.md) — Interface Requirements Specification
- [SDD](SDD.md) — Software Design Description
- [DBDD](DBDD.md) — Database Design Description

---

## 3. Interface Design

### 3.1 Integration API (REST)

**Base URL:** `/api/integration/`
**Authentication:** Bearer token in `Authorization` header
**Content-Type:** `application/json`
**Middleware:** `AuthenticateIntegration`

**Registration:** Integration apps are registered by one of three means — the admin UI, the imperative Artisan commands (`integration:create`, `integration:token`), or the declarative `config/integrations.php` + `integrations:sync` command (see [IRS §3.5a IF-INTCFG](IRS.md#35a-integration-declarative-configuration-if-intcfg) for the interface contract and [SSDD §5.4.5](SSDD.md#545-integration-declarative-config-reconciler) for the reconciliation design). The Helm-deployed LanCore sub-chart uses the declarative path via a pre-install/pre-upgrade Job; Docker Compose deployments may opt into boot-time reconciliation through `LANCORE_INTEGRATIONS_RECONCILE_ON_BOOT`.

#### 3.1.1 Authentication

```
Authorization: Bearer <plain-text-token>
```

The AuthenticateIntegration middleware:
1. Extracts the token from the Authorization header
2. Hashes it with SHA-256
3. Looks up the hash in `integration_tokens` table
4. Validates the token has not expired
5. Updates `last_used_at` timestamp
6. Sets the integration app context for the request

#### 3.1.2 User Resolution Endpoint

```
GET /api/integration/user
Authorization: Bearer <token>
```

**Response:**
```json
{
  "id": 1,
  "name": "John Doe",
  "email": "john@example.com"
}
```

#### 3.1.3 Error Responses

| Status | Meaning |
|--------|---------|
| 401 | Invalid or expired token |
| 403 | Token valid but insufficient permissions |
| 404 | Resource not found |
| 422 | Validation error |
| 429 | Rate limit exceeded |

### 3.2 SSO Authorization Flow

#### 3.2.1 Authorization Request

```
GET /integration/{app}/sso/authorize
```

If the user is not authenticated, they are redirected to login first. After authentication, the system generates an authorization code and redirects:

```
302 Redirect → {callback_url}?code={authorization_code}
```

#### 3.2.2 Code Exchange

```
POST /api/integration/sso/exchange
Authorization: Bearer <token>
Content-Type: application/json

{
  "code": "<authorization_code>"
}
```

**Response:**
```json
{
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com"
  }
}
```

#### 3.2.3 Authorization Code Properties

- Time-limited (short TTL, typically minutes)
- Single-use (invalidated after exchange)
- Bound to the integration app that initiated the flow

### 3.3 Webhook Delivery

#### 3.3.1 Delivery Format

```
POST {webhook_url}
Content-Type: application/json
X-LanCore-Signature: sha256={hmac_hex}
X-LanCore-Event: {event_type}

{
  "event": "NewsArticlePublished",
  "timestamp": "2026-04-02T12:00:00Z",
  "data": {
    // Event-specific payload
  }
}
```

#### 3.3.2 Signature Verification

The `X-LanCore-Signature` header contains an HMAC-SHA256 signature:

```
signature = HMAC-SHA256(webhook_secret, request_body)
header = "sha256=" + hex(signature)
```

Receivers should:
1. Extract the signature from the header
2. Compute HMAC-SHA256 of the raw request body using their known secret
3. Compare using a timing-safe comparison function

#### 3.3.3 Event Payloads

**NewsArticlePublished:**
```json
{
  "event": "NewsArticlePublished",
  "timestamp": "2026-04-02T12:00:00Z",
  "data": {
    "article": {
      "id": 1,
      "title": "Event Update",
      "visibility": "public",
      "published_at": "2026-04-02T12:00:00Z"
    }
  }
}
```

**EventPublished:**
```json
{
  "event": "EventPublished",
  "timestamp": "2026-04-02T12:00:00Z",
  "data": {
    "event": {
      "id": 1,
      "name": "LAN Party 2026",
      "start_date": "2026-06-15T10:00:00Z",
      "end_date": "2026-06-17T18:00:00Z"
    }
  }
}
```

**UserRegistered:**
```json
{
  "event": "UserRegistered",
  "timestamp": "2026-04-02T12:00:00Z",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com"
    }
  }
}
```

**TicketPurchased:**
```json
{
  "event": "TicketPurchased",
  "timestamp": "2026-04-02T12:00:00Z",
  "data": {
    "order": {
      "id": 1,
      "user_id": 1,
      "event_id": 1,
      "total": "25.00",
      "status": "confirmed"
    }
  }
}
```

**AnnouncementPublished:**
```json
{
  "event": "AnnouncementPublished",
  "timestamp": "2026-04-02T12:00:00Z",
  "data": {
    "announcement": {
      "id": 1,
      "title": "Doors Open",
      "priority": "high",
      "event_id": 1
    }
  }
}
```

**UserRolesUpdated:**
```json
{
  "event": "UserRolesUpdated",
  "timestamp": "2026-04-02T12:00:00Z",
  "data": {
    "user": {
      "id": 1,
      "roles": ["Admin"]
    }
  }
}
```

**ProfileUpdated / IntegrationAccessed:** Similar structure with relevant entity data.

#### 3.3.4 Delivery Tracking

Each delivery attempt is recorded in `webhook_deliveries`:
- `status_code` — HTTP response code (null if connection failed)
- `duration_ms` — Round-trip time in milliseconds
- `response_body` — Truncated response body for debugging

### 3.4 Stripe Checkout Integration

#### 3.4.1 Checkout Session Creation

```
StripePaymentProvider::initiate(User $user, Order $order): PaymentResult
```

Creates a Stripe Checkout Session with:
- Line items derived from order lines (`price_data` with dynamic pricing, not pre-created Stripe prices)
- Success URL: `/cart/checkout/{order}/success?session_id={CHECKOUT_SESSION_ID}`
- Cancel URL: `/cart/checkout/{order}/cancel`
- Metadata: `order_id` for webhook correlation
- Customer: user's Stripe customer ID (created via Cashier if needed)
- Discount: Stripe coupon created dynamically if voucher discount applies

Returns a `PaymentResult::redirect()` that redirects the user to Stripe's hosted Checkout page.

#### 3.4.2 Payment Confirmation — Success URL

When the user completes payment on Stripe's hosted page, Stripe redirects to the success URL:

1. `CartController::checkoutSuccess()` receives the request with `session_id` query parameter
2. `StripePaymentProvider::handleSuccess()` retrieves the Checkout Session from Stripe API
3. If `payment_status === 'paid'`, updates order with `provider_session_id` and `provider_transaction_id`
4. `FulfillOrder::execute()` is invoked:
   - Order status updated to `completed`
   - Tickets created for each ticket-type line item with addons attached
   - Voucher usage incremented
   - `OrderConfirmationNotification` sent to user
   - `TicketPurchased` event dispatched

#### 3.4.3 Payment Confirmation — Webhook Fallback

If the user does not return to the success URL (e.g., closes browser), Stripe sends a `checkout.session.completed` webhook:

1. Stripe POSTs to `/stripe/webhook` (Cashier's auto-registered route)
2. Cashier verifies the webhook signature using `STRIPE_WEBHOOK_SECRET`
3. Cashier dispatches `WebhookReceived` event
4. `HandleStripeCheckoutCompleted` listener:
   - Filters for `checkout.session.completed` event type
   - Extracts `order_id` from session metadata
   - Updates order with `provider_session_id` and `provider_transaction_id`
   - Calls `FulfillOrder::execute()` (idempotent — skips if already completed)

Both paths (3.4.2 and 3.4.3) may fire for the same order. `FulfillOrder::execute()` is idempotent: it checks `order.status === Completed` and returns early if already fulfilled.

### 3.5 Inertia.js Interface

#### 3.5.1 Request/Response Cycle

**Initial page load:**
```
GET /events → HTML response with Inertia page component + props as JSON
```

**Subsequent navigation:**
```
GET /events/1/edit
X-Inertia: true
X-Inertia-Version: {asset_version}

→ JSON response:
{
  "component": "events/Edit",
  "props": {
    "event": { ... },
    "venues": [ ... ]
  },
  "url": "/events/1/edit",
  "version": "{asset_version}"
}
```

#### 3.5.2 Shared Data (HandleInertiaRequests)

Every Inertia response includes shared data:
- `auth.user` — Authenticated user with roles
- `flash` — Session flash messages
- `ziggy` — Route definitions for client-side URL generation
- `appearance` — Theme/appearance preferences

#### 3.5.3 Form Submissions

Inertia.js forms submit via standard HTTP methods (POST, PUT, DELETE) and receive Inertia redirect responses, preserving SPA navigation.

### 3.6 S3-Compatible Storage

LanCore exposes two semantic storage roles via `App\Support\StorageRole`:

- **Public role** (`StorageRole::public()`) — non-sensitive assets such as organization logos and public images. Backed by the disk named in `filesystems.public_disk` (default `public`, typically `s3_public` in production).
- **Private role** (`StorageRole::private()`) — sensitive artifacts such as invoices, receipts, and ticket PDFs. Backed by the disk named in `filesystems.private_disk` (default `local`, typically `s3_private` in production).

Each role is independently routable. Operators may keep both on a single bucket (legacy behavior), split them across two S3 buckets with separate credentials, or mix local + S3.

#### 3.6.1 File Upload

Files are written through the role helper rather than a hardcoded disk name:

```php
use App\Support\StorageRole;

// sensitive artifact
StorageRole::private()->put("invoices/{$order->id}.pdf", $pdf->output());

// public asset
$path = $request->file('logo')->store('organization', StorageRole::publicDiskName());
```

#### 3.6.2 File Serving

`StorageFileController` serves files from S3 with appropriate headers. Supports both signed URLs and anonymous access (configurable). Sensitive artifacts on the private role are never served via anonymous access.

#### 3.6.3 Migrating Between Buckets

The `storage:migrate` Artisan command supports the role disks (`s3_public`, `s3_private`) and can move existing files from a single legacy bucket into the split layout, e.g.:

```
php artisan storage:migrate --from=s3 --to=s3_private --path=invoices --delete
php artisan storage:migrate --from=s3 --to=s3_public --path=organization
```

### 3.7 Web Push

#### 3.7.1 Subscription Registration

```
POST /notifications/push/subscribe
Content-Type: application/json

{
  "endpoint": "https://push.example.com/...",
  "keys": {
    "p256dh": "<public_key>",
    "auth": "<auth_token>"
  }
}
```

#### 3.7.2 Push Delivery

Uses minishlink/web-push library with VAPID authentication:
1. Serialize notification payload as JSON
2. Encrypt with subscriber's public key
3. POST to subscriber's push endpoint
4. Handle expired subscriptions (remove on 410 Gone)

### 3.8 LanBrackets Integration

#### 3.8.1 Outbound (LanCore → LanBrackets)

LanCore communicates with LanBrackets via `LanBracketsClient` (REST API, bearer token auth).

**Key operations:**
- `POST /api/v1/competitions` — Create competition with external_reference_id
- `POST /api/v1/competitions/{id}/participants/bulk` — Sync teams as participants
- `GET /api/v1/competitions/{id}/stages/{stageId}/matches` — Fetch stage matches
- `POST /api/v1/competitions/{id}/matches/{matchId}/result` — Report match result

**Configuration:** `config/lanbrackets.php` — base_url, token, webhook_secret, timeout, retries

#### 3.8.2 Inbound (LanBrackets → LanCore)

LanBrackets sends webhooks to `POST /webhooks/lanbrackets` with HMAC-SHA256 signature verification.

**Events handled:**
- `competition.completed` — Marks competition as Finished
- `match.result_reported` — Resolves match result proofs, triggers orchestration for next-round matches
- `bracket.generated` — Triggers orchestration for first-round matches with all participants set

### 3.9 TMT2 Integration

#### 3.9.1 Outbound (LanCore → TMT2)

LanCore communicates with TMT2 via `Tmt2Client` (REST API, bearer token auth).

**Key operations:**
- `POST /api/matches` — Create match with game server, teams, map pool, election steps, webhook URL
- `GET /api/matches/{id}` — Get match state
- `PATCH /api/matches/{id}` — Update match
- `DELETE /api/matches/{id}` — Stop match supervision (executes end rcon commands)
- `POST /api/login` — Validate token / health check

**Configuration:** `config/tmt2.php` — base_url, token, timeout, retries

#### 3.9.2 Inbound (TMT2 → LanCore)

TMT2 sends webhooks to `POST /webhooks/tmt2/{orchestrationJob}` for each match event.

**Events handled:**
- `MATCH_END` — Auto-reports result to LanBrackets, completes orchestration job, releases server
- `CHAT` — Stores player chat messages via SupportsChatFeature
- `MAP_START`, `MAP_END`, `ROUND_END` — Logged for observability (future: live score updates)

**Payload format** (TMT2 webhook):
```json
{
  "type": "MATCH_END",
  "timestamp": "2026-04-05T14:30:00Z",
  "matchId": "tmt2-uuid",
  "matchPassthrough": "lancore-orchestration-job-id",
  "wonMapsTeamA": 2,
  "wonMapsTeamB": 1,
  "winnerTeam": { "name": "Team Alpha", "passthrough": "lb-participant-1" }
}
```

### 3.10 MatchHandlerContract Interface

Internal extensibility interface for game-specific match handlers.

```php
interface MatchHandlerContract {
    public function supports(Game $game): bool;
    public function deploy(GameServer $server, array $matchConfig): void;
    public function teardown(GameServer $server, array $matchConfig): void;
    public function healthCheck(GameServer $server): bool;
}
```

**Optional feature interfaces:**
- `SupportsChatFeature` — `handleChatMessage(OrchestrationJob $job, array $chatData): void`

**Registered implementations:**
- `Tmt2MatchHandler` — CS2/Source 2 engine games via TMT2 API

### 3.11 Signed Ticket Token — LCT1 Format

#### 3.11.1 Token ABNF Grammar

```abnf
lct1-token   = "LCT1" "." kid "." body "." sig
kid          = 1*16(ALPHA / DIGIT / "-" / "_")
body         = base64url
sig          = base64url
base64url    = *( ALPHA / DIGIT / "-" / "_" ) *( "=" )
```

The token is composed of four dot-separated segments:

| Segment | Value |
|---------|-------|
| `"LCT1"` | Literal version prefix |
| `kid` | Key identifier, max 16 URL-safe characters |
| `body` | base64url(json payload) — see §3.11.2 |
| `sig` | base64url(Ed25519 signature over signing input) |

The signing input is the raw ASCII string: `"LCT1." + kid + "." + body` (no newlines, no padding adjustments).

#### 3.11.2 Token Body Payload (JSON)

```json
{
  "tid": 1234,
  "nonce": "base64url-encoded-128-bit-random",
  "iat": 1743984000,
  "exp": 1744070400,
  "evt": 7
}
```

| Field | Type | Description |
|-------|------|-------------|
| `tid` | integer | LanCore ticket primary key |
| `nonce` | string | base64url-encoded 128-bit value derived deterministically from `HMAC_SHA256(pepper, ticket_id_le64 \|\| epoch_le64)` truncated to 16 bytes; identical for repeat renders, distinct across rotation epochs |
| `iat` | integer | Issued-at Unix timestamp (UTC) |
| `exp` | integer | Expiry Unix timestamp (UTC); typically event end + grace period |
| `evt` | integer | LanCore event primary key |

The nonce is derived deterministically at render time as `substr(HMAC_SHA256(pepper, pack('J', ticket_id).pack('J', rotation_epoch), raw_output=true), 0, 16)` where `rotation_epoch` is the ticket's `validation_rotation_epoch` column. It is never persisted; only its HMAC-SHA256 with the pepper is stored, hex-encoded, in `validation_nonce_hash`. Rotation means incrementing the epoch counter and recomputing: the stored hash changes, the previously-printed QR embeds the old nonce, and `locate()` at scan time returns `null` → `revoked`. The plaintext nonce never leaves the application process because it is recomputed on-demand from the in-memory pepper.

#### 3.11.3 Signature Algorithm

```
sig_bytes = sodium_crypto_sign_detached(
    message  = "LCT1." || kid || "." || body,
    sk       = Ed25519_private_key[kid]
)
sig = base64url_no_padding(sig_bytes)
```

The PHP implementation uses `sodium_crypto_sign_detached()`. The resulting 64-byte signature is base64url-encoded without padding characters.

#### 3.11.4 Nonce Hash Derivation

```
nonce_hash = hex(HMAC-SHA256(key=pepper_bytes, data=nonce_raw_bytes))
```

Stored in `tickets.validation_nonce_hash` as a 64-character lowercase hex string. Used by the validate endpoint to locate the ticket row without exposing either the nonce or the full token.

#### 3.11.5 Example Token (illustrative, not a real key)

```
LCT1.key-2026-01.eyJ0aWQiOjEyMzQsIm5vbmNlIjoiQUFBQUFBQUFBQUFBQUFBQUFBQUFBQT09IiwiaWF0IjoxNzQzOTg0MDAwLCJleHAiOjE3NDQwNzA0MDAsImV2dCI6N30.AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
```

Maximum token length: approximately 350–380 characters for a 32-byte nonce and a typical payload.

### 3.12 JWKS Endpoint Design

**Endpoint:** `GET /api/entrance/signing-keys`
**Authentication:** Bearer token (AuthenticateIntegration middleware)
**Content-Type:** `application/json`

#### 3.12.1 Response Structure

```json
{
  "keys": [
    {
      "kty": "OKP",
      "crv": "Ed25519",
      "use": "sig",
      "kid": "key-2026-01",
      "x": "base64url-encoded-32-byte-public-key"
    },
    {
      "kty": "OKP",
      "crv": "Ed25519",
      "use": "sig",
      "kid": "key-2025-12",
      "x": "base64url-encoded-32-byte-retired-public-key"
    }
  ]
}
```

The first entry in the `keys` array is the currently active signing key. Subsequent entries are retired keys still present because unexpired tokens may have been signed by them. The `x` field is the 32-byte Ed25519 public key in base64url encoding (no padding).

#### 3.12.2 Caching Contract

- LanCore sets `Cache-Control: max-age=3600, s-maxage=3600` (1 hour default, configurable)
- LanEntrance must not poll this endpoint more frequently than the returned `max-age`
- On a cache miss, LanEntrance fetches the fresh key set before attempting signature verification
- LanCore must not remove a `kid` from the JWKS response while tokens signed with that `kid` may still be within their TTL window

#### 3.12.3 Error Responses

| Status | Condition |
|--------|-----------|
| 401 | Missing or invalid Bearer token |
| 200 | Success (including empty `keys` array if no keys generated yet) |

### 3.13 Validate Endpoint — Updated Error Decision Codes

The `POST /api/entrance/validate` endpoint returns a `decision` field. The following error decision codes are added by the LCT1 scheme (in addition to existing codes):

| Decision | HTTP Status | When Returned | `override_allowed` |
|----------|-------------|---------------|-------------------|
| `invalid_signature` | 200 | Ed25519 signature does not verify | false |
| `unknown_kid` | 200 | `kid` not found in known key set | false |
| `expired` | 200 | Current time > `exp` claim | false |
| `revoked` | 200 | Nonce hash not found in `tickets` (cancelled or rotated) | false |

All four new codes follow the same 200-OK response envelope as existing decision codes; the `decision` field distinguishes them. The `message` field provides a human-readable description for the door operator (e.g., "Ticket has been cancelled or reissued — ask attendee to show their latest ticket").

---

### 3.14 Seat Picker — End-User HTTP Endpoints

Implements SET-F-006/007/008. All endpoints require `auth` + `verified` middleware.

#### 3.14.1 `GET /events/{event}/seats` (`events.seats.picker`)

Renders the Inertia page `seating/Picker.vue` with the props described in SDD §5.3c. Optional query params `?ticket={id}&user={id}` preselect the active assignment context.

**`myTickets[].assignees[]` item shape** (relevant to SET-F-011):
```
{
  user_id: int,
  name: string,
  can_pick: bool,            // Gate::pickSeat outcome
  ticket_category_id: int | null,   // SET-F-011; drives client-side block filtering
  assignment: { id, seat_plan_id, seat_id, seat_title } | null
}
```

#### 3.14.2 `POST /events/{event}/seats` (`events.seats.assign`)

Body (JSON / form):

```json
{
  "ticket_id": 45,
  "user_id": 7,
  "seat_plan_id": 2,
  "seat_id": 812
}
```

`seat_id` is the integer PK of the `seat_plan_seats` row (the normalization migration replaced the earlier `varchar(64)` JSON pointer).

Authorization: `Gate::authorize('pickSeat', [$ticket, $assignee])` — see SDD §5.3c.

Responses:
- `302` (Inertia redirect back) on success — flashes `status=seat-assigned`.
- `422` `ValidationException` on: seat plan not part of event, seat doesn't exist or not salable, seat already taken (unique-violation), assignee not on ticket, **block does not accept the ticket's category (SET-F-011, key `seat_id`, message key `seating.errors.block_category_forbidden`)**.
- `403` Forbidden on policy denial (e.g., third-party user, ticket already checked-in).

#### 3.14.3 `DELETE /events/{event}/seats/{assignment}` (`events.seats.release`)

Releases the named assignment. Same authorization as `assign`. Returns `302` redirect with `status=seat-released`.

### 3.15 Privacy Settings Endpoints

Implements SET-F-010.

#### 3.15.1 `GET /settings/privacy` (`privacy.edit`)

Renders `settings/Privacy.vue` with `isSeatVisiblePublicly: boolean`.

#### 3.15.2 `PATCH /settings/privacy` (`privacy.update`)

Body: `{ "is_seat_visible_publicly": true|false }`. Returns `302` redirect back.

### 3.16 Admin Seat-Plan Update — Two-Phase `PATCH /seat-plans/{seatPlan}`

Implements SET-F-012/013. Auth: `SeatPlanPolicy::update`.

**Request body:**
```
name?: string
background_image_url?: string|null
data?: string | object            // Full normalized tree — see below. Serialised JSON or direct object.
confirm_invalidations?: boolean   // default false
```

`data` carries the full desired state of the plan's block/row/seat/label tree (full-state replace):

```
{
  "blocks": [
    {
      "id"?: int,                                      // omit or send "new-*" for inserts
      "title": string,
      "color": string,
      "background_image_url"?: string|null,
      "sort_order"?: int,
      "allowed_ticket_category_ids"?: int[],           // pivot contents; empty = open
      "rows": [
        {
          "id"?: int,
          "name": string,
          "sort_order"?: int,
          "seats": [
            {
              "id"?: int,
              "number"?: int,
              "title": string,
              "x": int, "y": int,
              "salable": bool,
              "color"?: string|null,
              "note"?: string|null,
              "custom_data"?: object|null
            }
          ]
        }
      ],
      "labels": [
        { "id"?: int, "title": string, "x": int, "y": int, "sort_order"?: int }
      ]
    }
  ]
}
```

**Responses:**
- `302` + session flash `invalidations: Array<{
    assignment_id, ticket_id, user_id, seat_id, seat_title,
    block_id, block_title, assignee_name,
    reason: 'seat_removed' | 'category_mismatch'
  }>` — when the proposed change would orphan existing seat assignments AND `confirm_invalidations` is false. **No DB write occurs.** The `Edit.vue` page renders an `<InvalidationConfirmDialog>` from this flash payload.
- `302` + session flash `status = 'seat-plan-updated'` AND `id_map: { blocks: {<new-*>: int}, rows: {...}, seats: {...}, labels: {...} }` on final success (either no invalidations detected, or `confirm_invalidations=true`). The editor calls `useEditorStore.reconcileIds(id_map)` to rewrite its client placeholders in place. Affected `seat_assignments` rows are hard-deleted inside the transaction and a `SeatAssignmentInvalidated` domain event is dispatched for each.
- `403` Forbidden — viewer lacks `SeatPlanPolicy::update`.
- `422` ValidationException — standard validation errors on `name`, `data`, or `confirm_invalidations`.

**Emitted notification (SET-F-014):** `SeatAssignmentInvalidatedNotification` is dispatched (queued) for each released assignment to ticket owner + manager + assignee (deduped). Channels are gated by `mail_on_seating` / `push_on_seating` preferences; the `database` channel is always used. See SDD §5.3c.4 for the `toArray()` payload schema.

### 3.17 Admin Seat-Plan Background Image Endpoints

Implements SET-F-015. Auth: `SeatPlanPolicy::update`.

| Method + path | Name | Purpose |
|---|---|---|
| `POST /seat-plans/{seatPlan}/background` | `seat-plans.background.store` | Upload (multipart `image`, PNG/JPG/WEBP ≤ 5 MB) and set the plan's global background image URL. |
| `DELETE /seat-plans/{seatPlan}/background` | `seat-plans.background.destroy` | Remove the plan's global background image. |
| `POST /seat-plans/{seatPlan}/blocks/{block}/background` | `seat-plans.blocks.background.store` | Upload a per-block background image. |
| `DELETE /seat-plans/{seatPlan}/blocks/{block}/background` | `seat-plans.blocks.background.destroy` | Remove a block's background image. |

Files are written through `StorageRole::public()` under `seat-plans/{plan_id}/…`. Responses are `302` Inertia redirects with a `status=seat-plan-background-uploaded|…` flash.

---

## 4. Requirements Traceability

| Interface Requirement (IRS) | Interface Design (IDD) |
|---------------------------|----------------------|
| IF-INTAPI-* | Section 3.1 |
| IF-SSO-* | Section 3.2 |
| IF-WHK-* | Section 3.3 |
| IF-STRIPE-* | Section 3.4 |
| IF-PUSH-* | Section 3.7 |
| IF-S3-* | Section 3.6 |
| IF-LANBRACKETS-* | Section 3.8 |
| IF-TMT2-* | Section 3.9 |
| IF-JWKS-* | Section 3.12 |
| IF-VALIDATE-* | Section 3.13 |
| SET-F-006..008 (Seat Picker) | Section 3.14 |
| SET-F-011 (Category filter in picker / assign 422) | Section 3.14.1, 3.14.2 |
| SET-F-010 (Privacy) | Section 3.15 |
| SET-F-012..013 (Two-phase seat-plan update) | Section 3.16 |
| SET-F-015 (Background images) | Section 3.17 |
| SET-F-016 (Wire-shape preservation via SeatPlanResource) | Section 3.14.1 |

---

## 5. Notes

### 5.1 Acronyms

| Term | Definition |
|------|-----------|
| HMAC | Hash-based Message Authentication Code |
| REST | Representational State Transfer |
| SPA | Single Page Application |
| SSO | Single Sign-On |
| TTL | Time To Live |
| VAPID | Voluntary Application Server Identification |
| JWKS | JSON Web Key Set |
| kid | Key Identifier |
| LCT1 | LanCore Token version 1 |
| ABNF | Augmented Backus-Naur Form |
| CSPRNG | Cryptographically Secure Pseudo-Random Number Generator |

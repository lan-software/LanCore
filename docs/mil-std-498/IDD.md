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
StripePaymentProvider::createCheckoutSession(Cart $cart)
```

Creates a Stripe Checkout Session with:
- Line items derived from cart items
- Success URL: `/shop/checkout/success?session_id={CHECKOUT_SESSION_ID}`
- Cancel URL: `/shop`
- Customer: user's Stripe customer ID (created via Cashier if needed)

#### 3.4.2 Payment Confirmation

Stripe sends a webhook to Laravel Cashier's webhook endpoint. On `checkout.session.completed`:

1. FulfillOrder action is invoked
2. Order status updated to `confirmed`
3. Tickets created for each ticket-type line item
4. `TicketPurchased` event dispatched

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

#### 3.6.1 File Upload

Files uploaded via form requests are stored using Laravel's Storage facade:

```php
Storage::disk('s3')->put($path, $file);
```

#### 3.6.2 File Serving

`StorageFileController` serves files from S3 with appropriate headers. Supports both signed URLs and anonymous access (configurable).

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

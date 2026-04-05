# LanCore Integration System

A lean first-party integration mechanism for the LAN-Software ecosystem.
Side-apps (like LanShout) authenticate with bearer tokens to resolve LanCore user data.

## Overview

- **No OAuth / OpenID Connect** — token-based, server-to-server
- **Admin-managed** — only Admin+ users can create apps and tokens via UI or CLI
- **Scoped data access** — each app declares which data scopes it needs (`user:read`, `user:email`, `user:roles`)
- **Tokens are hashed** — stored as SHA-256; plain text is shown only at creation

## Data Model

### `integration_apps`

| Column | Type | Description |
|---|---|---|
| `id` | bigint | Primary key |
| `name` | string | Human-readable name, e.g. "LanShout" |
| `slug` | string (unique) | URL-safe identifier, e.g. "lanshout" |
| `description` | text (nullable) | Optional description |
| `callback_url` | string (nullable) | Reserved for future bootstrap redirects |
| `allowed_scopes` | json | Array of granted scopes |
| `is_active` | boolean | Whether the app can authenticate |

### `integration_tokens`

| Column | Type | Description |
|---|---|---|
| `id` | bigint | Primary key |
| `integration_app_id` | bigint (FK) | Owning app |
| `name` | string | Label, e.g. "Production Token" |
| `token` | string(64) | SHA-256 hash of the plain text token |
| `plain_text_prefix` | string(8) | First 8 chars of plain text, for identification |
| `last_used_at` | datetime (nullable) | Updated on each authenticated request |
| `expires_at` | datetime (nullable) | Optional expiration |
| `revoked_at` | datetime (nullable) | Set when a token is revoked |

## Available Scopes

| Scope | Data Returned |
|---|---|
| `user:read` | `id`, `username`, `locale`, `avatar_url`, `created_at` (required baseline) |
| `user:email` | `email` (in addition to above) |
| `user:roles` | `roles` array (in addition to above) |

An app must have at least `user:read` to resolve any user data.

## API Endpoints

All API endpoints require a valid integration token in the `Authorization` header.

### Authentication

```
Authorization: Bearer lci_<60-random-chars>
```

The middleware validates the token, checks the app is active and the token is not revoked/expired, and updates `last_used_at`.

### `GET /api/integration/user/me`

Returns the currently session-authenticated LanCore user. Useful for same-origin setups where the side-app proxies through, or shares the LanCore session.

**Response (200):**

```json
{
  "data": {
    "id": 42,
    "username": "mkohn",
    "locale": "en",
    "avatar_url": null,
    "created_at": "2025-01-15T10:30:00+00:00",
    "email": "mkohn@example.com",
    "roles": ["Admin"]
  }
}
```

Fields beyond the baseline depend on the app's allowed scopes.

**Errors:** `401` if no session user, `403` if missing `user:read` scope.

### `POST /api/integration/user/resolve`

Server-to-server user lookup. Send either `user_id` or `email`.

**Request Body:**

```json
{ "user_id": 42 }
```

or

```json
{ "email": "mkohn@example.com" }
```

**Response (200):** Same structure as `/me`.

**Errors:** `404` if user not found, `403` if missing `user:read` scope.

## Admin UI

Navigate to **Settings → Integrations** in the sidebar (visible to Admin+ users).

- **Index** — Lists all integration apps with status, scopes, and token counts
- **Create** — Form for name, slug, description, callback URL, active toggle, and scope checkboxes
- **Edit** — Update app settings, create/revoke tokens, view token prefix and last-used timestamp, delete the app

When a new token is created, the plain text is displayed once. Copy it immediately.

## Artisan Commands

### Create an app

```bash
vendor/bin/sail artisan integration:create "LanShout" \
  --scopes=user:read --scopes=user:email \
  --description="LanShout notification service"
```

### Generate a token

```bash
vendor/bin/sail artisan integration:token lanshout "Production Token" \
  --expires="+90 days"
```

The plain text token is printed to stdout once.

### List all apps

```bash
vendor/bin/sail artisan integration:list
```

## Consuming the API (Side-App Example)

```php
// LanShout resolving a user by email
$response = Http::withToken($integrationToken)
    ->post('https://lancore.example.com/api/integration/user/resolve', [
        'email' => 'player@example.com',
    ]);

$user = $response->json('data');
// ['id' => 42, 'username' => 'mkohn', 'locale' => 'en', ...]
```

```typescript
// TypeScript / fetch example
const res = await fetch('https://lancore.example.com/api/integration/user/resolve', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${LCI_TOKEN}`,
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  body: JSON.stringify({ user_id: 42 }),
});
const { data } = await res.json();
```

## File Structure

```
app/Domain/Integration/
├── Actions/
│   ├── CreateIntegrationApp.php
│   ├── CreateIntegrationToken.php
│   ├── DeleteIntegrationApp.php
│   ├── ResolveIntegrationUser.php
│   ├── RevokeIntegrationToken.php
│   └── UpdateIntegrationApp.php
├── Http/
│   ├── Controllers/
│   │   ├── IntegrationAppController.php
│   │   ├── IntegrationTokenController.php
│   │   └── IntegrationUserController.php
│   ├── Middleware/
│   │   └── AuthenticateIntegration.php
│   └── Requests/
│       ├── IntegrationAppIndexRequest.php
│       ├── StoreIntegrationAppRequest.php
│       ├── StoreIntegrationTokenRequest.php
│       └── UpdateIntegrationAppRequest.php
├── Models/
│   ├── IntegrationApp.php
│   └── IntegrationToken.php
└── Policies/
    └── IntegrationAppPolicy.php

routes/integrations.php
resources/js/pages/integrations/{Index,Create,Edit}.vue

tests/Feature/Integrations/
├── AccessTest.php
├── IntegrationApiTest.php
├── IntegrationAppCrudTest.php
├── IntegrationCommandTest.php
└── IntegrationTokenTest.php
```

## Security Notes

- Tokens are SHA-256 hashed before storage; plain text is never persisted
- Token prefix (`lci_`) makes it easy to identify leaked tokens in logs
- Revocation is immediate and irreversible
- Inactive apps cannot authenticate even with valid tokens
- All admin routes require `auth` + `verified` middleware and Admin+ policies
- The `user:email` scope must be explicitly granted — emails are not exposed by default

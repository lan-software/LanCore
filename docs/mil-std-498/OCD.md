# Operational Concept Description (OCD)

**Document Identifier:** LanCore-OCD-001
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

This Operational Concept Description (OCD) applies to the **LanCore** system — a LAN Party & BYOD Event Management Platform.

### 1.2 System Overview

LanCore is a self-hosted web application that enables LAN party organizers to plan, promote, and manage events. It handles the full event lifecycle from creation through ticket sales, check-in, scheduling, news publishing, and post-event activities.

### 1.3 Document Overview

This document describes the operational concept for LanCore, including the user classes, operational scenarios, and the system's role in the broader event management context.

---

## 2. Referenced Documents

- [SRS](SRS.md) — Software Requirements Specification
- [SSS](SSS.md) — System/Subsystem Specification
- [SUM](SUM.md) — Software User Manual

---

## 3. Current System or Situation

### 3.1 Background and Objectives

LAN party organizers currently rely on a combination of fragmented tools (spreadsheets, manual ticket tracking, separate CMS for news, third-party ticketing platforms) or legacy software (eventula-manager) that has become difficult to maintain.

LanCore aims to provide a single, integrated platform that:

- Eliminates dependency on multiple disconnected tools
- Provides a modern, responsive user experience
- Supports self-hosting for data sovereignty
- Enables extensibility through integrations and webhooks

### 3.2 Operational Policies and Constraints

- The system must be self-hostable on commodity hardware or cloud infrastructure
- User data must remain under the control of the event organizer
- The system must support both online (Stripe) and on-site payment methods
- The system must be accessible on mobile devices
- The system shall provide two supported installation paths:
  - **Docker Compose** — canonical single-host install; primary target for small events and demo stacks. See [SIP](SIP.md) §3.4.
  - **Helm chart on Kubernetes** — canonical multi-service install; primary target for production, multi-satellite deployments, and operator-managed stateful infrastructure. Distributed as an OCI artifact at `oci://ghcr.io/lan-software/charts/lan-software`. See [SIP](SIP.md) §3.5.

---

## 4. Justification for and Nature of Changes

### 4.1 Justification

- Legacy eventula-manager is built on outdated technology and is difficult to extend
- Modern LAN events require real-time features (push notifications, live announcements)
- Integration with companion apps (tournament brackets, check-in, food ordering) requires a robust API
- Domain-driven architecture enables parallel development of isolated features

### 4.2 Description of Desired Changes

LanCore is a ground-up rewrite providing:

- Domain-driven architecture for maintainability
- Real-time notifications via Web Push
- Webhook-driven integration ecosystem
- Canvas-based seating plan editor
- Flexible ticketing with quotas, categories, groups, and add-ons
- Achievement/badge system for attendee engagement

---

## 5. Concepts for the Proposed System

### 5.1 User Classes and Roles

#### 5.1.1 Attendee (Unauthenticated Visitor)

- Browse published events and news
- View public announcements
- Register an account

#### 5.1.2 Registered User (Authenticated)

- Purchase tickets for events
- View and manage their own tickets
- Manage profile and security settings (including 2FA)
- Select preferred display language (English, German, French, Spanish) in their profile; the selected locale is stored in LanCore and propagated to all satellite apps at next SSO exchange
- Customize notification preferences (email, web push)
- Comment on and vote on news articles
- Dismiss announcements
- View earned achievements
- Subscribe to program schedule notifications
- Configure ticket discovery (visibility to other users)

#### 5.1.3 Sponsor Manager

- All Registered User capabilities
- Manage assigned sponsor profiles
- View sponsorship assignments

#### 5.1.4 Admin

- All Registered User capabilities
- Create, edit, publish, and unpublish events
- Manage venues and addresses
- Create and manage ticket types, categories, groups, and add-ons
- Configure seating plans
- Create and manage programs and time slots
- Publish news articles and announcements
- Manage sponsors and sponsor levels
- Define achievements and grant them to users
- Configure webhooks and integration apps
- Manage orders and vouchers
- View audit trails for all entities
- Manage users and assign roles
- Configure purchase requirements and checkout conditions

#### 5.1.5 Superadmin

- All Admin capabilities
- Full system access with no policy restrictions
- User role management including admin promotion

### 5.2 Operational Scenarios

#### 5.2.1 Event Creation and Publication

1. Admin creates a new event with name, dates, and description
2. Admin assigns or creates a venue with address and images
3. Admin creates a primary program with time slots
4. Admin configures ticket types with pricing and quotas
5. Admin optionally creates a seating plan
6. Admin assigns sponsors to the event
7. Admin publishes the event — triggers webhook notifications to integrations
8. Registered users can now browse and purchase tickets

#### 5.2.2 Ticket Purchase Flow

1. User browses published event and available ticket types
2. User adds tickets and optional add-ons to cart
3. System validates quotas, per-user limits, and availability
4. User proceeds to checkout, acknowledges purchase requirements
5. User selects payment method (Stripe or on-site)
6. For Stripe: redirected to Stripe Checkout, then back on success
7. For on-site: order marked pending, fulfilled manually by admin
8. System generates ticket with unique validation ID
9. Ticket purchase event triggers webhooks and notifications

#### 5.2.2a Group Ticket Purchase and Assignment

1. Admin creates a group ticket type with max users per ticket (e.g., 4) and a check-in mode (individual or group)
2. User purchases a group ticket — seat capacity is reserved based on `seats_per_user × max_users_per_ticket` (on-site orders stay pending until admin confirms payment)
3. Purchaser (owner) can assign additional users to the ticket up to the configured limit
4. Each assigned user appears in the ticket's user list with their own check-in status
5. Owner or manager can add/remove assigned users before the event
6. At check-in:
   - **Individual mode:** Each user checks in independently; the ticket is fully checked in when all users have checked in
   - **Group mode:** A single check-in action marks all assigned users as checked in simultaneously
7. Companion apps (LanEntrance) can query per-user check-in status via the Integration API

#### 5.2.3 News Publishing

1. Admin creates a news article with rich text content (TipTap editor)
2. Admin sets visibility (public, members-only, draft)
3. Admin publishes the article
4. System sends notifications to users with matching preferences (email, push)
5. System dispatches webhooks to subscribed integration apps
6. Users can comment on articles and vote on comments

#### 5.2.4 Real-Time Announcements

1. Admin creates an announcement with priority level (low, normal, high, critical)
2. Admin targets announcement to a specific event or all users
3. System delivers push notifications to subscribed users
4. System dispatches webhooks to subscribed integrations
5. Users can dismiss announcements; dismissals are tracked per-user

#### 5.2.5 Check-In at Event

1. Attendee arrives at venue with a QR code displayed from their ticket PDF or LanCore portal
2. Door staff uses companion app (LanEntrance) connected via Integration API
3. LanEntrance performs a fast local signature pre-check against LanCore's published public key, then calls LanCore's authoritative validate endpoint; the plaintext token is never stored by either party
4. LanCore locates the ticket by comparing the HMAC of the token nonce against the stored nonce hash, verifies the Ed25519 signature, checks expiry, and returns a validation decision
5. Companion app presents the decision (valid / already_checked_in / expired / revoked / etc.) to the door operator and marks the ticket as checked in
6. System updates ticket status from "active" to "checked_in"
7. For group tickets with individual check-in: each user checks in separately; ticket status becomes "checked_in" when all users have checked in
8. For group tickets with group check-in: a single scan checks in all assigned users simultaneously

#### 5.2.5a Ticket Token Confidentiality and Signing

LanCore issues cryptographically signed ticket tokens to protect against the following threat scenarios:

1. **Database dump leak** — An attacker who obtains a full copy of the database cannot forge QR codes. The private signing key and the HMAC pepper are never stored in the database; the stored nonce hash is a one-way derivation that reveals neither the token value nor the original nonce.
2. **QR photo leak** — An attacker who photographs a QR code cannot discover the database record it corresponds to. The stored value is an HMAC of the nonce, not the nonce itself; brute-force reversal is computationally infeasible.
3. **Insider with database access** — An insider who can read the database still cannot mint new valid QR codes without access to the private signing key, which lives outside the database in secured key storage.
4. **Scanner (LanEntrance) theft** — LanEntrance holds only public keys. A compromised LanEntrance instance cannot forge new ticket tokens.

Token format: `LCT1.<kid>.<body>.<sig>`, where `body` is a base64url-encoded JSON payload containing the ticket ID, event ID, nonce, issued-at, and expiry timestamps. `sig` is an Ed25519 signature over `"LCT1." + kid + "." + body`. Tokens expire at event end plus a configurable grace period.

Key management is performed by operators via the `php artisan tickets:keys:rotate` command. Retired keys remain in the verification set so tokens issued before rotation remain valid until expiry. The active public key set is published to LanEntrance via an authenticated JWKS endpoint (`GET /api/entrance/signing-keys`).

#### 5.2.5b Operator Flow — Key Rotation

1. Admin triggers key rotation via `php artisan tickets:keys:rotate`
2. System generates a new Ed25519 key pair, assigns a new `kid`, and writes the private key to `storage/keys/ticket_signing/{kid}.key`
3. The new `kid` becomes the active signing key for all subsequently issued tokens
4. LanEntrance fetches the updated JWKS from `GET /api/entrance/signing-keys` on its next cache refresh interval
5. Previously issued tokens remain valid; retired keys are retained in the JWKS verify list until all tokens signed with them have expired
6. Admins can also trigger per-ticket token rotation (`rotateToken` action) or cancel invalidation (clearing the nonce hash) through the admin ticket management interface

#### 5.2.6 Third-Party Integration

1. Admin registers an integration app with name, callback URL, and webhook subscriptions
2. System generates API tokens for the integration
3. Integration authenticates via Bearer token on stateless API routes
4. Integration receives webhook events for subscribed event types. The full set of dispatched events is: `user.registered`, `user.roles_updated`, `user.profile_updated`, `announcement.published`, `news_article.published`, `event.published`, `ticket.purchased`, `integration.accessed`
5. Integration can initiate SSO flow for user authentication
6. Integration appears in navigation for users who have authorized it

#### 5.2.6a Shared Integration Client (Lan\* Satellite Apps)

The Lan\* satellite ecosystem (LanBrackets, LanEntrance, LanShout, LanHelp, LanChart, LanBase) consumes the LanCore Integration API through a shared Composer package, `lan-software/lancore-client`, rather than each app maintaining its own HTTP client, webhook verification, and exception handling. This establishes a single operational protocol between LanCore and its satellites:

1. Each satellite declares `lan-software/lancore-client` as a Composer dependency
2. Satellites configure LanCore access via a uniform environment contract (`LANCORE_URL`, `LANCORE_TOKEN`, `LANCORE_APP_SLUG`, `LANCORE_WEBHOOK_SECRET`, …)
3. The package provides the SSO exchange, user resolution, webhook signature verification, and abstract webhook controllers for all eight event types; satellites supply only domain concerns (role model, user persistence, business response to events)
4. LanEntrance additionally uses the package's opt-in `entrance()` sub-client for ticket validation and JWKS caching
5. Package releases are versioned independently; satellites adopt new capabilities by bumping the dependency and implementing any newly-exposed hooks

#### 5.2.7 Achievement Tracking

1. Admin defines achievements with criteria (grantable events)
2. System listens for qualifying events (ticket purchase, profile update, etc.)
3. When criteria met, system grants achievement to user
4. User receives notification of earned achievement
5. User can view their achievements in settings

### 5.3 System Context

```
                    +-------------------+
                    |   Web Browser     |
                    |  (Attendee/Admin) |
                    +--------+----------+
                             |
                    +--------v----------+
                    |    LanCore        |
                    |  (Laravel/Vue)    |
                    +--+----+----+------+
                       |    |    |
          +------------+    |    +-------------+
          |                 |                  |
+---------v---+   +---------v---+   +----------v--------+
| PostgreSQL  |   |    Redis    |   |  S3/Minio Storage  |
| (Database)  |   | (Cache/     |   |  (Files/Images)    |
+-------------+   |  Sessions)  |   +-------------------+
                  +-------------+
                             |
                    +--------v----------+
                    | External Services |
                    | - Stripe API      |
                    | - SMTP Mail       |
                    | - Web Push        |
                    | - Integration Apps|
                    +-------------------+
```

### 5.4 Modes of Operation

| Mode | Description |
|------|-------------|
| Normal Operation | All services running, full feature set available |
| Maintenance Mode | Application returns maintenance page; admin can still access via allowed IPs |
| Development Mode | Debug toolbar, Telescope, detailed error pages enabled |
| Queue Processing | Horizon manages background jobs (webhooks, notifications, email) |
| Demo Purposes | System pre-loaded with synthetic event, ticket, and user data to showcase platform capabilities to prospective organizers or stakeholders; payments are processed through the real payment provider configured in test mode, so no real financial transactions occur |
| Localized Display | Each authenticated user's UI is rendered in their chosen locale (English, German, French, or Spanish). LanCore is the authoritative store of the `User.locale` field; satellite apps receive the locale via the SSO exchange DTO and apply it per-request via a `SetLocale` middleware. Locale strings are sourced from Laravel `lang/` directories and the Vue front-end via `vue-i18n`; translation assets are managed in Tolgee Cloud and pulled nightly to each app repository via a GitHub Actions workflow |

---

## 6. Operational and Organizational Impacts

### 6.1 Operational Impacts

- Event organizers consolidate from multiple tools to a single platform
- Real-time notifications reduce the need for manual attendee communication
- Integration API enables ecosystem of companion apps
- Self-hosting ensures data sovereignty and reduces recurring SaaS costs

### 6.2 Organizational Impacts

- Organizers need basic Docker/server administration skills for self-hosting
- Operators deploying on Kubernetes additionally need familiarity with Helm values and Kubernetes primitives (Deployments, Services, Ingress, Secrets, NetworkPolicy); the `lan-software` umbrella chart ships sensible defaults so the happy path does not require writing manifests by hand
- Staff roles map to application roles (Admin, Sponsor Manager)
- Companion app developers interact via documented Integration API

---

## 7. Notes

### 7.1 Glossary

| Term | Definition |
|------|-----------|
| LAN Party | A social gathering where participants bring personal computers to play multiplayer games together on a local network |
| BYOD | Bring Your Own Device — events where attendees bring their own computing equipment |
| Seating Plan | A visual layout of physical seat positions at a venue |
| Voucher | A discount code that can be applied during ticket checkout |
| Add-on | An additional purchasable item attached to a ticket (e.g., t-shirt, meal) |
| Group Ticket | A ticket that allows multiple users to gain entry, purchased by one owner and assignable to multiple attendees |
| Webhook | An HTTP callback that delivers event notifications to external systems |
| Integration App | A third-party application registered with LanCore for API access and SSO |
| lancore-client | Shared Composer package (`lan-software/lancore-client`) consumed by all Lan\* satellite applications as the canonical LanCore Integration API client |
| Lan\* Satellite | A companion application (LanBrackets, LanEntrance, LanShout, LanHelp, LanChart, LanBase) that integrates with LanCore via the shared lancore-client package |
| LCT1 Token | Signed ticket validation token in the format `LCT1.<kid>.<body>.<sig>` issued by LanCore and verified by LanEntrance |
| kid | Key identifier — a short string (up to 16 characters) that identifies which Ed25519 signing key was used for a given token |
| JWKS | JSON Web Key Set — a JSON document listing public keys, served by LanCore to LanEntrance for token verification |
| Ed25519 | An elliptic-curve digital signature algorithm used for ticket token signing; asymmetric (private key on LanCore, public key shared to LanEntrance) |
| Nonce | A 128-bit random value embedded in each ticket token, rotated on every token regeneration; never stored in plaintext |
| Nonce Hash | HMAC-SHA256 of the nonce using a server-side pepper; stored in the database for token-to-ticket lookup without exposing the nonce |
| Pepper | A secret value (not stored in the database) used as the HMAC key when deriving the nonce hash |
| Locale | A BCP 47 language tag (e.g., `en`, `de`, `fr`, `es`) that identifies the user's preferred display language; stored as the `locale` column on the LanCore `users` table and propagated to satellite apps via the `LanCoreUser` DTO |
| i18n | Internationalization — the process of designing software so that it can be adapted to multiple languages without code changes |
| l10n | Localization — the process of adapting an internationalized application for a specific language or region |
| vue-i18n | The Vue.js internationalization plugin (`vue-i18n@9+`) used in the LanCore and satellite Vue frontends to translate UI strings from JSON locale files |
| Tolgee | Cloud-based translation management platform (free-plan, single project with per-app namespaces) used to maintain translation strings; a nightly GitHub Actions workflow pulls approved translations into each app repository |

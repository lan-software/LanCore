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
- Drag-to-place seat-plan editor with row/grid wizard, mass editing, undo/redo, and optional background images
- Flexible ticketing with quotas, categories, groups, and add-ons
- Achievement/badge system for attendee engagement

---

## 5. Concepts for the Proposed System

### 5.1 User Classes and Roles

#### 5.1.1 Attendee (Unauthenticated Visitor)

- Browse published events and news
- View public announcements
- Register an account
- Save a published event to a personal calendar (Google / Apple / Outlook) by downloading an iCalendar file from the public event page, so the date and venue are not lost between learning about the event and registering

#### 5.1.2 Registered User (Authenticated)

- Purchase tickets for events
- View and manage their own tickets
- Manage profile and security settings (including 2FA)
- Choose a public-facing **username** (gamer handle) distinct from their real name; the username is mandatory, unique, 3–32 characters from a constrained gamer character set, and is the identity surfaced on every public-facing display across the LAN ecosystem (tournament brackets, leaderboards, public profile)
- Have a **public profile page** (`/u/{username}`) showing the username, custom emoji, short bio, long-form description, avatar, banner, and earned achievements (with global rarity); the page never exposes the user's real name, contact, address, country, or locale
- Choose **profile visibility** in privacy settings (public to anonymous visitors / visible to logged-in users only / private), and a **profile picture source** (built-in default / Gravatar / custom upload normalized server-side to 1000×1000 px; a Steam-linked source is reserved for a future iteration)
- Upload a **profile banner** (normalized server-side to a 1500×500 / 3:1 image)
- Preview the public profile from the profile settings page exactly as an anonymous visitor would see it, regardless of the current visibility setting
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

#### 5.2.5c Operator Flow — Per-ticket Nonce Rotation

Rendering a QR (web UI or PDF regen) never rotates the stored nonce: the system recomputes the same deterministic nonce on every render using the stored `validation_rotation_epoch`. A nonce rotation is a deliberate, infrequent event triggered by:

1. Initial ticket issuance inside `FulfillOrder`.
2. `UpdateTicketAssignments::{addUser, removeUser, updateManager, rotateToken}` (user-pivot or manager changes, plus any explicit rotate).
3. A ticket holder clicking "Rotate QR" in "My Tickets" (`POST /tickets/{ticket}/rotate-token`, rate-limited to 10/min/user, owner or manager only).
4. An admin triggering `rotateToken` from the admin ticket management interface.

Whenever rotation (2)–(4) fires, every affected user receives a `TicketTokenRotatedNotification` through the in-app notification bell and by email. Affected users = ticket owner + every user currently attached to the ticket + the just-removed user (on `removeUser`) + the previously-assigned manager (on `updateManager`). The notification explicitly warns that previously-printed PDFs are now invalid and links to "My Tickets" for a fresh download or a live on-screen QR at the entrance.

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
6. **Public-facing identity policy:** every satellite that renders a user's identity on a publicly visible surface (tournament brackets, public leaderboards, player cards, scoreboards, OBS overlays, etc.) **shall** consume the `LanCoreUser.username` field from the DTO. Satellites **shall not** display the `name` (real name) or `email` fields publicly under any circumstance. Satellites **shall** apply a generic placeholder (e.g. "Player #ID") when a satellite-side `LanCoreUser` payload carries `username = null` (a transitional state for users registered before the username feature shipped, until they complete the one-time username selection on next login)

#### 5.2.7 Achievement Tracking

1. Admin defines achievements with criteria (grantable events)
2. System listens for qualifying events (ticket purchase, profile update, etc.)
3. When criteria met, system grants achievement to user
4. User receives notification of earned achievement
5. System maintains a per-achievement **earned-user count** that is incremented whenever a grant fires and decremented on revocation; the count, divided by the current registered-user total, yields the **achievement rarity percentage**
6. User can view their earned achievements in settings, and any visitor with permission to see a user's public profile sees the user's earned achievements alongside the rarity label (e.g., "Earned by 5% of users")

#### 5.2.8 Showing the Orga-Team to Attendees

1. Admin opens the Orga-Teams admin section and creates a reusable Orga-Team (e.g., "SXLAN Crew") composed of one *Veranstalter* (top-level Organizer), N *Stellvertreter* (Deputies), and N Sub-Teams (e.g., Tech, Marketing, Tournaments, General)
2. For each Sub-Team, admin assigns one optional Leader, N optional Fallback Leaders (Deputies), and N regular Members; all members must be registered LanCore users; a single user may belong to multiple Sub-Teams
3. Admin assigns the Orga-Team to one or more events. An event has at most one Orga-Team; multiple events may share the same team. When no team is assigned to an event, the public Orga-Team display is suppressed for that event
4. While logged in, attendees see a compact OrgaTeamCard inside the new event-bound RightContentArea on event-scoped pages (Welcome resolves the next upcoming Published event)
5. The OrgaTeamCard links to a dedicated public OrgChart page (`/events/{event}/orga-team`) showing the full hierarchy as a tree: Organizer + Deputies at the top, each Sub-Team as a coloured/emoji-tagged group below with its Leader, Fallback Leaders, and Members
6. Each person card on the OrgChart links to the user's public profile (`/u/{username}`)
7. Membership is purely informational and confers no system permissions; only `ManageOrgaTeams` (admin) governs editing the team

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
| Localized Display | Each authenticated user's UI is rendered in their chosen locale (English, German, French, or Spanish). LanCore is the authoritative store of the `User.locale` field; satellite apps receive the locale via the SSO exchange DTO and apply it per-request via a `SetLocale` middleware. Locale strings are sourced from Laravel `lang/` directories and the Vue front-end via `vue-i18n`; translation assets are managed in the self-hosted Weblate instance at `https://weblate.sxcs.de` and merged into each app repository via the `weblate` branch and a fast-forward workflow |

### 5.5 Platform Policies and User Consent

LanCore captures, versions, and audits user consent for platform-wide policies (Terms of Service, Privacy Policy, EULA, …) as a first-class domain feature.

**Operational scenarios:**

- *Platform admin manages policies.* From `/admin/policies` an admin creates policy types, then policies referencing those types. Each policy carries metadata (`name`, `description`, `is_required_for_registration`, `sort_order`) but no content; content lives in versions.
- *Publishing a version.* The admin opens a policy and clicks "Publish new version". Inputs: markdown content, locale (defaults to app locale), effective date, and a single `is_non_editorial_change` flag. Editorial publishes (typo / formatting fixes) are **silent** — they do not email anyone and do not force re-acceptance. Non-editorial publishes (rights-affecting) trigger a two-step confirmation modal (5-second delayed-enable) showing the count of prior acceptors who will receive an email; on confirmation, the system queues one email per prior acceptor and updates the policy's `required_acceptance_version_id` pointer, which causes the gate middleware to redirect every active user to `/policies/required` on their next request.
- *User accepts at registration.* The Fortify register form lists every `is_required_for_registration` policy as a checkbox; submission without all required acceptances 422s.
- *User accepts after a non-editorial publish.* On the next request after publish, `RequirePolicyAcceptance` middleware stashes the intended URL and redirects to `/policies/required`, where the user reviews + accepts each unaccepted required policy.
- *User withdraws consent (GDPR Art. 7(3)).* From `/settings/privacy` the user expands "Consent" and clicks "Withdraw" on a previously accepted policy. The acceptance row is preserved (audit trail) — `withdrawn_at` + reason + IP + user-agent are stamped. Every user holding `ManagePolicies` receives a mail + database notification.
- *Public read.* `/legal` lists every non-archived policy. `/policies/{key}` renders the latest version of a policy.

**Legacy migration.** The old per-organization `OrganizationSetting->privacy_content` field, rendered at `/privacy` and `/datenschutz` in earlier releases, is removed in this release. The legacy routes are gone; the field is left in the DB un-touched and un-rendered. Operators must re-author privacy content as a new Policy in the admin UI.

### 5.6 GDPR Article 15 Operator Workflow

A platform operator (acting on a Subject Access Request) runs `vendor/bin/sail artisan gdpr:export-user user@example.com`. Laravel-Prompts walks them through optional AES-256 password protection. The command writes a single ZIP at `storage/app/gdpr-exports/{user-id}-{Y-m-d_His}.zip` containing JSON dumps of every record about the subject across every domain (Profile, Policy acceptances, Sessions, Audits, Shop, Ticketing, Competition, News, Notification, OrgaTeam, Sponsoring, Achievements), plus a copy of the PDF of every accepted policy version, plus a `manifest.json` and `README.txt`. Identifiers of other users that appear inside the subject's records (teammates, comment authors, ticket co-users, etc.) are obfuscated as `user_a`, `user_b`, … per export run; no reverse map is included. The export command also locates anonymized users by salted email-hash, so post-deletion subject access requests are still serviceable — see §5.7.

### 5.7 GDPR Article 17 — Right to Erasure & Data Lifecycle

The Data Lifecycle feature domain (`app/Domain/DataLifecycle/`) implements right-to-erasure and per-domain retention. There are three flows:

- *User-initiated deletion.* From `/account/delete` the user re-confirms their password and submits a deletion request. A confirmation email with a one-shot token is sent. Clicking the link starts a 30-day grace period: the account remains accessible but is locked to read-only by `EnforceAccountReadOnlyDuringGrace` middleware, allowing the user to download GDPR exports, reverse the decision, or simply log out. After the grace expires, `ProcessDueDeletionRequestsJob` (scheduled at 03:00 daily) hands the request to `AnonymizeUser`, which walks every registered `DomainAnonymizer` in dependency order and ends with `UserAnonymizer` scrubbing the `users` row in place. The `email_hash` (HMAC-SHA256 of the lowercased original email, salted with an HKDF-derived per-installation key) is preserved as the post-deletion lookup key for §5.6.

- *Admin-initiated deletion.* From `/admin/data-lifecycle/deletion-requests` an admin holding `RequestUserDeletion` opens a request on behalf of a user (e.g. ToS violation, support ticket asking for deletion). The same email-confirmation + grace flow applies. An "Anonymize now" action skips the remaining grace once a justification is recorded.

- *Force-delete (legal request).* From the same admin page an admin holding the dedicated `ForceDeleteUserData` permission can bypass retention and hard-delete the user row plus all force-deletable data, on signed legal request. A free-text reason is required and recorded; the action is fully audited via `owen-it/laravel-auditing`. Pinned `RetentionPolicy` rows (`can_be_force_deleted = false`) still hold.

Retention windows per data class are configurable from `/admin/data-lifecycle/retention-policies` and stored in the `retention_policies` table (default 10 years for accounting and audit data, 30 days for sessions, 0 for non-financial fields). `PurgeExpiredDataCommand` (scheduled at 03:15 daily) walks soft-deleted, anonymized users and applies expired retention policies, ultimately hard-deleting the user row when no retention obligation remains.

**Events are soft-delete only.** `EventPolicy::forceDelete` permanently returns false; events are never hard-deleted, preserving attendance, accounting, and competition history.

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
| Seating Plan | A relational layout of blocks, rows, seats, and labels at a venue, with optional global and per-block background images |
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
| Nonce | A 128-bit value embedded in each ticket token, derived deterministically from HMAC(pepper, ticket_id \|\| rotation_epoch); never stored in plaintext. Rotation = incrementing the epoch counter |
| Nonce Hash | HMAC-SHA256 of the nonce using a server-side pepper; stored in the database for token-to-ticket lookup without exposing the nonce |
| Pepper | A secret value (not stored in the database) used as the HMAC key when deriving the nonce hash |
| Locale | A BCP 47 language tag (e.g., `en`, `de`, `fr`, `es`) that identifies the user's preferred display language; stored as the `locale` column on the LanCore `users` table and propagated to satellite apps via the `LanCoreUser` DTO |
| Username | A user-chosen public-facing handle distinct from the user's real name (`name`). 3–32 characters, gamer-friendly character set (`[A-Za-z0-9]` plus `_` and `-`, never leading or trailing). Unique case-insensitively across all LanCore users. Mandatory at signup. Surfaced on the public profile and in every satellite app's public-facing display via the `LanCoreUser.username` claim. The real name is never publicly visible |
| Public Profile | The page rendered at `/u/{username}` showing a user's username, custom emoji, short bio, long-form description, avatar, banner, and earned achievements with global rarity. Subject to the user's `profile_visibility` setting: `public` (visible to anonymous), `logged_in` (visible to authenticated LanCore users only), `private` (visible only to the user themselves). Real name, email, phone, address, country, and locale are never rendered |
| Avatar Source | The origin of a user's profile picture: `default` (built-in identicon-style placeholder), `gravatar` (resolved from email hash), `custom` (uploaded by the user, normalized to 1000×1000 WebP). `steam` is reserved for a future Steam account-linking iteration and currently falls back to `default` |
| Profile Banner | The optional wide image displayed at the top of the public profile, normalized server-side to 1500×500 (3:1 aspect ratio) |
| Achievement Rarity | The percentage of registered LanCore users who have earned a given achievement, computed from a per-achievement `earned_user_count` cache divided by the current total user count; rendered alongside the achievement on the public profile (e.g., "Earned by 5% of users") |
| i18n | Internationalization — the process of designing software so that it can be adapted to multiple languages without code changes |
| l10n | Localization — the process of adapting an internationalized application for a specific language or region |
| vue-i18n | The Vue.js internationalization plugin (`vue-i18n@9+`) used in the LanCore and satellite Vue frontends to translate UI strings from JSON locale files |
| Weblate | Self-hosted translation management system at `https://weblate.sxcs.de` used to maintain translation strings; organised as one project (`lan-software`) with five components (one per app); translator commits arrive via a dedicated `weblate` branch per app repository and are fast-forwarded onto `main` by the `.github/workflows/weblate-merge.yml` workflow; Weblate reads source strings directly from the existing `resources/js/locales/{en,de,fr,es}.json` files |

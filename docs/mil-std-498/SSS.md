# System/Subsystem Specification (SSS)

**Document Identifier:** LanCore-SSS-001
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

This System/Subsystem Specification (SSS) specifies the requirements for the **LanCore** system — a LAN Party & BYOD Event Management Platform.

### 1.2 System Overview

LanCore is a self-hosted web application built on Laravel 13, Vue.js 3, and Inertia.js v2 that provides comprehensive event management for LAN parties and BYOD events. The system encompasses event creation, ticketing, payments, seating, scheduling, news, notifications, integrations, and user management.

### 1.3 Document Overview

This document specifies the system-level requirements for LanCore, organized by capability, interface, performance, security, and environmental constraints.

---

## 2. Referenced Documents

- [OCD](OCD.md) — Operational Concept Description
- [SRS](SRS.md) — Software Requirements Specification
- [IRS](IRS.md) — Interface Requirements Specification
- [SDD](SDD.md) — Software Design Description

---

## 3. Requirements

### 3.1 Required States and Modes

| State | Description | Transitions |
|-------|-------------|-------------|
| Normal | Full operation, all features available | To Maintenance, To Queue-Only, To Demo |
| Maintenance | Application returns 503, scheduled work paused | To Normal |
| Queue-Only | Web interface disabled, queue workers process jobs | To Normal |
| Development | Debug features enabled, Telescope active | To Normal |
| Demo | System operates with pre-seeded synthetic data; payment provider is configured in test mode so no real transactions occur; intended for showcasing platform capabilities to prospective organizers or stakeholders | To Normal |

### 3.2 System Capability Requirements

#### 3.2.1 Event Management (CAP-EVT)

| Req ID | Requirement |
|--------|------------|
| CAP-EVT-001 | The system shall allow admins to create events with name, description, dates, and banner images |
| CAP-EVT-002 | The system shall support event statuses: draft and published |
| CAP-EVT-003 | The system shall allow assignment of venues with addresses and images to events |
| CAP-EVT-004 | The system shall emit `EventPublished` events and dispatch webhooks on publication |
| CAP-EVT-005 | The system shall support seat capacity configuration per event |
| CAP-EVT-006 | The system shall maintain audit trails for event modifications |
| CAP-EVT-007 | The system shall allow attendees to download a published event as an iCalendar (RFC 5545) file from the public event page so it can be imported into Google / Apple / Outlook calendars |
| CAP-EVT-008 | The system shall allow each Event to be optionally assigned exactly one Theme from the Theme Library; an unassigned (`null`) theme leaves the event rendered in the platform default appearance. Traces forward to CAP-THM-002 |

#### 3.2.2 Ticketing (CAP-TKT)

| Req ID | Requirement |
|--------|------------|
| CAP-TKT-001 | The system shall support multiple ticket types per event with individual pricing and quotas |
| CAP-TKT-002 | The system shall support ticket categories and groups for logical organization |
| CAP-TKT-003 | The system shall enforce per-user ticket purchase limits |
| CAP-TKT-004 | The system shall support ticket add-ons (purchasable extras attached to tickets) |
| CAP-TKT-005 | The system shall track ticket statuses: available, assigned, used, checked_in |
| CAP-TKT-006 | The system shall generate unique validation IDs for each ticket |
| CAP-TKT-007 | The system shall support ticket assignment (owner, manager, user roles per ticket) |
| CAP-TKT-008 | ~~The system shall support seating row tickets linked to seat plans~~ **(Deprecated — superseded by CAP-TKT-011)** |
| CAP-TKT-009 | The system shall support purchase windows (start/end dates for ticket sales) |
| CAP-TKT-010 | The system shall support ticket locking mechanisms |
| CAP-TKT-011 | The system shall support group tickets with configurable max users per ticket, where seat capacity is calculated as seats_per_user × max_users_per_ticket |
| CAP-TKT-012 | The system shall support configurable check-in modes per ticket type: individual (per-user) or group (all users at once) |
| CAP-TKT-013 | The system shall issue cryptographically signed ticket tokens in the LCT1 format (`LCT1.<kid>.<body>.<sig>`) for every ticket assigned to a user. The token's nonce is derived deterministically from a pepper held outside the database, so rendering the QR never rotates the nonce. The nonce is rotated only on intentional events: initial issuance, assignment change, manager change, ticket cancellation, and explicit rotate actions (admin or user-initiated) |
| CAP-TKT-014 | The system shall publish its active and retired verification public keys to LanEntrance via an authenticated JWKS endpoint (`GET /api/entrance/signing-keys`), and support key rotation without invalidating unexpired tokens issued under prior keys |
| CAP-TKT-015 | The system shall allow ticket holders (owner or manager) to self-invalidate previously printed copies via an explicit "Rotate QR" action, and shall notify every affected user (owner, currently-assigned users, the user just removed, and any previously-assigned manager) through in-app and mail channels whenever the nonce rotates |
| CAP-TKT-016 | The ticket PDF shall be rendered as an A4 portrait tri-fold with an event-banner hero, a scan face containing the QR and attendee data, a legal face reproducing every active `GlobalPurchaseCondition` as small print, and a low-opacity personalised watermark overlay (attendee name, event, venue, organisation, ticket type, ticket ID) that varies in angle and offset per ticket and leaves a safe zone around the QR code |

#### 3.2.3 Shop and Payments (CAP-SHP)

| Req ID | Requirement |
|--------|------------|
| CAP-SHP-001 | The system shall provide a shopping cart per user per event |
| CAP-SHP-002 | The system shall support Stripe Checkout as a payment provider |
| CAP-SHP-003 | The system shall support on-site (cash/manual) payment collection |
| CAP-SHP-004 | The system shall track order statuses: pending, confirmed, failed, refunded, cancelled |
| CAP-SHP-005 | The system shall support voucher codes with fixed-amount or percentage discounts |
| CAP-SHP-006 | The system shall support configurable purchase requirements (terms acceptance, conditions) |
| CAP-SHP-007 | The system shall support global purchase conditions and payment-provider-specific conditions |
| CAP-SHP-008 | The system shall track checkout acknowledgements per user |
| CAP-SHP-009 | The system shall fulfill orders asynchronously via Stripe `checkout.session.completed` webhook when users do not return to the checkout success URL |
| CAP-SHP-010 | The system shall ensure order fulfillment is idempotent — both the success URL redirect and webhook may trigger fulfillment, but tickets shall only be created once |
| CAP-SHP-011 | The system shall support PayPal Checkout (Orders v2) as a payment provider, with idempotent fulfillment via both the return-URL capture and the `PAYMENT.CAPTURE.COMPLETED` webhook |
| CAP-SHP-012 | The system shall expose a single admin-configurable shop currency, snapshotted per-order at creation time so historical documents remain stable across currency reconfigurations |

#### 3.2.4 Programs and Scheduling (CAP-PRG)

| Req ID | Requirement |
|--------|------------|
| CAP-PRG-001 | The system shall support programs with multiple time slots per event |
| CAP-PRG-002 | The system shall support program visibility levels: public, registered_only, hidden |
| CAP-PRG-003 | The system shall support time slot ordering and visibility control |
| CAP-PRG-004 | The system shall emit notifications when time slots are approaching |
| CAP-PRG-005 | The system shall allow users to subscribe to program notifications |

#### 3.2.5 Seating (CAP-SET)

| Req ID | Requirement |
|--------|------------|
| CAP-SET-001 | The system shall provide a canvas-based seat plan editor |
| CAP-SET-002 | The system shall store seat plans as JSONB data for flexibility |
| CAP-SET-003 | The system shall link seat plans to events |
| CAP-SET-004 | The system shall maintain audit trails for seat plan modifications |
| CAP-SET-005 | The system shall enforce per-block ticket-category restrictions on seat assignments, with an open-to-all permissive default when no restriction is configured |
| CAP-SET-006 | The system shall protect existing seat assignments from silent invalidation during seat-plan edits, releasing affected assignments only upon explicit admin confirmation, and shall notify all affected parties via configurable channels |

#### 3.2.6 Sponsors (CAP-SPO)

| Req ID | Requirement |
|--------|------------|
| CAP-SPO-001 | The system shall support sponsor profiles with name, logo, and link |
| CAP-SPO-002 | The system shall support sponsor tier levels (e.g., Gold, Silver, Bronze) |
| CAP-SPO-003 | The system shall support sponsor assignment to events, programs, and time slots |
| CAP-SPO-004 | The system shall support sponsor manager user assignments |

#### 3.2.6a Orga-Team (CAP-OT)

| Req ID | Requirement |
|--------|------------|
| CAP-OT-001 | The system shall support reusable Orga-Teams composed of name, slug, optional description, one Organizer, and zero or more Deputies |
| CAP-OT-002 | The system shall support Sub-Teams nested under an Orga-Team, each with name, optional description, optional emoji, optional accent color, sort order, optional Leader, zero or more Fallback Leaders, and zero or more Members |
| CAP-OT-003 | The system shall allow assignment of at most one Orga-Team to an Event; the same Orga-Team may be assigned to multiple Events |
| CAP-OT-004 | The system shall render a public OrgChart of the Orga-Team for any Event that has one assigned, and shall return 404 when an Event has no Orga-Team assigned |
| CAP-OT-005 | The system shall expose an event-bound RightContentArea to logged-in users on event-scoped pages, surfacing the OrgaTeamCard and other event-bound secondary content |
| CAP-OT-006 | The system shall require all Orga-Team members to be registered LanCore users; person cards in the OrgChart shall link to the corresponding public profile |
| CAP-OT-007 | The system shall confine all Orga-Team management to holders of the `ManageOrgaTeams` permission; membership itself shall confer no other permissions |

#### 3.2.7 News (CAP-NWS)

| Req ID | Requirement |
|--------|------------|
| CAP-NWS-001 | The system shall support rich-text news articles with SEO metadata |
| CAP-NWS-002 | The system shall support article visibility: public, draft, members_only, archived |
| CAP-NWS-003 | The system shall support user comments on articles |
| CAP-NWS-004 | The system shall support comment voting (upvote/downvote) |
| CAP-NWS-005 | The system shall send notifications to users on article publication |

#### 3.2.8 Announcements (CAP-ANN)

| Req ID | Requirement |
|--------|------------|
| CAP-ANN-001 | The system shall support announcements with priority levels: low, normal, high, critical |
| CAP-ANN-002 | The system shall support event-scoped and global announcements |
| CAP-ANN-003 | The system shall track per-user announcement dismissals |
| CAP-ANN-004 | The system shall deliver push notifications for announcements |

#### 3.2.9 Achievements (CAP-ACH)

| Req ID | Requirement |
|--------|------------|
| CAP-ACH-001 | The system shall support achievement definitions with event-based triggers |
| CAP-ACH-002 | The system shall automatically grant achievements when trigger conditions are met |
| CAP-ACH-003 | The system shall track achievement progress and earned timestamps per user |
| CAP-ACH-004 | The system shall notify users when achievements are earned |
| CAP-ACH-005 | The system shall maintain a per-achievement earned-user count incremented on grant and decremented on revocation, and shall expose a derived rarity percentage (count divided by current registered-user total) on every public-facing rendering of an earned achievement |

#### 3.2.10 Notifications (CAP-NTF)

| Req ID | Requirement |
|--------|------------|
| CAP-NTF-001 | The system shall support per-user notification preferences for email and push channels |
| CAP-NTF-002 | The system shall support Web Push notifications via service workers |
| CAP-NTF-003 | The system shall support notification categories: news, events, comments, programs, announcements |
| CAP-NTF-004 | The system shall support notification archiving |

#### 3.2.11 Integrations (CAP-INT)

| Req ID | Requirement |
|--------|------------|
| CAP-INT-001 | The system shall support registration of third-party integration apps |
| CAP-INT-002 | The system shall provide API token authentication with rotation and revocation |
| CAP-INT-003 | The system shall provide OAuth2-like SSO with authorization codes |
| CAP-INT-004 | The system shall support webhook event subscriptions per integration |
| CAP-INT-005 | The system shall support navigation hints for registered integrations |
| CAP-INT-006 | The system shall log integration access events |

#### 3.2.12 Webhooks (CAP-WHK)

| Req ID | Requirement |
|--------|------------|
| CAP-WHK-001 | The system shall support webhook registration with URL and event subscriptions |
| CAP-WHK-002 | The system shall sign webhook payloads with HMAC secrets |
| CAP-WHK-003 | The system shall track webhook delivery status, response codes, and duration |
| CAP-WHK-004 | The system shall support 8 webhook event types: NewsArticlePublished, AnnouncementPublished, EventPublished, UserRegistered, UserRolesUpdated, ProfileUpdated, TicketPurchased, IntegrationAccessed |

#### 3.2.13 Games (CAP-GAM)

| Req ID | Requirement |
|--------|------------|
| CAP-GAM-001 | The system shall maintain a game catalog with descriptions |
| CAP-GAM-002 | The system shall support game modes with team sizes and parameters |

#### 3.2.14 Competition (CAP-COMP)

| Req ID | Requirement |
|--------|------------|
| CAP-COMP-001 | The system shall support competition management with lifecycle states (draft, registration, running, finished, archived) |
| CAP-COMP-002 | The system shall support team registration with captain and member roles |
| CAP-COMP-003 | The system shall integrate with LanBrackets for bracket/match management via REST API |
| CAP-COMP-004 | The system shall support match result submission with screenshot proof |
| CAP-COMP-005 | The system shall receive webhook notifications from LanBrackets for competition status updates |

#### 3.2.15 User Management (CAP-USR)

| Req ID | Requirement |
|--------|------------|
| CAP-USR-001 | The system shall support user registration with email verification |
| CAP-USR-002 | The system shall support role-based access with five static roles: User, Moderator, Admin, Superadmin, SponsorManager |
| CAP-USR-003 | The system shall support two-factor authentication (TOTP) |
| CAP-USR-004 | The system shall support password reset via email |
| CAP-USR-005 | The system shall support user profile management (name, email, phone, address). The fields name, email, phone, street, city, zip code, country, and locale shall not be exposed on any public-facing endpoint or page (carve-out enforced by SEC-021) |
| CAP-USR-009 | The system shall require a complete profile (address and at least one contact method) before allowing purchases |
| CAP-USR-006 | The system shall support ticket discovery settings (user visibility control) |
| CAP-USR-007 | The system shall support sidebar favorites for navigation customization |
| CAP-USR-008 | The system shall support per-user appearance settings (`light` / `dark` / `system`) persisted via cookie + localStorage. **Scope note:** this capability covers the user's *personal* display preference only and does not govern the admin-managed event-scoped Theme Library — see CAP-THM-001..004 |
| CAP-USR-010 | The system shall store a `locale` field on the user record representing the user's preferred display language, selectable from the supported locales: `en`, `de`, `fr`, `es` |
| CAP-USR-011 | The system shall require every user to choose a public-facing `username` (gamer handle), 3–32 characters, drawn from the gamer character set `[A-Za-z0-9_-]` with no leading or trailing punctuation, globally unique case-insensitively, distinct from the `name` (real name) field. The username shall be mandatory at signup. Users registered before this capability shipped shall be intercepted on next login by a one-time onboarding step until they choose a username; until that point the user's `username` claim shall be `null` (transitional state) |
| CAP-USR-012 | The system shall provide a public profile page at `/u/{username}` rendering the user's username, custom emoji, short bio, long-form description, avatar, banner, and earned achievements with rarity. The page shall enforce the user's `profile_visibility` setting: `public` (accessible to anonymous visitors), `logged_in` (default; accessible to authenticated LanCore users), `private` (accessible only to the user themselves). Forbidden states shall return HTTP 404 to avoid leaking user existence |
| CAP-USR-013 | The system shall support profile customization fields: avatar source (`default` / `gravatar` / `custom`; the value `steam` is reserved for a future Steam-linking iteration and shall fall back to `default` until that iteration ships), custom-uploaded avatar image (server-side normalized to 1000×1000 px WebP, max 5 MB ingress), banner image (server-side normalized to 1500×500 px / 3:1 WebP, max 5 MB ingress), short bio (≤ 160 characters), long-form profile description (text), and a single custom emoji |
| CAP-USR-014 | The system shall provide a `profile_visibility` setting in the user's privacy settings page with the three modes defined in CAP-USR-012, defaulting to `logged_in` for both new signups and users migrated from before this capability shipped, and shall provide a profile preview action that renders the public profile as it would appear to an anonymous visitor regardless of the user's current visibility setting |
| CAP-USR-015 | The system shall publish the `username` claim on every `LanCoreUser` DTO returned by SSO exchange, user resolution, and profile webhook payloads. Satellite applications consuming the DTO shall use `username` as the sole public-facing player display field and shall not display `name` (real name) or `email` publicly. When `username` is `null` (transitional state for unmigrated users), satellites shall display a generic placeholder rather than substituting the real name |

#### 3.2.19 Internationalization (CAP-I18N)

| Req ID | Requirement |
|--------|------------|
| CAP-I18N-001 | The system shall support four display locales: English (`en`), German (`de`), French (`fr`), and Spanish (`es`); `en` shall be the default when no locale is set |
| CAP-I18N-002 | LanCore shall be the authoritative store of `User.locale`; the locale shall be included in the `LanCoreUser` DTO returned by the SSO exchange and user-resolution endpoints so that satellite apps can adopt it without an independent locale preference store |
| CAP-I18N-003 | LanCore shall expose a language-switcher UI in the user profile settings; satellite apps shall render a read-only locale indicator linking back to the LanCore profile settings page |
| CAP-I18N-004 | The system shall apply the authenticated user's stored locale on each request; unauthenticated requests shall use the browser `Accept-Language` header, falling back to `en` |
| CAP-I18N-005 | Backend translated strings shall be managed via Laravel `lang/{en,de,fr,es}/` directories; frontend translated strings shall be managed via `resources/js/locales/{en,de,fr,es}.json` and delivered through `vue-i18n` |
| CAP-I18N-006 | Translation strings shall be managed in the self-hosted Weblate instance at `https://weblate.sxcs.de` (project `lan-software`, one component per app); translator commits shall arrive via a dedicated `weblate` branch per app repository and be fast-forwarded onto `main` by the `.github/workflows/weblate-merge.yml` workflow, committing updated locale files |
| CAP-I18N-007 | The `ResolveIntegrationUser` action shall pass `$user->locale` (the stored user locale) — not `app()->getLocale()` (the request locale) — when constructing the `LanCoreUser` DTO returned to SSO consumers |

#### 3.2.16 Game Server Orchestration (CAP-ORC)

| Req ID | Requirement |
|--------|------------|
| CAP-ORC-001 | The system shall provide CRUD operations for game servers, accessible to administrators with the ManageGameServers permission |
| CAP-ORC-002 | The system shall support game server allocation types: Competition, Casual, and Flexible, with server selection prioritising Competition over Flexible over Casual |
| CAP-ORC-003 | The system shall create orchestration jobs from match-ready events and process them via dedicated queue workers with retry logic |
| CAP-ORC-004 | The system shall deploy match configurations via pluggable MatchHandler implementations, with TMT2 as the first concrete handler for CS2/Source 2 games |
| CAP-ORC-005 | The system shall track orchestration job lifecycle states: Pending, SelectingServer, Deploying, Active, Completed, Failed, Cancelled |
| CAP-ORC-006 | The system shall release game servers upon match completion or job failure, preventing server exhaustion |
| CAP-ORC-007 | The system shall prevent duplicate orchestration jobs per competition/match pair via database constraint |
| CAP-ORC-008 | The system shall receive TMT2 webhooks and auto-report match results to LanBrackets for automated bracket progression |
| CAP-ORC-009 | The system shall support admin manual controls: retry failed jobs, cancel pending/failed jobs, force-release in-use servers |
| CAP-ORC-010 | The system shall display server connection details (IP:port, password) to match participants when the orchestration job is Active |

#### 3.2.17 Organization Identity and Branding (CAP-ORG)

| Req ID | Requirement |
|--------|------------|
| CAP-ORG-001 | The system shall maintain an organization identity record comprising: name, logo (uploadable image), address, and legal notice |
| CAP-ORG-002 | The system shall restrict modification of organization identity to administrators |
| CAP-ORG-003 | The system shall make organization identity globally available to all frontend pages via Inertia shared props (the `organization` prop) |
| CAP-ORG-004 | The system shall cache the organization shared prop (cache key `inertia.organization`, TTL 1 hour) and invalidate the cache on any update, logo upload, or logo removal |

#### 3.2.18 Integration Client Library (CAP-ICLIB)

| Req ID | Requirement |
|--------|------------|
| CAP-ICLIB-001 | The system shall provide a shared Composer package (`lan-software/lancore-client`) as the canonical client implementation of the LanCore Integration API, to be consumed by all Lan\* satellite applications (LanBrackets, LanEntrance, LanShout, LanHelp, LanChart, LanBase) in place of per-app HTTP client implementations |
| CAP-ICLIB-002 | The package shall expose a unified exception hierarchy covering: disabled integration, upstream unavailability (connection failure / 5xx), client request errors (4xx with status code), and invalid user payload schema |
| CAP-ICLIB-003 | The package shall provide abstract webhook controllers and typed payload DTOs for every webhook event type defined by LanCore's `WebhookEvent` enum (the eight events enumerated in CAP-WHK-004), such that satellites implement only domain-specific handling |
| CAP-ICLIB-004 | The package shall provide a webhook verification middleware implementing HMAC-SHA256 signature verification using a single environment secret (`LANCORE_WEBHOOK_SECRET`), rejecting any request whose signature or event header does not match |
| CAP-ICLIB-005 | The package shall provide an opt-in `entrance()` sub-client for LanEntrance consumers, offering ticket validation, check-in confirmation, attendee search, entrance statistics, and JWKS fetching with configurable TTL-based caching; the sub-client shall not be loaded by satellites that do not enable it |

#### 3.2.20 Platform Policies and User Consent

| Req ID | Requirement |
|--------|------------|
| CAP-POL-001 | The system shall provide a platform-wide Policy domain in which platform admins create policy types (TOS, Privacy, EULA, …) and policies referencing those types, with policies marked optionally as `is_required_for_registration` |
| CAP-POL-002 | Each policy shall have a linear, immutable version history; publishing a new version renders a PDF snapshot stored on the private storage role and stamps the publisher, locale, content, and effective date |
| CAP-POL-003 | The system shall distinguish editorial publishes (typo / formatting only — silent) from non-editorial publishes (rights-affecting — drives re-acceptance) via a single boolean flag captured at publish time |
| CAP-POL-004 | The system shall record per-user acceptances in a single `policy_acceptances` table, capturing locale, IP, user-agent, and source (`registration`, `re_acceptance_gate`, `settings`, `checkout`, `manual_admin`) |
| CAP-POL-005 | When a non-editorial version is published, the system shall queue one mail per distinct prior acceptor with the new PDF attached and the operator's `public_statement` rendered inline |
| CAP-POL-006 | After a non-editorial publish, the system shall force every active user to re-accept the policy on next login via a `RequirePolicyAcceptance` middleware redirect to `/policies/required`, stashing the user's intended URL |
| CAP-POL-007 | The system shall let logged-in users withdraw consent (GDPR Article 7(3)) for any policy from `/settings/privacy`, recording `withdrawn_at` + reason + IP + user-agent on the existing acceptance row |
| CAP-POL-008 | Withdrawal shall trigger a notification (mail + database channels) to every user holding `ManagePolicies`, excluding the withdrawing user |
| CAP-POL-009 | All Policy CRUD, version publish, acceptance, and withdrawal events shall emit audit log rows via `owen-it/laravel-auditing` and be readable from the Policy admin UI |

#### 3.2.21 GDPR Article 15 Operator Workflow

| Req ID | Requirement |
|--------|------------|
| CAP-GDPR-001 | The system shall provide an artisan command `gdpr:export-user` that produces a single ZIP archive containing every record held about a single subject user, written to `storage/app/gdpr-exports/` |
| CAP-GDPR-002 | The export shall obfuscate every other-user identifier appearing in the subject's records via deterministic per-export pseudonyms (`user_a`, `user_b`, …), with no reverse mapping persisted on disk or in the export |
| CAP-GDPR-003 | The export shall optionally apply AES-256 encryption to every ZIP entry when an operator-supplied password is provided |
| CAP-GDPR-004 | The export shall include a copy of the PDF of every policy version the user has accepted, alongside their acceptance/withdrawal records |

#### 3.2.22 Data Lifecycle (GDPR Article 17 + Retention)

| Req ID | Requirement |
|--------|------------|
| CAP-DL-001 | The system shall provide a self-service "delete my account" flow at `/account/delete` that requires the user's current password and produces a `DeletionRequest` row in the `pending_email_confirm` state |
| CAP-DL-002 | The system shall provide an admin-initiated deletion flow at `/admin/data-lifecycle/deletion-requests` for users holding `RequestUserDeletion`; the resulting request records both the admin and the subject |
| CAP-DL-003 | After the user clicks the email confirmation link, the request shall transition to `pending_grace` with a 30-day window before automatic anonymization; the user may cancel at any time during the grace via `data-lifecycle.account.cancel-via-link` (signed URL) or while logged in |
| CAP-DL-004 | At the end of the grace window (or immediately on admin "Anonymize now"), the system shall scrub every PII column on the `users` row in place (name, email, username, phone, address, profile fields, two-factor secrets, remember tokens) and run every registered `DomainAnonymizer` so that no personal data remains in any domain table not under retention |
| CAP-DL-005 | The system shall maintain admin-editable per-data-class `retention_policies` and shall hold (not anonymize / not purge) data classes whose retention has not yet expired; a nightly scheduler shall purge expired data and ultimately hard-delete the `users` row when no obligations remain |
| CAP-DL-006 | The system shall provide a force-delete path that bypasses retention windows for users holding the dedicated `ForceDeleteUserData` permission, requires a recorded reason, and is fully audited via `owen-it/laravel-auditing`; pinned policies (`can_be_force_deleted = false`) shall still hold |
| CAP-DL-007 | After in-place anonymization, the GDPR Art.15 export command shall remain able to locate the (now anonymized) subject by their original email address via a salted email_hash column on the `users` row, so that post-deletion subject access requests remain serviceable for as long as the soft-deleted users row exists |
| CAP-DL-008 | The system shall not permit hard-deletion of `Event` rows; events shall be soft-deletable only, with `EventPolicy::forceDelete` permanently returning `false` to preserve attendance, accounting, and competition history |

| Security Req ID | Requirement |
|-----------------|------------|
| SEC-DL-001 | The `email_hash` column shall be derived via HMAC-SHA256 keyed by an HKDF-derived secret with a versioned context (`data-lifecycle-email-v1`) so the column cannot be brute-forced from a leaked DB dump alone |
| SEC-DL-002 | All deletion-request and retention-policy state changes shall be auditable via `owen-it/laravel-auditing`; the dedicated `AnonymizationLogEntry` table shall be append-only at the model layer |

> **Scope note**: `CAP-SHP-006` (checkout-condition acknowledgement) remains scoped to shop checkout and is distinct from `CAP-POL-*`. The two flows do not share storage or middleware.

#### 3.2.23 Event Theme Library (CAP-THM)

| Req ID | Requirement |
|--------|------------|
| CAP-THM-001 | The system shall provide a Theme Library admin area (gated by the `ManageThemes` permission) in which authorized admins can create, list, update, and delete named Themes. Each Theme shall comprise a unique `name`, an optional `description`, an optional `light_config` JSON map of CSS-variable overrides applied to `:root`, and an optional `dark_config` JSON map applied to `.dark`. No `kind`, `vendor`, `skin`, or vendor stylesheet is involved. Admins may also designate any Theme as the site-wide default via the same admin area |
| CAP-THM-002 | The system shall allow each Event to be assigned at most one Theme from the Theme Library via a nullable `theme_id` foreign key on the `events` table; a `null` value indicates no event-scoped theme. Multiple events may share the same Theme. Theme assignment changes shall be captured in the existing Event audit trail. Traces upstream to CAP-EVT-008 |
| CAP-THM-003 | When a request resolves to a route under `/events/{event}/...` (admin or public), the system shall apply the active palette by inlining `light_config` overrides under `:root` and `dark_config` overrides under `.dark` server-side (two distinct `<style>` blocks), and expose the same overrides client-side via `<ThemeProvider>`. No `data-theme` attribute shall be set and no vendor stylesheet chunk shall be loaded |
| CAP-THM-004 | The system shall resolve the active Theme using the following priority order: (1) the per-event assigned Theme (`events.theme_id`); (2) the site-wide default Theme (`OrganizationSetting` key `default_theme_id`); (3) no palette override (platform default appearance). The user's personal `dark` class shall never be suppressed regardless of which Theme is active |

### 3.3 System External Interface Requirements

#### 3.3.1 External Interfaces

| Interface | Type | Direction | Protocol |
|-----------|------|-----------|----------|
| Stripe API | Payment processing | Bidirectional | HTTPS/REST |
| PayPal API (Orders v2) | Payment processing | Bidirectional | HTTPS/REST |
| S3/Minio Storage | File storage | Bidirectional | HTTPS/S3 Protocol |
| SMTP Server | Email delivery | Outbound | SMTP/TLS |
| Web Push Service | Push notifications | Outbound | HTTPS (RFC 8030) |
| Integration API | Third-party apps | Bidirectional | HTTPS/REST |
| Webhook Endpoints | Event notification | Outbound | HTTPS/POST |

See [IRS](IRS.md) for detailed interface requirements.

### 3.4 System Internal Interface Requirements

| Interface | Components | Protocol |
|-----------|-----------|----------|
| Application ↔ Database | Laravel ↔ PostgreSQL | TCP/SQL |
| Application ↔ Cache | Laravel ↔ Redis | TCP/RESP |
| Application ↔ Queue | Laravel ↔ Database/Redis | TCP |
| Web Server ↔ Application | FrankenPHP ↔ Laravel Octane | In-process |
| Frontend ↔ Backend | Vue.js ↔ Inertia.js | HTTPS/JSON |

### 3.5 System Internal Data Requirements

- Session data stored in database with configurable lifetime (120 minutes default)
- Cache stored in Redis with tag-based invalidation
- Queue jobs stored in database with batch tracking and failure logging
- Audit trail records maintained for all auditable entities
- File uploads stored in S3-compatible storage with signed URLs

### 3.6 Security and Privacy Requirements

| Req ID | Requirement |
|--------|------------|
| SEC-001 | All user passwords shall be hashed using bcrypt with configurable rounds (default: 12) |
| SEC-002 | The system shall support TOTP-based two-factor authentication with recovery codes |
| SEC-003 | All sessions shall be encrypted and stored server-side |
| SEC-004 | API tokens shall be hashed before storage |
| SEC-005 | Webhook payloads shall be signed with HMAC-SHA256 |
| SEC-006 | CSRF protection shall be enforced on all state-changing requests |
| SEC-007 | Authorization shall be enforced via Laravel Policies on all domain entities |
| SEC-008 | The system shall support role-based access control with 29 authorization policies |
| SEC-011 | Authorization shall use a static enum-based permission system mapping permissions to roles, with per-domain granularity |
| SEC-012 | Superadmin role shall bypass all authorization checks via a centralized Gate::before() callback |
| SEC-013 | User permissions shall be shared with the frontend via Inertia.js shared props for UI-level access control |
| SEC-009 | Integration API routes shall use stateless Bearer token authentication |
| SEC-010 | The system shall track unique request IDs for audit and debugging |
| SEC-014 | Ticket tokens shall be signed with Ed25519 (asymmetric); the private key shall never be stored in the database or transmitted to integration consumers |
| SEC-015 | The system shall store ticket tokens in the QR code payload only; the plaintext nonce and full token string shall never be persisted in the database or any log |
| SEC-016 | The system shall store a per-ticket nonce hash computed as HMAC-SHA256(nonce, pepper) in column `validation_nonce_hash`; the pepper shall be supplied via environment variable and shall not be stored in the database |
| SEC-017 | The database-stored nonce hash (`validation_nonce_hash`) shall be UNIQUE-indexed; no two active tickets shall share the same hash |
| SEC-018 | Ticket token validity shall be bounded by a configurable TTL ending at event end plus grace period; the system shall return `expired` for tokens past their expiry, even if the nonce hash is found |
| SEC-019 | The system shall support rotating Ed25519 signing keys via `php artisan tickets:keys:rotate`; each key pair shall carry a unique `kid` (max 16 characters); retired keys shall remain in the public JWKS for the duration of the maximum token TTL |
| SEC-020 | The JWKS endpoint (`GET /api/entrance/signing-keys`) shall require Bearer token authentication using the existing integration middleware; it shall return all public keys in active and retired-but-unexpired states |
| SEC-021 | Profile visibility shall be enforced server-side on the public profile route and any related rendering endpoint. The fields `name`, `email`, `phone`, `street`, `city`, `zip_code`, `country`, and `locale` shall not appear in the response body, headers, embedded JSON, or HTML fragments of any public-facing endpoint regardless of the user's `profile_visibility` setting. Visibility-mode failures (anonymous viewer requesting a `logged_in` profile, non-owner requesting a `private` profile) shall return HTTP 404 — never HTTP 403 — to avoid leaking user existence |
| SEC-022 | User-uploaded avatar and banner images shall be normalized server-side to fixed dimensions (1000×1000 for avatars, 1500×500 for banners) and re-encoded to WebP before storage. Ingress size shall be capped at 5 MB; mime type shall be restricted to `image/jpeg`, `image/png`, and `image/webp`. Stored files shall be addressable via cacheable public URLs (avatars and banners are by definition public artifacts when viewed via the public profile); replacing an avatar or banner shall delete the previous file. Validation shall reject EXIF-bomb and decompression-bomb inputs |

### 3.7 System Environment Requirements

#### 3.7.1 Hardware Requirements

| Component | Minimum | Recommended |
|-----------|---------|-------------|
| CPU | 2 cores | 4+ cores |
| RAM | 2 GB | 4+ GB |
| Storage | 10 GB | 50+ GB (dependent on file uploads) |
| Network | 100 Mbps | 1 Gbps |

#### 3.7.2 Software Requirements

| Component | Requirement |
|-----------|------------|
| Container Runtime | Docker Engine 20+ |
| PHP | 8.3+ |
| Database | PostgreSQL 15+ |
| Cache/Queue | Redis 7+ |
| Object Storage | S3-compatible (AWS S3, Minio, Garage) |
| HTTP Server | FrankenPHP (via Laravel Octane) |

#### 3.7.3 Deployment and Container Requirements (ENV-DEP)

| Req ID | Requirement | Verification |
|--------|------------|--------------|
| ENV-DEP-010 | Container images for LanCore and all satellite apps (LanBrackets, LanShout, LanHelp, LanEntrance) shall be built from multi-stage Dockerfiles with all base images pinned by immutable `@sha256:` digest to guarantee reproducible builds | Inspection of Dockerfiles |
| ENV-DEP-011 | All supervised application processes in production container images (Octane/FrankenPHP, queue workers, scheduler) shall run as the unprivileged `www-data` user via supervisord `user=` directives. supervisord itself runs as PID 1 under root so it can manage child-process stdio, but has no network exposure | Inspection of supervisor configs + `docker exec <container> ps -o user=,comm=` |
| ENV-DEP-012 | Runtime secrets (`APP_KEY`, database credentials, `TICKET_TOKEN_PEPPER`, Stripe keys, S3 credentials, `LANCORE_TOKEN` and `LANCORE_WEBHOOK_SECRET` on satellite apps consuming the lancore-client package) shall be injected via environment variables at container start and shall never be baked into any image layer or committed to a Dockerfile | Image layer inspection, source review |
| ENV-DEP-013 | Each production image shall expose runtime role selection via a `ROLE` environment variable supporting at minimum the values `web`, `worker`, and `all` (where applicable) | Inspection of entrypoint and supervisor configs |
| ENV-DEP-014 | Each production image shall honour a `SKIP_MIGRATE` environment variable so that in multi-container deployments exactly one container may be designated as the schema migrator | Inspection of entrypoint script |
| ENV-DEP-015 | Each production image shall ship a Docker `HEALTHCHECK` against the Laravel `/up` endpoint with a start period sufficient to cover cold boot and migration on the migrator container | `docker inspect` of the built image |
| ENV-DEP-016 | Octane-based images (LanCore, LanBrackets) shall allow operators to tune worker count and recycle threshold at runtime via `OCTANE_WORKERS` and `OCTANE_MAX_REQUESTS` without rebuilding the image | Inspection of supervisor web config |
| ENV-DEP-017 | When deployed to Kubernetes, all pods in the release namespace shall at minimum satisfy the Pod Security Admission `baseline` profile and shall additionally set `allowPrivilegeEscalation: false`, `seccompProfile.type: RuntimeDefault`, `capabilities.drop: [ALL]` (re-adding only `CHOWN`, `FOWNER`, `DAC_OVERRIDE`, `NET_BIND_SERVICE`, `SETUID`, `SETGID` — the narrow set required by supervisord-as-PID-1, the entrypoint `chown`, and su-exec drop to www-data), no host-path volumes, no privileged containers. The `restricted` profile is a Phase 2 target contingent on LanBase supporting a non-root supervisor | `kubectl label ns <ns> pod-security.kubernetes.io/enforce=baseline`; `kube-score` scan on rendered manifests |
| ENV-DEP-018 | Runtime secrets in Kubernetes deployments shall be sourced from Kubernetes `Secret` objects (injected via `envFrom.secretRef` or mounted volume) or from an External Secrets Operator `ExternalSecret`; secrets shall never appear in `ConfigMap` objects, container images, or plaintext `values.yaml` committed to a repository. Integration-app bearer tokens and webhook secrets (consumed by `config/integrations.php`; see IRS IF-INTCFG and SSDD §5.4.5) are valid candidates for `ExternalSecret` sourcing; the umbrella Helm chart's auto-generated seed Secret yields to operator-supplied values when present | Inspection of rendered manifests; `conftest` policy scan |
| ENV-DEP-019 | Application pods shall shut down gracefully on SIGTERM: Octane and queue workers shall drain in-flight requests/jobs before exit, `terminationGracePeriodSeconds` shall be ≥ 30s, and a `preStop` hook with `sleep 5` shall give load balancers time to deregister the endpoint before the container stops accepting connections | `kubectl delete pod` observation; application log inspection |
| ENV-DEP-020 | The Ed25519 ticket-signing keyring shall be provisioned as a Kubernetes `Secret` and mounted read-only at `/app/storage/keys/ticket_signing/` via a projected volume, one file per `kid`, with mode `0400`. Key rotation shall be executed by a dedicated Kubernetes `Job` (not from the application container) and shall trigger a rolling restart of the LanCore web Deployment via a pod-template annotation bump | Inspection of rendered Secret and Deployment volumeMounts; verification that no application pod has write access to the keyring directory |

### 3.8 Design and Implementation Constraints

| Constraint | Description |
|-----------|------------|
| CON-001 | Must be implemented using Laravel 13 framework |
| CON-002 | Frontend must use Vue.js 3 with Inertia.js v2 |
| CON-003 | Must run within Docker containers via Laravel Sail |
| CON-004 | Must follow domain-driven design with domains under `app/Domain/` |
| CON-005 | Must use Actions pattern for business logic (not in controllers) |
| CON-006 | Must use Eloquent ORM; raw queries only for complex operations |
| CON-007 | Must use Form Request classes for validation |
| CON-008 | Must support Laravel Octane for high-performance operation |

### 3.9 Software Quality Factors

| Factor | Requirement |
|--------|------------|
| Reliability | The system shall recover gracefully from failed queue jobs with retry mechanisms |
| Maintainability | Domain-driven architecture shall isolate business logic for independent evolution |
| Testability | All domains shall be testable via automated Pest tests |
| Portability | The system shall run on any Docker-capable host (Linux, macOS, Windows via WSL) |
| Scalability | The system shall support horizontal scaling via Octane worker processes |
| Auditability | The system shall maintain change audit trails for all critical entities |

---

## 4. Qualification Provisions

| Req Category | Verification Method |
|-------------|-------------------|
| Capability Requirements | Feature tests (Pest), E2E tests (Playwright) |
| Interface Requirements | Integration tests, manual verification |
| Security Requirements | Security audit, automated tests |
| Performance Requirements | Load testing, monitoring via Pulse/Prometheus |
| Environment Requirements | Docker deployment validation |

---

## 5. Requirements Traceability

Requirements in this document trace to:

- **Upstream:** Operational scenarios in [OCD](OCD.md) — OCD §5.4 Localized Display mode, OCD §5.1.2 locale selection, OCD §7.1 glossary
- **Downstream:** Detailed CSCI requirements in [SRS](SRS.md)
- **Downstream:** Interface requirements in [IRS](IRS.md)
- **Downstream:** Test cases in [STD](STD.md)

| SSS Capability | Traces from OCD | Traces to SRS |
|----------------|----------------|---------------|
| CAP-USR-010 | OCD §5.1.2 (locale selection in profile) | USR-F-021 |
| CAP-USR-011 | OCD §5.1.2 (username), OCD §7.1 glossary "Username" | USR-F-022 |
| CAP-USR-012 | OCD §5.1.2 (public profile), OCD §7.1 glossary "Public Profile" | USR-F-023 |
| CAP-USR-013 | OCD §5.1.2 (profile customization), OCD §7.1 glossary "Avatar Source", "Profile Banner" | USR-F-024 |
| CAP-USR-014 | OCD §5.1.2 (profile visibility, preview) | USR-F-025, USR-F-026 |
| CAP-USR-015 | OCD §5.2.6a (public-facing identity policy) | ICLIB-F-002 (amended), ICLIB-F-010 |
| CAP-ACH-005 | OCD §5.2.7 (achievement rarity step), OCD §7.1 glossary "Achievement Rarity" | ACH-F-008 |
| SEC-021 | OCD §5.1.2 (visibility carve-out), OCD §5.2.6a (public-facing identity policy) | USR-F-023, USR-F-012 (privacy carve-out amendment) |
| SEC-022 | OCD §5.1.2 (avatar/banner customization) | USR-F-024 |
| CAP-I18N-001..007 | OCD §5.4 (Localized Display mode), OCD §5.1.2 | I18N-F-001..007 |
| CAP-OT-001..007 | OCD §5.2.8 (Showing the Orga-Team to Attendees) | OT-F-001..010 |
| CAP-EVT-008 | OCD §5.2.1 step 7 (theme selection during event creation), OCD §5.2.9 | EVT-F-008 (theme assignment endpoint), THM-F-004 |
| CAP-THM-001 | OCD §5.1.4 (admin theme management bullet), OCD §5.2.9 bullet, OCD §7.1 glossary "Theme", "Theme Library" | THM-F-001, THM-F-002, THM-F-006 |
| CAP-THM-002 | OCD §5.2.1 step 7, OCD §5.2.9 steps 1–2 | THM-F-004, EVT-F-008 |
| CAP-THM-003 | OCD §5.2.9 steps 2–4 | THM-F-005 |
| CAP-THM-004 | OCD §5.2.9 steps 2, 5, bullet | THM-F-005, THM-F-006 |

---

## 6. Notes

### 6.1 Acronyms

| Term | Definition |
|------|-----------|
| CAP | Capability |
| CON | Constraint |
| SEC | Security |
| HMAC | Hash-based Message Authentication Code |
| RESP | Redis Serialization Protocol |
| CSRF | Cross-Site Request Forgery |
| TOTP | Time-based One-Time Password |
| ORC | Orchestration |
| ORG | Organisation |
| TTL | Time to Live |
| JWKS | JSON Web Key Set |
| kid | Key Identifier |
| LCT1 | LanCore Token version 1 (signed ticket token scheme) |
| ENV-DEP | Environment / Deployment requirement category |
| ICLIB | Integration Client Library (shared `lan-software/lancore-client` package) |
| I18N | Internationalization |
| L10N | Localization |
| BCP 47 | IETF standard for language tags (e.g., `en`, `de`, `fr`, `es`) |

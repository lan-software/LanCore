# MIL-STD-498 Documentation — LanCore

## Overview

This directory contains the software development and documentation artifacts for the **LanCore** project, structured in accordance with **MIL-STD-498** (Military Standard for Software Development and Documentation, 8 November 1994).

MIL-STD-498 defines 22 Data Item Descriptions (DIDs) that standardize the recording of software development and support processes. Each document below follows its respective DID section structure.

**Project:** LanCore — LAN Party & BYOD Event Management Platform
**Status:** Proof of Concept (pre-v1.0)
**Date:** 2026-04-02

### Author

| Role | Name |
|------|------|
| Project Lead | Markus Kohn |

---

## Document Index

### Plans

| DID | Document | Status |
|-----|----------|--------|
| [SDP](SDP.md) | Software Development Plan | Populated |
| [SIP](SIP.md) | Software Installation Plan | Scaffolded |
| [STrP](STrP.md) | Software Transition Plan | Scaffolded |

### Concept & Requirements

| DID | Document | Status |
|-----|----------|--------|
| [OCD](OCD.md) | Operational Concept Description | Populated |
| [SSS](SSS.md) | System/Subsystem Specification | Populated |
| [SRS](SRS.md) | Software Requirements Specification | Populated |
| [IRS](IRS.md) | Interface Requirements Specification | Populated |

### Design

| DID | Document | Status |
|-----|----------|--------|
| [SSDD](SSDD.md) | System/Subsystem Design Description | Scaffolded |
| [SDD](SDD.md) | Software Design Description | Populated |
| [DBDD](DBDD.md) | Database Design Description | Populated |
| [IDD](IDD.md) | Interface Design Description | Populated |

### Qualification Testing

| DID | Document | Status |
|-----|----------|--------|
| [STP](STP.md) | Software Test Plan | Populated |
| [STD](STD.md) | Software Test Description | Populated |
| [STR](STR.md) | Software Test Report | Populated |

### User & Operator Manuals

| DID | Document | Status |
|-----|----------|--------|
| [SUM](SUM.md) | Software User Manual | Populated |
| [SIOM](SIOM.md) | Software Input/Output Manual | Scaffolded |
| [SCOM](SCOM.md) | Software Center Operator Manual | Scaffolded |
| [COM](COM.md) | Computer Operation Manual | Scaffolded |

### Support Manuals

| DID | Document | Status |
|-----|----------|--------|
| [CPM](CPM.md) | Computer Programming Manual | Scaffolded |
| [FSM](FSM.md) | Firmware Support Manual | Scaffolded |

### Software Product Definition

| DID | Document | Status |
|-----|----------|--------|
| [SPS](SPS.md) | Software Product Specification | Populated |
| [SVD](SVD.md) | Software Version Description | Populated |

### Traceability

| DID | Document | Status |
|-----|----------|--------|
| [RTM](RTM.md) | Requirements Traceability Matrix | Populated |

---

## Recent Additions

- **2026-05-31** — LAN Party Publishing Standard (LPPS) capability implemented. New CSCI-PUB domain at `app/Domain/Publishing/` with `BuildLanPartyDocument` action, `LanPartyPublishingController`, and three JSON API resources (`LppsEventResource`, `LppsVenueResource`, `LppsTicketResource`). Exposes `GET /.well-known/lan-party.json` (route `lpps.document`) for unauthenticated machine-readable event syndication. Schema extensions: `addresses` gains `latitude`, `longitude`, `country_code` (migration `2026_05_31_093000`); `events` gains `attendance_mode`, `syndication_status`, `previous_start_date`, `has_showers`, five facility policy bitsets, three network speed fields (migration `2026_05_31_093100`); `organization_settings` gains four LPPS identity keys. Cache group `lpps` invalidated via `HasModelCache` on Event/Venue/Address/TicketType and a `booted()` flush on OrganizationSetting. Admin UI extended on venue Create/Edit (geo fields), event Create/Edit (LPPS syndication panel), and Organization settings (LPPS identity section). Discoverability via `<link rel="alternate">` in `app.blade.php` and a feed link in the public event page. 16 unit tests + 9 feature tests across 3 test files. New requirement IDs: `CAP-PUB-001..006` (SSS), `PUB-F-001..008`, `EVT-F-013`, `ORG-F-006`, `VEN-F-001` (extended) (SRS). Touches OCD §5.1.1, §5.1.4, §5.2.14 (new), §7.1; SSS §3.2.26, §5, §6.1; SSDD §5.15, §6; STD §4.36, §5; SRS §3.2.15 (VEN), §3.2.1 (EVT), §3.2.18 (ORG), §3.2.FF (new), §5, §6.1; SDD §5.14, §6; DBDD §4.2.1, §4.2.3, §4.21; IDD §3.21, §4; RTM §31.
- **2026-05-12** — LanCore↔LanBrackets integration repair. New requirement IDs `COMP-F-018..021` formalize: per-team upsert via `POST /api/v1/teams` with `external_reference_id` persisted into `CompetitionTeam.lanbrackets_id` before the bulk participant call (COMP-F-018); inbound `stage.completed` webhook dispatching `GenerateLanBracketsStages` for the next pending stage (COMP-F-019); `UserMatchController` resolving participant team identity from the enriched `CompetitionMatchResource` payload (COMP-F-020); declarative `apps.lanbrackets.subscribed_webhooks` manifest surfaced by `integrations:sync` (COMP-F-021). Touches SRS §3.2.14, IDD §3.8.1/§3.8.2/§3.8.3, RTM §15. New `LanBracketsClient::upsertTeam()`, refactored `SyncTeamsToLanBrackets`, new `stage.completed` branch in `HandleLanBracketsWebhook`, three new Pest feature tests.
- **2026-05-12** — Chat CSCI (`CSCI-CHT`) fully landed. Domain at `app/Domain/Chat/` with 4 auditable models (ChatRoom/ChatRoomMembership/ChatMessage/ChatModerationAction), 3 enums, `RoomPolicy` contract + `PolicyResolver`, `ChatService` (idempotent room creation, write-lock, archive), `PostMessage` action with rate limiting + duplicate suppression + mention parsing, `MessagePosted` broadcast (`ShouldBroadcastNow` on `private-chat.room.{id}`), moderation actions (Mute/Unmute/DeleteMessage/CloseRoom — soft delete preserves body for GDPR export), `ChatMentionNotification` (queued via `NotifyMentionedUsers`), `ChatDataSource` GDPR export, `chat:prune-archived` artisan command, HTTP layer (`ChatRoomController`/`ChatMessageController`/`ChatModerationController`/`ChatMentionSearchController`), Vue UI (`components/chat/{ChatRoom,MessageList,MessageItem,MessageComposer,MemberList}.vue`, page `pages/chat/Room.vue`, Echo subscription, mobile Sheet sidebar). Competition consumers: `CompetitionRoomPolicy` + `MatchRoomPolicy`, lifecycle observers (publish → ensureRoom, MatchReadyForOrchestration → ensure match room + auto-join, MatchFinalized → write-lock, archive cascade). New requirement IDs `CHT-F-001..032`. Touches SRS §3.2.EE, SDD §5.3e, IDD §3.14, DBDD §4.20, RTM §30. Also: Competition gains `COMP-F-016`/`COMP-F-017` for the RegistrationClosed → `Bus::chain([SyncTeamsToLanBrackets, GenerateLanBracketsStages])` path that triggers LanBrackets match generation, with `competitions:generate-matches` recovery command.
- **2026-05-12** — Presence CSCI (`CSCI-PRS`) + Competition `MatchFinalized` event + Reverb broadcast transport (`PLATFORM-CHT-001`). Presence: shared `active`/`idle`/`offline` signal derived from a per-request Redis heartbeat. New requirement IDs `PRS-F-001..012`. Touches SRS §3.2.DD, SDD §4.2.1 + §5.1 + §5.3d, IDD §3.5.2, DBDD §4.19 (no-schema), RTM §28. First consumers: admin Users index and public profile (with existence-leak protection). Competition gains `MatchFinalized` event with `MatchFinalizationSource` enum (`SubmittedByParticipants` / `ForcedByAdmin`), idempotent via cache marker — new IDs `COM-F-MATCH-FINAL-001..003`, SRS §3.2.14 extended, SDD §5.3a extended, RTM Competition section extended. Reverb installed (`laravel/reverb` v1) as the realtime transport for the forthcoming Chat CSCI: new `lancore-reverb` container in `compose.yaml`, `config/reverb.php`, `config/broadcasting.php`, `routes/channels.php`, Echo wired via `@laravel/echo-vue` in `resources/js/app.ts` with `VITE_REVERB_*` env. Deployment notes at `docs/deployment/reverb.md`.
- **2026-04-30** — Data Lifecycle / GDPR Article 17 feature domain. New requirement IDs `CAP-DL-001..008`, `SEC-DL-001..002`, `DL-F-001..018`. Touches OCD §5.7, SSS §3.2.Z, SRS §3.2.Z, IRS §5.X, SSDD §5.11, SDD §5.10, IDD §3.20, DBDD §5.X, STD §4.31, RTM §23. Article 17 scope statement in OCD §5.6 was lifted (formerly "out of scope"). Implementation domain: `app/Domain/DataLifecycle/`.

## Status Legend

- **Populated** — Document sections filled with current LanCore project data
- **Scaffolded** — MIL-STD-498 section structure in place with TBD markers; to be completed as the project matures

## References

- MIL-STD-498, Military Standard — Software Development and Documentation, 8 November 1994
- [VCTLabs MIL-STD-498 Templates](https://github.com/VCTLabs/MIL-STD-498)

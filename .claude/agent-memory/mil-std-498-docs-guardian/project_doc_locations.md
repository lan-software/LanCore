---
name: Doc Locations
description: File paths and naming conventions for all MIL-STD-498 documents in LanCore
type: project
---

All MIL-STD-498 documents are in: `/home/mawiguko/git/github/lan-software/LanCore/docs/mil-std-498/`

Full suite present (all version 0.1.0, dated 2026-04-02, status Draft):
- OCD.md — LanCore-OCD-001
- SSS.md — LanCore-SSS-001
- SSDD.md — LanCore-SSDD-001
- STD.md — LanCore-STD-001
- SRS.md — LanCore-SRS-001
- SDD.md — LanCore-SDD-001
- RTM.md — Requirements Traceability Matrix
- DBDD.md, IDD.md, IRS.md, SDP.md, STP.md, STR.md, STrP.md, SUM.md, SVD.md, FSM.md, SCOM.md, SIOM.md, SPS.md, COM.md, CPM.md

Traceability conventions:
- SSS uses CAP-XXX-NNN (e.g., CAP-EVT-001)
- SRS uses CSCI-XXX domain codes: EVT-F-*, TKT-F-*, SHP-F-*, COMP-F-*, ORC-F-*, DL-F-*, etc.
- SSS CAP-DL-001..008 anchor the Data Lifecycle / GDPR Art.17 domain; SEC-DL-001..002 cover security reqs
- SRS DL-F-001..018 trace to CAP-DL-* and SEC-DL-*; GDPR-F-009 cross-cuts into Policy domain
- IRS §5.X IF-DL-001 (EmailHasher), IF-DL-002 (DomainAnonymizer contract), IF-DL-003 (RetentionEvaluator contract)
- IDD §3.20 lists four artisan commands: lifecycle:user:delete, lifecycle:user:force-delete, lifecycle:purge, lifecycle:user:anonymize
- RTM §23 "Data Lifecycle / Right to Erasure (CSCI-DL)" — all 18 DL-F-* rows present
- SDD §6 maps SRS → design component (app/Domain/XXX/)
- STD §5 maps SRS → test files; STD TC-DL-001..009 cover DataLifecycle test suite
- RTM provides full coverage matrix per domain

Domain modules (planned 18 after Newsletter feature ships):
Achievements, Announcement, Api, Competition, Event, Games, Integration, Newsletter, News,
Notification, Orchestration, Policy, Program, Seating, Shop, Sponsoring, Ticketing, Venue, Webhook

**Why:** SRS says "15 domain modules", SDD §3.1 says "14 modules" and §4.2 says "14" — both are stale; actual count is 17 currently (including Api and Orchestration added later); Newsletter adds the 18th.

Newsletter/Listmonk integration (added 2026-05-04):
- SSS: CAP-CTD-001..002 (§3.2.24), CAP-NLT-001..004 (§3.2.25), CAP-ORC-011 extension
- SRS: NLT-F-001..007 (§3.2.BB), CTD-F-001..002 (§3.2.CC), EXT-F-001..005 (§3.2.17 Api extension)
- SSDD: §5.13 Newsletter/Listmonk Architecture
- SDD: §5.12 Newsletter Implementation
- STD: §4.33 TC-NLT-001..006, §4.34 TC-CTD-001..002, §4.35 TC-EXT-001..005
- RTM: §25 NLT-F-*, §26 CTD-F-*, §27 EXT-F-*

Section number choices:
- SRS placeholder convention: after §3.2.AA (Theme) → §3.2.BB (Newsletter), §3.2.CC (Countdown), §3.2.DD (Presence), §3.2.EE (Chat)
- SSS §3.2 numbering: Theme is §3.2.23; Countdown is §3.2.24; Newsletter is §3.2.25
- SSDD §5.13 is Newsletter; §5.14 is WebPush Channel Bridge; next free = §5.15
- SDD §5.13 is Ticket-Sale Notification Dispatcher; next free = §5.14
- SDD §6 traceability last entries: CTD-F-001..002, NLT-F-001..007, EXT-F-001..005 at bottom of table
- RTM last section: §30 (Chat Domain, CHT-F-001..032); next free = §31
- STD last test section: §4.35 (External API connectivity); next free = §4.36
- IDD last section: §3.20 (Data Lifecycle Artisan); next free = §3.21
- DBDD last section: §4.20 (Chat Domain); next free = §4.21
- SSS §6.1 acronym table last entries: NLT and CTD

LPPS / Publishing capability — IMPLEMENTED 2026-05-31:
- SSS: CAP-PUB-001..006 in §3.2.26; PUB and LPPS added to §6.1 acronyms
- SRS: §3.2.FF CSCI-PUB with PUB-F-001..008; EVT-F-013 (events LPPS cols); ORG-F-006 (org settings LPPS keys); VEN-F-001 extended (geo fields); PUB and LPPS added to §6.1 acronyms
- SSDD: §5.15 LPPS Publishing Architecture; new §6 traceability row CAP-PUB-001..006
- SDD: §5.14 LPPS Publishing Implementation; new §6 traceability row PUB-F-001..008
- STD: §4.36 (TC-PUB-001..009 feature + TC-PUB-010..025 unit, 16 total unit tests); §5 traceability row added
- RTM: §31 (10 rows: PUB-F-001..008, EVT-F-013, ORG-F-006, VEN-F-001 ext.); statistics updated to 179 total SRS reqs
- IDD: §3.21 LPPS Well-Known Endpoint with full JSON schema; §4 traceability row added
- DBDD: §4.2.1 events extended (LPPS cols); §4.2.3 addresses extended (geo cols); §4.21 LPPS Publishing Domain (org settings keys note)
- README: 2026-05-31 entry prepended to Recent Additions

Next free section numbers (post-LPPS):
- SSS §3.2.27 next
- SRS §3.2.GG next
- SSDD §5.16 next
- SDD §5.15 next
- STD §4.37 next
- RTM §32 next
- IDD §3.22 next
- DBDD §4.22 next

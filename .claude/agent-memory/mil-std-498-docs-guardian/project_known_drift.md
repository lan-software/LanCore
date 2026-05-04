---
name: Known Drift Patterns
description: Recurring stale references and missing coverage found across audits
type: project
---

Audit date: 2026-05-04 (Theme Library scope reduction). Last major batch: 2026-04-07 (My Pages event selector, org logo, team leave flow refactor, mail letter animation, mobile UI fixes).

## Pre-existing structural drift (not from this batch)

1. **Domain count mismatches** (pre-existing): SRS §1.2 says "17 domain modules" (updated). SDD §3.1 tree shows 14 leaf entries, §4.2 heading says "Domain Modules (17)" — count is correct in §4.2 but directory tree comment is stale.

2. **Permission count mismatches** (pre-existing): SRS USR-F-014 now updated to "27 cases total across 17 enums". RTM should be cross-checked.

3. **SSS has CAP-ORC-* section** (§3.2.16): added. SRS §5 traceability table includes CAP-ORC-* → ORC-F-*. SDD §6 missing ORC-F-* row — SDD traceability table does not list ORC-F-* entries.

4. **SDD permission matrix** (§3.3.4): updated to include ManageCompetitions, ManageGameServers, ViewOrchestration. Appears current.

5. **SDD traceability table missing ORC-F-*** (pre-existing): SDD §6 does not map ORC-F-* requirements.

6. **STD missing ORC test section** (pre-existing): STD has sections 4.1–4.22 but no section covering ORC-F-* tests. STD §5 traceability does not reference ORC-F-* or API-F-*.

7. **RTM does not enumerate ORC domain rows** (pre-existing): RTM contains rows for TKT, SHP, SET, etc., but no ORC or API domain rows are visible in the first 100 lines.

8. **SSS Policy count** (pre-existing): SSS SEC-008 now says "29 authorization policies" — consistent with SDD §3.3 count.

## New drift from 2026-04-07 feature batch

### My Pages event selector (EVT/USR cross-cutting)
- SRS EVT-F-011 documented. STD §4.17 documented. RTM not verified for EVT-F-011 row.
- SDD §5.3b design notes documented.

### Organization logo
- SRS ORG-F-001..005 documented. SSS CAP-ORG-001..004 documented. SDD note unclear (not in §5 detailed design, only AppLogo.vue listed in §5.3.2 component library).

### Team leave flow
- SRS COMP-F-007 updated with bool return + redirect. STD §4.16 documented. RTM not verified.

### Mail letter animation
- SDD §5.3.2 lists MailLetterAnimation component.
- COMP join request sub-flow (COMP-F-013..015) now documented in SRS.

## Theme Library scope reduction (2026-05-04) — RESOLVED

Vendor takeover layer deleted from codebase. All six docs updated:
- OCD §5.1.4, §5.2.1 step 7, §5.2.9, §7.1 "Theme" glossary entry
- SSS CAP-THM-001..004 rewritten; §5 traceability updated (THM-F-006 added)
- SSDD §5.12 rewritten: vendor stylesheet/enum/Reka clash subsections removed; PaletteVariables + site-wide default subsections added
- SRS §3.2.AA THM-F-001..005 rewritten, THM-F-006 added, §5 traceability updated
- STD §4.32 TC-THM-001..009 rewritten; TC-THM-006 (new site-wide default test), TC-THM-009 architecture invariant updated
- SDD §5.1 middleware table row 4 updated; §5.11 fully rewritten; §11 traceability line updated
- RTM §24 EVT-F-008 and THM-F-001..005 rows updated; THM-F-006 row added

Key traceability pattern: THM-F-006 traces to CAP-THM-001 + CAP-THM-004 → STD §4.32.5 TC-THM-008.
PaletteVariables is now the canonical key source; ThemeConfigKeysRule is the backend consumer.

## User Profile Enhancement — pending documentation (as of 2026-04-26)

Feature requested: custom username, public profile page, profile customization (avatar/banner/bio/emoji), achievements display with global rarity, privacy settings, OIDC/SSO `username` claim propagation to satellites.

**Gaps requiring doc work before code:**

### OCD
- OCD §5.1.2 "Registered User" has no mention of public-facing username, public profile page, or profile customization (avatar/banner/bio).
- OCD §5.2.7 "Achievement Tracking" does not mention global rarity or public profile display.
- OCD §5.2.6 "Third-Party Integration" / §5.2.6a: SSO DTO (LanCoreUser) mentions `username` field in ICLIB-F-002 but OCD does not state that satellites MUST consume `username` for public-facing display rather than real name.

### SSS
- CAP-USR-005 ("profile management (name, email, phone, address)") does not mention username, public profile, or profile customization.
- No CAP-USR-* requirement for: public profile page, privacy visibility modes, username uniqueness/charset constraints, avatar upload/normalization, banner upload, profile description/bio, achievement rarity display.
- CAP-ACH-* (§3.2.9) has no requirement for global rarity computation or display on public profiles.
- SSS §3.6 Privacy Requirements has no entry for profile visibility controls.
- ICLIB-F-002 lists `username: string` in LanCoreUser DTO but SSS does not have a capability requirement forcing satellites to use `username` for public-facing display (currently only CAP-USR-005 scope).

### SRS
- USR-F-012 ("store user address fields") needs an explicit MUST NOT for address/phone/real-name appearing on public profile page.
- No USR-F-* for: public profile at /u/{username}, privacy toggle, profile customization (avatar source enum, custom upload, banner, description, bio, emoji), username uniqueness + gamer charset + 3-32 char constraint.
- ICLIB-F-002: `username: string` is listed but there is no SRS requirement stating satellites MUST display `username` not `name` for public actions.
- No ACH-F-* for global rarity computation (e.g., percentage of users who earned an achievement) or public display.

### SSDD
- SSDD §5.7 i18n architecture exists; no parallel section for profile/identity architecture at system level.
- No system-level design for: S3 avatar upload pipeline (normalization to 1000×1000), banner storage, future Steam-linked avatar cache hooks.
- LanCoreUser DTO propagation: SSDD §5.7.5 describes locale propagation; similar section needed for `username` propagation to satellites.

### STD
- No test section for: public profile page rendering, privacy mode enforcement, username uniqueness/charset validation, avatar upload/normalization, achievement rarity display.

### SRS / SDD OIDC claim gap
- IRS §3.7 (IF-SSO) and ICLIB-F-002 establish that the LanCoreUser DTO carries `username`, but no IRS requirement mandates that the satellite MUST use `username` (not `name`) for leaderboards, tournament registration displays, etc.

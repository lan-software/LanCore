---
name: Known Drift Patterns
description: Recurring stale references and missing coverage found after the 2026-04-07 feature batch audit
type: project
---

Audit date: 2026-04-07. Feature batch: My Pages event selector, org logo, team leave flow refactor, mail letter animation, mobile UI fixes.

## Pre-existing structural drift (not from this batch)

1. **Domain count mismatches** (pre-existing): SRS §1.2 says "15 domain modules"; SDD §3.1 tree comment says "14 modules"; SDD §4.2 heading says "Domain Modules (14)". Actual count = 17 directories (Api and Orchestration added since initial draft).

2. **Permission count mismatches** (pre-existing): SRS USR-F-014 says "24 cases across 14 enums"; RTM USR-F-014 repeats "24 cases across 14 enums". Actual = 26 cases across 16 Permission.php files (+ AuditPermission.php = 27 total). Orchestration domain added ManageGameServers + ViewOrchestration; Competition added ManageCompetitions — these 3 cases are missing from the "24" claim.

3. **SSS missing Orchestration system requirement** (pre-existing): SSS §3.2 has no CAP-ORC-* section for game server orchestration/TMT2. SRS has a full ORC-F-* section (§3.2.16). The SRS→SSS traceability table in SRS §5 has no CAP-COMP-*/CAP-ORC-* → COMP-F-*/ORC-F-* entries.

4. **SDD permission matrix missing Competition/Orchestration** (pre-existing): SDD §3.3.2 role-permission matrix does not list ManageCompetitions, ManageGameServers, or ViewOrchestration. All three exist in RolePermissionMap.php.

5. **SDD traceability table missing COMP-F-* and ORC-F-*** (pre-existing): SDD §6 maps EVT-F-* through USR-F-* but has no row for COMP-F-* or ORC-F-*.

6. **SRS traceability table missing COMP and ORC** (pre-existing): SRS §5 lists CAP-EVT-* through CAP-USR-* but has no CAP-COMP-* → COMP-F-* or CAP-ORC-* → ORC-F-* rows.

7. **STD missing competition/team test section** (pre-existing): STD has sections 4.1–4.15 but no section covering COMP-F-* tests. STD §5 traceability does not reference COMP-F-* or ORC-F-*.

8. **SSS Policy count** (pre-existing): SSS SEC-008 says "24 authorization policies". Actual = 29 policy files.

## New drift from 2026-04-07 feature batch

### My Pages event selector (EVT/USR cross-cutting)
- No SRS requirement for per-user event scoping (Event::forUser scope, my_selected_event_id session, myEventContext Inertia prop)
- No SDD design entry for EventContextController::storeMy/destroyMy or the forUser scope
- Routes POST/DELETE /my-event-context not mentioned in any doc
- TicketController, UserCompetitionController, UserTeamController, UserOrderController, ShopController index filtering by my_selected_event_id not documented
- Needs: SRS new req (e.g., EVT-F-011 or USR-F-021), SDD §5 update, STD new test cases

### Organization logo (cross-cutting)
- No SRS requirement for OrganizationSetting model or organization branding/logo
- OrganizationSettingsController (update/uploadLogo/removeLogo) not listed in any SRS domain
- shared `organization` Inertia prop in HandleInertiaRequests not documented in SDD §5.1
- AppLogo.vue reading organization prop not in SDD §5.3.2
- Cache::forget('inertia.organization') invalidation pattern not documented
- Needs: SRS new domain section or USR extension, SDD update

### Team leave flow redirect change
- SRS COMP-F-007: "The software shall support team leaving with captain succession" — does not mention return value or redirect behavior
- Actual: LeaveTeam.execute() now returns bool (true = team deleted); TeamController::leave/destroy redirect to my-competitions.show with flash message (not back())
- RTM entry for COMP-F-007 does not reflect the bool return or flash redirect
- SDD has no detailed design note for LeaveTeam behavior (no back() documented either, but the new behavior is more specific and testable)
- Needs: SRS COMP-F-007 updated to include "shall return team-deleted indicator and redirect to competition view with flash", SDD design note, RTM updated

### Mail letter animation
- MailLetterAnimation.vue component exists in resources/js/components/ but has no SDD §5.3.2 entry
- Used in join request resolved feedback flow — JoinRequestResolvedNotification and related actions (RequestToJoinTeam, ResolveJoinRequest) have NO SRS requirement at all
- The entire join request sub-flow (TeamJoinRequest model, RequestToJoinTeam action, ResolveJoinRequest action, TeamJoinRequestNotification, JoinRequestResolvedNotification) is absent from SRS §3.2.14 COMP domain
- Needs: SRS COMP-F-013+ for join request flow, SDD component entry for MailLetterAnimation

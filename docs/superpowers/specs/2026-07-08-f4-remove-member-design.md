# F4 phase-2 — remove a team member (design)

**Date:** 2026-07-08
**Status:** approved, implementing
**Epic:** F4 per-team RBAC, phase 2. Pairs with #511 (add member); reuses #498 (audit) / #499
(owner guard).

## Problem

`TeamMemberResource` can add (#511) and re-role (#497) members, but there is no way to
**remove** one from the role UI.

## Fix

A **Remove** row action on `TeamMemberResource`, plus a service method:

- `TeamManagementService::removeTeamMember(User, Team): void`
  - **owner guard:** throws if the target is the team owner (`$team->user_id`, mirrors #499).
  - removes the four team roles the user holds (in the team's permission context),
  - detaches team membership,
  - records a `team.member_removed` audit (`AuditLogService`, #498).
- The row action mirrors `changeRole`'s visibility: hidden on the acting admin's **own** row
  (no self-removal) and on the **owner** row; `requiresConfirmation`. Access is the resource's
  admin / super_admin gate.

## Testing (TDD)

1. `removeTeamMember` detaches the member and strips their team roles.
2. `removeTeamMember` **rejects** removing the team owner (throws).
3. The Remove action is hidden on the admin's own row and the owner's row, visible on a regular
   member.
4. Calling the action detaches the member.

phpstan 0-new, MySQL 8.4-verified, pint clean. Inline TDD, one PR to `main`.

## Out of scope

Bulk remove, reassigning the removed member's records, deleting the user account (removal only
detaches from the team), removing via Jetstream team settings (separate surface).

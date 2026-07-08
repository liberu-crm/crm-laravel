# F4 phase-2 — Protect the team owner's role (design)

**Date:** 2026-07-08
**Status:** approved, implementing
**Epic:** F4 per-team RBAC, phase 2. Follows #497 (role UI) / #498 (audit).

## Problem

#497's self-guard stops an admin re-rolling **their own** row, so the acting admin always
stays admin — the team can't lose all admins that way. But the **team owner** is unprotected:
admin A can demote owner B (B ≠ A) to `free`, leaving the owner without the admin role while
still owning the team — a broken governance state.

## Fix

Make the team owner's role immutable through this surface:

1. `TeamManagementService::changeTeamRole` throws `InvalidArgumentException` when
   `$user` is the team owner (`$user->getKey() === $team->getAttribute('user_id')`) —
   defense in depth behind the UI.
2. `TeamMemberResource` change-role row action is hidden on the owner's row (in addition to
   the acting admin's own row): `visible = $record !== auth && $record !== tenant.user_id`.

Combined with #497's self-guard, every team keeps at least its owner as a stable admin.

## Testing (TDD, PHPUnit sqlite → MySQL-verify)

1. `changeTeamRole` **rejects** changing the team owner's role (throws) — using a team whose
   owner is not the acting admin.
2. The change-role action is **hidden on the owner's row** and visible on a regular member's.

phpstan 0-new, MySQL 8.4-verified, pint clean. Inline TDD, one PR to `main`.

## Out of scope

Guaranteeing the owner *holds* the admin role (ownership vs role is a Jetstream concern),
transferring ownership, last-admin math beyond the owner invariant.

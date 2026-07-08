# F4 phase-2 — bulk role change (design)

**Date:** 2026-07-08
**Status:** approved, implementing
**Epic:** F4 per-team RBAC, phase 2. Prerelease `0.9.4`.

## Problem

`TeamMemberResource` re-roles members one at a time (#497). Re-roling many (e.g. onboarding a
cohort) is tedious.

## Fix

A **Set role** bulk (toolbar) action on `TeamMemberResource`: select members → pick a role →
apply to all selected. Reuses `changeTeamRole` per record, so the role guard, **owner guard**
(#499), and **audit** (#498) apply. **Skips the acting admin and the team owner** (self-guard +
owner guard) — an owner in the selection is left untouched (`changeTeamRole` throws → caught).

## Testing (TDD)

1. Bulk-set `manager` on two members → both hold `manager`.
2. A selection that includes the acting admin (own + owner row) leaves the admin unchanged while
   re-roling the others.

phpstan 0-new, MySQL 8.4-verified, pint clean.

## Versioning

Scheme corrected to **`0.9.N`** (approaching `1.0.0`; `9 < 11` remaining tasks at start, patch
bumps per PR). Prior prereleases retagged `v0.9.1/2/3` (were `0.1.0-alpha.1/2/3`). This PR →
`VERSION 0.9.4`, prerelease `v0.9.4`.

## Out of scope

Bulk remove, cross-team bulk, role change with an explicit reason/audit note.

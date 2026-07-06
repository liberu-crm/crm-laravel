# F4 — Per-team RBAC (design)

**Date:** 2026-07-06
**Status:** approved-pending-review
**Task:** TASKS.md F4 — "Harden RBAC (Shield roles/permissions per team) + record-level scoping hook." The record-level hook (`RestrictsToOwner`) shipped earlier; this spec covers the **per-team RBAC** half.

## Goal

Make Spatie roles resolve **per team** so a user can hold different roles in different teams (manager in Team A, sales_rep in Team B), and every `hasRole`/`can`/policy check answers against the team the user is currently acting in.

## Decisions (agreed)

1. **Independent role per team** — real Spatie teams mode, not global roles.
2. **Taxonomy:** `super_admin` is platform-**global** (spans all teams); `admin`, `manager`, `sales_rep`, `free` are **per-team**.
3. **Default assignment:** team creator → `admin` in that team; invited member → `sales_rep` in that team (both adjustable later by a team admin).
4. **Scope of this spec:** the *mechanism* only. The team-admin self-service role-management UI is **phase 2** (separate spec/PR).
5. **Backfill:** existing non-`super_admin` assignments are stamped with the user's `current_team_id` (fallback: left global if the user has no current team). `super_admin` assignments become global.

## Architecture

**Two systems, layered, not merged:**

- **Jetstream** owns *membership*: who is on a team, invitations, ownership, `current_team_id`. Unchanged.
- **Spatie laravel-permission** owns *authorization*: the per-team role a member holds and what it permits.

A membership event (create/join) triggers a Spatie role assignment scoped to that team. Otherwise the two stay independent.

## Data model

Enable Spatie teams: publish `config/permission.php` with `'teams' => true` and `'team_foreign_key' => 'team_id'`. Migration adds a nullable `team_id` to `roles`, `model_has_roles`, `model_has_permissions` (Spatie's teams schema).

- **Role definitions are global** — the 5 role rows are created once with `roles.team_id = null`. We do **not** duplicate a `manager` row per team.
- **Assignments are per-team** — `model_has_roles.team_id` carries the team. `setPermissionsTeamId($teamA); $user->assignRole('manager')` writes a row `(user, manager, team_A)`.
- **`super_admin` is a global assignment** — written with `team_id = null`, meaning "applies in every team". `User::hasRole` is already overridden in this codebase; extend that override so **`super_admin` is resolved team-independently** (a query that ignores the current permission team), while every other role name resolves through the normal per-team path. This keeps the platform role working in the direct `hasRole('super_admin')` calls (`canAccessPanel`, `canAccessFilament`, `AccessContext`) without depending on Spatie's null-team internals, and is straightforwardly testable.

## Current-team wiring

A single middleware sets the permission team on every authenticated request, **before** any role/gate check:

```
app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($teamId);
```

`$teamId` comes from the same resolution `TenantContext` already uses — the Filament tenant on the `app` panel, the sanctum user's `currentTeam` on the API. It is applied on the `web`/panel and `api` middleware groups (mirroring `SetTenantContext`). Console/queue set it explicitly when a job runs "as" a team (out of scope here; see `TenantAware` for the tenant-context analogue).

**Consequence (the payoff):** `AccessContext::restrictToOwnerId()` calls `hasRole('sales_rep')`. Once the permission team is set per request, that check is automatically per-team — a user who is `sales_rep` in Team A but `manager` in Team B is owner-scoped in A and sees all records in B, with **no change to `AccessContext` or `RestrictsToOwner`**. Same for `canAccessPanel`/`canAccessFilament` and the record policies.

## Default role assignment

Hook the existing team lifecycle (already centralized in `TeamManagementService` + the personal-team listener, both hardened in an earlier PR):

- **Team created / personal team** → assign the creator `admin` scoped to the new team.
- **Member added** (Jetstream `addTeamMember` / invitation accepted) → assign the new member `sales_rep` scoped to that team.

Assignment always wraps `setPermissionsTeamId($team->id)` around `assignRole(...)`.

## Backfill migration

A data migration over existing `model_has_roles`:

- `super_admin` rows → `team_id = null` (global).
- every other row → the user's `current_team_id`; if the user has none, leave `team_id = null` (global fallback) and log it.

This preserves each user's effective permissions in the team they actively work in.

## Filament Shield

Set `config/filament-shield.php` `tenant_model = Team::class` so Shield's generated resource permissions and role resource are team-aware. Shield stays on the `admin` panel (super_admin). The per-team role-management surface is phase 2.

## Interaction with existing code (no behavioural regressions expected)

- `AccessContext` / `RestrictsToOwner` — unchanged; become per-team for free once the middleware is wired.
- `canAccessPanel` / `canAccessFilament` (User) — unchanged; resolve per current team. Note: a user with a role only in Team A must have the permission team set to A when accessing the app panel (the Filament tenant guarantees this).
- Record policies (`belongsToTeam(currentTeam)`) — unchanged; already team-based.
- `SetTenantContext` middleware — the new permission-team middleware sits beside it and resolves the team the same way; consider merging the two resolutions into one helper to avoid drift.

## Testing strategy

- A user assigned `manager` in Team A and `sales_rep` in Team B: `hasRole` resolves correctly after `setPermissionsTeamId` for each; owner-scope follows (all deals in A, own-only in B).
- `super_admin` answers `true` across all teams regardless of context.
- Team creation → creator has `admin` in that team and no role elsewhere; invited member → `sales_rep` in that team only.
- A `manager` in Team A has **no** role/powers in Team B (cross-team isolation).
- Backfill migration: seed pre-teams assignments, run migration, assert each lands on the right team.
- Regression: the full existing suite stays green (the wiring must not change single-team behaviour).
- **Verify the schema + backfill on MySQL 8.4**, not just sqlite (recreate the mysql container + `migrate:fresh`, as established).

## Scope boundary

**In:** teams mode config, pivot migration, global role definitions, current-team middleware, default assignment on create/join, backfill migration, Shield `tenant_model`, tests.

**Out (phase 2):** team-admin self-service role management (a team-scoped Shield role resource on the `app` panel), custom per-team permissions beyond the 5 roles, territory/field-level scoping (G3 remainder).

## Risks

- **`super_admin` global resolution** — the extended `hasRole` override must be covered by a test proving super_admin answers true under any `setPermissionsTeamId` value (including a team the user has no assignment in).
- **Permission cache** — Spatie caches permissions; confirm the team id is set before the first gate check each request and that the cache key is team-aware (it is in teams mode, but verify).
- **Middleware ordering** — `setPermissionsTeamId` must run before any authorization check (before `SubstituteBindings`/policy resolution), like `SetTenantContext`.
- **Backfill correctness** — a user on multiple teams with one global assignment collapses to their current team only; acceptable per the decision, but note it in the migration.

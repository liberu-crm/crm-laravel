# G3 ABAC — slice 1: territory foundation (design)

**Date:** 2026-07-08
**Status:** approved, implementing
**Epic:** G3 attribute-based access control (territory + field-level scoping). First slice.

## Epic decomposition

1. **Territory foundation** (this slice) — a `Territory` per team + assigning users to
   territories + a management UI. No record scoping yet.
2. **Record territory scoping** — add `territory_id` to Leads/Deals and a
   `RestrictsToTerritory` global scope gated by the user's territories (mirrors F4's
   `RestrictsToOwner` / `AccessContext`).
3. **Field-level masking** — hide/mask sensitive fields by attribute.

Building the foundation first (like F1/F4) keeps the scoping mechanism a separate, focused
slice once the data model exists.

## Slice 1 architecture

- `territories` table: `team_id` (FK), `name`, timestamps. `IsTenantModel` (team scope +
  team_id auto-stamp + the `team()` relationship the app panel needs).
- `territory_user` pivot: `territory_id`, `user_id` (unique pair), cascade on delete.
- `App\Models\Territory` — `IsTenantModel`; `users(): BelongsToMany` (via `territory_user`).
- `App\Filament\App\Resources\TerritoryResource` (app panel, team-scoped): CRUD; `canAccess`
  → super_admin / admin / manager (territory management is a lead's job, not a rep's). Form:
  `name` + a **multi-select of the team's members** (`->relationship('users','name')` scoped
  to `Filament::getTenant()->allUsers()`), so assigning members is part of create/edit.

## Testing (TDD, PHPUnit sqlite → MySQL-verify)

1. An admin creates a territory → `team_id` stamped.
2. Creating with members selected writes the `territory_user` pivot rows.
3. Team-scoped: team A's admin doesn't see team B's territory.
4. `canAccess`: manager ✓, sales_rep ✗.

`Territory` is `IsTenantModel`, so the `CrossTenantLeakageTest` auto-covers it. phpstan 0-new,
MySQL 8.4-verified, pint clean. Inline TDD, one PR to `main`.

## Out of scope (later slices)

Record `territory_id` + the territory scope (slice 2), field-level masking (slice 3), territory
hierarchy/parenting, auto-assignment rules, per-territory quotas.

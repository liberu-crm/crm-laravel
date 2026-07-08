# G3 ABAC — slice 2: record territory scoping (design)

**Date:** 2026-07-08
**Status:** approved, implementing
**Epic:** G3 ABAC. Builds on #505 (territory foundation). Territories now *do* something.

## Scope

Filter **Contact** records by the current user's territories. Contact is chosen because it has
**no** F4 owner-scope (`RestrictsToOwner` was skipped for Contact — no owner column), so the
new territory scope stacks cleanly on the team scope without an owner-AND-territory conflict.

Mirrors the F4 pattern: a removable global scope gated by `AccessContext`, restricting the same
roles (`sales_rep` / `free`); managers / admins / super_admins / roleless / non-auth contexts
see everything in scope.

## Architecture

- Migration: `contacts.territory_id` (nullable FK → `territories`, `nullOnDelete`).
- `User::territories(): BelongsToMany` (inverse of `Territory::users`, via `territory_user`).
- `App\Support\AccessContext::restrictedTerritoryIds(): ?array` — for a restricted-role user,
  the ids of their assigned territories; otherwise `null` (see-all). Same guard resolution
  (sanctum → default) as `restrictToOwnerId`.
- `App\Traits\RestrictsToTerritory` — a global `territory` scope: when
  `restrictedTerritoryIds()` is non-null, `whereIn(territory_id, ids)`. **No auto-stamp**
  (territory is assigned, not creator-derived). Read-only scoping.
- Apply the trait to `Contact` (+ `territory_id` fillable).

**Behavior:** a restricted user sees only contacts whose `territory_id` is in their
territories. Unassigned (`null`) contacts are **not** visible to restricted users (strict
territory ABAC); managers/admins see all.

## Testing (TDD, PHPUnit sqlite → MySQL-verify)

1. A `sales_rep` assigned to territory T sees a contact in T and **not** one in another
   territory.
2. A `manager` sees contacts across all territories.
3. A roleless user is unrestricted (sees all).
4. `AccessContext::restrictedTerritoryIds` returns the ids for a `sales_rep`, `null` for a
   `manager`.

phpstan 0-new, MySQL 8.4-verified, pint clean. Inline TDD, one PR to `main`.

## Out of scope

Territory scoping on other models (Lead/Deal already owner-scoped — a combined policy is its
own decision), auto-assigning a contact's territory, field masking (slice 3), letting
restricted users also see unassigned contacts (a product toggle if wanted later).

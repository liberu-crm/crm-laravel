# G3 ABAC — mask the ad set budget (design)

**Date:** 2026-07-08
**Status:** approved, implementing
**Epic:** G3 ABAC masking. Applies the existing masking framework to another model.

## Problem

An `AdSet`'s `budget` (money) is visible to every staff role. A `free`-tier user should be able to
work an ad set without seeing its budget.

## Design — reuse the masking framework

- `AdSet` uses `MasksFields` with `$maskedFields = ['budget']` (masks `budget` in serialization /
  API for masked-role viewers, no attribute mutation).
- `AdSetResource` table `budget` column → `[hidden]` for masked viewers, else the formatted currency
  (`AccessContext::shouldMaskFields()` at build time). The column is not searchable, so no search leak.
- `AdSetResource` edit form: the `budget` input is hidden for a masked viewer on edit and a
  `[hidden]` Placeholder shows instead — the hidden field isn't validated or dehydrated, so a save
  preserves the stored value. Create is unmasked.

`AdSet` is team-scoped only (`IsTenantModel`, not `RestrictsToOwner`), so a `free` user sees every ad
set in their team; masking hides the budget on all of them.

## Testing (TDD)

1. Serialization: a `free` user's `AdSet::toArray()` masks `budget`; a `manager` sees the real
   number; direct `$adSet->budget` is unmasked (no mutation).
2. Table: a `free` user sees `[hidden]`; a `manager` sees the formatted value.
3. Edit form: the `budget` field is hidden for a `free` user (unsaveable → no corruption); a
   `manager` sees the real value.

phpstan 0-new, MySQL-verified, pint clean.

## Versioning

GitHub prerelease `v1.3.0-rc.3`.

## Out of scope

Masking other AdSet fields (e.g. targeting/metadata), encrypt-at-rest.

# G3 ABAC — mask the deal value (design)

**Date:** 2026-07-08
**Status:** approved, implementing
**Epic:** G3 ABAC field masking. Applies the masking framework (#507–#510) to a second model.

## Problem

Deal financials (`value`) are visible to every staff role. A `free`-tier user should be able to
work a deal without seeing its value.

## Fix — reuse the masking framework

- `Deal` uses `MasksFields` with `$maskedFields = ['value']` (masks `value` in serialization /
  API for masked-role viewers, no attribute mutation).
- `DealResource` table `value` column → `[hidden]` for masked viewers, else the formatted
  currency (`AccessContext::shouldMaskFields()` at build time; the column is not searchable, so
  no search leak).
- `DealResource` edit form: the `value` input is hidden for a masked viewer on edit and a
  `[hidden]` Placeholder shows instead — the hidden field isn't validated or dehydrated, so a
  save preserves the stored value (same pattern as #510). Create is unmasked.

Deal is owner-scoped (`RestrictsToOwner`), so a `free` user only sees their own deals; masking
hides the value on those.

## Testing (TDD)

1. Serialization: a `free` user's `Deal::toArray()` masks `value`; a `manager` sees the real
   number; direct `$deal->value` is unmasked (no mutation).
2. Table: a `free` user (owning the deal) sees `[hidden]`; a `manager` sees the formatted value.
3. Edit form: the `value` field is hidden for a `free` user (unsaveable → no corruption).

phpstan 0-new, MySQL 8.4-verified, pint clean.

## Versioning

`VERSION` → `0.9.6`; GitHub prerelease `v0.9.6`.

## Out of scope

Masking other Deal fields, masking on Lead/Opportunity, encrypt-at-rest.

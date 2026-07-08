# G3 ABAC — mask the lead potential value (design)

**Date:** 2026-07-08
**Status:** approved, implementing
**Epic:** G3 ABAC masking. Applies the existing masking framework to a third model.

## Problem

A lead's `potential_value` (money) is visible to every staff role. A `free`-tier user should be
able to work a lead without seeing its potential value.

## Fix — reuse the masking framework

- `Lead` uses `MasksFields` with `$maskedFields = ['potential_value']` (masks `potential_value` in
  serialization / API for masked-role viewers, no attribute mutation).
- `LeadResource` table `potential_value` column → `[hidden]` for masked viewers, else the formatted
  currency (`AccessContext::shouldMaskFields()` at build time; the column is not searchable, so no
  search leak).
- `LeadResource` edit form: the `potential_value` input is hidden for a masked viewer on edit and a
  `[hidden]` Placeholder shows instead — the hidden field isn't validated or dehydrated, so a save
  preserves the stored value. Create is unmasked.

Lead is owner-scoped (`RestrictsToOwner`), so a `free` user only sees their own leads; masking hides
the potential value on those.

## Testing (TDD)

1. Serialization: a `free` user's `Lead::toArray()` masks `potential_value`; a `manager` sees the
   real number; direct `$lead->potential_value` is unmasked (no mutation).
2. Table: a `free` user (owning the lead) sees `[hidden]`; a `manager` sees the formatted value.
3. Edit form: the `potential_value` field is hidden for a `free` user (unsaveable → no corruption).

phpstan 0-new, MySQL 8.4-verified, pint clean.

## Versioning

GitHub prerelease `v1.2.0-rc.2`.

## Out of scope

Masking other Lead fields, encrypt-at-rest.

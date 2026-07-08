# G3 ABAC — mask the Opportunity deal size (design)

**Date:** 2026-07-08
**Status:** approved, implementing
**Epic:** G3 ABAC masking. Applies the masking framework to a third model, mirroring Deal.

## Problem

`Opportunity.deal_size` (a money field) is visible to every staff role. A `free`-tier viewer
should be able to work an opportunity without seeing its deal size — an exact parallel of how
`Deal.value` is already masked.

## Design — reuse the masking framework

- `Opportunity` uses `MasksFields` with `$maskedFields = ['deal_size']`: masks `deal_size` in
  serialization / API output for masked-role viewers (`AccessContext::shouldMaskFields()`, true
  only for role `free`), with no attribute mutation.
- `OpportunityResource` table `deal_size` column → `[hidden]` for masked viewers, else the
  formatted currency. `->searchable()` is gated off for masked viewers so search can't probe the
  hidden value.
- `OpportunityResource` edit form: the `deal_size` input is hidden for a masked viewer on edit and
  a `[hidden]` Placeholder shows instead — the hidden field isn't validated or dehydrated, so a
  save preserves the stored value. Create is unmasked.

`Opportunity` is team-scoped (`IsTenantModel`, `team_id` column), not owner-scoped, so a `free`
user sees all of their team's opportunities with `deal_size` masked. Note the non-standard key:
`$primaryKey = 'opportunity_id'`, `$incrementing = false`.

## Testing (TDD)

1. Serialization: a `free` user's `Opportunity::toArray()` masks `deal_size`; a `manager` sees the
   real number; direct `$opportunity->deal_size` is unmasked (no mutation).
2. Table: a `free` user sees `[hidden]`; a `manager` sees the formatted value.
3. Edit form: the `deal_size` field is hidden for a `free` user (unsaveable → no corruption); a
   `manager` sees the real field.

## Out of scope

Masking other Opportunity fields, masking on Lead, encrypt-at-rest, a `decimal:2` cast on
`deal_size` (unchanged).

## Versioning

Prerelease `1.2.0-rc.4`.

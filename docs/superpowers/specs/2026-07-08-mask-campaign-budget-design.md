# G3 ABAC — mask the campaign budget (design)

**Date:** 2026-07-08
**Status:** approved, implementing
**Epic:** G3 ABAC masking. Prerelease `1.3.0-rc.2`. Applies the existing masking framework to another model.

## Problem

A campaign's `budget` (money) is visible to every staff role. A `free`-tier user should be able to
work a campaign without seeing its budget.

## Design — reuse the masking framework

- `Campaign` uses `MasksFields` with `$maskedFields = ['budget']` (masks `budget` in serialization /
  API for masked-role viewers, no attribute mutation).
- `CampaignResource` table `budget` column → `[hidden]` for masked viewers, else the formatted
  currency (`AccessContext::shouldMaskFields()` at build time; the column is not searchable, so no
  search leak).
- `CampaignResource` edit form: the `budget` input is hidden for a masked viewer on edit and a
  `[hidden]` Placeholder shows instead — the hidden field isn't validated or dehydrated, so a save
  preserves the stored value. Create is unmasked.

Campaign is team-scoped (`IsTenantModel`) only, not owner-scoped, so a `free` team member sees every
campaign in the team; masking hides the budget on all of them.

## Testing (TDD)

1. Serialization: a `free` user's `Campaign::toArray()` masks `budget`; a `manager` sees the real
   number; direct `$campaign->budget` is unmasked (no mutation).
2. Table: a `free` user sees `[hidden]`; a `manager` sees the formatted value.
3. Edit form: the `budget` field is hidden for a `free` user (unsaveable → no corruption); a
   `manager` sees the real value.

phpstan 0-new, MySQL 8.4-verified, pint clean.

## Out of scope

Masking other Campaign fields, encrypt-at-rest.

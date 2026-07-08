# G3 ABAC — mask Company PII (design)

**Date:** 2026-07-08
**Status:** approved, implementing
**Epic:** G3 ABAC masking. Applies the existing masking framework to a third model.

## Problem

A Company's `phone_number` and `annual_revenue` are visible to every staff role. A `free`-tier
viewer should be able to work a company record without seeing those sensitive fields.

## Design — reuse the masking framework

- `Company` uses `MasksFields` with `$maskedFields = ['phone_number', 'annual_revenue']` — both
  fields are masked to `[hidden]` in serialization / API output for masked-role viewers, with no
  mutation of the stored attributes (`$company->phone_number` and saves still see the real value).
- `CompanyResource` table `phone_number` column → `maskFor('phone_number', $state)` renders
  `[hidden]` for masked viewers, else the real value; `->searchable(! shouldMaskFields())` so a
  masked viewer cannot confirm the hidden value via search.
- `CompanyResource` edit form: the `phone_number` input is hidden for a masked viewer on edit and
  a `[hidden]` Placeholder shows instead — the hidden field isn't validated or dehydrated, so a
  save preserves the stored value. Create is unmasked.
- `annual_revenue` is masked at the model layer (serialization / API) but is not surfaced by
  `CompanyResource` — it has no table column or form input — so there is no resource-level
  display to mask.

Company is tenant-scoped only (`IsTenantModel`, `team_id`), with no owner/territory restriction,
so every team member sees all team companies; masking hides the sensitive fields on those.

## Testing (TDD)

1. Serialization: a `free` user's `Company::toArray()` masks both fields; a `manager` sees the
   real values; direct `$company->phone_number` is unmasked (no mutation).
2. Table: a `free` user sees `[hidden]` for phone and cannot find the record by searching it; a
   `manager` sees and can search the real phone.
3. Edit form: the `phone_number` field is hidden for a `free` user (unsaveable → no corruption);
   a `manager` sees the real value.

## Out of scope

Masking `annual_revenue` in the Filament UI (no column/input exists to mask), masking other
Company fields, encrypt-at-rest.

## Versioning

Prerelease `1.2.0-rc.3`.

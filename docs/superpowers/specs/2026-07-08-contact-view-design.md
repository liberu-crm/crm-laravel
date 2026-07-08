# Contact detail View page (design)

**Date:** 2026-07-08
**Status:** approved, implementing
**Epic:** F1 detail-view / G3 masking-safety. Prerelease `1.9.0-rc.1`.

## Problem

Deal, Lead, and Company got read-only detail View pages in 1.8.0, but Contact — the flagship CRM
record — still has only List/Create/Edit. It masks `email` and `phone_number` for the `free`
role, so a naive View page would leak those fields (a masking bypass).

## Design

A `ViewContact` `ViewRecord` page with an infolist, mirroring `ViewCompany`. The `email` and
`phone_number` entries mask via the model's `maskFor()` helper (the same source the Contact table
columns use), so `[hidden]` shows for masked roles and the real value otherwise. A `ViewAction` is
added to the resource's record actions and a `'view'` page is registered. The page is
territory/tenant-scoped through the resource's existing query (Contact uses `RestrictsToTerritory`
+ `IsTenantModel`).

## Testing (TDD)

1. An admin mounts `ViewContact` → `assertOk`.
2. A `free`-role viewer (in the contact's territory) sees `[hidden]` for `email` and
   `phone_number`.
3. A `manager` sees the real email.

phpstan 0-new, MySQL 8.4-verified, pint clean.

## Out of scope

Related activity/timeline on the detail page; editing from the view.

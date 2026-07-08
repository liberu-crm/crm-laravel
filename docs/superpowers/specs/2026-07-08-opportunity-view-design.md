# Opportunity detail View page (design)

**Date:** 2026-07-08
**Status:** approved, implementing
**Epic:** F1 detail-view / G3 masking-safety. Prerelease `1.9.0-rc.2`.

## Problem

Opportunity is the last masked-money resource without a read-only detail View page (Contact, Deal,
Lead, Company have them). It masks `deal_size` for the `free` role, so a naive View page would leak
it (a masking bypass).

## Design

A `ViewOpportunity` `ViewRecord` page with an infolist mirroring `ViewDeal`. The `deal_size` entry
masks with the same `AccessContext::shouldMaskFields()` gate as the table column. A `ViewAction` is
added to the resource's record actions and a `'view'` page registered. Team-scoped through the
resource's query (`IsTenantModel`). Opportunity has a custom primary key (`opportunity_id`,
non-incrementing), which the record route resolves via `getKey()`.

## Testing (TDD)

1. An admin mounts `ViewOpportunity` → `assertOk`.
2. A `free`-role viewer sees `[hidden]` for `deal_size`.
3. A `manager` sees the real formatted value.

phpstan 0-new, MySQL 8.4-verified, pint clean.

## Out of scope

Related notes/timeline on the detail page.

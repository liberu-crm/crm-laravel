# Campaign detail View page (design)

**Date:** 2026-07-08
**Status:** approved, implementing
**Epic:** F1 detail-view / G3 masking-safety. Prerelease `1.9.0-rc.3`.

## Problem

Campaign has a masked money field (`budget`) but only List/Create/Edit — no read-only detail View
page. A naive View page would leak `budget` to the `free` role (a masking bypass).

## Design

A `ViewCampaign` `ViewRecord` page with an infolist mirroring `ViewDeal`/`ViewOpportunity`. The
`budget` entry masks with the same `AccessContext::shouldMaskFields()` gate as the table column. A
`ViewAction` is added to the resource's record actions and a `'view'` page registered. Team-scoped
through the resource's query (`IsTenantModel`).

## Testing (TDD)

1. An admin mounts `ViewCampaign` → `assertOk`.
2. A `free`-role viewer sees `[hidden]` for `budget`.
3. A `manager` sees the real formatted value.

phpstan 0-new, MySQL 8.4-verified, pint clean.

## Out of scope

Ad/AdSet drill-down on the detail page.

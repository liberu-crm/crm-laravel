# Campaign CSV export (design)

**Date:** 2026-07-08
**Status:** approved, implementing
**Epic:** F1 export / G3 masking-safety. Prerelease `1.11.0-rc.2`.

## Problem

Contact, Deal, Lead, and Company got masking-gated CSV export in the 1.6/1.7 sweep, but Campaign
was skipped — it can't be exported at all, even though it has a masked money field (`budget`).

## Design

A `CampaignExporter` + a header `ExportAction` on `CampaignResource`, exactly like the Contact
export: the action is **gated off for masked (`free`) roles** (`->visible(fn () => !
AccessContext::shouldMaskFields())`), so a CSV can't bypass the `budget` masking. The export
inherits the resource's tenant scope (Campaign is IsTenantModel). Uses the shared Filament
`exports` table (published in 1.6.0).

## Testing (TDD)

1. `Schema::hasTable('exports')` is true.
2. `CampaignExporter::getColumns()` is non-empty.
3. An admin sees the `export` header action.
4. A `free`-role user's export action `isVisible()` is false.

phpstan 0-new, MySQL 8.4-verified, pint clean.

## Out of scope

AdSet export (sibling advertising resource); bulk (selected-row) export.

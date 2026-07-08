# Company CSV export (design)

**Date:** 2026-07-08
**Status:** approved, implementing
**Epic:** F1 export / G3 masking-safety. Prerelease `1.7.0-rc.3`.

## Problem

Team admins can export Contacts to CSV (#545) but not Companies. Companies carry
the same masking hazard: the `free` role has `phone_number` / `annual_revenue`
masked in every serialized view (MasksFields + `AccessContext::shouldMaskFields()`),
so a raw CSV would hand that role the cleartext values the UI hides.

The Filament `exports` table already exists (published + migrated for #545), so
no migration is needed here — this is an exact copy of the Contact export.

## Design

- **`CompanyExporter`** (`app/Filament/Exports/`) mirrors `ContactExporter`:
  `extends Exporter`, `$model = Company::class`, business-field columns
  (name, industry, website, phone_number, annual_revenue, address/city/state/zip,
  size, domain, created_at) including the masked `phone_number` +
  `annual_revenue` in the clear. Clear values are safe because the action is
  hidden entirely for masked roles — only non-masking roles can ever trigger the
  export. Freeform `description`/`location` and internal ids are omitted.
- **CompanyResource** gains `->headerActions([ExportAction::make()
  ->exporter(CompanyExporter::class)
  ->visible(fn () => ! AccessContext::shouldMaskFields())])`. The `visible`
  closure is the security control — the masked `free` role never sees or fires
  the export. Existing columns / filters / toolbarActions are untouched; the
  export inherits the resource's tenant scope (Company is IsTenantModel).

## Testing (TDD)

1. `Schema::hasTable('exports')` — proves the table is present (already migrated).
2. `CompanyExporter::getColumns()` is non-empty.
3. Admin mounts `ListCompanies` → `assertTableHeaderActionsExistInOrder(['export'])`.
4. Free role: `AccessContext::shouldMaskFields()` is true, and the resolved
   `export` header action's `isVisible()` is false. (`getHeaderActions()` lists
   actions regardless of visibility and there is no `assertTableHeaderActionHidden`
   helper in this Filament version, so the gate is asserted on the action object.)

## Out of scope

Import. A masked-role export that emits masked values — the whole action is gated
off instead, which is simpler and leaves no partial-cleartext path. Column
selection UI / XLSX-specific formatting beyond Filament defaults. Export blocked
for masked roles specifically so phone/revenue can't bypass masking via CSV.

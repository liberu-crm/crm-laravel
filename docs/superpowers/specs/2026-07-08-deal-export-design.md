# Deal CSV export (design)

**Date:** 2026-07-08
**Status:** approved, implementing
**Epic:** F1 export / G3 masking-safety. Prerelease `1.7.0-rc.1`.

## Problem

Team admins can export Contacts (#545) but not Deals from the app panel. Adding
a Deal export is the same masking hazard: the `free` role has `value` masked in
every serialized view (MasksFields + `AccessContext::shouldMaskFields()`), and a
raw CSV would hand that role the cleartext deal values the UI hides.

Filament's `exports` table already exists (migrated with #545), so no migration
is needed here.

## Design

- **`DealExporter`** (`app/Filament/Exports/`) is an exact copy of
  `ContactExporter`: `extends Exporter`, `$model = Deal::class`,
  business-field columns including `value` in the clear. Clear values are safe
  because the action is hidden entirely for masked roles — only non-masking
  roles can ever trigger the export. Columns: `name`, `value`, `stage`,
  `close_date`, `probability`, plus relation labels `contact.name` (Contact),
  `user.name` (Owner), `pipeline.name` (Pipeline), and `created_at`. Internal
  ids are omitted; `stage_id` is skipped because `stage` is also a plain string
  column, so `stage.name` would resolve the attribute, not the Stage relation.
- **DealResource** gains `->headerActions([ExportAction::make()
  ->exporter(DealExporter::class)
  ->visible(fn () => ! AccessContext::shouldMaskFields())])`. The `visible`
  closure is the security control — the masked `free` role never sees or fires
  the export, preventing a value-masking bypass. Existing columns / filters /
  toolbarActions are untouched; the export inherits the resource's tenant scope
  (Deal is IsTenantModel).

## Testing (TDD)

1. `Schema::hasTable('exports')` — proves the table is present.
2. `DealExporter::getColumns()` is non-empty.
3. Admin mounts `ListDeals` → `assertTableHeaderActionsExistInOrder(['export'])`.
4. Free role: `AccessContext::shouldMaskFields()` is true, and the resolved
   `export` header action's `isVisible()` is false. (`getHeaderActions()` lists
   actions regardless of visibility and there is no `assertTableHeaderActionHidden`
   helper in this Filament version, so the gate is asserted on the action object.)

## Out of scope

Import (ImportAction + its tables). A masked-role export that emits masked
values — the whole action is gated off instead, which is simpler and leaves no
partial-cleartext path. Column selection UI / XLSX-specific formatting beyond
Filament defaults.

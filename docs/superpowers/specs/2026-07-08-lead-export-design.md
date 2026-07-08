# Lead CSV export (design)

**Date:** 2026-07-08
**Status:** approved, implementing
**Epic:** F1 export / G3 masking-safety. Prerelease `1.7.0-rc.2`.

## Problem

Team members have no way to export Leads to CSV from the app panel. Adding one
is a masking hazard: the `free` role has `potential_value` masked in every
serialized view (MasksFields + `AccessContext::shouldMaskFields()`), and a raw
CSV would hand that role the cleartext value the UI hides. This mirrors the
Contact export shipped in PR #545.

The `exports` table (from `filament/actions`) already exists — no migration is
needed here.

## Design

- **`LeadExporter`** (`app/Filament/Exports/`) mirrors `ContactExporter`:
  `extends Exporter`, `$model = Lead::class`, business-field columns (`status`,
  `source`, `potential_value`, `lifecycle_stage`, `score`,
  `expected_close_date`, `created_at`) including `potential_value` in the clear.
  The clear value is safe because the action is hidden entirely for masked
  roles — only non-masking roles can ever trigger the export. `custom_fields`
  (array cast) and internal ids are omitted. No contact column: the resource
  table doesn't display one.
- **LeadResource** gains a separate `->headerActions([ExportAction::make()
  ->exporter(LeadExporter::class)
  ->visible(fn () => ! AccessContext::shouldMaskFields())])`. The `visible`
  closure is the security control — the masked `free` role never sees or fires
  the export, so `potential_value` masking cannot be bypassed via CSV. Existing
  columns / filters / toolbarActions are untouched; the export inherits the
  resource's owner (RestrictsToOwner) + tenant (IsTenantModel) scope, so a
  restricted user only exports rows they may already see.

## Testing (TDD)

1. `Schema::hasTable('exports')` — proves the table exists.
2. `LeadExporter::getColumns()` is non-empty.
3. Admin mounts `ListLeads` → `assertTableHeaderActionsExistInOrder(['export'])`.
4. Free role: `AccessContext::shouldMaskFields()` is true, and the resolved
   `export` header action's `isVisible()` is false. (`getHeaderActions()` lists
   actions regardless of visibility, so the gate is asserted on the action
   object.) An owned lead is seeded so the owner-scoped list mounts with a row;
   the gate itself is row-independent.

## Out of scope

Import. A masked-role export that emits masked values — the whole action is
gated off instead, leaving no partial-cleartext path. Column selection UI /
XLSX-specific formatting beyond Filament defaults. A contact relation column.

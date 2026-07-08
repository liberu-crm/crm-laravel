# Contact CSV export (design)

**Date:** 2026-07-08
**Status:** approved, implementing
**Epic:** F1 export / G3 masking-safety. Prerelease `1.6.0-rc.2`.

## Problem

Team admins have no way to export Contacts to CSV from the app panel. Adding
one is a masking hazard: the `free` role has `email` / `phone_number` masked in
every serialized view (MasksFields + `AccessContext::shouldMaskFields()`), and a
raw CSV would hand that role the cleartext values the UI hides.

Separately, Filament's `exports` table (from `filament/actions`) was never
published. The audit-log ExportAction shipped in #542 has therefore been
runtime-broken — the first export attempt hits a missing table. Any new export
needs that table too, so it is fixed here.

## Design

- **Migration** `2026_07_08_000004_create_exports_table.php` — replicates the
  vendor `create_exports_table` stub exactly (`exports`: id, completed_at,
  file_disk, file_name, exporter, processed/total/successful_rows, user_id FK
  cascade, timestamps). Backs both the audit-log export (#542) and this one.
  ImportAction's tables are not published — no import feature exists.
- **`ContactExporter`** (`app/Filament/Exports/`) mirrors `AuditLogExporter`:
  `extends Exporter`, `$model = Contact::class`, business-field columns
  including `email` + `phone_number` in the clear. Clear values are safe because
  the action is hidden entirely for masked roles — only non-masking roles can
  ever trigger the export. `custom_fields` / `metadata` (array casts) are
  omitted, same rationale as AuditLogExporter's `changes`.
- **ContactResource** gains `->headerActions([ExportAction::make()
  ->exporter(ContactExporter::class)
  ->visible(fn () => ! AccessContext::shouldMaskFields())])`. The `visible`
  closure is the security control — the masked `free` role never sees or fires
  the export. Existing columns / filters / toolbarActions are untouched; the
  export inherits the resource's tenant scope (Contact is IsTenantModel).

## Testing (TDD)

1. `Schema::hasTable('exports')` — proves the migration ran.
2. `ContactExporter::getColumns()` is non-empty.
3. Admin mounts `ListContacts` → `assertTableHeaderActionsExistInOrder(['export'])`.
4. Free role: `AccessContext::shouldMaskFields()` is true, and the resolved
   `export` header action's `isVisible()` is false. (`getHeaderActions()` lists
   actions regardless of visibility and there is no `assertTableHeaderActionHidden`
   helper in this Filament version, so the gate is asserted on the action object.)

## Out of scope

Import (ImportAction + its tables). A masked-role export that emits masked
values — the whole action is gated off instead, which is simpler and leaves no
partial-cleartext path. Column selection UI / XLSX-specific formatting beyond
Filament defaults.

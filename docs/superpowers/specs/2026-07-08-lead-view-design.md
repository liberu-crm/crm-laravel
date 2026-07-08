# Lead detail View page (design)

**Date:** 2026-07-08
**Status:** approved, implementing
**Epic:** F1 detail-view / G3 masking-safety. Prerelease `1.8.0-rc.2`.

## Problem

The app-panel `LeadResource` has List / Create / Edit but no read-only detail
view. Adding one is a masking hazard: the `free` role has `potential_value`
masked everywhere it is serialized (MasksFields + `AccessContext::shouldMaskFields()`),
and the table column already renders `[hidden]` for that role. A detail infolist
that read `potential_value` straight off the record would hand `free` the
cleartext the rest of the UI hides — a masking bypass.

## Design

- **`ViewLead`** (`LeadResource/Pages/`) `extends ViewRecord`, mirrors
  `ViewTerritory`: a single `infolist(Schema $schema): Schema` of `TextEntry`s
  for the meaningful business fields — status, source, lifecycle_stage, score,
  expected_close_date, `contact.name`, created_at.
- **`potential_value`** uses the exact masking expression from the resource's
  table column: `->getStateUsing(fn (Lead $record) => AccessContext::shouldMaskFields()
  ? '[hidden]' : '$'.number_format((float) $record->potential_value, 2))`. This
  is the security control — the `free` role never sees the value on the detail
  page, matching the table.
- **LeadResource** gains `ViewAction::make()` in `recordActions([...])` (before
  Edit) and `'view' => ViewLead::route('/{record}')` in `getPages()`. Everything
  else — form, table columns/filters, export gate, edit masking — is untouched.
  The page inherits the resource's Team tenant scope and `RestrictsToOwner`
  owner-scoping, so a `free`/`sales_rep` user can only reach leads they own.

## Testing (TDD)

1. Admin mounts `ViewLead` → `assertOk()`.
2. `free` role that **owns** the lead sees `[hidden]` and not `50,000`
   (owner-scoped, so the viewer must be `user_id` on the lead).
3. `manager` sees the real formatted value (`50,000`).

## Out of scope

Editing / actions on the view page (Edit already exists). Activities / notes /
documents relation panels. Any change to the masking rule itself — the infolist
reuses the table column's exact expression, so masking behaviour stays defined
in one place.

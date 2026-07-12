# AdSet / Task / Note detail Views — design (2026-07-10)

Three independent read-only detail-View slices toward the 1.14.0 cut. Each is a
direct repeat of the established `ViewRecord` + infolist pattern (ViewCampaign /
ViewContact), one PR per resource.

## Slice A — AdSet detail View (masking-safe)

`AdSetResource` had List/Create/Edit but no detail View. Add a `ViewAdSet`
`ViewRecord` whose infolist masks the `budget` money field with the **same gate**
as the table column (`AccessContext::shouldMaskFields()`), so the detail view
can't bypass masking. (Under role enforcement, `free` has no advertising access
so it can't reach the page — the mask is defense-in-depth; the test covers an
admin seeing the real value.)

## Slice B — Task detail View

`TaskResource` gains a `ViewTask` page. Task is unmasked; the infolist shows
name, description, status, due date, contact, reminder date, created.

## Slice C — Note detail View

`NoteResource` gains a `ViewNote` page. Note is unmasked; the infolist shows the
content, related contact/company, and created date.

## Common

Each adds a `ViewAction` (first record action) + a `view` route, and access is
already permission-gated by the `EnforcesResourcePermissions` trait (no extra
gating). No schema changes → MySQL parity by construction. Each slice = one PR,
rc of 1.14.0.

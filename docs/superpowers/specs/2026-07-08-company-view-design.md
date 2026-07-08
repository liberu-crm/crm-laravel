# Company read-only View page (design)

**Date:** 2026-07-08
**Status:** approved, implementing
**Epic:** F1 detail-view / G3 masking-safety
**Prerelease:** 1.8.0-rc.3

## Problem

The app-panel `CompanyResource` (`/app/companies`) has only List/Create/Edit
pages. A team member who wants to inspect a company has to open the Edit form —
a mutation surface — because there is no read-only detail page. Company also
masks `phone_number` and `annual_revenue` for the `free` role in the table and
edit form (G3 ABAC), so any new read surface MUST apply the same masking or it
becomes a masking bypass.

## Design

Add a Filament v5 `ViewRecord` page, `ViewCompany`, mirroring the existing
`ViewTerritory` infolist shape.

- `CompanyResource\Pages\ViewCompany extends ViewRecord` with
  `public function infolist(Schema $schema): Schema` showing `name`, `industry`,
  `website`, `city`, `state`, `size`, `domain`, `created_at` (dateTime), plus
  the two sensitive fields.
- **Masking:** `phone_number` and `annual_revenue` each use
  `TextEntry::make(...)->getStateUsing(fn (Company $record) => $record->maskFor($field, $record->$field))`,
  the same `MasksFields::maskFor()` helper the table column uses — `[hidden]`
  for masked-role (`free`) viewers, the real value for everyone else. No new
  masking logic; the trait is the single source of truth.
- `CompanyResource::table()` gains `ViewAction::make()` in `recordActions()`
  (ahead of the existing `EditAction`); `getPages()` registers
  `'view' => ViewCompany::route('/{record}')`.

### Tenant scope + gating (security)

`Company` is `IsTenantModel`, so the record resolves through the tenant global
scope — the View page can only mount a company in the current team. The page
inherits the resource access gate, so no new access surface opens.

## Testing

`tests/Feature/Filament/CompanyViewTest.php` (PHPUnit, `RefreshDatabase`),
reusing the `CompanyMaskingUiTest` setUp (RolesSeeder, personal team,
`setPermissionsTeamId`, `assignRole`, `actingAs`,
`Filament::setCurrentPanel('app')`, `setTenant`), with a company created at
`phone_number => '+15551234567'`, `annual_revenue => 5000000`:

- admin mounts `ViewCompany` → `assertOk()`;
- `free` viewer → sees `[hidden]`, does not see the real phone or revenue;
- `manager` viewer → sees the real phone, no `[hidden]`.

## Out of scope

- Editing/deleting from the View page — Edit page already covers mutation.
- Company relations (notes/tasks/opportunities/contacts/deals) on the detail view.
- Any change to the masking rule or roles — reused as-is from `MasksFields`.

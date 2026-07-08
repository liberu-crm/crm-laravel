# Deal read-only View page (design)

**Date:** 2026-07-08
**Status:** approved, implementing
**Epic:** F1 detail-view / G3 masking-safety
**Prerelease:** 1.8.0-rc.1

## Problem

The app-panel `DealResource` (`/app/deals`) has only List/Create/Edit pages.
Inspecting a deal means opening the Edit form, which is a mutation surface, not
a read view. There is no read-only detail page.

The Deal `value` is masked in the UI (`[hidden]`) for the `free` role — in the
table column and on the Edit form. A View page whose infolist rendered the raw
`value` would be a masking bypass: the same masked-role viewer could read the
sensitive amount from the detail page.

## Design

Add a Filament v5 `ViewRecord` page, `ViewDeal`, mirroring `ViewTerritory`.

- `DealResource\Pages\ViewDeal extends ViewRecord` with
  `public function infolist(Schema $schema): Schema` showing `name`, `value`,
  `stage`, `close_date` (date), `probability` (`%`), `contact.name`,
  `user.name` (Owner), `pipeline.name`, `created_at` (dateTime).
- `value` is masked **exactly** like the `DealResource` table column:
  `TextEntry::make('value')->getStateUsing(fn (Deal $record) => AccessContext::shouldMaskFields() ? '[hidden]' : '$'.number_format((float) $record->value, 2))`.
  Same `AccessContext::shouldMaskFields()` gate as the table/edit surfaces, so the
  detail view is not a masking bypass.
- `DealResource::table()` gains `ViewAction::make()` in `recordActions()` (ahead
  of `EditAction`); `getPages()` registers `'view' => ViewDeal::route('/{record}')`.

### Tenant + owner scope (security)

`Deal` is `IsTenantModel` + `RestrictsToOwner`, so the record resolves through
the tenant and owner global scopes — the View page can only mount a deal in the
current team, and a `free`/`sales_rep` viewer can only reach deals they own. No
new access surface; the page inherits the resource gate.

## Testing

`tests/Feature/Filament/DealViewTest.php` (PHPUnit, `RefreshDatabase`), reusing
the `DealValueMaskingTest` tenant/role setUp (RolesSeeder, personal team,
`setPermissionsTeamId`, `assignRole`, `actingAs`, `setCurrentPanel('app')`,
`setTenant`) with an owner-scoped `Deal::factory()` (value 50000):

- admin mounts `ViewDeal` for a team deal → `assertOk`;
- `free` viewer sees `[hidden]` (and not `50,000`) for value;
- `manager` sees the real formatted `50,000`.

## Out of scope

- Editing/deleting from the View page — Edit page covers mutation.
- Unmasking `value` for `free` — masking is the whole point of this slice.
- Any new Deal field — infolist mirrors existing table columns only.

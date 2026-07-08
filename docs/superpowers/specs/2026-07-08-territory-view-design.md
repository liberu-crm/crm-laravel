# Territory read-only View page (design)

**Date:** 2026-07-08
**Status:** approved, implementing
**Epic:** G3 ABAC / territory
**Prerelease:** 1.6.0-rc.3

## Problem

The app-panel `TerritoryResource` (`/app/territories`) has only List/Create/Edit
pages. A team admin who wants to inspect a territory — its name, when it was
created, and which team members are assigned to it — has to open the Edit form,
which is a mutation surface, not a read view. There is no read-only detail page.

## Design

Add a Filament v5 `ViewRecord` page, `ViewTerritory`, mirroring the existing
`ViewAuditLog` infolist shape.

- `TerritoryResource\Pages\ViewTerritory extends ViewRecord` with
  `public function infolist(Schema $schema): Schema` showing `name`,
  `created_at` (dateTime), and the assigned members as
  `TextEntry::make('users.name')->label('Members')->listWithLineBreaks()->badge()`.
  Territory has no description/region column — only `name`, `team_id`, timestamps —
  so those are the only fields shown.
- `TerritoryResource::table()` gains `ViewAction::make()` in `recordActions()`
  (ahead of the existing `EditAction`); `getPages()` registers
  `'view' => ViewTerritory::route('/{record}')`.

### Tenant scope + gating (security)

`Territory` is `IsTenantModel`, so the record resolves through the tenant global
scope — the View page can only mount a territory belonging to the current team.
The page inherits the resource `canAccess()` gate (`SuperAdmin|Admin|Manager`),
so no new access surface is opened. Nothing about the gating or columns changes.

## Testing

`tests/Feature/Filament/TerritoryViewTest.php` (PHPUnit, `RefreshDatabase`),
reusing the `TerritoryResourceTest` setUp (RolesSeeder, admin + personal team,
`setPermissionsTeamId`, `assignRole('admin')`, `actingAs`,
`Filament::setCurrentPanel('app')`, `setTenant`): create a team-scoped Territory
with two attached members, then
`Livewire::test(ViewTerritory::class, ['record' => $territory->getKey()])->assertOk()`
and assert the name and a member name render.

## Out of scope

- Editing/deleting from the View page — Edit page already covers mutation.
- Any territory field beyond name/created_at/members — no other columns exist.
- Contact/record scoping by territory (later G3 slice), untouched here.

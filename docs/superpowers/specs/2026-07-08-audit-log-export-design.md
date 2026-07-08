# Export team audit log to CSV (design)

**Date:** 2026-07-08
**Status:** approved, implementing
**Epic:** F1 audit / compliance
**Prerelease:** 1.5.0-rc.3

## Problem

The app-panel Audit log (`AuditLogResource`, `/app/audit-log`) is a read-only, team-scoped
view of the team's audit trail. Team admins doing compliance work need to pull that trail
out of the UI — into a spreadsheet for review, retention, or handing to an auditor. There is
currently no export; the only way out is copy/paste from a paginated table.

## Design

Use Filament's official export pipeline (already installed in `filament/actions`) — no
custom CSV writer.

- `App\Filament\Exports\AuditLogExporter extends Filament\Actions\Exports\Exporter`, bound to
  `AuditLog::class` via `protected static ?string $model`. `getColumns()` exports:
  `created_at`, `user.name` (label "By"), `action`, `auditable_type` (label "Subject"),
  `auditable_id`, `ip_address`, `description`. `getCompletedNotificationBody()` returns the
  standard Filament row-count summary.
- `AuditLogResource::table()` gains one header action:
  `->headerActions([ExportAction::make()->exporter(AuditLogExporter::class)])`. Nothing else
  on the resource changes (category filter, ViewAction, `canCreate() = false`, and the
  `SuperAdmin|Admin` `canAccess()` gate all stay).

### Tenant scope + gating (security)

The export inherits the resource's `getEloquentQuery()`, and `AuditLog` is `IsTenantModel`,
so the tenant global scope limits exported rows to the current team — the export cannot be
used to exfiltrate another team's trail. Reaching the button at all requires passing
`canAccess()` (admin / super-admin), so the export carries the same gate as the page. No
`modifyQueryUsing()` override is added that could bypass the scope.

## Testing

`tests/Feature/Filament/AuditLogExportTest.php` (PHPUnit, `RefreshDatabase`), mirroring the
setUp of `AppAuditLogResourceTest` (RolesSeeder, admin + personal team, `setPermissionsTeamId`,
`assignRole('admin')`, `actingAs`, `Filament::setCurrentPanel('app')`, `setTenant`):

1. `AuditLogExporter::getColumns()` is non-empty and its column names include the seven
   expected fields (and exclude `changes`).
2. `Livewire::test(ListAuditLogs::class)->assertOk()->assertTableHeaderActionsExistInOrder(['export'])`
   — the list page mounts and the export header action is wired up.

No full async round-trip: Filament exports run as a queued job (`ExportCsv` batch), which is
out of scope to drive here — the two assertions above prove the wiring without the queue.

## Out of scope

- **`changes` column** — the audit `changes` cast is a nested array; it does not flatten into
  a single CSV cell cleanly. Left out of the export; revisit with a JSON-encoded column if a
  flat serialisation is ever needed.
- The async export job round-trip (batch completion, file assembly, download notification) —
  covered by Filament's own test suite, not re-tested here.
- XLSX-specific styling / custom formats — the default `Csv`/`Xlsx` formats are accepted as-is.

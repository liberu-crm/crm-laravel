# F4 phase-2 — Audit team role changes (design)

**Date:** 2026-07-08
**Status:** approved, implementing
**Epic:** F4 per-team RBAC, phase 2. Follows #497 (team-admin role UI).

## Problem

#497 lets a team admin change members' roles, but the change leaves **no audit trail** — a
security/governance gap (who granted whom which role, when).

## Fix (reuse #490 audit infra + #491 browse pattern)

1. `TeamManagementService::changeTeamRole` records a `team.role_changed` audit via
   `AuditLogService::record(action, description, ?auditable)` (#490): auditable = the member,
   description = `"Changed {email} from {old} to {new}"`. `team_id` auto-stamps (AuditLog is
   IsTenantModel, tenant set on the app panel). `AuditLogService` is resolved inline via
   `app(AuditLogService::class)` — no constructor change to `TeamManagementService`, so
   `new TeamManagementService` callers are untouched. `AuditLogService::record` already guards
   `Auth::check()`, so the pure-unit reject test (no actingAs) is unaffected.
2. `App\Filament\App\Resources\TeamRoleLogResource` (app panel, model `AuditLog`, read-only):
   `getEloquentQuery` = `parent()->where('action', 'like', 'team.%')` (parent applies AuditLog's
   IsTenantModel team scope → current team only). `canAccess` → admin / super_admin. Columns:
   created_at / by (user.name) / action / description. Nav group "Team". Mirrors
   `PortalAccessLogResource` (#491).

## Security / tenancy

Team-scoped throughout (AuditLog IsTenantModel + the `team.%` filter). A team admin sees only
their own team's role-change history; the global super_admin `AuditLogResource` still shows
everything.

## Testing (TDD, PHPUnit sqlite → MySQL-verify)

1. `changeTeamRole` records a `team.role_changed` AuditLog (auditable = the member, `team_id`
   stamped to the team).
2. `TeamRoleLogResource` lists the team's `team.%` entries and **excludes** a `portal.%` entry
   and another team's entry.
3. `canAccess`: admin ✓, sales_rep ✗.

phpstan 0-new, MySQL 8.4-verified, pint clean. Inline TDD, one PR to `main`.

## Out of scope

Old/new role diff payload beyond the description string, export, auditing member invite/remove
(no such flows yet), a combined portal+team audit view.

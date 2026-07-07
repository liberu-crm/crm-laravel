# G_5 slice 12 — Browse the portal audit trail (design)

**Date:** 2026-07-07
**Status:** approved, implementing
**Epic:** G_5 customer portal, slice 12. Surfaces the `portal.*` audit entries (#490) to the
team's own staff.

## Problem

Invite/revoke now write audit entries (#490), but the only place to read them is the **admin**
panel's `AuditLogResource` — super_admin, global. A team's own managers can't see who invited or
revoked *their* customers.

## Decision

A team-scoped, read-only **`PortalAccessLogResource`** on the **app** panel. `AuditLog` is
`IsTenantModel`, so on the tenant-scoped app panel it already filters to the current team; the
resource additionally narrows to `action like 'portal.%'` so it shows only portal grants/revokes,
not unrelated audit noise.

## Architecture

- `App\Filament\App\Resources\PortalAccessLogResource` over `AuditLog`, list-only, read-only
  (`canCreate = false`, no create/edit/delete/view pages).
- `getEloquentQuery()` → `parent()->where('action', 'like', 'portal.%')` (parent applies the
  IsTenantModel team scope).
- `canAccess()` gates to management roles (`super_admin` / `admin` / `manager`) — the same people
  who manage access; sales_rep / free / customer are denied.
- Columns: timestamp, actor (`user.name`), action badge, description.

## Testing (TDD)

- A manager sees their team's `portal.invited` / `portal.revoked` entries; a non-portal action
  (`login`) and another team's portal entry are excluded.
- `canAccess()` is true for a manager, false for a sales_rep.
- MySQL 8.4 verify; phpstan 0-new; pint clean.

## Out of scope (ceilings)

No per-entry detail/diff view (list only); no export; covers `portal.*` actions only; the
super_admin global view remains the admin `AuditLogResource`.

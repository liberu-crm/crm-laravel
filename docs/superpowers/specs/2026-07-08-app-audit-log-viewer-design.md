# App-panel audit log viewer (design)

**Date:** 2026-07-08
**Status:** approved, implementing
**Epic:** F1 audit / compliance. Prerelease `1.2.0-rc.1`.

## Problem

`AuditLog` (IsTenantModel, `team_id`) records everything — record CRUD (`created`/
`updated`/`deleted` with `auditable_type`/`changes`), `team.*`, `portal.*`, `auth.*`/`login.*`.
The only full viewer is `AuditLogResource` on the **admin** panel (global, super_admin). Team
admins on the **app** panel have only two narrow views — `TeamRoleLogResource` (`team.%`) and
`PortalAccessLogResource` (`portal.%`). They cannot see record changes or logins for their own
team.

## Design

A read-only `AuditLogResource` on the **app** panel — the team's full audit trail. `AuditLog` is
IsTenantModel, so the tenant global scope already limits rows to the current team; no manual
scoping needed (mirrors `TeamRoleLogResource`).

- **Access:** Admin / SuperAdmin only (the full trail is more sensitive than the manager-visible
  portal log). `canCreate() = false`; list page only.
- **Columns:** `created_at`, `user.name` (By), `action` (badge), `auditable_type` (Subject —
  class basename), `description` (wrap, searchable). Default sort `created_at desc`.
- **Category filter:** a `SelectFilter` mapping a category to a query — Record changes
  (`created`/`updated`/`deleted`), Team (`team.%`), Portal (`portal.%`), Auth (`auth.%`/`login%`).
  This is what makes the catch-all list usable.

## Testing (TDD)

1. Team admin sees only their own team's entries (tenant isolation) — an own-team entry visible,
   another team's entry not.
2. `canCreate()` is false (read-only).
3. A non-admin (sales_rep) fails `canAccess()`; an admin passes.
4. The "Record changes" category filter narrows to `created/updated/deleted`, hiding `team.*`.

phpstan 0-new, MySQL 8.4-verified, pint clean.

## Out of scope

A per-entry detail/diff view of `changes` (the admin panel has one; add to the app panel when a
team admin needs field-level diffs). Export. Retention/purge policy.

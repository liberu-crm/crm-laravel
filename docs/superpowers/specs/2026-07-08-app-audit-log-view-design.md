# App-panel audit log detail view (design)

**Date:** 2026-07-08
**Status:** approved, implementing
**Epic:** F1 audit / compliance. Prerelease `1.3.0-rc.1`.

## Problem

The app-panel `AuditLogResource` (PR #527) is list-only — a team admin can scan the
trail but cannot open a single entry. The field-level `changes` diff (the array cast on
`AuditLog`) has nowhere to render, so record-CRUD detail is invisible on the app panel.
This was left explicitly out of scope in the app-audit-log-viewer design; a team admin
now needs field-level diffs.

## Design

Add a read-only `ViewRecord` page to the existing app-panel `AuditLogResource`.

- **Access / scope:** unchanged. The record resolves through the tenant-scoped resource
  query (AuditLog is IsTenantModel), so an admin only ever opens their own team's entry.
  Access stays Admin / SuperAdmin via the resource's `canAccess()`; `canCreate()` stays
  false. ViewRecord is inherently read-only.
- **Entry point:** a `ViewAction` in the table's `recordActions`, plus a `view` page
  registered in `getPages()`.
- **Infolist:** `created_at` (dateTime), `user.name` (By), `action`, `auditable_type`
  (Subject), `auditable_id`, `ip_address`, `description`, and the `changes` array rendered
  with `KeyValueEntry` (present in this Filament version) — no custom formatting needed.

## Testing (TDD)

1. The view page mounts for an admin opening an own-team entry (`assertOk`).
2. The page shows the entry's `action` and `description`.

## Out of scope

Export. A richer old/new column layout for nested `changes` (KeyValueEntry renders the
nested value as-is). Retention/purge policy.

# F4 — custom per-team roles (design)

**Date:** 2026-07-08
**Status:** approved, implementing slice 1
**Epic:** F4 RBAC. Prerelease `1.10.0-rc.1` (first of the F4 batch).

## Problem

Teams can only assign the four fixed roles (admin / manager / sales_rep / free). A team can't
define its own roles with a tailored permission set. spatie is in teams mode and the `roles` table
already carries `team_id`, so team-scoped custom role *definitions* are natively supported (system
roles are the `team_id = null` global definitions seeded from the `Role` enum).

## Epic decomposition

1. **Custom team-role CRUD + permissions (this slice).** A team admin creates/edits/deletes roles
   scoped to their team (`roles.team_id = <team>`) and picks each role's permissions.
2. **Assign members to custom roles.** Extend the team-member role picker to offer the team's
   custom roles alongside the four fixed ones.
3. **Enforcement.** Audit gates/policies so custom-role permissions actually grant access
   (permission checks, not hard-coded role-name checks).

## Slice 1 design

`TeamRoleResource` on the **app** panel, model `Spatie\Permission\Models\Role`.

- **Scope.** `$isScopedToTenant = false` (Role has no `team` ownership relation); `getEloquentQuery`
  returns `Role::where('team_id', <tenant id>)` — only this team's custom roles. System roles
  (`team_id = null`) and other teams' roles are excluded.
- **Access.** Admin / SuperAdmin only (mirrors `TeamMemberResource`).
- **Create/Edit.** Fields: `name` (required; rejected if it collides with a `Role` enum value) and
  `permissions` (a `CheckboxList` limited to the **grantable** set). On save the role is stamped
  `team_id = <tenant>`, `guard_name = 'web'`, and permissions are synced.
- **Anti-escalation (security).** The grantable permission set EXCLUDES role/permission/user
  management (`manage_roles`, `manage_permissions`, `manage_users`, and any shield `*_role` /
  `*_permission` resource permission). Enforced server-side on save (the submitted permissions are
  intersected with the grantable set), not only in the UI — so a crafted request can't grant a
  management permission. This stops a team admin from minting a role that escalates privilege.

## Testing (TDD)

1. An admin creates a custom role with a grantable permission → a `Role` exists with `team_id` =
   the team and the permission attached.
2. The list shows only the current team's custom roles (not system roles, not another team's).
3. A non-admin (sales_rep) fails `canAccess`.
4. Anti-escalation: a create submitting `manage_roles` persists the role WITHOUT that permission
   (server-side clamp), and `manage_roles` is not offered as an option.

phpstan 0-new, MySQL 8.4-verified, pint clean.

## Out of scope (later slices)

Assigning members to custom roles (slice 2); permission enforcement audit (slice 3); role cloning;
per-role member counts.

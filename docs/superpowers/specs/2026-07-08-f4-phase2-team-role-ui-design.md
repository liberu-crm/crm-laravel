# F4 phase-2 — Team member role management UI (design)

**Date:** 2026-07-08
**Status:** approved, implementing
**Epic:** F4 per-team RBAC. The self-service role UI deferred from #465 (mechanism-only).

## Problem

#465 shipped spatie teams-mode RBAC (per-team roles, `setPermissionsTeamId` per request,
team-scoped `hasRole`). But there is **no UI for a team admin to manage their own team's
member roles** — role assignment only happens implicitly at registration
(`CreateNewUser` → `Free`) or via `TeamManagementService`. This is F4 phase 2.

## Scope (slice 1)

Assign the **existing** five roles to team members from the app panel. Creating custom
per-team roles/permissions stays out (a later slice / G3).

## Architecture

### `App\Filament\App\Resources\TeamMemberResource` (app panel, model `User`)

- **`getEloquentQuery()`** = `User::whereIn('id', Filament::getTenant()->allUsers()->pluck('id'))`
  — the current tenant team's members (owner + members). List-only (index page only).
- **`canAccess()`** → team `admin` or `super_admin` (`hasRole([Admin, SuperAdmin])`, team-scoped
  on the app panel). Managers/reps/free cannot manage roles.
- **Table:** name, email, current team-role badge (`getStateUsing` →
  `$record->getRoleNames()->first()`; the request's permission team is the tenant, so this is
  the team-scoped role).
- **Change-role row action:** a `Select` of **`admin`/`manager`/`sales_rep`/`free` only** →
  `TeamManagementService::changeTeamRole($member, tenant, $role)`. `super_admin` (global) and
  `customer` (portal) are never offered — no escalation out of the team.
- **Self-guard:** the action is hidden on the acting admin's own row
  (`visible(fn ($record) => $record->id !== Auth::id())`) — a lone admin can't self-demote into
  lockout.

### `TeamManagementService::changeTeamRole(User $user, Team $team, Role $role): void`

Guards `$role ∈ {admin, manager, sales_rep, free}` (throws otherwise — defense in depth behind
the Select). Then, in the team's permission context, removes any of the four team roles the
user holds and assigns the new one — **never touches global `super_admin`/`customer`**:

```php
if (! in_array($role, self::TEAM_ROLES, true)) throw new InvalidArgumentException(...);
setPermissionsTeamId($team->getKey());
foreach (self::TEAM_ROLES as $r) { $user->removeRole($r->value); } // no-op if absent
$user->assignRole($role->value);
```

## Security / tenancy

Team-scoped throughout: the resource query and role writes are bound to the current tenant
team; a team admin only sees and changes their own team's members. `super_admin`/`customer`
are unassignable. Self-demotion blocked.

## Testing (TDD, PHPUnit sqlite → MySQL-verify)

1. A team admin sees only their own team's members (scoping; a member of another team is
   excluded).
2. Changing a member to `manager` writes the team-scoped role and does **not** leak to another
   team (member has `manager` in team A context, not in team B).
3. `changeTeamRole` **rejects** a non-team role (`super_admin`) — throws.
4. `canAccess`: team admin ✓, manager ✗.
5. The change-role action is **hidden on the admin's own row** (self-guard).

phpstan 0-new, MySQL 8.4-verified, pint clean. Inline TDD, one PR to `main`.

## Out of scope (later slices)

Custom per-team roles/permissions, bulk role changes, invite-with-role, last-admin protection
beyond the self-guard, territory/field-level ABAC (G3), a Shield permission-matrix UI.

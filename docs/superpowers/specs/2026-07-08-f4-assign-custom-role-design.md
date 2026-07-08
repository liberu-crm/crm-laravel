# F4 assign custom role

- Date: 2026-07-08
- Epic: F4 RBAC slice 2
- Prerelease: 1.10.0-rc.2

## Problem

`TeamManagementService::changeTeamRole()` only assigns the four fixed team
roles (`admin`, `manager`, `sales_rep`, `free`). Slice 1 gave team admins a
`TeamRoleResource` to create per-team **custom** roles
(`Spatie\Permission\Models\Role` with `team_id = <team>`), but nothing lets an
admin actually put a member on one — the member table's Change role action
still offers only the fixed four.

## Design

New `TeamManagementService::assignCustomRole(User, Team, SpatieRole)`, additive
and modelled on `changeTeamRole` (the fixed-role path is untouched):

- Reject if the role's `team_id` is not this team's key (compared as ints, so a
  string `team_id` off a retrieved row can't slip a cross-team role through) —
  `InvalidArgumentException`.
- Reject if the target user is the team owner (owner role is immutable).
- `setPermissionsTeamId($team)`, capture the previous role name for the audit.
- Remove any fixed team role AND any of this team's custom roles the member
  holds, then `assignRole($customRole->name)`.
- Audit `team.role_changed` via `AuditLogService::record` (same shape as
  `changeTeamRole`).

`TeamMemberResource` Change role action: the `Select` options become a closure
merging the four fixed roles with the tenant's custom roles
(`Role::where('team_id', tenant)->pluck('name','name')`). The handler branches
on the selected value — one of the four fixed enum values goes to
`changeTeamRole`; anything else is looked up as a tenant-scoped custom role and
routed to `assignCustomRole`. Existing visibility guards (self / owner rows
hidden) and the success notification are unchanged.

## Testing

`tests/Feature/Filament/TeamMemberCustomRoleTest.php` (PHPUnit +
`RefreshDatabase`), seeded with `RolesSeeder`, acting as an admin on the `app`
panel with the tenant set:

- `assignCustomRole` gives the member the custom role and drops the prior fixed
  role (checked under `setPermissionsTeamId`).
- A custom role whose `team_id` is a different team throws
  `InvalidArgumentException`.
- An `audit_logs` row `team.role_changed` is written for the member.

## Out of scope

- No change to `changeTeamRole`, `addTeamMember`, `assignTeamRole`, the `Role`
  enum, or `TeamRoleResource`.
- Bulk custom-role assignment (the toolbar `setRole` action stays fixed-only).
- Custom-role name collisions with a fixed enum value (a custom role named e.g.
  `admin` is shadowed by the fixed option and treated as the fixed role).

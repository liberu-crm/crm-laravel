# F4 phase-2 — add a team member with a role (design)

**Date:** 2026-07-08
**Status:** approved, implementing
**Epic:** F4 per-team RBAC, phase 2. Follows #497 (role UI) / #498 (audit) / #499 (owner guard).

## Problem

`TeamMemberResource` (#497) lets a team admin **re-role existing** members, but there is no way
to **add** a member to the team from that surface — you can only change roles of people already
there.

## Fix

An **Add member** header action on `ListTeamMembers`:

- Form: `email` (required) + `role` Select (the four team roles — `admin` / `manager` /
  `sales_rep` / `free`; `super_admin` and `customer` are never offered).
- On submit: resolve the `User` by email. If none → a danger notification (no user creation
  here — that is onboarding/SSO territory). Otherwise
  `TeamManagementService::addTeamMember($user, $tenant, $role)`.

`TeamManagementService::addTeamMember(User, Team, Role)`:
- attaches the user to the team if not already a member, then calls the existing
  `changeTeamRole(...)` — so the role is validated (∈ team roles), the **owner guard** (#499)
  and the **audit** (`team.role_changed`, #498) both apply for free, and the assignment is
  team-scoped.

Access is inherited from the resource (`canAccess` = admin / super_admin, #497).

## Testing (TDD)

1. An admin adds an existing user by email with role `manager` → the user is a team member and
   holds `manager` in the team.
2. Adding an **unknown** email adds no one (danger path).
3. The added member then appears in the team-scoped resource list.

phpstan 0-new, MySQL 8.4-verified, pint clean. Inline TDD, one PR to `main`.

## Out of scope

Creating a brand-new user (onboarding/SSO does that), Jetstream email invitations, bulk add,
removing a member (a separate action), choosing a role at Jetstream-invitation acceptance.

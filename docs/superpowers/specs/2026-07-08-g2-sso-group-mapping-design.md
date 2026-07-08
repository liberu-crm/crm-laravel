# G2 SSO — IdP group → team role mapping (design)

**Date:** 2026-07-08
**Status:** approved, implementing
**Epic:** G2 SSO. Prerelease `0.1.0-alpha.3`.

## Problem

SSO login resolves a user but not their **role** — new members land as `Free` (JIT) and
existing members keep whatever they had. Enterprises want the **IdP** to drive team roles via a
`groups` claim, so access is centrally managed.

## Design

- New column `sso_connections.role_mappings` (JSON, nullable): `{ "idp-group": "team_role" }`.
- `SsoConnection::roleForGroups(array $groups): ?Role` — the first mapping whose group is present
  in `$groups` **and** whose value is one of the four team roles (`admin` / `manager` /
  `sales_rep` / `free`); `super_admin` / `customer` mappings are ignored. Null if none.
- `SsoLoginController::callback`, after the user is resolved (member or JIT): read the `groups`
  claim; if a mapping matches and the user does **not** already hold that role, sync it via
  `changeTeamRole` (so the role guard, **owner guard** #499, and **audit** #498 apply). The
  "already holds" check avoids re-roling + auditing on every login. Owner rows are left as-is
  (the guard throws → caught).
- `SsoConnectionResource` form: a `KeyValue` field for the mappings.

## Testing (TDD, `Http::fake`)

1. `roleForGroups`: returns the mapped team role for a present group; null for no match;
   **ignores a non-team role** mapping (e.g. `super_admin`).
2. A JIT SSO login whose `groups` claim maps to `manager` → the provisioned user holds
   `manager` (not the `Free` default).
3. No matching group → the JIT user keeps `Free` (no group override).

phpstan 0-new, MySQL 8.4-verified, pint clean.

## Versioning

`VERSION` → `0.1.0-alpha.3`; GitHub prerelease `v0.1.0-alpha.3`.

## Out of scope

Configurable groups-claim name (assumes `groups`), removing roles when a group is dropped
(only additive/override on match), nested/hierarchical groups, SAML attribute mapping.

# G_5 slice 6 — Revoke portal access (design)

**Date:** 2026-07-07
**Status:** approved, implementing
**Epic:** G_5 customer portal, slice 6. The admin complement to invite (#482): staff can take a
customer's portal access away.

## Problem

Onboarding (#482) grants portal access by giving a `User` the global `customer` role. There is
no way to take it back — a departed or abusive customer keeps access. Staff need a revoke.

## Decision

**Revoke = remove the global `customer` role.** `canAccessPanel('portal')` already keys on
`hasRole(Customer)`, so removing the role denies portal access on the next request with no new
gate. The `User` row, its `current_team_id`, and all its tickets/documents are preserved
(revocation is not deletion); re-inviting (#482) restores access.

## Architecture

### 1. `App\Support\PortalCustomer` (shared helper)

- `forEmail(?string $email): ?User` — the `User` with that email **iff** it holds a global
  (`team_id = null`) `customer` role. Checked with a direct `model_has_roles` query (config-
  driven, mirroring `InvitePortalCustomer::emailBelongsToStaff`) so it does **not** mutate the
  request's permission-team context.
- `existsForEmail(?string $email): bool`.

### 2. `App\Actions\Portal\RevokePortalCustomer`

`__invoke(Contact $contact): void`:
- Resolve `PortalCustomer::forEmail($contact->email)`; if none, throw `PortalOnboardingException`
  ("not a portal customer" — nothing to revoke).
- `setPermissionsTeamId(null); $user->removeRole(Role::Customer->value)` (mirrors the invite
  action's global-role handling).

### 3. `ContactResource` action

A **"Revoke portal access"** row action (app panel, staff), **visible only when**
`PortalCustomer::existsForEmail($record->email)`. `requiresConfirmation`; catches
`PortalOnboardingException` → notification. Additive — the "Invite to portal" action is left as
is (so it still doubles as a resend), and revoke simply appears alongside for active customers.

## Testing (TDD, PHPUnit + sqlite, then MySQL-verify)

- **Revokes:** a provisioned portal customer loses the `customer` role; the `User` row and its
  `current_team_id` remain.
- **Locked out:** after revoke, the (former) customer gets 403 at `/portal/tickets`.
- **Refuses non-customer:** a contact whose email maps to a staff user or no user →
  `PortalOnboardingException`; that user's other roles are untouched.
- **ContactResource action:** the revoke row action removes the role.
- MySQL 8.4 verify; phpstan 0-new; pint clean.

## Out of scope (ceilings)

No audit log of revoke/invite events; an already-authenticated session is denied on its next
panel request (not force-logged-out mid-session); no bulk revoke; no "disabled but role kept"
state — revoke is role removal.

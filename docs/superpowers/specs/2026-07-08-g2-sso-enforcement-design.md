# G2 SSO — slice 4: SSO enforcement (design)

**Date:** 2026-07-08
**Status:** approved, implementing
**Epic:** G2 SSO. Builds on #500–#502. Lets a team **require** SSO for its members.

## Problem

SSO is available but optional — an SSO-enforced organisation still lets its members log in
with a password. We need a team to be able to mandate SSO.

## Why middleware, not a Fortify hook

The app panel's `->login()` is disabled, so team members log in via Fortify `/login` — but a
member could also authenticate at `/admin/login` (Filament, same web guard) and then reach
`/app`. A Fortify-only block is therefore **bypassable**. Enforcing in **middleware on the app
panel** catches an SSO-required user no matter how they authenticated.

## Design

- New column `sso_connections.require_sso` (bool, default false).
- `App\Support\SsoEnforcement::enforcingTeamFor(User): ?Team` — the user's team (owned or
  member) that has an **enabled** connection with `require_sso = true`, else null.
- `App\Http\Middleware\EnsureSsoWhenRequired` (on the app panel `authMiddleware`): if the
  authenticated user has an enforcing team **and** the session lacks the `sso_authenticated`
  flag → `Auth::logout()` and redirect to `sso.redirect` for that team. So any non-SSO session
  (password login, Filament login) is bounced into the IdP.
- `SsoLoginController::callback` sets `session(['sso_authenticated' => true])` on success, so a
  genuine SSO login is not bounced.
- `SsoConnectionResource` form gains a `require_sso` Toggle.

## Security

Enforcement runs on every `/app` request, so it can't be sidestepped by the login route used.
`require_sso` only bites when the connection is `enabled`. A genuine SSO login carries the
session flag; everything else is logged out and redirected to the IdP.

## Testing (TDD)

1. An SSO-required user hitting `/app` **without** the SSO session flag → logged out +
   redirected to `sso.redirect`.
2. An SSO-required user **with** `sso_authenticated` in the session → not bounced.
3. A user with **no** enforcing team → `/app` proceeds normally.
4. `require_sso = true` but `enabled = false` → not enforced.
5. `SsoLoginController::callback` sets `sso_authenticated` (a real SSO login survives
   enforcement).

phpstan 0-new, MySQL 8.4-verified, pint clean. Inline TDD, one PR to `main`.

## Out of scope

Enforcing the admin panel (super_admins aren't the target; a regular member can't access it),
per-user SSO exemptions, break-glass password bypass, id_token/JWKS validation, SAML.

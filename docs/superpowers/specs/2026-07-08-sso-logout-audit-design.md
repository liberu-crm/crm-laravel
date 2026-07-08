# SSO logout audit (design)

**Date:** 2026-07-08
**Status:** approved, implementing
**Epic:** G2 SSO / F1 audit. Prerelease `1.5.0-rc.2`.

## Problem

SSO login is already audited (`SsoLoginController` writes an entry and sets the
`sso_authenticated` session flag). There is no matching logout trail, so the audit log shows an
SSO sign-in with no corresponding sign-out.

## Design

A `LogSsoLogout` listener on `Illuminate\Auth\Events\Logout`. On logout it `session()->pull()`s
the `sso_authenticated` flag (read-and-clear); if it was set and the event carries a user, it
records an `auth.sso_logout` audit entry via `AuditLogService::log()` — the same method
`LogSuccessfulLogin` uses (attribution from `Auth::id()` / `request()->ip()`; `team_id`
auto-stamped by `IsTenantModel`, inert when no tenant is bound).

Registered in `EventServiceProvider::$listen` (replacing the commented `Logout` stub). The
pre-existing generic-`logout` closure is left as-is — the two entries use distinct actions and
don't collide. This works because `SessionGuard::logout()` fires the event *before* nulling the
user and only clears the auth session keys, so both the flag and `Auth::id()` are still readable.

## Testing (TDD)

1. With `sso_authenticated` set and a logged-in user, logging out writes an `auth.sso_logout`
   AuditLog row for that user.
2. Without the flag, logging out writes no `auth.sso_logout` row.

## Out of scope

IdP-initiated single logout (redirect to the OIDC `end_session_endpoint`) — a separate slice
touching the logout response.

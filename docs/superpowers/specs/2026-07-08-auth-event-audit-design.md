# Auth event audit (design)

**Date:** 2026-07-08
**Status:** approved, implementing
**Epic:** F1 audit / security. Prerelease `1.6.0-rc.1`.

## Problem

Login and logout are already audited. Two security-relevant auth events leave no
trail: a **failed login** against a known account (credential-stuffing / brute-force
signal) and a **password reset** (account takeover signal). Neither is recorded.

## Design

Two listeners mirroring `LogSuccessfulLogin`, registered in
`EventServiceProvider::$listen`:

- `LogFailedLogin` on `Illuminate\Auth\Events\Failed`. When `$event->user` is set
  (wrong password on a real account) it writes an `auth.failed` AuditLog for that
  user. When `$event->user` is null (unknown email) it returns without writing.
- `LogPasswordReset` on `Illuminate\Auth\Events\PasswordReset` — `$event->user` is
  always present; writes an `auth.password_reset` AuditLog for that user.

Both `AuditLog::create()` directly rather than via `AuditLogService::log()`: the
service attributes from `Auth::id()`, but on a `Failed` event no one is
authenticated, so `user_id` would be null and violate the NOT NULL column. The user
comes from the event instead. `team_id` is auto-stamped by `AuditLog`'s
`IsTenantModel` hook (inert when no tenant is bound); `ip_address` falls back to
`'0.0.0.0'`.

## Testing (TDD)

1. `Failed` with a real user → an `auth.failed` row for that user.
2. `Failed` with `user = null` → no `auth.failed` row.
3. `PasswordReset` with a user → an `auth.password_reset` row for that user.

## Out of scope

Storing **unknown-email** failed attempts. `audit_logs.user_id` is NOT NULL and an
unattributable probe has low compliance value; capturing anonymous brute-force
volume belongs to rate-limiting/WAF, not the per-user audit trail.

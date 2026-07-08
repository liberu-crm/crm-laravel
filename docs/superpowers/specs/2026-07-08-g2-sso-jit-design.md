# G2 SSO — slice 3: JIT provisioning (design)

**Date:** 2026-07-08
**Status:** approved, implementing
**Epic:** G2 SSO. Builds on #501 (OIDC login). Completes "a new hire's first SSO login just
works".

## Problem

#501 logs in **existing** team members; an authenticated email with no matching team member
is denied. So a new employee whose IdP account exists but who was never manually added to the
CRM team can't get in.

## Fix — opt-in, domain-restricted, least-privilege JIT

Two new columns on `sso_connections`:
- `allow_jit` (bool, default **false**) — JIT is off unless a team admin turns it on.
- `allowed_domain` (nullable string) — when set, only userinfo emails ending in
  `@{allowed_domain}` are provisioned (guards against the IdP asserting arbitrary emails).

On callback, when the email is **not** already a team member:
- **Deny (403)** unless `allow_jit` is true **and** (`allowed_domain` is null **or** the email
  matches it).
- Otherwise **provision** via `App\Actions\Sso\ProvisionSsoUser`:
  - `firstOrCreate` the `User` by email (name from userinfo or the email local-part; a random
    unusable password — SSO only; `email_verified_at = now()`, the IdP verified it),
  - attach to the team if not already a member,
  - assign the **least-privilege** default role `Role::Free` (via
    `TeamManagementService::assignTeamRole`) only when the user has no team role yet.
- Then log in exactly as #501 (session regenerate, `current_team_id`, redirect).

Idempotent: a repeat JIT login reuses the user, doesn't double-attach or re-role.

## Components

- Migration: add `allow_jit` + `allowed_domain` (guarded `Schema::hasColumn`).
- `SsoConnection`: `allow_jit`/`allowed_domain` fillable, `allow_jit` boolean cast.
- `SsoConnectionResource` form: `allow_jit` Toggle + `allowed_domain` TextInput.
- `App\Actions\Sso\ProvisionSsoUser::__invoke(Team, string $email, ?string $name): User`.
- `SsoLoginController::callback` — JIT branch when membership check fails.

## Security

JIT is **off by default**; when on, the optional domain allowlist bounds who can be
auto-created; provisioned users land as `Free` (never admin). The `state` CSRF, server-side
token exchange, and session regeneration from #501 are unchanged.

## Testing (TDD, `Http::fake()`)

1. `allow_jit` **off** + non-member email → 403 (unchanged).
2. `allow_jit` **on** + new email (matching `allowed_domain`) → user created, attached, role
   `Free`, logged in.
3. `allow_jit` on + email domain **not** in `allowed_domain` → 403, guest.
4. `allow_jit` on + `allowed_domain` **null** + any email → provisioned.
5. Idempotent: an existing global user (not in the team) with JIT on → attached once + logged
   in; no duplicate user.

phpstan 0-new, MySQL 8.4-verified, pint clean. Inline TDD, one PR to `main`.

## Out of scope

Configurable default role (fixed to `Free`), group/role mapping from IdP claims, deprovision on
IdP removal, multiple allowed domains, id_token/JWKS validation (still deferred).

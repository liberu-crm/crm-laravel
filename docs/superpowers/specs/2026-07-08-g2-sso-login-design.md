# G2 SSO — slice 2: OIDC login flow (design)

**Date:** 2026-07-08
**Status:** approved, implementing
**Epic:** G2 SSO. Builds on #500 (per-team SSO connection). The payoff slice — a team's
users actually sign in via their IdP.

## Scope

Authenticate an **existing team member** through the team's OIDC connection. Just-in-time
user provisioning (create the user on first login) is **slice 3** — here an email with no
matching team member is denied.

## Flow (OIDC authorization-code + userinfo)

Raw OIDC over Laravel's HTTP client — no id_token crypto to hand-roll, no new dependency. The
email comes from the IdP's **userinfo** endpoint, fetched server-side with an access token the
IdP issued to our authenticated token request.

- `GET /sso/{team}/redirect` → `SsoLoginController@redirect`
  - Load the team's `SsoConnection` (must be `enabled`, else 404).
  - Discover endpoints from `{issuer}/.well-known/openid-configuration` (cached).
  - Generate a random `state`, store it (+ team id) in the session (**CSRF**).
  - Redirect to `authorization_endpoint` with `client_id`, `redirect_uri` (= the callback
    route), `response_type=code`, `scope=openid email profile`, `state`.
- `GET /sso/{team}/callback` → `SsoLoginController@callback`
  - **Verify** `state` matches the session (else 403); forget it.
  - Exchange `code` at `token_endpoint` (`grant_type=authorization_code`, `client_id`,
    `client_secret`, `redirect_uri`) → `access_token`.
  - `GET userinfo_endpoint` with the bearer token → `email`.
  - Find the `User` by email; if they **belong to this team** (`belongsToTeam`) → `Auth::login`,
    **regenerate the session**, set `current_team_id`, redirect to `/app`. Otherwise **403**
    (no JIT in this slice).

## Components

- `App\Services\Sso\OidcClient` — `discover()` (cached), `authorizeUrl()`, `exchangeCode()`,
  `userinfo()`. All via `Http::` (fakeable). Throws `App\Exceptions\SsoException` on failure.
- `App\Http\Controllers\SsoLoginController` — `redirect` / `callback`.
- Routes in `web.php` (web/session middleware, **unauthenticated**): `sso.redirect`,
  `sso.callback`.

## Security

- `state` CSRF: minted on redirect, stored server-side (session), verified on callback.
- `client_secret` only ever sent server-to-server to the token endpoint (`client_secret_post`).
- Only existing **team members** are logged in (no JIT here); session regenerated on login.
- The `SsoConnection` is read with `withoutGlobalScope('tenant')` (login is pre-auth, no
  tenant context).

## Testing (TDD, PHPUnit + `Http::fake()`)

1. `redirect` → 302 to the `authorization_endpoint` carrying `client_id`/`state`/`redirect_uri`;
   the session holds the state.
2. `callback` with a valid code and a **matching team member** → authenticated + redirected.
3. `callback` with a **mismatched state** → 403, not authenticated.
4. `callback` whose userinfo email is **not a team member** → 403 (no JIT).
5. `redirect` for a **disabled/absent** connection → 404.

phpstan 0-new, MySQL 8.4-verified, pint clean. Inline TDD, one PR to `main`.

## Out of scope / ceilings (stated)

- **JIT provisioning** (create the user + team membership on first login) — slice 3.
- `id_token` signature / JWKS validation (we trust userinfo via the server-side token
  exchange) — a hardening slice later.
- `client_secret_basic` token auth (only `client_secret_post` here), PKCE, multiple providers,
  SSO-enforcement (require SSO for a team), SAML.

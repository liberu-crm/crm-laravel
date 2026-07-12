# SSO single-logout (SLO) — design (2026-07-10)

Today logout always clears the local session and redirects to `/login`; the IdP
session stays open. SLO ends the IdP session too. Two protocols, two slices:
OIDC RP-initiated logout, then SAML SP-initiated single-logout. Both reuse one
shared piece — a request-scoped holder that survives session invalidation.

## The timing crux (shared)

Filament's logout runs `auth()->logout()` → `session()->invalidate()` →
`app(LogoutResponse)`. By the time `LogoutResponse::toResponse` runs, the session
(and any `id_token` / SAML `NameID` we'd need) is gone. Fix: a listener on the
`Illuminate\Auth\Events\Logout` event — which fires *inside* `auth()->logout()`,
before invalidation — reads the session, builds the IdP logout URL, and stashes
it on a request-scoped `App\Support\SsoLogoutState` singleton. `LogoutResponse`
reads the holder: a URL → redirect there; otherwise the current `/login`.
`LogSsoLogout` (the `auth.sso_logout` audit) already listens to the same event —
unchanged.

## Slice 1 — OIDC RP-initiated logout

1. **Login persists the hint:** `SsoLoginController::callback` stores
   `sso_id_token` (the validated raw id_token) and keeps `sso_team` in the session
   (currently forgotten) — the `id_token_hint` and the connection lookup key.
2. **Discovery:** `OidcClient::discover` also surfaces `end_session_endpoint` —
   **optional** (don't throw when absent; fall back to local logout).
3. **Logout listener → holder:** on `Logout`, if `sso_id_token` + the team's
   connection has an `end_session_endpoint`, build
   `end_session_endpoint?id_token_hint=<id_token>&post_logout_redirect_uri=<url('/login')>`
   and set it on `SsoLogoutState`.
4. **`LogoutResponse`:** redirect to the holder's URL, else `/login`.

**Tests:** login stores `sso_id_token`/`sso_team`; discovery parses (and tolerates
a missing) `end_session_endpoint`; logout of an OIDC session → 302 to the
`end_session_endpoint` with `id_token_hint` + `post_logout_redirect_uri`; non-SSO
logout → `/login`; SSO session whose IdP has no end-session endpoint → `/login`.

## Slice 2 — SAML SP-initiated single-logout

1. **Config:** migration adds `idp_slo_url` (Single Logout Service URL) to
   `saml_connections`; `SamlConnectionResource` exposes it. `SamlSettings` adds the
   IdP `singleLogoutService.url` and the SP `singleLogoutService.url`
   (`/saml/{team}/sls`).
2. **SP metadata:** `SamlMetadataController` advertises a `SingleLogoutService`
   (HTTP-Redirect, `/saml/{team}/sls`).
3. **Login persists logout keys:** `SamlLoginController::acs` stores the assertion's
   `NameID` (`sso_saml_nameid`), its `SessionIndex` (`sso_saml_session_index`), and
   `sso_team` — OneLogin needs them to build the `LogoutRequest`.
4. **Logout listener (SAML branch):** on `Logout`, if the session is SAML-
   established and the connection has `idp_slo_url`, build the `LogoutRequest`
   redirect URL via OneLogin `Auth::logout(returnTo, [], nameId, sessionIndex,
   stay: true)` and set it on `SsoLogoutState`.
5. **SLS endpoint:** `GET /saml/{team}/sls` (`saml.sls`, CSRF-exempt like the ACS)
   handles the IdP's `LogoutResponse` (SP-initiated) via OneLogin `processSLO()`,
   validating its signature; then completes local logout and redirects to
   `/login`. (IdP-initiated `LogoutRequest` handling is a non-goal for v1 — see
   below.)

**Tests:** ACS stores `NameID` + `SessionIndex`; SP metadata includes the
`SingleLogoutService`; logout of a SAML session → 302 to `idp_slo_url` with a
`SAMLRequest`; the SLS endpoint validates a signed `LogoutResponse` (reusing the
in-test IdP keypair / `SignsSamlResponses` approach) and finishes logout; a
bad-signature `LogoutResponse` → rejected.

## Enforcement / interaction

The `sso_authenticated` flag already distinguishes SSO sessions. The Logout
listener branches on which login keys are present (`sso_id_token` = OIDC,
`sso_saml_nameid` = SAML). A team uses one protocol, so only one branch fires.

## Security notes

- OIDC: `id_token_hint` proves the logout is for the authenticated session;
  `post_logout_redirect_uri` must be a registered URL (`/login`).
- SAML: the SLS `LogoutResponse` is validated (signature via `idp_x509_cert`) by
  OneLogin before we act on it — never log out on an unvalidated message.
- The SLS route is CSRF-exempt (cross-site IdP redirect), protected by the signed
  SAML message.

## Non-goals

- IdP-initiated logout (the IdP pushing a `LogoutRequest` to our SLS to kill the
  local session) — SP-initiated only for v1; the SLS handles the SP-initiated
  `LogoutResponse`. Add IdP-initiated later if needed.
- SP-signed SAML logout messages (leave room in the settings; add if an IdP
  requires it).

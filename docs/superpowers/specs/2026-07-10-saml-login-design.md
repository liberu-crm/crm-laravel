# SAML login flow — design (2026-07-10)

Complete the G2 SAML feature: the config + SP metadata shipped in v1.0 (#521);
this adds the actual login (SP-initiated AuthnRequest → IdP → ACS → signed-
assertion validation → login), reaching parity with the OIDC flow.

## Approach — mirror OIDC, reuse what exists

`SamlLoginController` parallels `SsoLoginController`. Reused as-is:
- `App\Actions\Sso\ProvisionSsoUser` — team-generic JIT (creates user, attaches
  to team, lands as `Free`).
- `TeamManagementService::changeTeamRole` — group→role mapping.
- The `sso_authenticated` session flag + `EnsureSsoWhenRequired` enforcement.

Only the SAML **protocol** layer is new, and it is delegated to a library — we
never hand-roll XML signature crypto.

### Dependency

`composer require onelogin/php-saml` (`OneLogin\Saml2`). A `SamlSettings` factory
builds OneLogin's per-request settings array from a team's `SamlConnection`:
- IdP: `entityId` ← `idp_entity_id`, `singleSignOnService.url` ← `idp_sso_url`,
  `x509cert` ← `idp_x509_cert`.
- SP: `entityId` ← `url('/saml/{team}/metadata')`, `assertionConsumerService.url`
  ← `url('/saml/{team}/acs')`, `NameIDFormat` = emailAddress. `authnRequestsSigned
  = false`, `wantAssertionsSigned = true` — matches the shipped SP metadata, so no
  SP signing key is needed.

Login is pre-auth (no tenant context), so the connection is read with
`withoutGlobalScope('tenant')` + `where('enabled', true)`, exactly as OIDC does.

## Slices (one PR each, sequential)

### Slice 1 — redirect / AuthnRequest
`SamlLoginController::redirect(Team $team)` + `GET /saml/{team}/login`
(`saml.login`). Build the AuthnRequest via OneLogin `Auth::login(returnTo, ...,
stay=true)` to get the redirect URL; store the request id in the session
(`saml_request_id`) for `InResponseTo` validation. Redirect to the IdP.
- **Test:** hitting the route 302-redirects to `idp_sso_url` with a `SAMLRequest`
  query param; a team with no enabled SAML connection → 404.

### Slice 2 — ACS + validation + login (existing members)
`SamlLoginController::acs(Team $team, Request)` + `POST /saml/{team}/acs`
(`saml.acs`, CSRF-exempt — it's a cross-site IdP POST, protected by the signed
assertion instead). OneLogin `processResponse(requestId)` validates the XML-DSig
signature against `idp_x509_cert`, the conditions/audience, `InResponseTo`, and
replay. On success: pull the NameID (email) + attributes. Find a user who
`belongsToTeam($team)`; if none → **deny (403)** (JIT is slice 3). Log in,
`session()->regenerate()`, set `sso_authenticated`, redirect `/app`.
- **Errors:** any OneLogin validation error → 403 "SAML verification failed".
- **Test:** an in-test IdP keypair (`openssl_pkey_new` + self-signed cert) signs a
  crafted `SAMLResponse`; store the matching cert on the connection; POST to ACS →
  asserts the member is logged in + `sso_authenticated`. A response with a broken
  signature, wrong audience, or wrong `InResponseTo` → 403. A valid response for a
  non-member email → 403.

### Slice 3 — JIT provisioning + group→role mapping
Migration adds to `saml_connections` (mirroring `sso_connections`):
`allow_jit` (bool, default false), `allowed_domain` (nullable string),
`role_mappings` (json, nullable). ACS: when the email isn't a member, JIT-provision
via `ProvisionSsoUser` iff `allow_jit` and the domain allowlist passes, else deny.
Map a `groups` attribute → team role via a new `SamlConnection::roleForGroups()`
(copied from `SsoConnection`), applied only when it differs from the current role
(no re-role/audit spam; owner left as-is). `SamlConnectionResource` gains the three
fields.
- **Test:** JIT off → non-member denied; JIT on + domain match → provisioned as
  `Free`; a mapped group attribute → the mapped role; owner unaffected.

## Enforcement integration (in slice 2)

`EnsureSsoWhenRequired` currently always bounces to `sso.redirect` (OIDC). Change
it to resolve the login route by connection type: if the enforcing team has an
enabled `SamlConnection`, redirect to `saml.login`; else `sso.redirect`. A team
uses one protocol. `SsoEnforcement::enforcingTeamFor` already yields the team;
add the route resolution beside it (e.g. `SsoEnforcement::loginRouteFor($team)`).

## Security notes

- **Signature is the trust anchor.** `wantAssertionsSigned = true`; a response
  whose assertion isn't signed by `idp_x509_cert` is rejected. Never accept
  attributes from an unvalidated response.
- **XXE / signature-wrapping** are handled by OneLogin/xmlseclibs — do not parse
  the SAML XML ourselves.
- **ACS is CSRF-exempt** (external IdP POST); the signed assertion + `InResponseTo`
  (bound to our session-stored request id) provide the CSRF/replay protection.
- The IdP `x509` cert is public (already stored unencrypted, per the model doc).

## Testing strategy

The signed-response fixture is the crux. Generate an IdP RSA keypair + self-signed
cert in-test (as the #525 validate-connection test did), build a `SAMLResponse`
with a signed assertion using `robrichards/xmlseclibs` (pulled in by
onelogin/php-saml), base64-encode, and POST it to ACS. This exercises the real
validation path. SQLite suite + a MySQL check on the slice-3 migration.

## Non-goals

- Single-Logout (SLO) — its own gated epic.
- SP-signed AuthnRequests / encrypted assertions (add later if an IdP requires it;
  the settings factory leaves room).
- IdP-initiated SSO (SP-initiated only for v1; `InResponseTo` required).

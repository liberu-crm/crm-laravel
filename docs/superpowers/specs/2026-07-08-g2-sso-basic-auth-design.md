# G2 SSO — client_secret_basic token auth (design)

**Date:** 2026-07-08
**Status:** approved, implementing
**Epic:** G2 SSO hardening. Prerelease `0.1.0-alpha.2`.

## Problem

The token exchange (#501/#513) always authenticates the client with `client_secret_post`
(secret in the request body). Some IdPs (and the OIDC default) require **`client_secret_basic`**
— the client id/secret in an HTTP Basic `Authorization` header. Without it, those IdPs reject
the token request.

## Design

- New column `sso_connections.token_auth_method` (string, default `client_secret_post`).
- `OidcClient::exchangeCode`: when the connection is `client_secret_basic`, send the token
  request with `->withBasicAuth(client_id, client_secret)` and **omit** `client_secret` from the
  body; otherwise keep the current post behavior (secret in the body). `client_id` stays in the
  body either way (harmless, some IdPs expect it).
- `SsoConnectionResource` form gains a `token_auth_method` Select (Post / Basic).

## Testing (TDD, `Http::fake`)

1. A `client_secret_basic` connection: the token request carries an `Authorization: Basic …`
   header and the body omits `client_secret`; the member still logs in.
2. A default (`client_secret_post`) connection: the token request body includes `client_secret`
   and there is no Basic header.

Existing #501–#513 flows are the post default → unaffected. phpstan 0-new, MySQL 8.4-verified,
pint clean.

## Versioning

`VERSION` → `0.1.0-alpha.2`; GitHub prerelease `v0.1.0-alpha.2`.

## Out of scope

`private_key_jwt` / `client_secret_jwt` token auth, SAML, mTLS.

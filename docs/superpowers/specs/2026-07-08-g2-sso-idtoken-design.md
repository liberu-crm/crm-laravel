# G2 SSO — slice 5: id_token / JWKS validation (design)

**Date:** 2026-07-08
**Status:** approved, implementing
**Epic:** G2 SSO. Hardens the OIDC login flow (#501). Closes the deferred "no id_token
signature validation" ceiling.

## Problem

#501 reads the email from the **userinfo** endpoint, trusting the server-side token exchange
as the anchor. The OIDC gold standard is to validate the **id_token** JWT the token endpoint
returns: verify its signature against the provider's JWKS and check `iss`/`aud`/`exp`/`nonce`.
`firebase/php-jwt` is already installed (no new dependency).

## Design

- **Nonce (replay protection):** `redirect` mints a `nonce` alongside `state`, stores it in the
  session, and adds it to the authorize URL. `callback` reads it back to verify the id_token.
- **`OidcClient` additions:**
  - `authorizeUrl(...)` gains a `$nonce` param (adds `nonce=` to the query).
  - `exchangeCode(...)` now returns the **full token response array** (so the caller sees both
    `access_token` and `id_token`).
  - `jwks(connection)` — fetches `jwks_uri` from discovery (cached), returns the JWKS.
  - `validateIdToken(connection, idToken, expectedNonce): array` — `JWT::decode` against
    `JWK::parseKeySet(jwks)` (verifies **signature** + `exp`), then checks `iss` == issuer,
    `aud` contains `client_id`, `nonce` == the session nonce. Throws `SsoException` on any
    failure. Returns the verified claims.
- **`callback` email resolution:**
  - If the token response carries an `id_token` → **validate it** and take `email`/`name` from
    the verified claims.
  - Otherwise fall back to `userinfo` (backward-compatible — existing #501–#503 flows return no
    id_token, so they are unaffected).

## Security

The id_token signature is cryptographically verified against the IdP's published keys; a
forged or tampered token, a wrong audience/issuer, an expired token, or a replayed
nonce is rejected (403). Nonce closes the id_token replay window; `state` still closes the
authorization-request CSRF window.

## Testing (TDD, `Http::fake` + a generated RSA keypair)

Helper: generate an RSA keypair, expose the public key as a JWKS (`n`/`e` base64url), sign an
id_token with the private key (`RS256`, `kid`).

1. Valid signed id_token (correct iss/aud/nonce, not expired) → member logged in.
2. **Tampered / wrong-key** id_token → 403, guest.
3. Wrong `aud` → 403.
4. Wrong `nonce` → 403.
5. Backward compat: a token response **without** id_token → userinfo path still logs in
   (covered by the unchanged #501 tests).

phpstan 0-new, MySQL 8.4-verified, pint clean. Inline TDD, one PR to `main`.

## Out of scope

`client_secret_basic`, PKCE, SAML, IdP group→role mapping, encrypted (JWE) id_tokens,
at_hash/c_hash validation.

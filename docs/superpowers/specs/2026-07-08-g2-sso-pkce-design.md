# G2 SSO — PKCE for the OIDC flow (design)

**Date:** 2026-07-08
**Status:** approved, implementing
**Epic:** G2 SSO hardening. Adds PKCE (RFC 7636) to the OIDC login flow (#501 / #504).

## Problem

The authorization-code flow (#501) has no PKCE, so a stolen authorization code could in
principle be redeemed by an attacker. PKCE binds the code to a per-request secret. No new
dependency (SHA-256 + base64url).

## Design

- **`redirect`** mints a random `code_verifier` (alongside state/nonce), stores it in the
  session, and sends `code_challenge = base64url(sha256(verifier))` +
  `code_challenge_method=S256` on the authorize URL.
- **`callback`** reads the `code_verifier` back from the session and includes it in the token
  exchange, so the IdP only issues tokens to the party that started the flow.
- `OidcClient`:
  - `authorizeUrl(..., string $codeChallenge)` adds `code_challenge` + `code_challenge_method`.
  - `exchangeCode(..., string $codeVerifier)` adds `code_verifier` to the token request body.

## Versioning (per request)

This is the first prerelease. `VERSION` = `0.1.0-alpha.1`; each subsequent PR bumps the
`alpha.N`, with a matching GitHub prerelease. Starting `alpha.1` is below the remaining
backlog (~11 items), leaving headroom.

## Testing (TDD, `Http::fake`)

1. `redirect` → the authorize URL carries `code_challenge=` + `code_challenge_method=S256`, and
   the session holds `sso_verifier`.
2. `callback` → the token request body includes the same `code_verifier` (`Http::assertSent`),
   and the member is logged in.

Existing #501–#504 flows are unaffected (they drive the controller, which supplies the new
params; the token fakes don't assert the body). phpstan 0-new, MySQL 8.4-verified, pint clean.

## Out of scope

`client_secret_basic`, SAML, plain (non-S256) challenge method.

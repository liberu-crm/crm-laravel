# SSO — test connection action (design)

**Date:** 2026-07-08
**Status:** approved, implementing
**Epic:** G2 SSO. Prerelease `1.1.0-rc.2`.

## Problem

An admin configures an OIDC connection (#500) and flips `enabled`, but only finds out whether
the issuer URL is a working OIDC provider when a user tries to log in. They want to validate the
config first.

## Design

A **Test** row action on `SsoConnectionResource`: calls `OidcClient::discover($connection)` and
reports the result — success (the IdP discovery document + endpoints were found) or a danger
notification with the failure message (`SsoException`). Reuses the existing cached discovery; no
new dependency.

## Testing (TDD, `Http::fake`)

1. A connection whose issuer serves a valid discovery document → the Test action shows a success
   notification.
2. A connection whose discovery fetch fails (HTTP 500) → a danger notification.

(Distinct issuers per test to avoid the discovery cache bleeding between cases.) phpstan 0-new,
MySQL 8.4-verified, pint clean.

## Versioning

`VERSION` → `1.1.0-rc.2`; GitHub prerelease `v1.1.0-rc.2`.

## Out of scope

Testing the full token/userinfo round-trip (needs a live IdP), a SAML validate action (separate
slice), auto-disabling a broken connection.

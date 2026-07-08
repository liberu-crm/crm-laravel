# SAML — validate connection action (design)

**Date:** 2026-07-08
**Status:** approved, implementing
**Epic:** G2 SSO SAML. Mirrors #524 (OIDC test). Prerelease `1.1.0-rc.3`.

## Problem

A SAML connection (#521) stores an IdP x509 cert + SSO URL, but nothing checks they are
well-formed until a login is attempted (later slice). An admin wants to validate the config now.

## Design

A **Validate** row action on `SamlConnectionResource` (no dependency, no network):

- x509 cert: PEM-wrap it if it is a bare base64 body, then `openssl_x509_parse()` — must parse.
- `idp_sso_url`: must be a valid `https://` URL.
- `idp_entity_id`: must be non-empty.

Reports a success notification when all pass, or a danger notification listing the problems.

## Testing (TDD)

1. A valid PEM cert (generated in-test via `openssl_csr_sign`) + an `https` SSO URL → success.
2. A garbage cert → failure (cert error).
3. A non-https / malformed SSO URL → failure (URL error).

phpstan 0-new, MySQL 8.4-verified, pint clean.

## Versioning

`VERSION` → `1.1.0-rc.3`; GitHub prerelease `v1.1.0-rc.3`.

## Out of scope

Fetching/parsing IdP metadata, checking the cert's validity dates or chain, the SAML login flow.

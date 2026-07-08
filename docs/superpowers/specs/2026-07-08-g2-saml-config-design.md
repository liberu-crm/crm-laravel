# G2 SSO — SAML slice 1: connection config + SP metadata (design)

**Date:** 2026-07-08
**Status:** approved, implementing
**Epic:** G2 SSO — SAML (the OIDC complement). Prerelease `0.9.9`.

## Scope

The **config foundation** for SAML, mirroring the OIDC connection (#500): a team stores its
IdP's SAML details and can fetch the Service-Provider (SP) metadata XML to hand to that IdP. The
actual SAML login (AuthnRequest → ACS → assertion signature validation) needs a SAML library and
is a **later slice** — no new dependency here.

## Architecture

- `saml_connections` table: `team_id` (unique — one per team), `idp_entity_id`, `idp_sso_url`,
  `idp_x509_cert` (text — the IdP's public signing cert, not a secret), `enabled` (bool, default
  false), timestamps. `IsTenantModel`.
- `App\Models\SamlConnection` — `IsTenantModel`, `enabled` boolean cast.
- `App\Filament\App\Resources\SamlConnectionResource` (app panel, team-scoped, one per team):
  `canAccess` admin / super_admin; form for the IdP fields + enabled. Mirrors
  `SsoConnectionResource`.
- `App\Http\Controllers\SamlMetadataController` + route `GET /saml/{team}/metadata`
  (`saml.metadata`): returns the SP `EntityDescriptor` XML — `entityID` = the metadata URL,
  `AssertionConsumerService` Location = `/saml/{team}/acs` (the future ACS, slice 2), NameID
  format emailAddress, `WantAssertionsSigned="true"`. Available only when the team has a SAML
  connection (404 otherwise) — it's what the admin gives their IdP.

## Testing (TDD)

1. `SamlConnectionResource`: admin creates a connection → `team_id` stamped, fields saved;
   team-scoped; `canAccess` admin ✓ / sales_rep ✗; one per team (`canCreate`).
2. `GET /saml/{team}/metadata` for a team with a connection → 200, XML containing the SP
   `entityID` and the ACS `Location`.
3. Metadata 404 when the team has no connection.

phpstan 0-new, MySQL 8.4-verified, pint clean.

## Versioning

`VERSION` → `0.9.9`; GitHub prerelease `v0.9.9`.

## Out of scope (later slice)

The SAML login flow (AuthnRequest, ACS endpoint, XML-DSig assertion validation — needs a SAML
package), IdP-initiated SSO, SLO, signed AuthnRequests, encrypted assertions.

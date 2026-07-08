# G2 SSO — slice 1: per-team SSO connection config (design)

**Date:** 2026-07-08
**Status:** approved, implementing
**Epic:** G2 SSO (SAML/Okta/Auth0). First slice — the config foundation, no auth flow yet.

## Epic decomposition

1. **Per-team SSO connection config** (this slice) — store + manage a team's OIDC connection,
   secret encrypted at rest. No login flow.
2. OIDC login flow — redirect → callback via Socialite generic OIDC, match/login the user.
3. JIT provisioning — first SSO login provisions the user into the team with a default role.
4. Enforcement / SAML / discovery metadata.

Socialstream (→ Laravel Socialite) is already installed, so slice 2 rides Socialite. This
slice is greenfield (the existing `oauth_configurations`/`connected_accounts` are for
helpdesk/social integrations, not team login).

## Slice 1 architecture

### `sso_connections` table

`team_id` (FK, **unique** — one connection per team), `provider` (oidc/okta/azure/auth0),
`client_id`, `client_secret` (encrypted at rest), `issuer_url`, `enabled` (bool, default
false), timestamps. Guarded create (`Schema::hasTable`), `constrained()->cascadeOnDelete()`.

### `App\Models\SsoConnection`

`IsTenantModel` (gives the global team scope, `team_id` auto-stamp, and the `team()`
relationship the app panel's tenancy needs) + casts `client_secret => 'encrypted'`,
`enabled => 'boolean'`. The encrypted cast means the secret is ciphertext in the DB and
decrypted transparently by the model.

### `App\Filament\App\Resources\SsoConnectionResource` (app panel, team-scoped)

- `canAccess` → team admin / super_admin.
- Form: provider (Select), client_id, client_secret, issuer_url (url), enabled (Toggle).
- **Secret handling (trust boundary):** the field is a password input, **blanked on form
  load** (never re-displays the stored secret), **required on create**, and **dehydrated only
  when filled** — so submitting an edit with the secret left blank preserves the stored one.
- One connection per team: `canCreate` returns false once the team already has one.
- Team scoping is automatic (IsTenantModel + the app panel tenant), like other app resources.

## Security

`client_secret` is encrypted at rest (Laravel `encrypted` cast, APP_KEY) and never rendered
back into the form after save. The connection is team-scoped; a team admin only manages their
own team's connection.

## Testing (TDD, PHPUnit sqlite → MySQL-verify)

1. Admin creates a connection → `team_id` stamped; the stored `client_secret` column is
   **ciphertext (≠ the plaintext)** and the model decrypts it back.
2. Team-scoped: team A's admin doesn't see team B's connection.
3. `canAccess`: admin ✓, sales_rep ✗.
4. Editing with the secret field left blank **preserves** the stored secret.
5. `canCreate` is false once the team has a connection (one per team).

phpstan 0-new, MySQL 8.4-verified, pint clean. Inline TDD, one PR to `main`.

## Out of scope (later slices)

The OIDC redirect/callback login flow, JIT provisioning, SAML, SSO enforcement (require-SSO
per team), live discovery-URL/metadata validation, multiple providers per team.

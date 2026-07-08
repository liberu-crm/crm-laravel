# Changelog

## 1.2.0

Compliance visibility + wider field-masking coverage (v1.2.0-rc.1–rc.4).

- **App-panel audit log** (#527) — a read-only, team-scoped Audit log for team admins, with a
  category filter (record changes / team / portal / auth). The full trail was previously
  admin-panel only.
- **Field masking extended (G3 ABAC)** — sensitive money/PII fields now masked for `free`-role
  viewers on three more core models: Lead `potential_value` (#528), Company `phone_number` +
  `annual_revenue` (#529), Opportunity `deal_size` (#530). Masking now covers Contact, Deal,
  Lead, Company, and Opportunity across serialization, tables, search, and edit forms; stored
  values are never mutated.

## 1.1.0

SSO configuration UX and portal branding polish (v1.1.0-rc.1–rc.3).

- **Portal logo upload** — a team's portal logo can be an uploaded file (public disk), not only
  a URL; resolution prefers the upload, then a URL, then global config.
- **SSO connection test** — a Test action on the OIDC connection validates the config by
  fetching the IdP discovery document before it is enabled.
- **SAML connection validate** — a Validate action checks the IdP x509 certificate parses, the
  SSO URL is https, and the entity ID is set (local, no network).

## 1.0.0

First stable release. Multi-tenant CRM (Laravel 13 / Filament 5 / Jetstream Teams) with
tenant isolation, per-team RBAC, a customer portal, SSO, and attribute-based access control.

### Tenancy & access (F1–F6)
- Real `IsTenantModel` global team scope + auto-stamp (`TenantContext`); 20 tables backfilled
  with `team_id`. Cross-tenant leakage test covers every tenant model.
- API tenant scoping (closed an IDOR), queue tenant scoping (`TenantAware`), record-level
  ownership (`RestrictsToOwner` / `AccessContext`), structured audit logging.

### Team lifecycle (F3)
- Archive, backup (JSON-zip), restore, clone (config-only), and cross-environment import — one
  per PR (#475–#479), each with schema-introspected FK handling.

### Per-team RBAC (F4)
- Spatie teams-mode roles; per-request permission-team context. Team-admin UI: add / re-role /
  bulk-role / remove members, with an owner guard, a self guard, and role-change auditing.

### Customer portal (G_5)
- A third Filament `portal` panel: ticket self-service (create/view/reply/close), knowledge
  base (browse + staff authoring), document sharing, staff-invite onboarding, access revoke,
  dashboard, notifications, and per-team branding (name + logo).

### SSO (G2)
- OIDC end-to-end: per-team connection config, login, JIT provisioning, enforcement, id_token
  (JWKS) validation, PKCE, `client_secret_basic`, and IdP group → team-role mapping.
- SAML: connection config + SP metadata (the login flow is planned).

### ABAC (G3)
- Territory scoping (`RestrictsToTerritory`) and field-level masking of sensitive fields
  (`MasksFields`) across serialization, tables, search, and edit forms — applied to Contact and
  Deal.

Full suite: 954 passing. PHPStan clean (0 new). MySQL 8.4-verified.

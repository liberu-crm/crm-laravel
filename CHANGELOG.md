# Changelog

## 2.0.0

Feature-complete milestone. PII encrypted at rest (v2.0.0-rc.1–rc.2), and the
full scoped roadmap is done.

- **PII encrypt-at-rest (Security)** (#596, #598) — the masked PII columns are now
  encrypted at rest: `Contact.email` (with a deterministic `email_hash` blind
  index that carries equality lookups + uniqueness, since encrypted ciphertext
  can't be uniquely indexed) and `Contact.phone_number` + `Company.phone_number`
  (encrypted, no blind index). Email search is now exact-match; phone search is
  dropped. Money columns are intentionally left plaintext — they need database
  sort/range/aggregation for forecasting, and masking already hides them from the
  `free` role. An idempotent backfill encrypts existing rows on upgrade.

Since 1.0.0, this release also completed: the customer portal, SSO (OIDC + SAML,
login **and** single-logout), per-team role enforcement (F4), attribute-based
access control (territory scoping + field masking), and detail views / exports
across the CRM.

**Operational note:** run migrations on upgrade — existing email/phone rows are
encrypted in place. `APP_KEY` rotation orphans the email blind index (and the
ciphertext); re-run the backfill after a rotation.

## 1.17.0

SSO single-logout — OIDC and SAML (v1.17.0-rc.1–rc.2).

- **OIDC RP-initiated logout** (#592) — logging out of an OIDC SSO session now
  redirects to the IdP's `end_session_endpoint` (with `id_token_hint` +
  `post_logout_redirect_uri`) so the IdP session ends too. The `id_token` +
  endpoint are captured at login; a `Logout`-event listener stashes the logout
  URL before the session is invalidated. IdPs without an end-session endpoint fall
  back to local logout.
- **SAML SP-initiated single-logout** (#594) — SAML sessions log out through the
  IdP's Single Logout Service: a OneLogin `LogoutRequest` (built from the
  `NameID` + `SessionIndex` captured at login) targets the new `idp_slo_url`, and
  a `/saml/{team}/sls` endpoint handles the IdP's `LogoutResponse`. The SP metadata
  now advertises the `SingleLogoutService`.

With this, G2 SSO is complete end-to-end for both OIDC and SAML — login and
logout.

**Operational note:** run migrations on upgrade (`saml_connections` gains
`idp_slo_url`). Set the IdP's Single Logout URL on the SAML connection to enable
SAML SLO.

## 1.16.0

Detail Views for the advertising records (v1.16.0-rc.1–rc.3).

- **Ad / MarketingCampaign / SocialMediaPost detail View** (#588, #589, #590) —
  each app-panel resource gains a read-only View page (previously List/Create/Edit
  only), completing detail-view coverage across the advertising/marketing records
  (alongside Campaign, AdSet, and AdvertisingAccount). Access is permission-gated
  by the role-enforcement trait.

## 1.15.0

SAML SSO login — end-to-end (v1.15.0-rc.1–rc.3).

- **SAML login flow (G2)** (#582, #585, #586) — completes the SAML feature whose
  config + SP metadata shipped in v1.0. A team's members now log in through their
  IdP: SP-initiated `AuthnRequest` → IdP → ACS, where the signed assertion is
  validated (XML-DSig against the IdP cert, audience, `InResponseTo`, replay) via
  the audited `onelogin/php-saml` toolkit. Non-members are just-in-time
  provisioned when the connection allows it (domain allowlist, lands as a limited
  role), IdP groups map to team roles, and teams with `require_sso` are enforced
  onto SAML — full parity with the OIDC flow.

**Operational note:** run migrations on upgrade (SAML connections gain
`allow_jit` / `allowed_domain` / `require_sso` / `role_mappings`). The ACS
(`/saml/{team}/acs`) is CSRF-exempt by design — it's protected by the signed
assertion and `InResponseTo`.

## 1.14.0

Detail Views for ad sets, tasks, and notes (v1.14.0-rc.1–rc.3).

- **AdSet / Task / Note detail View** (#578, #579, #580) — each app-panel resource
  gains a read-only View page (previously List/Create/Edit only). AdSet's infolist
  **masks `budget`** with the same gate as its table column, so the detail view
  isn't a masking bypass. Access is permission-gated by the role-enforcement trait.

## 1.13.0

Role enforcement (F4) — make per-team roles actually gate the app panel
(v1.13.0-rc.1–rc.3).

- **Permission foundation (#572)** — a real permission catalog
  (`{action}_{resource}` for ~34 resources) and a system-role → permission
  matrix, seeded on fresh installs and backfilled on existing deploys by an
  idempotent migration so no role loses access. `super_admin` gains a global
  gate bypass. The permission table previously seeded empty (a dead generator),
  so custom roles couldn't grant anything and nothing enforced them.
- **Core-CRM enforcement (#575)** — a reusable `EnforcesResourcePermissions`
  trait gates view/create/update/delete on the nine core records
  (Contact, Deal, Lead, Company, Opportunity, Task, Note, Message, Activation).
  `free` becomes a limited editor (view/create/update, no delete) with sensitive
  fields still masked; other roles are scoped per the matrix.
- **All-resource enforcement (#576)** — the trait now covers the remaining 25
  resources: advertising/marketing (previously open to every role) and the
  settings/security/log resources (migrated off ad-hoc `hasRole` gates onto one
  permission model). Custom per-team roles finally restrict access, and
  security/settings/audit permissions can't be granted to a custom role.

**Operational note:** run migrations on upgrade — the role-permission seed must
apply before enforcement takes effect, or non-super-admins lose access.

## 1.12.0

Finish masking-gated exports and add an ad-account detail view (v1.12.0-rc.1–rc.3).

- **Opportunity / AdSet CSV export** (#568, #569) — the masking-gated CSV export now
  covers Opportunity (`deal_size`) and AdSet (`budget`), both hidden for field-masked
  (`free`) roles so a CSV can't bypass masking. AdSet was the last masked money/PII
  record without an export — every masked record is now exportable and every export is
  gated off for `free`.
- **AdvertisingAccount detail view** (#570) — a read-only View page for the app-panel
  ad accounts. Its infolist renders only the non-secret display fields (name, platform,
  account_id, status, last sync, created) and **deliberately omits the encrypted
  `access_token` / `refresh_token`**, so the detail view is never a secret-disclosure
  surface.

## 1.11.0

Upload-security hardening and Campaign export (v1.11.0-rc.1–rc.2).

- **Ticket attachment content validation (Security)** (#565) — portal ticket uploads now validate
  the file's real content type (Finfo magic bytes) against an allowlist, not just the spoofable
  declared MIME, matching `DocumentService`'s guard.
- **Campaign CSV export** (#566) — the masking-gated CSV export now covers Campaign too
  (previously skipped), completing export for the core + advertising money records.

## 1.10.0

Custom per-team roles (F4), a webhook delivery log, and dead-code cleanup (v1.10.0-rc.1–rc.4).

- **Custom per-team roles (F4)** — a team admin can define custom roles scoped to their team
  (`roles.team_id`) with a chosen permission set (#560), and assign members to them alongside the
  four fixed roles (#561). Role/permission/user-management permissions are **not grantable** to a
  custom role (anti-escalation, enforced server-side), and a custom role from another team can't be
  assigned.
- **Webhook delivery log** (#563) — every webhook send (success / HTTP failure / exception) is
  recorded in a team-scoped `webhook_deliveries` table, surfaced read-only on the app panel.
- **Cleanup** (#562) — removed the unreachable `OpportunityPipeline` kanban (dead code that rendered
  a masked field unmasked).

## 1.9.0

More masking-safe detail View pages (v1.9.0-rc.1–rc.3).

- **Contact / Opportunity / Campaign detail View** (#556, #557, #558) — read-only View pages,
  completing detail views across the core and masked-money records. Each infolist masks its
  sensitive fields (Contact `email` + `phone_number`, Opportunity `deal_size`, Campaign `budget`)
  with the same gate as the table columns, so a detail view can't bypass field masking for the
  `free` role.

## 1.8.0

Read-only detail View pages for the core records, masking-safe (v1.8.0-rc.1–rc.3).

- **Deal / Lead / Company detail View** (#552, #553, #554) — each app-panel resource gains a
  read-only View page (previously only List/Create/Edit). The infolist **masks the sensitive
  money/PII fields** (Deal `value`, Lead `potential_value`, Company `phone_number` +
  `annual_revenue`) with the same gate as the table columns, so the detail view can't bypass field
  masking for the `free` role.

## 1.7.0

CSV export for the core CRM records, masking-safe (v1.7.0-rc.1–rc.3).

- **Deal / Lead / Company CSV export** (#548, #549, #550) — each app-panel resource gains a Filament
  CSV export, **gated off for field-masked (`free`) roles** so an export can't bypass the value /
  potential_value / phone / revenue masking. Combined with the Contact and audit-log exports from
  1.6.0, all core records are now exportable.

## 1.6.0

Auth-event auditing, masking-safe contact export, and a territory detail view (v1.6.0-rc.1–rc.3).

- **Auth-event audit** (#544) — failed logins against a known account (`auth.failed`) and password
  resets (`auth.password_reset`) are now recorded, extending the trail beyond login/logout.
- **Contact CSV export** (#545) — the app-panel Contacts gains a Filament CSV export, **gated off
  for field-masked (`free`) roles** so an export can't bypass email/phone masking. This also
  publishes the Filament `exports` table (previously unpublished), fixing the audit-log export from
  1.5.0 which lacked it at runtime.
- **Territory detail view** (#546) — a read-only View page showing a territory's name, created
  date, and assigned members.

## 1.5.0

Finish credential encryption, add SSO-logout auditing and audit-log export (v1.5.0-rc.1–rc.3).

- **ConnectedAccount tokens encrypted (Security)** (#540) — social-auth `token` / `secret` /
  `refresh_token` now encrypted at rest, completing the credential-encryption sweep (SsoConnection,
  AdvertisingAccount, OAuthConfiguration, Webhook, ConnectedAccount all covered).
- **SSO logout audit** (#541) — logging out of an SSO session records an `auth.sso_logout` entry,
  the counterpart to the existing SSO login audit.
- **Audit log export** (#542) — the app-panel Audit log gains a CSV export (Filament ExportAction),
  tenant-scoped and admin-gated.

**Operational note:** existing plaintext ConnectedAccount tokens won't decrypt after upgrade —
users must re-connect their social accounts.

## 1.4.0

Encrypt stored credentials at rest (v1.4.0-rc.1–rc.3).

- **Credential encryption (Security)** — secrets that were stored in plaintext are now encrypted
  at rest via Laravel's `encrypted` cast, matching how `SsoConnection.client_secret` was already
  handled: AdvertisingAccount `access_token` + `refresh_token` (#536), OAuthConfiguration
  `client_secret` (#537), and Webhook `secret` (#538). Reads decrypt transparently, so services
  and webhook signing are unchanged. **Operational note:** existing plaintext rows must be
  re-saved (re-authorized) after upgrading, and these columns can no longer be queried by value.

## 1.3.0

Audit detail view + wider field-masking coverage (v1.3.0-rc.1–rc.3).

- **Audit log detail view** (#532) — the app-panel Audit log gains a read-only View page (row
  action + infolist) showing the entry detail and the field-level `changes` diff.
- **Field masking extended (G3 ABAC)** — money fields now masked for `free`-role viewers on
  Campaign `budget` (#533) and AdSet `budget` (#534). Masking now spans Contact, Deal, Lead,
  Company, Opportunity, Campaign, and AdSet across serialization, tables, search, and edit forms;
  stored values are never mutated.

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

# Changelog

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

# F4 role enforcement — design (2026-07-10)

Make per-team roles (system + F4 custom) actually gate the app-panel CRM
resources. Today the permission substrate is absent: the `permissions` table
seeds empty (the generator is dead code and not in the seed chain), `super_admin`
has no gate bypass, ~20 resources default-allow to every role, 11 gate on
hardcoded `hasRole`, and 7 model policies only do team-ownership. F4 custom roles
are inert — the grant picker reads an empty table and nothing enforces a grant.

This epic builds the foundation, then turns on enforcement in staged slices.

## Permission catalog

- Naming `{action}_{resource}` snake_case (matches existing `view_reports`,
  `manage_users`). `resource` = snake of the model (`contact`, `ad_set`,
  `sso_connection`, …).
- Four actions: `view` (gates `canViewAny` **and** `canView`), `create`,
  `update` (`canEdit`), `delete` (`canDelete` + `canDeleteAny`). Full CRUD with
  4 verbs, ~35 resources ⇒ ~140 permissions.
- View-only pages (dashboards) get only a `view_` gate.

The catalog + the role matrix live in one canonical `config/permissions.php`
(single source of truth for both the seeder and the upgrade migration).

## Role → permission matrix

`super_admin` = global bypass (not in matrix). `customer` = portal only.

Groups:
- **G1 Core CRM:** contact, deal, lead, company, opportunity, task, note,
  activation, message
- **G2 Adv/Marketing:** advertising_account, campaign, ad_set, ad,
  marketing_campaign, mailchimp_campaign, social_media_post, landing_page,
  form_builder, workflow, knowledge_base_article, dashboard_widget,
  call_setting, whatsapp_number
- **G3 Settings/Security:** team_member, team_role, sso_connection,
  saml_connection, oauth_configuration, webhook_delivery, portal_branding,
  territory
- **G4 Logs (read-only):** audit_log, portal_access_log, team_role_log

| Role | G1 Core | G2 Adv/Mktg | G3 Settings/Security | G4 Logs |
|------|---------|-------------|----------------------|---------|
| admin | CRUD | CRUD | CRUD | view |
| manager | CRUD | CRUD | territory + knowledge_base CRUD only | view |
| sales_rep | CRUD (owner/territory-scoped) | view | — | — |
| free | view | — | — | — |

Preserves admin/manager access exactly as their hardcoded gates grant today;
intentionally tightens `sales_rep`/`free` to core CRM (the point of the epic).
The no-lockout guarantee protects admin/manager/super_admin and makes the seeded
sets deterministic. (knowledge_base and territory sit in G3 but carry a
manager-CRUD exception — encoded per-resource in the matrix, not per-group.)

## super_admin bypass

`config/filament-shield.php` → `super_admin.define_via_gate = true` (enabled is
already true). Shield registers a `Gate::before` granting `super_admin` every
ability, so every `$user->can(...)` short-circuits true — survives new
resources/permissions. Global (also covers the admin panel); intended.

## Enforcement trait

`App\Filament\Concerns\EnforcesResourcePermissions` overrides
`canViewAny/canView/canCreate/canEdit/canDelete/canDeleteAny` →
`Auth::user()?->can("{action}_".static::permissionResource())`.
`permissionResource()` defaults to the snake of the model, overridable. Composes
with `IsTenantModel` team-scoping (tenant isolation stays independent) and the 7
ownership policies (per-record team checks remain). Per-resource opt-in via
`use` = staged rollout.

## Seeding + no-lockout migration

- Fresh installs: rewrite the dead `PermissionsSeeder` to build the catalog from
  `config/permissions.php` and assign the matrix to the system roles; add it to
  `DatabaseSeeder`. Shared logic lives in `App\Support\PermissionCatalog::sync()`.
- Existing deploys: an idempotent migration calls `PermissionCatalog::sync()`
  (`firstOrCreate` permissions + `syncPermissions`/`givePermissionTo` per matrix)
  **in the same release as enforcement**, so roles hold their permissions before
  any `can()` check matters. Teams-mode: system roles are global (`team_id=null`)
  so their permissions apply in every team; the existing `TeamsPermission`
  middleware sets per-request team context.

## Slices (one PR each, sequential — 3b/3c depend on 3a)

- **3a Foundation:** `config/permissions.php`, `PermissionCatalog`, rewritten
  seeder, no-lockout migration, super_admin bypass. **No trait → zero behavior
  change, suite green.**
- **3b Enforce pilot:** trait applied to G1 core CRM. No-lockout + per-role tests.
- **3c Enforce sweep:** trait on G2/G3/G4; migrate the 11 hardcoded-`hasRole`
  resources onto the trait; extend `TeamRoleResource::grantablePermissions()` to
  exclude the security tokens (sso/saml/oauth/webhook/audit/team_member/team_role/
  portal_branding) so a team admin can't grant a custom role security access.
  Custom roles then enforce automatically.

## Testing

Per-role access-matrix tests (role × representative resource → can/can't per
verb), super_admin-bypass test, no-lockout test (admin/manager retain today's
access), custom-role-enforce test (grant `view_contact` → member sees Contacts,
not Deals). SQLite suite + MySQL-verify the migration.

## Non-goals

No GraphQL/API-token permission surface (app panel only). No per-field
permissions (masking already covers that). No portal-panel permission changes.

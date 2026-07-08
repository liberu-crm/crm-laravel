# Portal — per-team branding (design)

**Date:** 2026-07-08
**Status:** approved, implementing
**Epic:** G_5 customer portal. Closes the per-tenant ceiling from #489/#519 (branding was global
config only).

## Problem

The portal brand name (#489) and logo (#519) are **global config** — every tenant's customers
see the same branding. A multi-tenant CRM wants each team to brand its own portal.

## Design

- `teams.portal_brand_name` + `teams.portal_logo_url` (nullable).
- `App\Support\PortalBranding` — resolves the **authenticated customer's** team (via
  `current_team_id`): `brandName()` = the team's name or global config fallback; `logo()` =
  the team's logo or the global config fallback. On the (unauthenticated) login page there is no
  customer → the global config value is used.
- `PortalPanelProvider` — `->brandName(fn () => PortalBranding::brandName())` and
  `->brandLogo(fn () => PortalBranding::logo())` (favicon stays global config, #519).
- `App\Filament\App\Resources\PortalBrandingResource` (app panel) — a team admin edits their own
  team's branding. Model `Team`, **`$isScopedToTenant = false`** (Team has no `team` ownership
  relationship), `getEloquentQuery` restricted to the current tenant team (a one-record
  resource). `canAccess` → admin / super_admin. Form: `portal_brand_name` + `portal_logo_url`.

## Testing (TDD)

1. `PortalBranding::brandName` / `logo` resolve the customer's team values; fall back to config
   when unset.
2. The portal panel `brandName` reflects the logged-in customer's team.
3. `PortalBrandingResource`: `canAccess` admin ✓ / sales_rep ✗; editing saves the team's
   branding.

phpstan 0-new, MySQL 8.4-verified, pint clean.

## Versioning

`VERSION` → `0.9.8`; GitHub prerelease `v0.9.8`.

## Out of scope

Logo/favicon **upload** (URL only), per-team favicon (stays global), theme colors/fonts, a
customer belonging to multiple teams.

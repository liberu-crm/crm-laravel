# Portal — logo & favicon branding (design)

**Date:** 2026-07-08
**Status:** approved, implementing
**Epic:** G_5 customer portal. Extends #489 (configurable brand name) with visual assets.

## Problem

#489 made the portal's **brand name** configurable but left logo/favicon on the default
(Filament) branding — the #489 ceiling.

## Fix (config-driven, matching #489)

- `config/portal.php` gains `logo` (`PORTAL_LOGO_URL`) and `favicon` (`PORTAL_FAVICON_URL`).
- `PortalPanelProvider` wires `->brandLogo(fn () => config('portal.logo'))` and
  `->favicon(fn () => config('portal.favicon'))` (closures re-read per render, like #489's
  brand name). Null → Filament falls back to the brand-name text / default favicon.

## Testing (TDD)

1. With `portal.logo` set, `Filament::getPanel('portal')->getBrandLogo()` returns it.
2. With `portal.favicon` set, `getFavicon()` returns it.
3. Unset → both null (Filament default).

phpstan 0-new, MySQL 8.4-verified, pint clean.

## Versioning

`VERSION` → `0.9.7`; GitHub prerelease `v0.9.7`.

## Out of scope

Per-team branding (each tenant's own logo — the portal is non-tenant; a bigger slice), asset
**upload** (URL config only for now), theme colors/fonts.

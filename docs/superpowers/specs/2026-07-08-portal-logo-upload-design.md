# Portal — logo upload (design)

**Date:** 2026-07-08
**Status:** approved, implementing
**Epic:** G_5 customer portal. Closes the upload ceiling from #519/#520 (logo was URL-only).

## Problem

Per-team portal branding (#520) accepts a logo **URL**. Admins want to **upload** a logo file
rather than host it themselves.

## Design

- `teams.portal_logo_path` (nullable) — the uploaded file's path on the `public` disk.
- `PortalBranding::logo()` resolution order: uploaded file (`Storage::disk('public')->url(path)`)
  → external `portal_logo_url` → global config. So an upload wins; a URL remains supported.
- `PortalBrandingResource` form gains an image `FileUpload` (`portal_logo_path`, `public` disk,
  `portal-logos/` dir, size-limited), alongside the existing URL field.

## Testing (TDD)

1. An admin uploads a logo → `teams.portal_logo_path` is set.
2. `PortalBranding::logo()` returns the public-disk URL of the uploaded file, preferring it over
   the URL field and config.
3. With no upload, it falls back to the `portal_logo_url` / config (unchanged from #520).

phpstan 0-new, MySQL 8.4-verified, pint clean.

## Versioning

`VERSION` → `1.1.0-rc.1` (release candidate toward `1.1.0`; bumped per PR). GitHub prerelease
`v1.1.0-rc.1`.

## Out of scope

Favicon upload (stays config), image resizing/CDN, per-tenant themes.

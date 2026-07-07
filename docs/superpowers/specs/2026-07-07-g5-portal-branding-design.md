# G_5 slice 10 — Portal branding / theming (design)

**Date:** 2026-07-07
**Status:** approved, implementing
**Epic:** G_5 customer portal, slice 10. Makes the portal read as a customer product rather than
the staff admin chrome. Stacks on the dashboard slice (#488).

## Problem

The portal reuses Filament's default admin look — a generic sidebar with the app name. Nothing
signals to a customer that this is *their* support portal, and the brand can't be changed per
deployment (white-labelling).

## Decision

Asset-free, config-driven branding on the panel:

- **Brand name** — a configurable `config('portal.brand_name')` (env `PORTAL_BRAND_NAME`, default
  "Customer Portal"), wired as a `brandName` **closure** so it re-reads at render (overridable at
  runtime / per deployment).
- **Top navigation** — `->topNavigation()` gives a horizontal product-style nav instead of the
  admin sidebar.
- Keep the existing Blue primary accent.

No logo/favicon assets (those need real files — out of scope); no custom CSS build.

## Architecture

- `config/portal.php` → `brand_name`.
- `PortalPanelProvider`: `->brandName(fn () => config('portal.brand_name'))->topNavigation()`.

## Testing (TDD)

- The portal renders the default brand name ("Customer Portal").
- The brand name is configurable — setting `config(['portal.brand_name' => 'Acme Support'])` shows
  "Acme Support" (proves the closure re-reads config).
- MySQL 8.4 verify; phpstan 0-new; pint clean.

## Out of scope (ceilings)

No logo/favicon (asset files); no per-tenant branding (single deployment-level brand); no custom
theme CSS/fonts; no dark-mode palette tuning.

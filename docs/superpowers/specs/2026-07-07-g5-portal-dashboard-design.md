# G_5 slice 9 — Portal dashboard / landing (design)

**Date:** 2026-07-07
**Status:** approved, implementing
**Epic:** G_5 customer portal, slice 9. Gives the portal a home page instead of dropping the
customer straight into a bare resource list.

## Problem

The portal panel registers no pages (`->pages([])`), so `/portal` has no landing — the customer
arrives at whichever resource Filament picks first, with no overview. There is no at-a-glance
sense of "my open tickets / help / documents".

## Decision

Register Filament's default `Dashboard` as the portal landing, populated by a single
**stats-overview widget** with four scoped counts:

- **Open tickets** — the customer's non-closed tickets
- **Closed tickets** — the customer's closed tickets
- **Help articles** — published KB articles for their tenant
- **Documents** — documents shared with them

Each count **reuses the corresponding resource's `getEloquentQuery()`** (Ticket per-user, KB
team+published, Document own-contact), so the dashboard inherits the exact, already-tested
scoping — one source of truth, no new leak surface.

## Architecture

- `App\Filament\Portal\Widgets\PortalOverview` extends `StatsOverviewWidget`; `getStats()` builds
  the four `Stat`s from the resource query builders.
- `PortalPanelProvider`: `->pages([Dashboard::class])` + `->widgets([PortalOverview::class])`, so
  `/portal` renders the dashboard with the overview.

## Testing (TDD)

- `/portal` (dashboard) loads for a customer (200).
- The overview widget renders (labels present) and reflects the customer's scoped data — e.g.
  with 3 open tickets for the customer and one for another user, the Open-tickets stat reads 3.
- MySQL 8.4 verify; phpstan 0-new; pint clean.

## Out of scope (ceilings)

No recent-activity feed or charts; no per-resource deep links beyond the panel nav; counts are
live (not cached); no personalised greeting beyond Filament defaults.

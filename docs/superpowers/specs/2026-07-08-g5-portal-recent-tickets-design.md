# G_5 polish — Recent-tickets feed on portal dashboard (design)

**Date:** 2026-07-08
**Status:** approved, implementing
**Epic:** G_5 customer portal (polish). Extends the dashboard (#488).

## Problem

The portal dashboard (#488) shows only four stat **counts** (`PortalOverview`). A customer
landing there sees numbers but no view of what actually changed — no list of their recent
tickets to click into.

## Fix

`App\Filament\Portal\Widgets\RecentTickets` (a `TableWidget`): the customer's five
most-recently-updated tickets — subject / status / updated_at — each row linking to the
portal `ViewTicket`. **Reuses `TicketResource::getEloquentQuery()`** (already `where user_id
= Auth::id()`), so the widget inherits the same one-source-of-truth scoping as
`PortalOverview` — it can never show a ticket the resource itself would hide. Registered on
the portal panel alongside `PortalOverview`.

## Security / tenancy

Scoping is inherited from `TicketResource::getEloquentQuery` (per-user). No new query surface,
no cross-customer disclosure. `recordUrl` resolves to the portal `ViewTicket`, itself
ownership-scoped (foreign id → 404, #480).

## Testing (TDD, PHPUnit sqlite → MySQL-verify)

1. The widget lists the customer's own recent tickets and **excludes a foreign user's**
   (scoping) — `Livewire::test(RecentTickets::class)` under the portal panel as the customer.
2. The widget is registered on the portal panel (`Filament::getPanel('portal')->getWidgets()`).

phpstan 0-new, MySQL 8.4-verified, pint clean. Inline TDD, one PR to `main`.

## Out of scope

Cross-surface activity (documents/KB — tickets are the actionable one), real-time updates,
configurable row count, per-status filtering.

# G_5 slice 7 — Customer ticket close/reopen (design)

**Date:** 2026-07-07
**Status:** approved, implementing
**Epic:** G_5 customer portal, slice 7. Gives the customer the resolve end of the ticket
lifecycle: close a ticket they no longer need, and reopen it if the issue returns.

## Problem

A customer can create, view and reply to tickets (#480/#481) but cannot mark one resolved.
Only staff can change status, so a customer's queue fills with tickets that are done.

## Decision

Two owner-scoped status transitions on the portal `ViewTicket` page:

- **Close** — `status = 'closed'`, visible when the ticket is not already closed,
  `requiresConfirmation`.
- **Reopen** — `status = 'open'`, visible when the ticket is closed.

Ownership is inherited: `ViewTicket` resolves its record through
`TicketResource::getEloquentQuery()` (scoped `where user_id = auth id`), so a customer can only
act on their own tickets — a foreign ticket id already 404s (#480). No new gate.

## Out of scope / ceilings (stated)

No staff notification on close/reopen (there is no ticket-status event; staff see the status in
their queue); replying to a closed ticket does not auto-reopen it (the customer uses Reopen);
closed tickets remain listed (the list is not status-filtered), which is intended so the
customer can reopen.

## Testing (TDD)

- Customer closes an open ticket → `status = 'closed'`.
- Customer reopens a closed ticket → `status = 'open'`.
- (Ownership is inherited from #480's scoped record resolution — a foreign ticket view already
  404s.)
- MySQL 8.4 verify; phpstan 0-new; pint clean.

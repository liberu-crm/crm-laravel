# G_5 slice 8 — Staff-side ticket attachment display (design)

**Date:** 2026-07-07
**Status:** approved, implementing
**Epic:** G_5 customer portal, slice 8. Closes the loop on portal ticket attachments (#481):
staff can now see and download the file a customer uploaded.

## Problem

Slice 2 (#481) lets a portal customer attach a file when raising a ticket (`tickets.attachment`,
private `local` disk). But staff working the ticket on the app-panel `TicketResource` have no
way to reach it — the attachment is write-only from the customer's side.

## Decision

Add a **Download attachment** row action to the app-panel `TicketResource`, visible only when
the ticket carries an `attachment`, streaming the file off the private `local` disk (mirrors the
portal-side download). Tenant scoping is inherited: the app panel is team-scoped, so staff only
ever see/download their own team's tickets.

## Testing (TDD)

- A staff member (manager, in a team) can download a ticket's attachment via the row action
  (`Storage::fake('local')` + a stored file → `assertFileDownloaded`).
- (Tenancy is enforced by the app panel's existing team scope; not re-tested here.)
- MySQL 8.4 verify; phpstan 0-new; pint clean.

## Out of scope (ceilings)

Single attachment only (matches #481); no inline preview; the attachment is not surfaced on the
edit form, only as a download action; no separate access log.

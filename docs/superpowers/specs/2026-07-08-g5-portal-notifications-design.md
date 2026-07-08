# G_5 — Portal customer notifications (design)

**Date:** 2026-07-08
**Status:** approved, implementing
**Epic:** G_5 customer portal (extension). Completes the notification story from #492/#494.

## Problem

Two loose ends in customer awareness:

1. **The portal notification bell was never enabled.** #492 sends the customer a
   `TicketReplyNotification` on `['mail', 'database']`, but `PortalPanelProvider` never calls
   `->databaseNotifications()`, so the `database` channel is **write-only** — rows land in
   `notifications` but no bell renders in the portal.
2. **Document shares are silent.** #494 lets staff share a document with a Contact, but the
   customer is never told.

## Fix

1. Add `->databaseNotifications()` to the portal panel. Surfaces the bell for every portal DB
   notification — retroactively lighting up #492's ticket replies. (`notifications` table
   already exists.)
2. New `App\Notifications\DocumentSharedNotification` (`ShouldQueue`, `['mail', 'database']`,
   links `/portal/documents`). Fired from `App\Actions\Portal\ShareDocumentWithContact` after
   the document is saved:
   `PortalCustomer::forEmail($contact->email)?->notify(new DocumentSharedNotification($document))`.
   `PortalCustomer::forEmail` (#485) resolves the active portal customer (global `customer`
   role) by email; **null-safe** — a Contact with no portal account notifies no one, and never
   mutates permission-team context.

## Security / tenancy

The document share is already tenant-safe (#494). The notification targets only the one
portal customer whose email matches the Contact — no broadcast, no cross-tenant reach.

## Testing (TDD, PHPUnit sqlite → MySQL-verify)

1. `Filament::getPanel('portal')->hasDatabaseNotifications()` is true.
2. Sharing a document with a Contact that **is** a portal customer sends
   `DocumentSharedNotification` to that user (`Notification::assertSentTo`).
3. Sharing with a Contact that is **not** a portal customer sends nothing and does not error
   (`Notification::assertNothingSent`).

phpstan 0-new, MySQL 8.4-verified, pint clean. Inline TDD, one PR to `main`.

## Out of scope

Notification preferences / digest / per-channel opt-out; notifying on ticket close/reopen or
KB changes; real-time (websocket) delivery; unread-count badges beyond Filament's built-in.

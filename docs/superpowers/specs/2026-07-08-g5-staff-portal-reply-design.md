# G_5 — Staff reply to portal tickets + portal thread display (design)

**Date:** 2026-07-08
**Status:** approved, implementing
**Epic:** G_5 customer portal (extension after the 12-slice core #480–#491).

## Problem

The customer↔staff support loop is broken for **portal-origin** tickets, in both directions:

1. **Staff can't reply.** The app-panel `TicketResource` `reply` action routes every reply
   through `UnifiedHelpDeskService::sendReply($source_id, $content, $source, $account_id)`,
   which only supports external channels (gmail/whatsapp/outlook/imap/pop3/facebook). A
   portal ticket has `source='portal'` and the `0` `account_id` sentinel, so the call throws
   (`OAuthConfiguration::findOrFail(0)`, then `default => throw "Unsupported channel"`). The
   staff reply fails, persists no `Message`, and never reaches the customer.

2. **The customer can't see the thread.** The portal `ViewTicket` (a `ViewRecord`) renders
   only the resource `form()` read-only (subject/body/priority/attachment). It shows **no
   message thread** — so even the customer's own replies (#480) and any staff reply are
   invisible in the portal. A persisted reply is worthless without a thread view.

Customers can already *see* staff/customer messages on the staff side (app-panel
`MessagesRelationManager`), so only the two gaps above remain.

## Decisions

- **Persist, don't route.** For `source === 'portal'` tickets, staff replies persist a
  `Message` to the ticket thread instead of calling the external service. Mirrors the
  customer reply idiom (#480 portal `ViewTicket`): `channel='portal'`, `content`,
  `priority = ticket->priority ?: 'medium'`, `status='unread'`, `account_id = (int)($ticket->account_id ?? 0)`,
  `metadata=[]`, `timestamp=now()`, `ticket_id`, and `team_id` set via `setAttribute` from
  the ticket (not fillable; the portal's null tenant context won't stamp it).
- **Sender = staff display name**, not email — the customer sees the thread, so no internal
  staff email is exposed.
- **Notify the customer.** A new `TicketReplyNotification` (`ShouldQueue`, `['mail','database']`)
  goes to `$ticket->user` (the requester) only — tenant-safe. Mail links to
  `/portal/tickets/{id}` (fixed portal path); `database` feeds the portal notification bell.
- **Portal thread view.** `ViewTicket` gains an `infolist()` rendering the ticket header
  plus a `RepeatableEntry` over `messages` (sender / content / timestamp). The record is
  already ownership-scoped (`TicketResource::getEloquentQuery` → `user_id = auth`), so the
  thread only ever shows the customer's own ticket's messages.
- **Non-portal replies unchanged.** Email/social tickets still route through
  `UnifiedHelpDeskService::sendReply` — a regression test guards this.

## Architecture

- `App\Actions\Portal\ReplyToPortalTicket` (invokable: `__invoke(Ticket, string $content, User $staff): Message`)
  — persists the `Message` + notifies `$ticket->user`. Unit-testable, mirrors
  Invite/RevokePortalCustomer.
- `App\Notifications\TicketReplyNotification(Ticket $ticket)` — mail + database.
- Edit app-panel `TicketResource` reply action: branch on `$record->source === 'portal'`
  → `ReplyToPortalTicket`; else the existing service call. Status update / cache flush /
  success-notification unchanged.
- Edit portal `ViewTicket`: add `infolist()` with the message thread.

## Security / tenancy

Staff act on the tenant-scoped `app` panel → they only touch their team's tickets. The
`Message` inherits `ticket.team_id`, staying in-tenant and visible to staff too. The
notification targets only the ticket's own requester. The portal thread is gated by the
already-scoped record resolution (foreign ticket = 404, #480). No cross-tenant surface.

## Testing (TDD, PHPUnit sqlite → MySQL-verify)

1. Staff reply to a portal ticket **persists a scoped `Message`** (channel=portal, sender=staff
   name, team_id=ticket team, content) + sets ticket status.
2. Staff reply **notifies the customer** (`Notification::assertSentTo(ticket->user, TicketReplyNotification)`).
3. Staff reply to a portal ticket **does not call** `UnifiedHelpDeskService` (mock: `shouldNotReceive`).
4. Staff reply to a **non-portal** ticket **still routes** to `UnifiedHelpDeskService::sendReply`
   (mock: `shouldReceive->once`) and sends no portal notification — regression guard.
5. Customer **sees the conversation thread** on portal `ViewTicket` (both a customer and a
   staff message rendered).
6. `ReplyToPortalTicket` unit: direct invoke persists the message + notifies.

phpstan 0-new (use `getAttribute`/`getKey`/`setAttribute` — models lack `@property` docblocks),
MySQL 8.4-verified, pint clean. Inline TDD, one PR to `main`.

## Out of scope (ceilings)

Staff reply attachments; rich text; per-message read receipts; closed-ticket auto-reopen on
reply (keeps existing status behavior); real-time thread updates (portal already polls);
digest/batching of reply notifications.

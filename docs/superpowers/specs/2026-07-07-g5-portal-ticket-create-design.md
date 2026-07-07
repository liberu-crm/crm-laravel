# G_5 slice 2 — Portal ticket creation (design)

**Date:** 2026-07-07
**Status:** approved, implementing
**Epic:** G_5 customer portal, slice 2. Slice 1 (#480) shipped the portal panel + own-ticket
view/reply. This adds customer-initiated ticket creation, closing the support loop.

## Problem

A portal customer can view and reply to tickets but cannot raise a new one — every ticket
still originates from the staff-side email/WhatsApp intake. Customers need a "New ticket"
surface. The sharp edges: `tickets.email_id` is unique NOT NULL (intake uses the mail
message-id), and a ticket needs a `team_id` or it is invisible to staff on the team-scoped
`app` panel.

## Decisions (locked with user)

1. **Require a team — block if missing.** A ticket's tenant = the customer's
   `current_team_id`. If the customer has none, creation is blocked (no orphan team-null
   tickets). The onboarding slice provisions the customer↔team link; until then a team-less
   customer simply cannot create (loud, safe).
2. **Form:** subject + description + priority + a single file attachment.
3. **Notify staff:** dispatch a `NewTicket` event wired into the existing
   `SendCRMEventNotification`, so the owning team's staff are told.

## Architecture

### 1. Create surface — portal `TicketResource`

Add a `CreateTicket` page (resource is currently index + view). Gate:

```php
public static function canCreate(): bool
{
    return filled(Auth::user()?->current_team_id);
}
```

`canCreate() == false` hides the New-ticket button **and** 403s the `/portal/tickets/create`
route for a team-less customer.

**Form components:** `subject` (TextInput, required) · `body` (Textarea, required, labelled
"Description") · `priority` (Select low/medium/high, default `medium`) · `attachment`
(FileUpload, single, private `local` disk, accepted mime types + max size).

**Server-set on create** (via `mutateFormDataBeforeCreate`, never from client input):

- `user_id` = `Auth::id()`
- `team_id` = `Auth::user()->current_team_id` (guaranteed non-null past `canCreate`)
- `source` = `'portal'`
- `email_id` = `'portal-'.Str::uuid()` (satisfies the unique NOT NULL column)
- `status` = `'open'`
- `priority`, `body`, `subject`, `attachment` from the form.

`afterCreate()` dispatches `NewTicket::dispatch($this->record)`.

### 2. Attachment (trust boundary)

- Stored on the **private** `local` disk (never a public URL).
- Upload validated: `->acceptedFileTypes([...])` + `->maxSize(...)`.
- Path persisted to a new nullable `tickets.attachment` column.
- Retrieval: a `download` action on the portal `ViewTicket` page, shown only when
  `attachment` is set, streaming via `Storage::disk('local')->download(...)`. The portal
  resource query is already `user_id`-scoped, so record resolution guarantees a customer
  downloads only their own ticket's file.
- Staff-side display of the attachment on the `app` panel is a later follow-up; the column
  exists, but this slice does not touch the staff resource.

### 3. `NewTicket` event + notification

`App\Events\NewTicket` mirrors `App\Events\NewLead`:

```php
public function __construct(public Ticket $ticket) {}
public function team(): ?Team { return $this->ticket->team; }
public function toArray(): array { return [ 'id' => ..., 'subject' => ..., 'priority' => ..., 'team_id' => ... ]; }
```

- Register `'App\Events\NewTicket' => [SendCRMEventNotification::class]` in `EventServiceProvider`.
- Add `'new_ticket' => true` to `config/crm.php` (`notifications.events`).
- The listener notifies `ticket.team->allUsers()` — the owning tenant's staff only (the
  #473 anti cross-tenant leak). Team is required, so `team()` is never null.

### 4. Migration

Guarded, nullable `tickets.attachment` (string).

## Testing (TDD, PHPUnit + sqlite, then MySQL-verify)

- **Team'd customer creates:** create page mounts (200); the new ticket has `user_id` = self,
  `team_id` = own team, `source` = 'portal', a unique `email_id`, `status` = 'open', the
  chosen priority; the attachment is stored on the private disk and its path saved.
- **Team-less customer blocked:** `GET /portal/tickets/create` → 403.
- **Notification:** creating a ticket dispatches `NewTicket` and a staff user on the ticket's
  team receives a notification (`Notification::fake` + real dispatch through the listener).
- **Attachment download:** the owner streams their own ticket's file (foreign already 404s via
  the slice-1 `user_id` scope).
- MySQL 8.4 verify (migration + create); phpstan 0-new; pint clean.

## Out of scope (later slices)

Multiple attachments, staff-side attachment display on the `app` panel, customer edit/close of
a ticket, ticket categories/types, rate-limiting / spam guard, portal branding.

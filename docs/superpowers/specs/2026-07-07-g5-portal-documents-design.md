# G_5 slice 5 — Portal document access (design)

**Date:** 2026-07-07
**Status:** approved, implementing
**Epic:** G_5 customer portal, slice 5. Lets a customer see and download the documents shared
with them.

## Problem / scope note

The user asked for "quote/document access", but there is **no quote/proposal artifact** for
customers — `QuoteRequest` is an inbound lead form (name/email/message). So this slice is
**document access** only. A customer-received "quote" would need a new billing/proposal model
(a separate epic).

`Document` is polymorphic (`documentable` morphTo) and carries `team_id` but is **not**
`IsTenantModel` (no auto-scope). `Contact` already has `documents()` (morphMany), and
onboarding links a customer to a `Contact` by email + team. So "documents shared with me" =
the documents attached to the customer's own Contact.

## Decision (locked with user)

**Scope to the customer's own Contact's documents** (leak-safe, per-customer). A customer never
sees internal or other customers' files.

## Architecture

### Portal resource — `Filament\Portal\Resources\DocumentResource`

Read-only, slug `documents`, index only (no create/edit/view/delete pages).

- **Resolve the customer's contact:** `Contact` where `team_id = customer.current_team_id` and
  `email = customer.email` → its id (null if none).
- `getEloquentQuery()` → `where('team_id', <current_team_id>)
  ->where('documentable_type', Contact::class)->where('documentable_id', <contactId>)`. A null
  contact id yields zero rows (safe default). Filament resolves table rows through this, so a
  row action can only ever act on the customer's own documents.
- **List:** name/title, type (badge), size, updated_at. A **Download** row action that reuses
  `DocumentService::download($record)` (same default disk + filename convention as the rest of
  the app), streaming only the resolved (scoped) record.

No `{record}` route is registered, so there is no direct-URL IDOR surface; the download action
resolves through the scoped table query.

## Testing (TDD, PHPUnit + sqlite, then MySQL-verify)

- **Only own-contact documents:** a customer with a matching Contact sees documents attached to
  that Contact; a document on another contact (same team) and a document on another team are
  **not** listed.
- **No matching contact → empty:** a customer whose email matches no contact sees nothing.
- **Download:** the customer streams their own document (`Storage::fake` + a stored file →
  `assertFileDownloaded`).
- MySQL 8.4 verify; phpstan 0-new; pint clean.

## Out of scope / ceilings (stated)

No customer-received quotes/proposals (no such model); documents attached to a customer's Deals
or Company are not shown (Contact only — the direct identity link); no upload from the portal;
no per-document share toggle; email→contact match assumes one contact per email per team.

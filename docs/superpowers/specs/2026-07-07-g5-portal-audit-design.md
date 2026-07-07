# G_5 slice 11 — Invite/revoke audit log (design)

**Date:** 2026-07-07
**Status:** approved, implementing
**Epic:** G_5 customer portal, slice 11. A compliance trail for who granted or removed a
customer's portal access, and when.

## Problem

Onboarding (#482) and revoke (#485) change a person's access to tenant data, but leave no
record — there's no way to answer "who invited this customer?" or "when was access revoked?".

## Decision

Reuse the existing `AuditLog` model / `audit_logs` table (no new table). Record an entry on each
invite and revoke: the acting staff user, an action code, a description, and the affected
customer as the polymorphic `auditable`.

## Architecture

- **`AuditLogService::record(action, description, ?auditable)`** — a richer companion to the
  existing thin `log()`. Sets `user_id = Auth::id()`, the action/description/ip, and the
  `auditable` morph. **Skipped when there is no authenticated actor** (`audit_logs.user_id` is
  NOT NULL and an unattributable entry has no compliance value). `team_id` is stamped by
  `AuditLog`'s `IsTenantModel` hook from the active tenant.
- **`InvitePortalCustomer`** — after provisioning + mailing the link, records
  `portal.invited` against the new customer User.
- **`RevokePortalCustomer`** — after stripping the role, records `portal.revoked` against the
  User.
- Both actions gain a constructor-injected `AuditLogService`.

The Filament invite/revoke actions run authenticated (staff), so entries are always attributed
there; direct/console invocations without an actor simply skip the log.

## Testing (TDD)

- Inviting (as a staff actor) writes an `audit_logs` row: `action = portal.invited`, `user_id =`
  the staff, `auditable` = the customer.
- Revoking writes `action = portal.revoked` against the customer.
- With no authenticated actor, no entry is written (guard holds; existing #482 unit tests that
  call the action without `actingAs` stay green).
- MySQL 8.4 verify; phpstan 0-new; pint clean.

## Out of scope (ceilings)

No admin UI to browse the portal audit trail (the entries live in `audit_logs`, viewable by
whatever reads that table); no diff/`changes` payload (the action codes carry the meaning); no
audit of login/logout or ticket actions (this slice covers access grant/revoke only).

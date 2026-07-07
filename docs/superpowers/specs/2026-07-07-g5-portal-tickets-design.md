# G_5 slice 1 — Customer portal: auth + own-ticket self-service (design)

**Date:** 2026-07-07
**Status:** approved, implementing
**Epic:** G_5 customer portal — the first of several slices. This one is the coupled auth
foundation plus a first visible feature (ticket self-service). Later slices: KB browse,
quote/document access, portal ticket *creation*, invite/set-password onboarding, branding.

## Problem

The CRM has two staff surfaces — `admin` (global) and `app` (team-scoped staff) — but no way
for a tenant's **end customers** to log in and self-serve. Customer-facing data already
exists (`Ticket` + `Message` threads, `KnowledgeBaseArticle`, `QuoteRequest`, `Document`),
but it is only reachable by staff. G_5 adds a third surface for external customers.

## Decisions (locked with user)

1. **First slice = thin vertical:** magic-link was declined; customers **log in and view +
   reply to their own support tickets**. Establishes the portal auth foundation and ships a
   usable feature in one PR.
2. **Identity = `User` + a new `customer` role.** No separate identity table, no new auth
   model. Reuses the existing `users` table, session guard, and Spatie RBAC.
3. **Auth = password**, via the portal panel's own Filament login (self-contained; does not
   depend on Fortify's admin-disabled routes).
4. **Surface = a third Filament panel** (`portal`), reusing Filament auth + resource
   scaffolding. Ceiling: Filament chrome, minimal customer branding (a later slice).

## Architecture

### 1. `Role::Customer`

Add `case Customer = 'customer';` to `App\Enums\Role`. Seeded as a **global** Spatie role
(`team_id` null — a customer is not per-team staff). `super_admin` stays the only other
global role; `admin/manager/sales_rep/free` remain per-team (F4).

### 2. Panel access fencing (trust boundary)

Rework `User::canAccessPanel` from its current `default => true` (which today lets *any*
authenticated user into `/app`):

```php
return match ($panel->getId()) {
    'super_admin' => $this->hasRole(Role::SuperAdmin),
    'admin'       => $this->hasRole(Role::Admin) || $this->hasRole(Role::SuperAdmin),
    'portal'      => $this->hasRole(Role::Customer),
    default       => ! $this->hasRole(Role::Customer), // app + future: staff only
};
```

Two-way isolation: a customer reaches **only** `/portal`; staff (non-customer) cannot reach
`/portal`. This closes the pre-existing `default => true` hole.

### 3. `PortalPanelProvider`

New panel: id `portal`, path `/portal`, `->login()`, **non-tenant** (no `->tenant()`;
customers need no Jetstream team membership). Web guard. Discovers resources under
`app/Filament/Portal/Resources`. Minimal brand.

### 4. `Filament\Portal\Resources\TicketResource` (over existing `Ticket`)

- **Ownership scope:** `getEloquentQuery()` = `parent()->where('user_id', Auth::id())`.
  Filament resolves *record binding* through this query, so `/portal/tickets/{id}` for a
  ticket the customer does not own → **404** (closes IDOR, same class as the F1 API fix). The
  portal panel is non-tenant, so `Ticket`'s IsTenantModel `'tenant'` scope runs under null
  context = **inert** (adds no predicate) → no `tickets.team_id` fatal.
- **List:** subject / status / priority / updated_at, read-only, newest first.
- **View page:** the ticket plus its `messages()` thread.
- **Reply action** (on the View page): creates a `Message`
  `{ticket_id: <scoped record>, content, sender: <customer>, channel: 'portal',
  team_id: <ticket tenant>, timestamp: now}`. `ticket_id` is taken from the resolved
  (already ownership-scoped) record — never from client input — so a customer cannot post to
  another customer's ticket.

### 5. Reply visibility — `tickets.team_id` (drift fix, folded in)

A staff agent views tickets on the tenant-scoped `app` panel, so a reply `Message` must carry
the correct `team_id` or it is invisible to staff. `messages` has `team_id`; **`tickets` does
not**, despite `Ticket` using `IsTenantModel` (recurring CRM model↔schema drift). Fold the fix
in: a guarded migration adds nullable `team_id` (FK, `nullOnDelete`) to `tickets`, backfilled
best-effort from the requester's `current_team_id`. The reply inherits `ticket.team_id`. This
makes `Ticket`'s IsTenantModel honest and keeps the reply in the tenant's data.

## Onboarding ceiling (stated, not built)

Customers minted by the email-to-ticket flow have no password. Slice 1 assumes a credentialed
customer (staff-set, or Fortify's existing "forgot password" reset, which already mails a
set-password link to any `User` by email — zero new code). A dedicated invite/set-password
flow is a later slice.

## Testing (TDD, PHPUnit + sqlite, then MySQL-verify)

- **Panel access, both directions:** a `customer` can GET `/portal`; cannot GET `/app` or
  `/admin`. A staff user (`manager`) cannot GET `/portal`.
- **Own-tickets only:** seed tickets for two different users; the customer's list shows only
  their own.
- **Foreign ticket 404 (IDOR):** opening another user's ticket id via the portal resource →
  not found.
- **Reply creates a correctly-scoped `Message`:** right `ticket_id`, `sender`, `channel`,
  and `team_id` inherited from the ticket.
- **Reply cannot target a foreign ticket:** attempting to reply against a ticket the customer
  does not own is blocked by the scoped record resolution.
- **`tickets.team_id` migration** applies on MySQL 8.4; phpstan 0-new; pint clean.

Filament test idiom (from F3): assign the role with `setPermissionsTeamId(null); assignRole(...)`,
mount pages via HTTP GET `/portal/...`, exercise table/record actions via Livewire test
helpers.

## Build shape

Coupled auth + panel foundation → **inline TDD, one PR to `main`** (not parallel — same as
F4-RBAC and every F3 slice). Full suite green, phpstan 0-new vs the stale 21-entry baseline,
MySQL-verified, pint clean.

## Out of scope (later slices)

KB browse, quote/document access, **ticket creation** from the portal, invite/set-password
onboarding, portal branding/theming, per-customer multi-team, portal notifications.

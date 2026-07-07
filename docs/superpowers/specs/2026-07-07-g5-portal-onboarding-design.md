# G_5 slice 3 — Customer onboarding (design)

**Date:** 2026-07-07
**Status:** approved, implementing
**Epic:** G_5 customer portal, slice 3. Slices 1–2 (#480/#481) shipped view/reply and ticket
creation, but creation requires a `current_team_id` and portal login requires a password —
neither of which an email-intake customer has. This slice provisions both.

## Problem

A portal customer is a `User` with the `customer` role, but nothing gives that user (a) a
password to log in or (b) the `current_team_id` that ticket creation demands. Result: the
portal is unusable for real customers. Onboarding closes both gaps in one staff-initiated
flow.

## Decisions (locked with user)

1. **Staff invite from a Contact.** An "Invite to portal" action on the app-panel
   `ContactResource`. The contact supplies email + name, and its `team_id` is the tenant the
   customer belongs to (resolves routing).
2. **Filament portal password-reset for the credential.** Enable `->passwordReset()` on the
   portal panel; the invite emails a one-click portal reset link (Laravel's broker token +
   `Panel::getResetPasswordUrl`). The customer sets their password on the built-in reset page,
   then logs in.

## Architecture

### 1. `App\Actions\Portal\InvitePortalCustomer` (invoked by the Filament action)

`__invoke(Contact $contact): User` — a testable unit holding all provisioning logic.

- **Guard — no email:** contact without an email → `InvalidArgumentException` (surfaced as a
  Filament notification by the action).
- **Guard — no staff downgrade (trust boundary):** if a `User` with that email exists and
  holds any non-`customer` role, refuse. Never convert a staff account into a customer.
- **Provision:** `firstOrCreate` the User keyed on email (`name` from the contact, a **random**
  password so the account cannot be logged into until reset). Set `current_team_id =
  contact.team_id`, `email_verified_at = now()` (staff vouched + the reset link proves email
  ownership; the portal expects a verified user). Assign the global `customer` role under
  `setPermissionsTeamId(null)`.
- **Send:** `$token = Password::broker()->createToken($user)`; `$url =
  Filament::getPanel('portal')->getResetPasswordUrl($token, $user)`; `$user->notify(new
  PortalInvitation($url))`.
- **Idempotent:** re-inviting an existing customer re-sends the link.

### 2. `App\Notifications\PortalInvitation`

Mail channel. "You've been invited to the customer portal — set your password:" + the reset
URL. Queued.

### 3. Portal panel

Add `->passwordReset()` to `PortalPanelProvider`. This registers the portal's own
request/reset pages; the invite link lands directly on the reset page (token + email
pre-filled).

### 4. Trigger — `ContactResource` action

An "Invite to portal" row action (app panel, staff, team-scoped). Calls
`InvitePortalCustomer`; success/failure surfaced as a Filament notification. Visible only when
the contact has an email.

## Result

Customer clicks the emailed link → sets a password on the portal reset page → logs in at
`/portal` with email + password. Holding a `current_team_id`, they can raise tickets (slice 2)
and see/reply to their own (slice 1).

## Testing (TDD, PHPUnit + sqlite, then MySQL-verify)

- `InvitePortalCustomer` provisions a customer from a contact: `customer` role,
  `current_team_id` = contact's team, `email_verified_at` set, a non-empty password hash; and
  sends `PortalInvitation` (`Notification::fake`).
- **Guard:** an email already belonging to a staff user is refused; the staff user's roles are
  untouched.
- **Guard:** a contact with no email is refused.
- **Re-invite:** a second invite of the same contact does not duplicate the user and re-sends.
- **Filament action:** the `ContactResource` action invokes the provisioning (user created +
  notified).
- **Panel wiring:** `GET /portal/password-reset/request` → 200 (confirms `->passwordReset()`).
- MySQL 8.4 verify; phpstan 0-new; pint clean.

## Out of scope (later slices)

Revoke/disable portal access, resend throttling UI, one customer across multiple tenants,
self-registration, bulk invite, reset-email branding, tying the portal user back onto the
Contact as a relation.

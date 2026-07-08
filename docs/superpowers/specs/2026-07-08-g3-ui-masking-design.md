# G3 ABAC — field masking in the Filament UI (design)

**Date:** 2026-07-08
**Status:** approved, implementing
**Epic:** G3 ABAC follow-up. Extends #507 (serialization masking) to the staff UI.

## Problem

#507 masks sensitive fields in serialized output (API / toArray), but the app-panel
`ContactResource` table reads attributes directly, so a masked-role (`free`) user still sees
the real `email` / `phone_number` in the Filament UI. Close that gap and make the mask logic
reusable.

## Design

- Add a reusable `MasksFields::maskFor(string $field, mixed $value): mixed` — returns the
  `'[hidden]'` mask when the value is non-null, the field is masked, and
  `AccessContext::shouldMaskFields()` is true; otherwise the value. Refactor
  `attributesToArray()` to use it (behavior unchanged).
- `ContactResource` `email` / `phone_number` columns get
  `->formatStateUsing(fn ($state, Contact $record) => $record->maskFor('<field>', $state))`,
  so the table shows `'[hidden]'` for a masked-role viewer and the real value for everyone else.

## Testing (TDD)

1. `maskFor` masks a masked field for a `free` user, leaves a non-masked field, and returns the
   real value for a `manager`.
2. Filament table: a `free` user (with the contact in their territory so it is visible) sees
   `'[hidden]'`, not the real email, in `ListContacts`.
3. Filament table: a `manager` sees the real email.

phpstan 0-new, MySQL 8.4-verified, pint clean. Inline TDD, one PR to `main`.

## Out of scope / ceiling

- Search still queries the real column, so a masked column that stays `searchable()` lets a
  user confirm a value by searching for it (display-level mask; the record is already
  territory-scoped). Restricting search per role is a later refinement.
- Edit/View form masking, masking on other resources, per-field role config.

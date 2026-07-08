# G3 ABAC — don't search masked columns (design)

**Date:** 2026-07-08
**Status:** approved, implementing
**Epic:** G3 ABAC. Closes the ceiling noted in #508 (UI field masking).

## Problem

#508 masks `email` / `phone_number` in the `ContactResource` table, but the columns stay
`searchable()`. So a masked-role (`free`) user can type a real email into the table search and
the query matches the real column — confirming a value the UI otherwise hides. The mask is
bypassable via search.

## Fix

Make the masked columns non-searchable for masked-role viewers. `AccessContext::shouldMaskFields()`
is resolved at `table()` build time (request-scoped, current user), so:

```php
TextColumn::make('email')->searchable(! AccessContext::shouldMaskFields())
```

A `free` user gets `searchable(false)` on `email` / `phone_number` (no match on those columns);
everyone else keeps full search. Other columns (name, etc.) stay searchable for all.

## Testing (TDD)

1. A `free` user: the contact is visible unfiltered (territory), but searching its **real
   email** returns no rows (the masked column is not searchable).
2. A `manager`: searching the email finds the contact.

phpstan 0-new, MySQL 8.4-verified, pint clean. Inline TDD, one PR to `main`.

## Out of scope

Global-search masking on other resources, edit/view-form masking, encrypting the stored value.

# G3 ABAC — mask sensitive fields in the edit form (design)

**Date:** 2026-07-08
**Status:** approved, implementing
**Epic:** G3 ABAC. Closes the last masking gap after #507 (serialization) / #508 (table) /
#509 (search).

## Problem

A masked-role (`free`) user opening a contact's **Edit** page still sees the real `email` /
`phone_number` in the form inputs. That's a read leak of the exact data the table + API hide.

## Fix (mask without corrupting on save)

Masking a form field naively would let the mask overwrite the real value on save. So on the
`email` / `phone_number` inputs, **only on edit (not create), only for a masked viewer**:

- `->disabled(...)` — the field is read-only.
- `->formatStateUsing(... '[hidden]' ...)` — it displays the mask, not the real value.
- `->dehydrated(...)` false — it is **not submitted**, so a save (e.g. changing the name)
  **preserves** the stored email/phone. This is the critical no-corruption property.

Gated on `$operation !== 'create' && AccessContext::shouldMaskFields()` so create is normal
(a user must be able to enter the value) and non-masked users edit normally.

## Testing (TDD)

1. A `free` user on the Edit page sees `'[hidden]'` in the email field (form state masked).
2. **No corruption:** a `free` user changes the name and saves → the stored `email` is still the
   real value (the disabled, non-dehydrated field was not written).
3. A `manager` on the Edit page sees the real email.

phpstan 0-new, MySQL 8.4-verified, pint clean. Inline TDD, one PR to `main`.

## Out of scope

Masking in create (no existing value to hide), masking other resources' forms, encrypting the
stored value, per-field role config.

# G3 ABAC — slice 3: field-level masking (design)

**Date:** 2026-07-08
**Status:** approved, implementing
**Epic:** G3 ABAC (final slice). Follows #505 (territories) / #506 (territory scoping).

## Problem

Territory scoping hides whole records; field masking hides *fields* of records a user may
otherwise see. A low-tier user should be able to see a Contact exists without seeing its
sensitive contact details (email / phone).

## Design — mask at serialization (not attribute mutation)

Masking is applied in `attributesToArray()` (i.e. `toArray()` / `toJson()` / API responses),
**not** by mutating the stored attributes. So:

- direct attribute access (`$contact->email`) and any save/business logic see the **real**
  value — no risk of a mask overwriting data;
- serialized / API output masks the field for a masked-role viewer.

Components:

- `App\Traits\MasksFields`:
  - `maskedFields(): array` (from `protected $maskedFields = [...]`).
  - overrides `attributesToArray()`: when `AccessContext::shouldMaskFields()` is true, each
    non-null masked field becomes the constant mask `'[hidden]'`.
- `App\Support\AccessContext::shouldMaskFields(): bool` — true when the current user holds a
  masked role (`MASKED_ROLES = ['free']` — the most limited tier); false for
  sales_rep / manager / admin / super_admin / roleless / non-auth. Same guard resolution
  (sanctum → default) as the other AccessContext methods.
- Apply to `Contact`: `protected $maskedFields = ['email', 'phone_number']`.

## Testing (TDD, PHPUnit)

1. A `free` user: `$contact->toArray()` masks `email` + `phone_number` (`'[hidden]'`) but keeps
   `name`.
2. A `manager`: `toArray()` shows the real `email` / `phone_number`.
3. **No mutation:** even for a `free` user, `$contact->email` (direct access) is the real value.
4. `AccessContext::shouldMaskFields` is true for `free`, false for `manager`.

(One `actingAs` per test method — the sanctum guard caches its user within a request, per the
#506 note.) phpstan 0-new, MySQL 8.4-verified, pint clean. Inline TDD, one PR to `main`.

## Out of scope

Masking in the Filament UI columns (serialization/API only for now), per-field role config
(fixed `free` gate + `Contact` fields), partial masks (last-4), encryption, masking on other
models.

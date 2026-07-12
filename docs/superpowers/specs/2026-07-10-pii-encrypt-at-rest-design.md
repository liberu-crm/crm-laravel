# PII encrypt-at-rest (masked-field hardening) — design (2026-07-10)

Encrypt the masked PII columns at rest so a DB dump doesn't expose them, without
breaking the queries that use them. Scope: **PII only** — `Contact.email`,
`Contact.phone_number`, `Company.phone_number`. Money columns stay plaintext
(they need DB sort / range / SUM for forecasting; masking already hides them from
the `free` role, and deal values aren't secrets). Credentials were already
encrypted in the 1.4/1.5 sweep. This is the final feature epic → **2.0.0**.

## Mechanism

`'<col>' => 'encrypted'` cast + widen the column to `text` (ciphertext is
longer). Attribute reads/writes decrypt transparently, so `$contact->email`,
`associateWithCompany` (reads the domain off the attribute), and the masking
display are all unaffected — only DB `where` / `LIKE` / `ORDER BY` / `unique` on
the raw column break.

### Blind index — `Contact.email` only

`email` is looked up by equality and is `unique`. Encryption uses a random IV, so
the ciphertext is neither queryable nor uniquely indexable. Fix with a
deterministic blind index:
- New column `contacts.email_hash` = `hash_hmac('sha256', strtolower(trim(email)),
  config('app.key'))` (hex, 64 chars), **`unique`** — which *inherits the email
  uniqueness* the plaintext column used to enforce.
- `Contact::hashEmail(string): string` (pure) + a model `saving` hook that keeps
  `email_hash` in sync. `email_hash` is guarded (never mass-assigned).
- Rewrite the two equality lookups — `LiveChatService` and `PersonalizationService`
  (`Contact::where('email', $x)` → `where('email_hash', Contact::hashEmail($x))`).
  The adjacent `Lead::where('email', …)` is **not** touched — Lead's email isn't
  masked/encrypted.
- The Contact table's email search switches to a blind-index **exact** match
  (partial `LIKE` search is gone — documented tradeoff; masking already gated it
  off for `free`).

### Phones — encrypt only

`Contact.phone_number` + `Company.phone_number`: no code does `where('phone_number')`,
so no blind index. Just encrypt and **drop the `LIKE` search** on those two table
columns (they were only ever searched, never equality-matched).

## Migration / backfill

A shared, idempotent helper `App\Support\PiiEncryptionBackfill::encryptColumn(
$table, $column, $hashColumn?, $hasher?)` reads each row's **raw** value (via
`DB::table`, before the cast applies), skips already-encrypted values (try-decrypt
detection → re-runnable), `Crypt::encryptString`s it back, and writes the blind
index when given. Reused by both slices.

Slice-1 migration order (email): `dropUnique(['email'])` → `text('email')->change()`
→ add nullable `email_hash` → `encryptColumn('contacts','email','email_hash',
Contact::hashEmail(...))` → `unique('email_hash')`.

## Slices (one PR each, sequential — 2.0.0 finale)

- **Slice 1 — `Contact.email`:** cast + `email_hash` blind index (unique) +
  `hashEmail`/`saving` hook + rewrite the 2 call sites + blind-index table search +
  migration/backfill + `PiiEncryptionBackfill`. → `2.0.0-rc.1`.
- **Slice 2 — phones:** encrypt `Contact.phone_number` + `Company.phone_number`,
  drop their `LIKE` search, migration/backfill. → `2.0.0-rc.2`.

## Testing

Per column: `getRawOriginal(col)` ≠ plaintext, `fresh()->col` == plaintext,
`Crypt::decryptString` round-trips. Blind index: `hashEmail` deterministic +
case-insensitive; `where('email_hash', hashEmail(x))` finds the row; both rewritten
call sites find the contact; email exact-search works; a duplicate email →
`unique` violation. `associateWithCompany` still works. `PiiEncryptionBackfill`:
raw-insert a plaintext row → run → asserts it becomes ciphertext + hash (idempotent
on re-run). MySQL-verify the migration.

## Security notes

The blind index leaks equality (same email → same hash) — inherent and acceptable;
full HMAC, no truncation. Emails normalized (lowercase) before hashing →
case-insensitive match, and the stored (encrypted) value keeps its original case.
`APP_KEY` rotation orphans the hashes (and the ciphertext) — re-run the backfill
after a rotation. Non-goal: money-column encryption (breaks forecasting for
marginal benefit) and searching encrypted phones.

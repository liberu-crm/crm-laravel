# Encrypt Webhook.secret at rest (design)

**Date:** 2026-07-08
**Status:** approved, implementing
**Epic:** Security: encrypt credentials at rest
**Prerelease:** 1.4.0-rc.3

## Problem

`Webhook.secret` (the HMAC signing secret sent as `X-Webhook-Signature`) is stored as
plaintext `VARCHAR(255)`. A DB read (backup, dump, injection) leaks every tenant's signing
secret, letting an attacker forge webhook deliveries. `SsoConnection.client_secret` already
uses the `encrypted` cast — this brings `Webhook` to parity.

## Design

- `Webhook::$casts` gains `'secret' => 'encrypted'` (mirrors `SsoConnection`). Laravel's
  `encrypted` cast tolerates null, so the nullable column keeps null → null.
- Migration `2026_07_08_000002_encrypt_webhook_secret` widens the column from
  `string` (VARCHAR 255) to nullable `text` — Laravel-encrypted ciphertext exceeds 255 chars.
  `->change()` needs no doctrine/dbal on Laravel 13. `down()` reverts to `string`.
- Consumer confirmed: `WebhookService::send()` reads `$webhook->secret` (the model
  attribute), which now transparently decrypts before `sign()` computes the HMAC. No caller
  reads the raw column, so signing is unaffected.

## Testing (TDD)

`tests/Feature/Security/WebhookEncryptionTest.php`:
1. Raw stored value differs from plaintext; `Crypt::decryptString(rawOriginal)` round-trips it.
2. `fresh()->secret` decrypts back to the plaintext.
3. A webhook created without a secret has `secret === null` (no decrypt error).

## Caveats

- **Existing plaintext rows are not migrated in place** — the widening migration only changes
  the column type, it does not re-encrypt data. Any row written before this change must be
  re-saved (e.g. re-run the secret regeneration) or its plaintext will fail to decrypt on read.
- **The column can no longer be queried by value** — ciphertext is non-deterministic, so
  `where('secret', $x)` will never match. Nothing in the codebase does this today.

## Out of scope

- Backfilling / re-encrypting existing rows (deploy-time data task, not a schema change).
- Rotating the `APP_KEY` or key-versioning of ciphertext.
- Changing the signature scheme, header names, or `WebhookService` verification logic.

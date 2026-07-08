# Encrypt AdvertisingAccount OAuth tokens at rest (design)

**Date:** 2026-07-08
**Status:** approved, implementing
**Epic:** Security: encrypt credentials at rest. Extends the pattern already used for `SsoConnection.client_secret`.

## Problem

`AdvertisingAccount` stores OAuth `access_token` and `refresh_token` in plaintext. A DB dump or
read-only DB access leaks live ad-platform credentials. `SsoConnection.client_secret` already
solves the same problem with Laravel's `encrypted` cast; these columns should match.

## Design — reuse the `encrypted` cast

- Add `'access_token' => 'encrypted'` and `'refresh_token' => 'encrypted'` to
  `AdvertisingAccount::$casts` (mirrors `SsoConnection`).
- No migration: both columns are already `text` (migration
  `2024_10_22_000000_create_advertising_accounts_table.php`), which holds the longer ciphertext.
- Encryption/decryption is transparent — every existing property read
  (`$account->access_token`, `FacebookAdsService`, `LinkedInAdsService`, `AdvertisingAccountResource`)
  keeps working, and writes are encrypted on save. No caller changes.

## Testing (TDD)

`tests/Feature/Security/AdvertisingAccountEncryptionTest.php` (PHPUnit, `RefreshDatabase`):
create an account with explicit plaintext tokens, then for each of `access_token` / `refresh_token`
assert: raw stored value differs from plaintext; the model transparently decrypts it; and
`Crypt::decryptString(getRawOriginal(...))` returns the plaintext.

## Out of scope / Notes

- **Existing plaintext rows will fail to decrypt** once the cast is added — reading them throws a
  `DecryptException`. Any pre-existing `AdvertisingAccount` must be re-saved (re-authorized) to be
  re-stored as ciphertext. No back-fill is done here.
- **These columns can no longer be queried by value** (`where('access_token', ...)`): each row
  uses a random IV, so equality on ciphertext never matches. Grep confirms nothing does this today —
  no `where`/`whereAccessToken`/`whereRefreshToken`/`pluck`/`firstWhere` on these columns exists;
  all usages are property reads that decrypt transparently. (The `OAuthManager` `refresh_token`
  references are on `ConnectedAccount`, a different model, and are also property reads.)
- Not encrypting other columns (`account_id`, `metadata`) or other models here.

## Versioning

GitHub prerelease `v1.4.0-rc.1`.

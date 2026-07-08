# Encrypt OAuthConfiguration.client_secret at rest (design)

**Date:** 2026-07-08
**Status:** approved, implementing
**Epic:** Security: encrypt credentials at rest. Prerelease `1.4.0-rc.2`.

## Problem

`oauth_configurations.client_secret` holds OAuth/IMAP/POP3 provider secrets in
plaintext. `SsoConnection.client_secret` is already `encrypted` at rest;
`OAuthConfiguration` is the last credential column still readable in the DB.
The column is `string` (VARCHAR 255) — too small for an encrypted payload.

## Design

Mirror `SsoConnection`: add `'client_secret' => 'encrypted'` to
`OAuthConfiguration::$casts`. Widen the column `string -> text` via a
`->change()` migration (Laravel 13, no doctrine/dbal). Encrypt/decrypt is
transparent at the model layer — all seven read sites
(`OAuthManager`, `ImapService`, `Pop3Service`, `WhatsAppBusinessService`,
`SocialstreamServiceProvider`) go through the cast unchanged.

## Testing (TDD)

`tests/Feature/Security/OAuthConfigurationEncryptionTest.php`: create a config
with a known plaintext secret, then assert the raw column differs from the
plaintext, `fresh()->client_secret` decrypts back, and the raw column is a
valid `Crypt::decryptString` payload.

## Out of scope

- Backfilling existing rows (see caveats).
- Encrypting `client_id` or `additional_settings`.
- Key rotation.

## Caveats

- **Existing plaintext rows must be re-saved** to encrypt — the cast only
  encrypts on write. A pre-existing plaintext value would fail decryption on
  read. Re-save each row (or one-off backfill) after deploy.
- **`client_secret` can no longer be queried by value** (`where('client_secret', ...)`)
  once encrypted — the ciphertext is non-deterministic. Grep confirms **no**
  code does this today; keep it that way.

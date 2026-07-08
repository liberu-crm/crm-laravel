# Encrypt ConnectedAccount social tokens at rest (design)

**Date:** 2026-07-08
**Epic:** Security: encrypt credentials at rest
**Prerelease:** 1.5.0-rc.1
**Status:** approved, implementing

## Problem

`connected_accounts` stores the social-auth `token`, `secret`, and `refresh_token`
as **plaintext**. These are live OAuth1/OAuth2 credentials — a DB leak or backup
hands an attacker every connected user's social account. `SsoConnection.client_secret`
is already `encrypted`; this closes the same gap on `ConnectedAccount`.

## Design

- Cast `token`, `secret`, `refresh_token` to `encrypted` on `App\Models\ConnectedAccount`
  (added to the existing `$casts`; the parent `casts()` for `created_at`/`expires_at`
  still merges in). Encryption uses `APP_KEY` via `Crypt::encryptString`.
- **Token accessor caveat.** The vendor parent (`HasOAuth2Tokens`) defines a
  `token(): Attribute` accessor (auto-refresh of expired OAuth2 tokens, enabled in
  `config/socialstream.php`). In Laravel, a get-mutator wins over a cast on read, so
  the `encrypted` cast's decrypt is bypassed for `token` and callers would receive
  ciphertext. Fix: override `token()` in the child to `Crypt::decryptString` the raw
  value, then delegate to the parent accessor (refresh logic preserved). No setter is
  added, so writes still encrypt through the cast. `secret`/`refresh_token` have no
  accessor and work through the cast unchanged.
- Ciphertext is far larger than the old column sizes, so migration
  `2026_07_08_000003_encrypt_connected_account_tokens` widens all three via `->change()`
  (Laravel 13 native, no doctrine/dbal), preserving nullability: `token` -> `text`
  NOT NULL, `secret` -> `text` nullable, `refresh_token` -> `text` nullable. `down()`
  reverts to the original `string` sizes.
- Consumers (`TwitterService`, `GoogleAdsService`, `MessageService`, `YouTubeService`,
  `FacebookMessengerService`, `LinkedInService`, `OAuthController`) all read
  `$account->token` / `->secret` / `->refresh_token` as model attributes, so they
  decrypt transparently — no consumer change needed.

## Testing

`tests/Feature/Security/ConnectedAccountEncryptionTest` (PHPUnit + RefreshDatabase):
per column assert the raw DB value differs from the plaintext, `fresh()->col` returns
the plaintext, and `Crypt::decryptString(getRawOriginal(col))` round-trips. A null-secret
case asserts `->secret === null` reads without a decrypt error.

## Caveats / Out of scope

- **Existing plaintext rows are not migrated.** After deploy, reading a pre-existing row
  throws `DecryptException` (Crypt can't decrypt plaintext). Affected users must
  **re-connect their social accounts**; a backfill/re-encrypt job is out of scope.
- **These columns can no longer be queried by value.** Confirmed nothing does: Socialstream
  looks accounts up by `provider` + `provider_id` (`HasConnectedAccounts`, `Socialstream`),
  never by token/secret. A grep of `app/` and the vendor package finds no `where('token'...)`
  / `where('secret'...)` / `where('refresh_token'...)`.
- No key-rotation tooling; rotation follows the standard Laravel `APP_KEY` /
  `php artisan model:prune`-style re-encrypt, out of scope here.

# F3 — Team data restore (design)

**Date:** 2026-07-06
**Status:** approved, implementing
**Slice of F3:** restore — the round-trip partner of backup (PR #476). Sibling slices:
`create` (done), `archive` (#475), `backup` (#476), `clone` (future). Restore is
**same-team disaster recovery**, not cross-env migration (that overlaps `clone`).

## Problem

PR #476 exports a team's data to a JSON-snapshot zip, but nothing consumes it. A backup
you cannot restore is half a feature. This adds restore: re-insert a team's data from one
of its own completed backups.

## Decisions (locked with user)

1. **Target:** **same team, disaster recovery.** Re-insert rows into their *original*
   `team_id` with *original* PKs. Refuses if the team already holds rows (won't
   duplicate/collide). FKs stay valid automatically — no id remapping.
2. **Source:** a **stored `team_backups` record** (trusted artifact already on our disk).
   No uploaded zips — no untrusted-file / zip-slip surface.
3. **Trigger:** super_admin only — a Restore action on `TeamBackupResource` + a
   `team:restore {backup}` command (mirrors backup).

## Backup format fix (matched pair)

Restore defines the contract, so switch the backup's **model** serialization from Eloquent
`->get()->toJson()` to raw **`DB::table()->get()`** (DB-native scalar values). Restore then
becomes a verbatim `DB::table()->insert()` with no cast round-trip ambiguity (json columns
serialize to arrays under Eloquent but the query-builder insert needs strings; raw rows
sidestep it entirely). `format_version` stays `1` — #476 is unreleased, nothing to migrate.
The extras were already raw `DB::table`, so only the models loop changes.

## Architecture

### 1. `App\Services\TeamRestoreService`

`restore(TeamBackup $backup): array` (rows restored per model):

- **Validate:** backup `status === 'completed'`; the file exists on `$backup->disk`; the
  zip opens; `manifest.json` present; `format_version === 1`. Each failure throws a typed
  `TeamRestoreException` with a clear message.
- **Resolve target:** `Team::withoutGlobalScope('archived')->find($manifest['team']['id'])`
  — must exist (same-team recovery). Missing → throw.
- **Guard (refuse if not empty):** if any `TenantModels::all()` table already holds a row
  for that `team_id`, throw `TeamNotEmptyException`. Prevents duplicate/PK-collision.
- **Insert:** one `DB::transaction` wrapping `Schema::withoutForeignKeyConstraints(...)`.
  For each `models/{Model}.json`: decode, `DB::table($table)->insert($chunk)` in chunks,
  preserving original ids/timestamps. FK-off ⇒ insert order irrelevant; the transaction ⇒
  a single bad row rolls back the whole restore (no partial team).
- **Extras untouched:** the team, memberships, and subscription survive a data-loss event
  (rows deleted, team intact), so restore only re-inserts the deleted model data.
- Skips a model whose table is absent (same drift guard as backup).

### 2. `App\Jobs\RestoreTeamBackup` (queued)

Mirrors backup for large-team safety. Runs the service; on success/failure sends a Filament
**database notification** to the initiating super_admin. Not `TenantAware` — writes
unscoped by design.

### 3. Triggers (super_admin only)

- **Restore** row action on `TeamBackupResource`, visible when `status === 'completed'`.
  Strong confirmation modal (writes data). A cheap pre-check (is the target team empty?)
  gives instant "can't restore — team not empty" feedback before dispatching.
- `team:restore {backup}` command — resolves the `TeamBackup`, dispatches the job. Unknown
  id → clean failure.

## Security

- Super_admin only (mirrors backup).
- Trusted on-disk artifact — no upload, no zip-slip surface.
- FK checks disabled **only inside the transaction**; `withoutForeignKeyConstraints`
  restores prior state after.
- Restore writes data → confirmation gate on the UI trigger.

## Testing (TDD, PHPUnit + sqlite, then MySQL-verify)

- **Round-trip:** seed a team + Contact/Lead/Task with cross-model FKs → backup → delete all
  the team's rows → restore → rows return with original ids and FK links intact.
- **Refuse when non-empty:** team still has a row → `TeamNotEmptyException`, nothing changes.
- **Refuse invalid source:** backup not `completed` / file missing / `format_version`
  mismatch → typed exception.
- **Atomicity:** a corrupt `models/*.json` mid-restore → transaction rolls back, zero rows
  inserted.
- **Command:** restores a valid backup; unknown id fails.
- **Admin action:** visible only for `completed` backups; gated to super_admin.
- **MySQL verify:** `withoutForeignKeyConstraints` + explicit-id inserts round-trip on
  MySQL 8.4.

## Out of scope (YAGNI)

- New-team / cross-env restore (that is `clone` — needs full FK remapping).
- Selective/partial restore, merge into a populated team.
- Uploaded zips (untrusted input).
- Restoring extras (team / memberships / subscription).

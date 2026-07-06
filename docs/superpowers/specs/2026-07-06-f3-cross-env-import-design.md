# F3 — Cross-env backup import (design)

**Date:** 2026-07-06
**Status:** approved, implementing
**Relation:** the cross-environment complement to restore (#477). #477 restores a team's
own backup into its original team_id (same DB, original PKs). This imports a backup from a
**different database** into a **brand-new team** with new PKs and a fully remapped FK graph.

## Problem

A backup zip (#476) taken on one install can't be used on another: its `team_id` and every
PK collide with the target DB, and its 19 user-reference columns point at source-env users.
Restore (#477) deliberately handles only the same-env case.

## Decisions (locked with user)

1. **Target:** a brand-new team on the target env; new PKs; full FK remap.
2. **User references:** **importer owns everything.** Every user-ref column
   (assigned_to/created_by/updated_by/user_id/owner_id + any declared FK to `users`)
   resolves to the importing super_admin; the new team's sole member is the importer. No
   backup-format change, no email matching, always valid.
3. **Trigger:** super_admin — an upload action + a `team:import` command.

## Scale (measured)

60 tables carry `team_id`; 101 FK constraints; 19 user-ref columns. Too large to hand-map
(that is what drifted in clone) — so the FK graph is **introspected from the live schema**.

## Architecture

### 1. `App\Support\SchemaGraph`

`edges(): array` → `table => [column => referencedTable]`, read once from
`information_schema.KEY_COLUMN_USAGE` (all 101 FK constraints). Built from the DB, so it
cannot drift. Cached for the request.

### 2. `App\Services\ImportTeamBackupService`

`import(string $localZipPath, string $name, User $importer): Team`:

- **Validate (untrusted input):** zip opens; `manifest.json` present; `format_version === 1`.
  Zip-slip-safe by construction — only fixed entry names are read via
  `getFromName('models/{Name}.json')`; the zip is never directory-walked or extracted to
  disk.
- **New team** owned by `$importer` (`personal_team = false`).
- All inside `Schema::withoutForeignKeyConstraints(fn => DB::transaction(...))` (FK-off
  **wraps** the transaction — sqlite ignores `PRAGMA foreign_keys` once one is open):
  - **Pass 1 — insert:** for each backup `models/{Name}.json` whose table exists, insert
    every row with a fresh PK, `team_id` = new team, all other columns verbatim; record
    `old→new` per table.
  - **Pass 2 — patch** each row's FK columns using `SchemaGraph`:
    - referenced table **users** (or a name-heuristic column: assigned_to / created_by /
      updated_by / user_id / owner_id) ⇒ **importer id**;
    - referenced table **in the imported set** ⇒ remap via that table's `old→new` map;
    - otherwise ⇒ left as-is (best-effort — a ceiling, see below).
- Membership: the importer only.
- Returns the new team.

### 3. `App\Jobs\ImportTeamBackup` (queued)

Full-data import can be large → off the request cycle. Reads the stored upload, calls the
service, notifies the initiating super_admin (Filament DB notification). Not `TenantAware`.

### 4. Triggers (super_admin only)

- **Import backup** header action on the admin `ListTeams` page: a `FileUpload` (zip, size
  limit) + new-team name → store the upload to a private disk → dispatch `ImportTeamBackup`.
- `team:import {path} {--name=} {--owner=}` command (ops; reads a local zip). `--owner`
  defaults to the first super_admin; `--name` defaults to the manifest team name.

## Security

Super_admin only. Upload validated (zip mime + size) and stored to a **private** disk.
Zip-slip-safe (fixed entry names only). Import writes solely into the freshly created team.

## Testing (TDD, PHPUnit + sqlite, then MySQL-verify)

- **Cross-team round-trip:** seed team A with Contact + Task (`task.contact_id` → the
  contact) + a user ref (`task.assigned_to`); back up A; import; assert a **new** team B
  holds the rows with **new PKs**, `task.contact_id` rewired to B's contact, and
  `task.assigned_to` = the importer.
- **Source untouched:** team A's rows and ids unchanged.
- **Bad input rejected:** missing manifest / wrong `format_version` → typed exception.
- Command + action gated to super_admin; unknown / missing file fails cleanly.
- MySQL 8.4 verify (introspection + FK-off remap + explicit-id inserts).

## Out of scope / ceilings (stated, not hidden)

- **Undeclared** FKs (a `*_id` column with no DB constraint) aren't in the schema graph, so
  they are remapped **best-effort by Laravel naming convention** (`contact_id` → `contacts`)
  — the remap only fires when the guessed table was actually imported, so a wrong guess is
  harmless. A reference that follows neither a declared FK nor the naming convention is left
  as-is.
- **Unique-constraint collisions**: importing verbatim rows into a target env that already
  holds a colliding unique value (e.g. a contact email) fails — the whole import rolls back
  atomically (no partial team). Cross-env assumes a disjoint/fresh target; this is a loud,
  safe failure, not corruption.
- The ~7 `team_id` tables that are neither `IsTenantModel` nor a backup extra aren't in the
  backup → not imported.
- No email-based user mapping, no cross-env user/role import, no merge into an existing
  team, no backup-format change.

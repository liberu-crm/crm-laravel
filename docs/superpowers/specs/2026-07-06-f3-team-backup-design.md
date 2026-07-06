# F3 — Team backup / data export (design)

**Date:** 2026-07-06
**Status:** approved, implementing
**Slice of F3:** backup only. Sibling slices already shipped/deferred: `create` (done),
`archive` (PR #475), `clone` (future). Restore/import is a **separate future slice** —
this backup's JSON is deliberately import-shaped so restore can consume it.

## Problem

SCOPE §51 "Tenant backups" (TASKS F3). There is no way to export a team's data — for
operational backup, pre-delete safety, or GDPR portability. 53 models are team-scoped via
`IsTenantModel`; nothing exports them.

## Decisions (locked with user)

1. **Artifact:** a **JSON snapshot zip** — every `team_id` row per model serialized to a
   model-keyed `*.json`, plus a `manifest.json`, bundled in one zip. Portable,
   machine-readable, re-importable later.
2. **Trigger/delivery:** a **queued job** builds the zip to a storage disk and tracks it in
   a `team_backups` row. Triggered by a super_admin admin-panel action **or** a
   `team:backup {team}` artisan command (schedulable). Super_admin downloads the stored
   file.
3. **Model scope:** **auto-discover** every model using `IsTenantModel` (self-maintaining,
   zero drift) plus explicit team-owned extras. Same enumeration `CrossTenantLeakageTest`
   already proves.

## Architecture — small, isolated units

### 1. `App\Support\TenantModels` (enumerator)

Reflects `app/Models/*.php`, returns each instantiable model class using `IsTenantModel`
(via `class_uses_recursive`). Single source of truth for "what is team-scoped."
`CrossTenantLeakageTest` is refactored to consume it, so the test that proves no model
escapes the tenant scope also proves the backup covers every model — completeness is
guarded by an existing test, not a hand-list that drifts.

### 2. `App\Services\TeamBackupService` (pure mechanism)

`backup(Team $team): TeamBackupResult` (path + size). For the given team:

- For each `TenantModels::all()` class: read rows **unscoped** —
  `Model::withoutGlobalScope('tenant')->where('team_id', $team->id)` — `chunkById` to
  avoid loading everything at once, stream each chunk as JSON into `{ModelBasename}.json`.
- Explicit extras (team-owned but not `IsTenantModel`): the `teams` row, `team_user`
  (memberships), `team_invitations`, `team_subscriptions` — queried by `team_id`.
- Write `manifest.json`: `{ team: {id, name}, generated_at, app_version, schema_version,
  models: { Contact: <count>, ... }, extras: { team_user: <count>, ... } }`.
- Bundle into a zip on the configured backup disk; return path + byte size.

Reads unscoped **by design** — a backup must capture all of a team's rows regardless of
request/tenant context. The completeness + "only this team's rows" tests guard it.

### 3. `App\Jobs\GenerateTeamBackup` (queued wrapper)

Takes a team id + the `team_backups` row id. Drives status `pending → processing →
completed | failed`; calls the service; records `path`, `size_bytes`, or `error`. **Not**
`TenantAware` — it reads unscoped intentionally. Works under the `sync` queue in dev and
Horizon in prod.

### 4. `team_backups` table + `TeamBackup` model

Columns: `id, team_id (fk), disk, path (nullable), size_bytes (nullable), status, error
(nullable text), created_by (nullable fk users), timestamps`. Tracks each backup's
lifecycle and location.

### 5. `team:backup {team}` command

Resolves the team (by id), creates a `pending` `team_backups` row, dispatches
`GenerateTeamBackup`. Schedulable for recurring "tenant backups." Unknown id → clean
failure (non-zero exit).

### 6. Admin `TeamBackupResource` (super_admin only)

`canAccess()` gated to `super_admin`. Table lists backups (team, status badge, size,
created_at). **Generate backup** header action (team select → dispatch). Row actions:
**Download** (streams via `Storage::disk($disk)->download($path)`, super_admin-gated —
never a public URL) and **Delete** (removes the file + row).

## Security

- Backups contain PII → written to a **private** disk (default `local`), never `public`.
- Download streamed through the super_admin-gated action, not a signed/public link.
- `team_backups` rows + the resource are super_admin-only.

## Testing (TDD, PHPUnit + sqlite, then MySQL-verify)

- `TenantModels::all()` returns the full team-scoped set; `CrossTenantLeakageTest` consumes
  it (completeness proof).
- `TeamBackupService`: produces a zip that unzips to the expected model JSONs; manifest
  counts match seeded rows; **only the target team's rows are present** (export-side
  cross-tenant check — seed two teams, assert the other team's rows absent).
- `GenerateTeamBackup`: success sets `completed` + path/size; a thrown service error sets
  `failed` + records the message.
- `team:backup`: dispatches the job for a valid team; unknown id fails cleanly.
- Admin: super_admin can generate/download/delete; non-super_admin denied (`canAccess`
  false).
- MySQL migration verify (`team_backups`).

## Out of scope (YAGNI)

- Restore/import (separate slice; JSON is import-shaped for it).
- Retention/expiry auto-prune (Delete action now; a prune command later).
- At-rest encryption beyond the private disk.
- Incremental/diff backups.

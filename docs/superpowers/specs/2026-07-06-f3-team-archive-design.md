# F3 — Team Archive / Lifecycle (design)

**Date:** 2026-07-06
**Status:** approved, implementing
**Slice of F3:** archive/lifecycle only. `create` is already covered (registration →
`CreateNewUserWithTeams`; Jetstream team-create). `clone` and `backup` are separate
future slices, each its own spec.

## Problem

Teams (Jetstream tenants) can only be hard-deleted (`DeleteUserWithTeams` / Jetstream
`deleteTeam`), which destroys all team-scoped data. There is no way to *deactivate* a
team — freeze it, hide it from everyone, keep the data — for off-boarding without loss.

## Decisions (locked with user)

1. **Slice:** archive/lifecycle first (most bounded, highest operational value).
2. **Access when archived:** **full lockout.** Archived team vanishes from every
   member's team list; nobody can switch into it, view, or edit. Data stays in the DB,
   invisible in the app. Restore brings it fully back.
3. **Trigger surface:** mechanism + a **super_admin-only** Archive/Restore action in the
   `/admin` panel. Owner self-service = deferred phase 2.

## Invariants (safety, not preference)

- **Personal teams are never archivable** — a user always needs a home team.
- Archiving **reassigns** any member whose `current_team_id` points at the team to their
  personal team, so no one is stranded mid-session.

## Architecture — one gate

The feature hinges on a single **removable global scope** on `Team` (same idiom as the
existing `IsTenantModel` tenant scope). Hiding archived teams at the query layer cascades
correctly through Jetstream + Filament + the API tenant boundary, instead of hand-filtering
every call site.

### 1. Data model

One guarded migration, MySQL-verified:

```
teams.archived_at TIMESTAMP NULL   // presence = archived; also records "when"
```

No `is_active` bool, no new table — a nullable timestamp is the flag *and* the timestamp.

### 2. Mechanism — on `App\Models\Team`

- `archive(): void` — throws `DomainException` if `personal_team`; sets `archived_at = now()`;
  reassigns members whose `current_team_id === $this->id` to `personalTeam()->id`.
  Idempotent (already-archived → no-op).
- `restore(): void` — clears `archived_at`.
- `isArchived(): bool` — `archived_at !== null`.

### 3. The gate — `App\Models\Scopes\ArchivedTeamScope` (global, removable)

Applied to `Team`. Default queries exclude `archived_at IS NOT NULL`. Local
`scopeWithArchived()` (and `withoutGlobalScope(ArchivedTeamScope::class)`) reveal them.
Cascades:

- `allTeams()` (Jetstream, drives the team switcher) → archived drop out automatically.
- `User::currentTeam` relation → an archived current team resolves to `null` →
  `SetTenantContext` sets a null tenant → **zero data leak** even if `current_team_id`
  still points at it (belt-and-suspenders with the reassign in `archive()`).

### 4. Enforcement points (defense-in-depth, all tested)

- `User::getTenants()` — scope already filters; add an explicit `->reject(isArchived)`
  guard for the direct-relation path.
- `User::canAccessTenant()` — return `false` for an archived team (blocks a hand-crafted
  `/app/{archivedTeamId}` URL).
- `User::getDefaultTenant()` — never resolve to an archived team.

### 5. Admin surface (super_admin only)

No admin `TeamResource` exists today (only `TeamSubscriptionResource`). Add a **minimal**
`TeamResource` on the `/admin` panel: list teams (`withArchived`), a status column, and
**Archive** / **Restore** row actions gated `->visible()` to `super_admin` (via
`hasGlobalRole`), each with a confirmation modal. No create/edit forms (teams are born via
the app registration flow). Owner self-service → phase 2.

## Testing (TDD, PHPUnit + sqlite, then MySQL-verify)

- `archive()` sets timestamp + reassigns stranded members; refuses personal team;
  idempotent.
- `restore()` reverses.
- scope hides archived by default; `withArchived` reveals.
- archived team drops from `getTenants`; `canAccessTenant` false; not returned as default.
- `SetTenantContext` → null tenant for a user stranded on an archived team.
- admin action archives/restores; action hidden from non-super_admin.

## Out of scope (YAGNI)

- No scheduled auto-hard-delete after N days.
- No owner-facing UI (phase 2).
- No cascade to the 53 team-scoped data models — they stay put, invisible via the tenant
  boundary. That is the entire point of full-lockout-not-delete.

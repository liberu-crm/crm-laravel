# F3 — Team template clone (design)

**Date:** 2026-07-06
**Status:** approved, implementing
**Slice of F3:** clone — the last of the four ops (create/clone/archive/backup). Closes F3.

## Problem

No way to stand up a new team pre-configured like an existing one. Onboarding a team means
rebuilding its pipelines, workflows, custom fields, and templates by hand.

## Decisions (locked with user)

1. **What copies:** **config/template only.** Copy the structure a new team needs; do NOT
   copy transactional data (contacts/leads/deals/tasks/…) or members.
2. **Target:** a brand-new team (new PKs → intra-config FKs remapped).
3. **Trigger:** super_admin — a Clone action on the admin `TeamResource` + a `team:clone`
   command.

## Cloneable set — curated, NOT auto-discovered

Config-vs-data is semantic, so an explicit ordered `CLONEABLE_MODELS` list (opposite of
backup's auto-discovery):

```
Pipeline, Stage, Workflow, WorkflowAction, WorkflowCondition, WorkflowTrigger,
EmailTemplate, CustomField, Tag, KnowledgeBaseArticle, Chatbot, ReportBuilder, Menu
```

Excludes all transactional models and members. `SiteSettings` is intentionally omitted (it
has no table — dead/pending drift); a `Schema::hasTable` guard covers any other absent
table.

## FK-remap engine — copy-then-patch under FK-off

New team → new PKs → intra-config FKs must be rewired. Declared edge map (small):

```
Stage.pipeline_id            -> Pipeline
Pipeline.stage_id            -> Stage           (circular pair)
WorkflowAction.workflow_id   -> Workflow
WorkflowCondition.workflow_action_id -> WorkflowAction
WorkflowTrigger.workflow_id  -> Workflow
Menu.parent_id               -> Menu            (self-ref)
```

All work happens inside `Schema::withoutForeignKeyConstraints(fn => DB::transaction(...))`
— the FK toggle **wraps** the transaction (sqlite ignores `PRAGMA foreign_keys` once a
transaction is open; the test DB enforces FKs).

- **Pass 1 — copy:** for each `CLONEABLE_MODELS` class (skip if no table), read every
  source row (`DB::table($table)->where('team_id', $source->id)->get()`); for each row drop
  `id`, set `team_id` = new team, **keep the edge-FK columns at their old values**, insert,
  record `old_id → new_id` per model. FK-off makes the transiently-stale FK values legal.
- **Pass 2 — patch:** for each cloned row, rewrite each edge column via the maps
  (`new = map[referencedModel][old]`); a value with no mapping (or null) is nulled/left.

Keeping old values then patching (rather than null-then-patch) avoids depending on each
FK column being nullable, and handles the **circular** Pipeline↔Stage and **self-ref** Menu
uniformly with no dependency ordering.

Only the declared edge columns are remapped. Config models are self-contained (no user_id
refs among the cloneable set), so no user remapping is needed. Any other `*_id` column is
copied verbatim — acceptable for config; a config row referencing a non-cloned team-scoped
row is out of scope (noted as a ceiling).

## New team + trigger

- `TeamCloneService::clone(Team $source, string $name, User $owner): Team` — creates the
  team (`personal_team = false`, owner = `$owner`), runs the two-pass copy in one
  transaction, returns the new team. **Synchronous** — config is small (no bulk data), so
  no job/queue; the admin gets the new team back immediately.
- Super_admin **Clone** action on the admin `TeamResource`: form = new name (default
  "Copy of {source}") + owner select (default = source team's owner). + `team:clone
  {source} {--name=} {--owner=}` command (owner defaults to source owner; name defaults to
  "Copy of {source}").

## Security

Super_admin only (mirrors archive/backup). Clone reads the source unscoped
(`withoutGlobalScope('archived')` so an archived team is still a valid template) and writes
only into the freshly created team.

## Testing (TDD, PHPUnit + sqlite, then MySQL-verify)

- New team created with the given name + owner; `personal_team = false`.
- Cloned config rows exist under the **new** team_id with **new PKs**; source rows
  untouched (count unchanged, ids unchanged).
- **Remap correctness:** cloned `Stage.pipeline_id` → the cloned Pipeline; cloned
  `Pipeline.stage_id` → the cloned Stage (circular); `WorkflowAction.workflow_id` → cloned
  Workflow; `WorkflowCondition.workflow_action_id` → cloned action; `Menu.parent_id` →
  cloned parent.
- **Data NOT copied:** contacts/leads/deals/tasks count 0 under the new team.
- Command clones; unknown source id fails. Admin action gated to super_admin.
- MySQL 8.4 verify (FK-off + copy-then-patch).

## Out of scope (YAGNI)

- Transactional data (that is "everything" / new-team restore).
- Members + per-team role assignments.
- Cross-env clone; cloning into an existing team.
- Remapping `*_id` columns outside the declared edge map.

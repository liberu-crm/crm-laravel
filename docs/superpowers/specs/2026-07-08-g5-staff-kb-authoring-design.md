# G_5 — Staff knowledge-base authoring (app panel) design

**Date:** 2026-07-08
**Status:** approved, implementing
**Epic:** G_5 customer portal (extension). Complements #483 (portal KB browse).

## Problem

The only `KnowledgeBaseArticleResource` is the **portal** read-only browse (#483). There is
**no staff-facing UI to author KB articles**, so the portal KB is an empty shelf — customers
browse content nobody can create. (A legacy global `KnowledgeBaseController` exists but is
un-scoped Blade, left untouched by #483.)

## Solution

`App\Filament\App\Resources\KnowledgeBaseArticleResource` on the **app panel**, which is
Jetstream-Team-tenant-scoped. `KnowledgeBaseArticle` is `IsTenantModel`, so on the app panel:

- reads auto-filter to the current team (global `tenant` scope, keyed on `Filament::getTenant()`),
- `creating()` auto-stamps `team_id` from the active tenant.

No explicit `team_id` handling needed (unlike the portal resource, which is non-tenant and
filters manually). This closes the loop with #483: **staff author → their team's customers
browse** (portal KB scopes `team_id` + `is_published`).

## Components

- **Form:** `title` (required), `category` (nullable text), `content` (Textarea — not
  RichEditor, per the #466 LandingPage RichEditor drift), `is_published` Toggle (default true
  → a draft/publish workflow). `helpful_count`/`not_helpful_count` are read-only stats, not
  form fields.
- **Table:** title (searchable), category (badge), `is_published` (badge/boolean),
  helpful/not-helpful counts, updated_at; category `SelectFilter`. Nav group "Help Desk".
- **Pages:** List / Create / Edit (full CRUD + Delete), mirroring the app `TicketResource`.
- **Access:** `canAccess` → `super_admin` / `admin` / `manager` (content management, not
  `sales_rep` / `free`) — mirrors #491's role idiom.

## Testing (TDD, PHPUnit sqlite → MySQL-verify)

1. Manager creates an article → `team_id` auto-stamped to their team, `is_published`
   defaults true.
2. Team-scoped: a manager in team A does not see team B's article (list scoping).
3. `canAccess`: manager ✓, `sales_rep` ✗.
4. **A draft (`is_published=false`) authored here is NOT visible in the portal browse**
   (cross-checks `Portal\KnowledgeBaseArticleResource::getEloquentQuery` — proves the publish
   gate ties authoring to #483).
5. Edit updates a field (e.g. publish a draft).

phpstan 0-new (`getAttribute`/`hasRole` idioms), MySQL 8.4-verified, pint clean. Inline TDD,
one PR to `main`.

## Out of scope

RichEditor / media uploads, article versioning, per-customer vote dedup, a managed category
taxonomy, manual ordering, moving the legacy `KnowledgeBaseController` off Blade.

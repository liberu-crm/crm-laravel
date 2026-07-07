# G_5 slice 4 — Portal knowledge-base browse (design)

**Date:** 2026-07-07
**Status:** approved, implementing
**Epic:** G_5 customer portal, slice 4. Adds customer self-help: browse and read the tenant's
knowledge-base articles, with a publish gate and helpful/not-helpful feedback.

## Problem

Customers have no self-service reference — every question becomes a ticket. The
`KnowledgeBaseArticle` model exists and is team-scoped (`IsTenantModel`), but there is no
portal surface to read articles, no publish state (drafts would be customer-visible), and the
factory is **broken** (writes ~10 columns the table lacks). A legacy public
`KnowledgeBaseController` renders all articles globally with no tenancy or auth — untouched
here.

## Decisions (locked with user)

1. **Publish gate:** add `is_published` (default true); the portal shows only published
   articles. Staff can hide an article without deleting it; default true keeps existing content
   visible.
2. **Helpful feedback:** a "Helpful" / "Not helpful" action per article, incrementing counters.

## Drift fix (required)

The `knowledge_base_articles` table is only `title/content/category/team_id/timestamps`, but
`KnowledgeBaseArticleFactory` writes `slug/excerpt/tags/author_id/status/published_at/
view_count/meta_*`. This slice makes real **only the columns it needs** and strips the rest
from the factory (YAGNI — those features aren't built).

## Architecture

### 1. Migration (guarded)

Add to `knowledge_base_articles`: `is_published` (boolean, default `true`), `helpful_count`
(unsigned int, default 0), `not_helpful_count` (unsigned int, default 0).

### 2. Model

Add `is_published`, `helpful_count`, `not_helpful_count` to `$fillable`; cast
`is_published => bool`.

### 3. Factory

Strip to the real columns: `team_id`, `title`, `content`, `category`, `is_published`,
`helpful_count`, `not_helpful_count`. (Fixes the broken factory.)

### 4. Portal resource — `Filament\Portal\Resources\KnowledgeBaseArticleResource`

Read-only (no create/edit/delete), slug `articles`, label "Knowledge base".

- `getEloquentQuery()` → `where('team_id', <customer's current_team_id>)->where('is_published',
  true)`. Filament resolves both table rows and single-record routes through this, so an
  unpublished or other-team article id 404s.
- **List:** title + category (badge); searchable on title/content; category `SelectFilter`.
- **View page:** title + content, plus two header actions **"Helpful"** / **"Not helpful"**
  that `increment('helpful_count')` / `increment('not_helpful_count')` on the scoped record and
  show a thank-you notification.

### 5. Scope

Team-shared: every one of a tenant's customers sees the same published KB (unlike tickets,
which are per-user). The portal panel is non-tenant, so the team filter is applied explicitly
in the resource query.

## Testing (TDD, PHPUnit + sqlite, then MySQL-verify)

- **Only own-team published:** seed published + unpublished for the customer's team and a
  published article for another team; the list shows only the own-team published one.
- **Unpublished / foreign → 404:** viewing an unpublished or other-team article id is not found.
- **Feedback:** "Helpful" increments `helpful_count`; "Not helpful" increments
  `not_helpful_count`, on the scoped record.
- **Index reachable:** a customer with a team can load the resource index.
- MySQL 8.4 verify (migration + queries); phpstan 0-new; pint clean.

## Out of scope / ceilings (stated)

Repeated votes are not deduped (no per-user vote table — a later slice); rating totals aren't
shown to customers; the legacy public `KnowledgeBaseController` is left as-is; slug/SEO/tags/
author columns are not added; no staff KB-authoring UI (articles come from seeds/existing data
or a future slice).

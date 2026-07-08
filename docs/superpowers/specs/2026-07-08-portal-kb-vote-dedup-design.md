# Portal — KB vote dedup (design)

**Date:** 2026-07-08
**Status:** approved, implementing
**Epic:** G_5 customer portal. Closes the #483 ceiling (KB feedback votes not deduped).

## Problem

Portal KB feedback (#483) increments `helpful_count` / `not_helpful_count` on every click, so a
customer can spam a vote and skew the counts. There is no per-customer vote record.

## Fix

- `kb_article_votes` table: `knowledge_base_article_id` (FK), `user_id` (FK),
  `vote` (`helpful` | `not_helpful`), **unique(`article_id`, `user_id`)**, timestamps.
- Portal `ViewArticle::recordFeedback` — before incrementing, check whether the current user
  already voted on this article. If so → a "already voted" notice, no increment. Otherwise
  record the vote and increment the chosen count. One vote per customer per article.

## Testing (TDD)

1. A customer's first Helpful vote → `helpful_count` = 1 and a vote row exists.
2. A second vote by the same customer on the same article → count unchanged (deduped).
3. A different customer voting → increments independently.

phpstan 0-new, MySQL 8.4-verified, pint clean.

## Versioning

`VERSION` → `0.9.5`; GitHub prerelease `v0.9.5`.

## Out of scope

Changing an existing vote (one-shot per customer), a vote-count backfill/reconcile, showing the
customer their own prior vote.

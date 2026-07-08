# Remove dead OpportunityPipeline code (design)

**Date:** 2026-07-08
**Status:** approved, implementing
**Epic:** cleanup. Prerelease `1.10.0-rc.3`.

## Problem

The `OpportunityPipeline` kanban (two Livewire components, a blade, and two resource methods) is
dead: it is mounted by no route, no page, no `<livewire:...>` tag, and no `Livewire::component`
registration. Its blade rendered `deal_size` **unmasked** — a latent field-masking footgun that
never actually reaches a user because the view is unreachable. Delete it.

## What's removed

- `app/Livewire/OpportunityPipeline.php`
- `app/Http/Livewire/OpportunityPipeline.php`
- `resources/views/livewire/opportunity-pipeline.blade.php`
- `OpportunityResource::getPipelineView()` and `getPipelineTable()`, plus the imports that only
  those methods used (`Illuminate\Contracts\View\View`, `Filament\Tables\Filters\SelectFilter`).

The live `OpportunityResource` (form, main table with its **masked** `deal_size` column, the
`ViewOpportunity` detail page, `getPages`) is untouched.

## Verification

A repo-wide grep for `OpportunityPipeline` / `opportunity-pipeline` / `getPipelineView` /
`getPipelineTable` found references only in the deleted files, the resource methods removed here,
and `tests/Feature/OpportunityPipelineTest.php` — which is a **name collision**: that test
exercises the `Pipeline`/`Stage`/`Deal` models and the opportunities index route (`ListOpportunities`),
never the deleted component or methods, so it keeps passing. The full suite stays green.

## Out of scope

Renaming `OpportunityPipelineTest` (it tests live pipeline *models*, not the dead component).

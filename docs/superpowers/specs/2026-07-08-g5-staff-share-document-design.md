# G_5 — Staff share document with a portal customer (design)

**Date:** 2026-07-08
**Status:** approved, implementing
**Epic:** G_5 customer portal (extension). Complements #484 (portal document browse).

## Problem

The portal document browse (#484) is an **empty shelf**: it shows the documents attached to
the customer's Contact (`documentable_type=Contact`, matched by email+team), but **no staff
surface attaches a document to a Contact**. There is no `documents` relation manager on the
app `ContactResource`; the only writer is the legacy Blade `DocumentController`.

`DocumentService::upload()` exists but is **insufficient on its own** for the portal: it sets
`file_path`/`original_filename`/`mime_type`/`title` but **not `team_id`, `name`, or `type`** —
the exact fields the portal browse filters (`where team_id`) and displays (`name`, `type`).
A document uploaded via `upload()` alone has `team_id = null` → the customer never sees it.

## Solution

`App\Filament\App\Resources\ContactResource\RelationManagers\DocumentsRelationManager`
(relationship `documents`, a `MorphMany`). A **Share document** action:

- `FileUpload->storeFiles(false)` yields the raw `TemporaryUploadedFile` (a subclass of
  `UploadedFile`) →
- `DocumentService::upload($file, $contact, ['title' => $name])` — **reuses** `storeFile`'s
  mime allowlist (`config('documents.allowed_mimes')`, aborts 422 on disallowed content) and
  disk/path convention that `DocumentService::download` reads back →
- patch the returned `Document`: `team_id = contact.team_id`, `name`, `type` (the portal
  fields `upload()` omits). `documentable` is auto-pinned to the Contact via the relation.

Result: the document appears in that customer's portal browse (#484), matched by email+team.

Table mirrors the portal: `name` / `type` / `updated_at`, with **download**
(`DocumentService::download`) and **delete** (`DocumentService::delete`, removes file+row)
row actions. Registered via `ContactResource::getRelations()` (added — none today).

## Security / tenancy

- File validation reused from `DocumentService::storeFile` (content-sniffed mime allowlist) —
  not bypassed.
- Stored on the private/default disk `DocumentService` already uses; never a public URL.
- `documentable` is pinned to the Contact the staffer is on; `team_id` pinned to that
  Contact's team. The relation manager lives on the tenant-scoped app panel, so staff only
  reach their own team's Contacts.

## Testing (TDD, PHPUnit sqlite → MySQL-verify)

Uploads use `UploadedFile::fake()->image('x.png')` (real PNG content so the Finfo allowlist
passes; `image/png` is in `config/documents.php`).

1. Staff share a document with a Contact → `Document` created with `team_id = contact.team_id`,
   `documentable_type = Contact`, `documentable_id`, `name`, `type`, `file_path` set.
2. The matching portal customer (email+team) sees it in
   `Portal\DocumentResource::getEloquentQuery` — ties the share to #484.
3. A document on a **different** Contact is not visible to that customer (scoping).
4. Delete removes the row (and the stored file).

phpstan 0-new (`getAttribute`/`setAttribute` idioms), MySQL 8.4-verified, pint clean.
Inline TDD, one PR to `main`.

## Out of scope

Version UI (`uploadNewVersion` exists, unsurfaced), documents on Deal/Company (Contact only,
per #484), customer-side upload from the portal, bulk share, per-document access controls
beyond the Contact link.

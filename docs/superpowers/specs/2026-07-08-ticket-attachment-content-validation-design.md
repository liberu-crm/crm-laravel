# Harden ticket attachment upload (design)

**Date:** 2026-07-08
**Status:** approved, implementing
**Epic:** Security. Prerelease `1.11.0-rc.1`.

## Problem

The portal `TicketResource` attachment upload uses only
`->acceptedFileTypes(['image/png','image/jpeg','application/pdf'])`. That constrains the
browser-declared MIME (an `accept` attribute + Livewire's client-reported type), which is
spoofable — a client can send arbitrary bytes with a declared `image/png`. Unlike
`DocumentService::storeFile` (which Finfo-detects the real content type against an allowlist and
aborts otherwise), the ticket upload does no server-side **content** check. A customer could store
a non-image/PDF file in `ticket-attachments`.

## Design

A reusable `App\Rules\ContentMimeType` validation rule: Finfo-detect the uploaded file's real MIME
from its bytes and fail unless it is in an allowed list. Attach it to the ticket attachment
`FileUpload` with the ticket's allowed set (`image/png`, `image/jpeg`, `application/pdf`), so the
declared type is enforced by actual content server-side (defense-in-depth alongside
`acceptedFileTypes`). No change to storage location or the download flow.

## Testing (TDD)

1. A file whose real content is a PDF (`%PDF` header) passes for the allowed set.
2. A file named `.png` whose real content is plain text is rejected (spoofed declared type).
3. A non-file value (no upload) is a no-op (attachment is optional).

phpstan 0-new, MySQL 8.4-verified, pint clean.

## Out of scope

Re-validating existing stored attachments; multi-file attachments; routing tickets through the
full `DocumentService` (documents table).

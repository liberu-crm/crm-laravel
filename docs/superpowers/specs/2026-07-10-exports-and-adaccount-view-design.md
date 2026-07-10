# Exports + AdvertisingAccount detail view — design (2026-07-10)

Three independent, pattern-repeat slices toward the 1.12.0 cut. Each is a
direct extension of an established, tested pattern — no new architecture.

## Slice A — Opportunity CSV export (masking-gated)

`Opportunity` masks `deal_size` for the `free` role but had no CSV export.
Add `OpportunityExporter` (columns: deal_size, stage, closing_date, created_at)
and wire an `ExportAction` header action on `OpportunityResource`, hidden when
`AccessContext::shouldMaskFields()` — identical to `CampaignResource`. The
export inherits the resource's tenant scope (Opportunity is `IsTenantModel`).

## Slice B — AdSet CSV export (masking-gated)

`AdSet` masks `budget`; it was the last masked resource without an export.
Add `AdSetExporter` (name, status, budget, budget_type, external_id,
created_at) and the same masking-gated `ExportAction`. With this, every masked
money/PII resource is exportable and every export is gated off for `free`.

## Slice C — AdvertisingAccount detail View (secret-safe)

`AdvertisingAccountResource` had List/Create/Edit but no read-only detail view.
Add a `ViewAdvertisingAccount` page whose infolist renders only the non-secret
display fields already shown in the table (name, platform, account_id, status,
last_sync, created_at). It **deliberately omits** the encrypted `access_token`
/ `refresh_token` columns so the detail view is never a secret-disclosure
surface. A test asserts a known token value is not present in the rendered page.

## Non-goals / gates

No schema changes (the `exports` table was published in #545); no migrations,
so MySQL parity holds by construction. Each slice is one PR, rc of 1.12.0.
Design-gated backlog (F4 enforcement, masking encrypt-at-rest, SSO SLO, SAML
login) is untouched — those need their own design round, not a blind build.

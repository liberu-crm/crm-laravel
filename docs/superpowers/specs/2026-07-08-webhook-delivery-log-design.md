# Webhook delivery log

- Date: 2026-07-08
- Epic: webhooks / observability
- Prerelease: 1.10.0-rc.4

## Problem

`WebhookService::send()` fires a POST, returns a bool, and on failure bumps a
counter / auto-disables — but leaves no per-attempt trail. Teams can't see
which events were delivered, which endpoint returned what status, or why a
send failed. Debugging a flaky integration means guessing from the aggregate
`failure_count`.

## Design

New `webhook_deliveries` table + `WebhookDelivery` model records one row per
send attempt:

- `webhook_id` (cascade), `team_id` (nullable, mirrors the parent webhook so
  `IsTenantModel` scopes the log to the owning team), `event`, `success`,
  `status_code` (nullable), `error` (nullable), timestamps.

`send()` writes a row on all three outcomes without changing its return value
or the existing `handleFailure` call:

- success -> `success=true`, `status_code=$response->status()`
- HTTP non-2xx -> `success=false`, `status_code`, `error="HTTP {status}"`
- exception -> `success=false`, `status_code=null`, `error=$e->getMessage()`

`WebhookDeliveryResource` (app panel) exposes a read-only, Admin/SuperAdmin-gated
list mirroring `TeamRoleLogResource`: `canCreate()=false`, index page only,
`IsTenantModel` supplies team scoping, default sort `created_at desc`.

## Testing

`tests/Feature/Filament/WebhookDeliveryTest.php` (PHPUnit + `RefreshDatabase`):

- Service: `Http::fake` a 200 -> asserts a delivery row with `success=true` +
  the event; a faked 500 -> `success=false`, `status_code=500`.
- Resource: an admin lists only their team's deliveries — another team's
  delivery is not visible; access is Admin-only.

## Out of scope

- Retry / replay of failed deliveries.
- Request/response body capture or payload storage.
- Retention / pruning of old delivery rows.
- Admin-panel (global) view — this log is team-scoped only.

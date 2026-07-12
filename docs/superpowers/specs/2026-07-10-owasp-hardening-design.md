# OWASP audit hardening — design (2026-07-10)

Fixes for the top findings from the post-2.0.0 OWASP audit. Three independent
slices (patch release 2.0.1). CSP `unsafe-eval` was left as-is (Alpine/Livewire
require it; removing it blindly breaks Filament — a manual-tested follow-up).

## Slice 1 — SSRF guard on OIDC discovery (HIGH)

`OidcClient` fetched the admin-configured `issuer_url` and every endpoint the
discovery document advertises (token/jwks/userinfo) with no host restriction — an
admin could point it at cloud metadata / localhost / the internal network, and the
`token_endpoint` (from the discovery doc) receives the `client_secret`. Add
`App\Support\SsrfGuard::assertPublicHttps($url)` and call it before each outbound
fetch. The guard requires `https`, blocks `localhost`, blocks private/reserved IP
literals, and blocks hostnames that resolve to a non-public address. An
**unresolvable** host is allowed (no reachable target; the request fails on its
own) so tests don't depend on live DNS.

## Slice 2 — CORS allowlist + SSO route throttling (HIGH/MEDIUM)

- `config/cors.php`: `allowed_origins` was `[env('FRONTEND_URL', '*')]` — a
  wildcard-with-credentials default. Replace with an explicit comma-separated
  allowlist (`CORS_ALLOWED_ORIGINS` → `FRONTEND_URL` → empty); never `*`.
- `routes/web.php`: the unauthenticated, crypto-heavy SSO/SAML routes (redirect,
  callback, login, ACS, SLS) get `throttle:30,1` (per-IP) to blunt DoS. The static
  metadata route stays unthrottled.

## Slice 3 — delete dead DocumentController (MEDIUM)

`app/Http/Controllers/DocumentController.php` had `download`/`upload`/`newVersion`
with **no authorization** (a latent IDOR), but it is unrouted and unreferenced.
Delete it — the live portal `DocumentResource` path is correctly tenant-scoped.

## Testing

`SsrfGuardTest` (accepts a public https URL; rejects http, localhost, cloud
metadata, RFC-1918 literals, IPv6 loopback). `SecurityHardeningTest` (CORS config
never `*`; an SSO route returns 429 after the limit). Existing OIDC tests stay
green (fake issuer hosts are unresolvable → allowed). No migrations.

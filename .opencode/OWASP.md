# OWASP Top 10 SAST Review — Liberu CRM Laravel

**Date:** 2026-05-24
**Scope:** Full codebase static analysis
**Methodology:** Manual source code review mapping findings to OWASP Top 10 (2021)

---

## Summary Table

| # | Vulnerability | OWASP | Severity | File(s) |
|---|---|---|---|---|
| 1 | IDOR — Missing team scope in API CRUD | A1 Broken Access Control | 🔴 Critical | `Api/ContactController`, `Api/DealController`, `Api/TaskController` |
| 2 | Unauthenticated bulk delete (web route) | A1 Broken Access Control | 🔴 Critical | `ContactListController.php:46` |
| 3 | Unrestricted file upload → potential RCE | A5 Security Misconfiguration | 🔴 Critical | `DocumentController.php:12`, `DocumentService.php:158` |
| 4 | SSRF via webhook URLs | A10 SSRF | 🔴 High | `WebhookService.php:43-50` |
| 5 | SSRF via workflow webhook actions | A10 SSRF | 🔴 High | `WorkflowAutomationService.php:294-305` |
| 6 | Stored XSS (raw `{!! !!}` Blade output) | A3 Cross-Site Scripting | 🔴 High | `views/livewire/webrender.blade.php:5` |
| 7 | Open redirect via email tracking | A1 Broken Access Control | 🟡 Medium | `EmailTrackingController.php:46-63` |
| 8 | Toll fraud via Twilio TwiML endpoint | A1 Broken Access Control | 🟡 Medium | `TwilioController.php:33-39` |
| 9 | Authorization bypass (incomplete permission map) | A1 Broken Access Control | 🟡 Medium | `TeamsPermission.php:47-63` |
| 10 | JSON injection in workflow configuration | A8 Software Integrity Failures | 🟡 Medium | `WorkflowController.php:21-22` |
| 11 | Webhook secret exposure (no owner check) | A5 Security Misconfiguration | 🟡 Medium | `WebhookController.php:52-55` |
| 12 | Dynamic validation rules from database | A1 Broken Access Control | 🟡 Medium | `LeadFormController.php:50-57` |
| 13 | Unprotected email tracking link route | A1 Broken Access Control | 🟢 Low | `EmailTrackingController.php:46` |

---

## Vulnerability #1: IDOR — Missing Team Scoping in API CRUD Operations

| Attribute | Details |
|---|---|
| **OWASP** | A1 — Broken Access Control (Insecure Direct Object References) |
| **Severity** | 🔴 Critical |
| **CVSS 3.1** | 9.1 (AV:N/AC:L/PR:L/UI:N/S:C/C:H/I:H/A:N) |

### Affected Files & Lines

| File | Line(s) | Method |
|---|---|---|
| `app/Http/Controllers/Api/ContactController.php` | 13, 30, 38, 45, 66, 97, 116, 136 | `index`, `store`, `show`, `update`, `destroy`, `bulkUpdate`, `bulkDelete`, `bulkAssign` |
| `app/Http/Controllers/Api/DealController.php` | 13, 30, 38, 45, 66, 96, 115, 135 | `index`, `store`, `show`, `update`, `destroy`, `bulkUpdate`, `bulkDelete`, `bulkAssign` |
| `app/Http/Controllers/Api/TaskController.php` | 13, 30, 38, 45, 66, 96, 115, 135 | `index`, `store`, `show`, `update`, `destroy`, `bulkUpdate`, `bulkDelete`, `bulkAssign` |

### Data Flow (Source → Sink)

```
Authenticated API request
  → GET /api/v1/contacts/{contact} (implicit route model binding)
  → ContactController@show / update / destroy
  → $contact->update(...) / $contact->delete()
  → NO team_id scope check applied
```

### Proof of Exploit

```http
GET /api/v1/contacts/42 HTTP/1.1
Authorization: Bearer <token-of-user-from-team-A>
```

User from Team A can read, update, or delete contacts belonging to Team B by simply guessing or enumerating resource IDs. The `applyTeamScope()` method is only invoked in bulk operations (`bulkUpdate`, `bulkDelete`, `bulkAssign`), leaving all standard CRUD endpoints unprotected.

### Root Cause

The API controllers perform individual resource operations without scoping queries to the authenticated user's team. Laravel's implicit route model binding fetches records by primary key regardless of ownership.

### Remediation Applied

Added a `scopeByTeam()` local scope and `belongsToTeam()` ownership check to the `IsTenantModel` trait, and called them explicitly in each controller method.

**Trait methods (`app/Traits/IsTenantModel.php`):**

```php
public function scopeByTeam(Builder $query, ?int $teamId): void
{
    if ($teamId) {
        $query->where($query->qualifyColumn('team_id'), $teamId);
    }
}

public function belongsToTeam(?int $teamId): bool
{
    return !$teamId || $this->team_id === $teamId;
}
```

**Usage in controllers:**

```php
// Listing — scope query before fetching
return Contact::byTeam($request->user()?->resolvedTeamId())->get();

// Show/Update/Delete — ownership check after route model binding
abort_unless($contact->belongsToTeam($request->user()?->resolvedTeamId()), 403);

// Store — assign team_id from auth context
$validated['team_id'] = $request->user()?->resolvedTeamId();

// Bulk operations — scope query before mutation
$query = Contact::whereIn('id', $validated['ids']);
$query->byTeam($request->user()?->resolvedTeamId());
$count = $query->delete();
```

Admin/super_admin users bypass team scoping via `resolvedTeamId()` on the User model, which returns `null` for elevated roles, making both `scopeByTeam()` and `belongsToTeam()` pass through without filtering.

**Supporting migration:**

A new migration (`2026_05_26_161939_add_team_id_to_deals_table`) added the missing `team_id` column to the `deals` table, which was absent from the schema despite the trait expecting it.

---

## Vulnerability #2: Unauthenticated Bulk Delete (Web Route)

| Attribute | Details |
|---|---|
| **OWASP** | A1 — Broken Access Control |
| **Severity** | 🔴 Critical |
| **CVSS 3.1** | 9.4 (AV:N/AC:L/PR:N/UI:N/S:U/C:N/I:H/A:H) |

### Affected Files & Lines

| File | Line(s) |
|---|---|
| `app/Http/Controllers/ContactListController.php` | 46–55 |
| `routes/web.php` | 17 |

### Data Flow (Source → Sink)

```
Any HTTP client (no authentication required)
  → DELETE /contacts/bulk/delete
  → Body: {"ids": [1, 2, 3, 4, 5]}
  → ContactListController@bulkDelete
  → Contact::whereIn('id', $ids)->delete()
  → Arbitrary contact records destroyed
```

### Proof of Exploit

```bash
curl -X DELETE http://crm.local/contacts/bulk/delete \
  -H "Content-Type: application/json" \
  -d '{"ids": [1, 2, 3, 4, 5, 6, 7, 8, 9, 10]}'
```

### Root Cause

The route is defined **outside** the `auth` middleware group in `routes/web.php:17`:

```php
Route::delete('/contacts/bulk/delete', [ContactListController::class, 'bulkDelete'])
    ->name('contacts.bulk.delete');
```

The controller method performs no authentication or authorization checks.

### Remediation Applied

Added `->middleware('auth')` to the route, request validation, and team-scoped deletion via the `IsTenantModel` trait:

```php
// routes/web.php
Route::delete('/contacts/bulk/delete', [ContactListController::class, 'bulkDelete'])
    ->name('contacts.bulk.delete')
    ->middleware('auth');

// app/Http/Controllers/ContactListController.php
public function bulkDelete(Request $request)
{
    $validated = $request->validate([
        'ids'   => 'required|array|min:1',
        'ids.*' => 'integer|exists:contacts,id',
    ]);

    $query = Contact::whereIn('id', $validated['ids']);
    $query->byTeam($request->user()?->resolvedTeamId());
    $count = $query->delete();

    return response()->json(['deleted' => $count]);
}
```

Changes:
- **Auth**: Route now requires authentication via `->middleware('auth')` instead of being public
- **Validation**: Added `required|array|min:1` and `integer|exists:contacts,id` — rejects empty/malformed payloads
- **Team scoping**: Uses `->byTeam()` from `IsTenantModel` trait, consistent with API controllers
- **Input safety**: Uses `$validated['ids']` from the validated request data instead of raw `$request->input('ids', [])`

---

## Vulnerability #3: Unrestricted File Upload → Potential Remote Code Execution

| Attribute | Details |
|---|---|
| **OWASP** | A5 — Security Misconfiguration |
| **Severity** | 🔴 Critical |
| **CVSS 3.1** | 9.0 (AV:N/AC:L/PR:L/UI:N/S:C/C:H/I:H/A:H) |

### Affected Files & Lines

| File | Line(s) |
|---|---|
| `app/Http/Controllers/DocumentController.php` | 12–31 |
| `app/Services/DocumentService.php` | 158–163 |

### Data Flow (Source → Sink)

```
Attacker uploads shell.php
  → POST /documents/upload (or DocumentService@upload)
  → Validation: only 'required|file|max:10240' — NO MIME type check
  → $file->store('documents')
  → Stored as: documents/2026/05/{uuid}.php  (original extension preserved)
  → If storage disk is public: /storage/documents/{uuid}.php is web-accessible
  → RCE via ?cmd=id
```

### Vulnerable Code

**DocumentController.php:12-16**
```php
$request->validate([
    'file' => 'required|file|max:10240', // 10MB max
    // No mimes: or mimetypes: validation
]);
```

**DocumentService.php:158-163**
```php
private function storeFile(UploadedFile $file): string
{
    $directory = 'documents/' . date('Y/m');
    $filename  = Str::uuid() . '.' . $file->getClientOriginalExtension();
    return $file->storeAs($directory, $filename);
}
```

`getClientOriginalExtension()` returns the extension from the client-provided filename, which an attacker can set to `.php`, `.phtml`, `.shtml`, `.php5`, etc.

### Root Cause

- No MIME type whitelist on file upload validation
- Stored filename uses the client-supplied extension without validation
- No malware/AV scanning of uploaded content

### Remediation

```php
// DocumentController.php
$request->validate([
    'file' => [
        'required',
        'file',
        'max:10240',
        'mimes:pdf,doc,docx,xls,xlsx,csv,png,jpg,jpeg,gif,svg',
        'mimetypes:application/pdf,image/jpeg,image/png,image/gif,application/msword,...',
    ],
]);

// DocumentService.php — validate MIME server-side (defense in depth)
private function storeFile(UploadedFile $file): string
{
    $allowedMimes = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/gif',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];

    if (!in_array($file->getMimeType(), $allowedMimes)) {
        throw new \Exception('File type not allowed.');
    }

    $directory = 'documents/' . date('Y/m');
    $extension = $file->guessExtension();  // Uses server-side MIME detection, not client header
    $filename  = Str::uuid() . '.' . $extension;
    return $file->storeAs($directory, $filename);
}
```

---

## Vulnerability #4: Server-Side Request Forgery (SSRF) via Webhook URLs

| Attribute | Details |
|---|---|
| **OWASP** | A10 — Server-Side Request Forgery |
| **Severity** | 🔴 High |
| **CVSS 3.1** | 8.8 (AV:N/AC:L/PR:L/UI:N/S:C/C:H/I:L/A:L) |

### Affected Files & Lines

| File | Line(s) |
|---|---|
| `app/Services/WebhookService.php` | 43–50 |

### Data Flow (Source → Sink)

```
Attacker creates/updates webhook with URL: http://169.254.169.254/latest/meta-data/
  → WebhookService@register / @update stores URL in DB
  → WebhookService@dispatch → @send
  → Http::send('POST', $webhook->url, ['body' => $body])
  → HTTP request to internal cloud metadata endpoint
  → IAM credentials leaked
```

### Vulnerable Code

```php
// WebhookService.php:43-50
$response = Http::timeout(10)
    ->withHeaders([...])
    ->send('POST', $webhook->url, ['body' => $body]);
```

The `url` field is validated as `required|url` on input (WebhookController.php:35), but the `url` Laravel validator only checks URL format, not whether the host resolves to a private IP range.

### Attack Vectors

| Target | URL |
|---|---|
| AWS metadata | `http://169.254.169.254/latest/meta-data/` |
| GCP metadata | `http://metadata.google.internal/computeMetadata/v1/` |
| Redis (unauthenticated) | `http://localhost:6379/` |
| Elasticsearch | `http://localhost:9200/` |
| Internal APIs | `http://10.0.0.1/admin/` |
| Docker socket | `http://localhost:2375/containers/json` |

### Remediation Applied

Two-layer defense-in-depth: controller input validation (blocks raw IPs, localhost, non-HTTPS) + service DNS resolution check (catches domain names aliased to private IPs). Both gated on `production` env.

**Layer 1 — `app/Http/Controllers/Api/WebhookController.php` (validation closure):**

```php
'url' => [
    'required',
    'url',
    function ($attribute, $value, $fail) {
        if (!app()->environment('production')) {
            return;
        }

        $scheme = parse_url($value, PHP_URL_SCHEME);
        if ($scheme !== 'https') {
            $fail('Webhook URL must use HTTPS in production.');
            return;
        }

        $host = parse_url($value, PHP_URL_HOST);
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            $fail('URL must use a domain name, not a raw IP address.');
            return;
        }

        if (in_array(strtolower($host), [
            'localhost', 'localhost.localdomain', '0.0.0.0', '[::1]',
        ], true)) {
            $fail('URL must point to a publicly reachable endpoint.');
            return;
        }
    },
],
```

**Layer 2 — `app/Services/WebhookService::ensurePublicUrl()` (DNS/IP check):**

```php
private function ensurePublicUrl(string $url): void
{
    $host = parse_url($url, PHP_URL_HOST);
    if (!$host) {
        return;
    }

    $ip = gethostbyname($host);

    if ($ip !== $host && !filter_var($ip, FILTER_VALIDATE_IP,
        FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
        throw new \RuntimeException(
            'Webhook URL resolves to an internal IP address.'
        );
    }
}
```

Called in `send()` before the HTTP request:
```php
if (app()->environment('production')) {
    $this->ensurePublicUrl($webhook->url);
}
```

**Key design decisions:**
- **Environment-gated**: All checks are relaxed in non-production so local dev/testing can use `localhost`, HTTP, or LAN IPs
- **No shared class**: A reusable `UrlValidator` was considered but rejected — each service stays self-contained and independently auditable
- **DNS resolution**: `gethostbyname()` catches `metadata.google.internal` → `169.254.169.254` style attacks that a simple domain check would miss
- **No synthetic CIDR matching**: `FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE` covers all RFC 1918, link-local, and loopback ranges without needing `ip2long`/CIDR math

---

## Vulnerability #5: SSRF via Workflow Automation Webhook Actions

| Attribute | Details |
|---|---|
| **OWASP** | A10 — Server-Side Request Forgery |
| **Severity** | 🔴 High |
| **CVSS 3.1** | 8.8 (AV:N/AC:L/PR:L/UI:N/S:C/C:H/I:L/A:L) |

### Affected Files & Lines

| File | Line(s) |
|---|---|
| `app/Services/WorkflowAutomationService.php` | 294–305 |

### Data Flow

```
Admin creates workflow with action type TYPE_WEBHOOK
  → URL stored in workflow action config JSON: {"url": "http://internal.admin:8080/"}
  → WorkflowAutomationService@callWebhook
  → Http::send($method, $url, ['json' => $data])
  → Internal service queried
```

### Vulnerable Code

```php
// WorkflowAutomationService.php:294-305
protected function callWebhook($entity, array $config, array $context): void
{
    $url = $config['url'] ?? null;
    if (!$url) {
        return;
    }

    $method = strtoupper($config['method'] ?? 'POST');
    $data = array_merge($entity->toArray(), $context);

    Http::send($method, $url, ['json' => $data]);
}
```

### Remediation Applied

Same SSRF protection as Vulnerability #4, plus a 10-second timeout (previously absent — a hang on a private IP would block the queue worker indefinitely):

**`app/Services/WorkflowAutomationService.php`:**
```php
protected function callWebhook($entity, array $config, array $context): void
{
    $url = $config['url'] ?? null;
    if (!$url) {
        return;
    }

    if (app()->environment('production')) {
        $this->ensurePublicUrl($url);
    }

    $method = strtoupper($config['method'] ?? 'POST');
    $data = array_merge($entity->toArray(), $context);

    Http::timeout(10)->send($method, $url, ['json' => $data]);
}

private function ensurePublicUrl(string $url): void
{
    $host = parse_url($url, PHP_URL_HOST);
    if (!$host) {
        return;
    }

    $ip = gethostbyname($host);

    if ($ip !== $host && !filter_var($ip, FILTER_VALIDATE_IP,
        FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
        throw new \RuntimeException(
            'Workflow webhook URL resolves to an internal IP address.'
        );
    }
}
```

**Key design decisions:**
- **Environment-gated**: `ensurePublicUrl()` only fires in `production`. In `local`, `testing`, or `staging`, local webhook URLs are allowed for dev testing.
- **Defense-in-depth**: The controller validation closure blocks raw IPs/localhost/non-HTTPS at the API layer; `ensurePublicUrl()` resolves DNS to catch domain names aliased to private IPs.
- **Lowest viable change**: A shared `UrlValidator` class was considered but rejected to keep each service self-contained and auditable in isolation.
- **Timeout**: `Http::timeout(10)` prevents queue worker starvation on dead/walled-garden endpoints.

---

## Vulnerability #6: Stored XSS via Unescaped Blade Output

| Attribute | Details |
|---|---|
| **OWASP** | A3 — Cross-Site Scripting |
| **Severity** | 🔴 High |
| **CVSS 3.1** | 8.7 (AV:N/AC:L/PR:L/UI:R/S:C/C:H/I:H/A:N) |

### Affected Files & Lines

| File | Line(s) |
|---|---|
| `resources/views/livewire/webrender.blade.php` | 5 |

### Data Flow

```
User (admin) creates landing page / CMS content with embedded <script>alert('XSS')</script>
  → Content stored in database (LandingPage or equivalent)
  → Livewire component loads $contents from DB
  → webrender.blade.php renders: {!! $element['content'] !!}
  → Raw HTML emitted to browser without escaping
  → Stored XSS executed in viewer's browser
```

### Vulnerable Code

```blade
{{-- resources/views/livewire/webrender.blade.php --}}
@if ($contents)
    @foreach ($contents as $element)
        {!! $element['content'] !!}
    @endforeach
@else
    <p>No content found</p>
@endif
```

The `{!! !!}` syntax in Blade outputs raw, unescaped HTML. If `$element['content']` contains user-supplied content (e.g., from landing page builder, CMS, or any editable rich text field), an attacker can inject arbitrary JavaScript.

### Remediation Applied

The view intentionally uses `{!! !!}` — it renders safe HTML (headings, paragraphs, images, links) for landing page content. The fix applies HTML Purification (via `stevebauman/purify`) to strip dangerous tags/attributes while preserving safe HTML:

**`resources/views/livewire/webrender.blade.php:5` (before):**
```blade
{!! $element['content'] !!}
```

**After:**
```blade
{!! app('purify')->clean($element['content']) !!}
```

HTML Purifier strips `<script>`, `on*` event handlers, `javascript:` URLs, `<iframe>`, `<embed>`, `<object>`, `<style>`, and other XSS vectors while preserving `<h1>`, `<p>`, `<img>`, `<a>`, `<ul>/<ol>`, `<table>`, etc.

**Package added:** `stevebauman/purify ^6.3` (wraps `ezyang/htmlpurifier`)
**Config published:** `config/purify.php` — customisable allowed elements, attributes, and URI schemes

---

## Vulnerability #7: Open Redirect via Email Tracking Link

| Attribute | Details |
|---|---|
| **OWASP** | A1 — Broken Access Control (Open Redirect) |
| **Severity** | 🟡 Medium |
| **CVSS 3.1** | 6.1 (AV:N/AC:L/PR:N/UI:R/S:C/C:L/I:L/A:N) |

### Affected Files & Lines

| File | Line(s) |
|---|---|
| `app/Http/Controllers/EmailTrackingController.php` | 46–63 |

### Data Flow

```
Attacker crafts email with tracking link:
  GET /email/track/link/{tracking_id}?url=<base64("https://evil.com/phish")>
  → EmailTrackingController@link
  → $encodedUrl = $request->get('url');
  → $url = base64_decode($encodedUrl);
  → return redirect($url);  // NO validation
  → User's browser redirected to evil.com
```

### Vulnerable Code

```php
// EmailTrackingController.php:46-63
public function link(Request $request, string $trackingId)
{
    $encodedUrl = $request->get('url');
    $url = base64_decode($encodedUrl);

    try {
        $this->trackingService->recordClick(
            $trackingId,
            $url,
            $request->header('User-Agent'),
            $request->ip()
        );
    } catch (\Exception $e) {
        Log::error("Error recording link click: " . $e->getMessage());
    }

    return redirect($url);  // Open redirect — no URL validation
}
```

### Proof of Concept

```php
base64_encode('https://evil.com/phishing-page')
// Output: aHR0cHM6Ly9ldmlsLmNvbS9waGlzaGluZy1wYWdl

// Request:
GET /email/track/link/abc-123?url=aHR0cHM6Ly9ldmlsLmNvbS9waGlzaGluZy1wYWdl
// User is redirected to https://evil.com/phishing-page
```

### Remediation

```php
public function link(Request $request, string $trackingId)
{
    $encodedUrl = $request->get('url');
    $url = base64_decode($encodedUrl);

    // Validate against allowlist of safe domains
    $safeUrl = $this->validateRedirectUrl($url);

    try {
        $this->trackingService->recordClick(
            $trackingId,
            $safeUrl,
            $request->header('User-Agent'),
            $request->ip()
        );
    } catch (\Exception $e) {
        Log::error("Error recording link click: " . $e->getMessage());
    }

    return redirect($safeUrl);
}

private function validateRedirectUrl(string $url): string
{
    $allowedDomains = config('app.allowed_redirect_domains', [parse_url(config('app.url'), PHP_URL_HOST)]);
    $host = parse_url($url, PHP_URL_HOST);

    // Allow relative URLs
    if ($host === null) {
        return $url;
    }

    foreach ($allowedDomains as $domain) {
        if ($host === $domain || Str::endsWith($host, '.' . $domain)) {
            return $url;
        }
    }

    Log::warning("Blocked redirect to untrusted domain: {$host}");

    // Fall back to the app homepage
    return config('app.url');
}
```

---

## Vulnerability #8: Toll Fraud via Twilio TwiML Endpoint

| Attribute | Details |
|---|---|
| **OWASP** | A1 — Broken Access Control |
| **Severity** | 🟡 Medium |
| **CVSS 3.1** | 5.3 (AV:N/AC:L/PR:N/UI:N/S:U/C:N/I:L/A:N) |

### Affected Files & Lines

| File | Line(s) |
|---|---|
| `app/Http/Controllers/TwilioController.php` | 33–39 |

### Data Flow

```
Attacker calls public TwiML endpoint:
  POST /twilio/twiml/outbound
  Body: To=+1234567890
  → TwilioController@handleOutboundCall (no auth)
  → $dial->number($request->input('To'));  // User-controlled phone number
  → Twilio places call to attacker-specified number at CRM's expense
```

### Vulnerable Code

```php
// TwilioController.php:33-39
public function handleOutboundCall(Request $request)
{
    $response = new VoiceResponse();
    $dial = $response->dial('', ['callerId' => config('services.twilio.phone_number')]);
    $dial->number($request->input('To'));

    return response($response)->header('Content-Type', 'text/xml');
}
```

This endpoint is **public** (no auth middleware), so any external attacker can trigger call placement to arbitrary numbers.

### Remediation

Verify the Twilio request signature to ensure the request originated from Twilio, not an external attacker:

```php
// routes/web.php
use App\Http\Middleware\VerifyTwilioRequest;

Route::post('/twilio/twiml/outbound', [TwilioController::class, 'handleOutboundCall'])
    ->middleware(VerifyTwilioRequest::class)  // Verify Twilio signature
    ->name('twilio.twiml.outbound');
```

```php
// app/Http/Middleware/VerifyTwilioRequest.php
class VerifyTwilioRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        $signature = $request->header('X-Twilio-Signature');
        $url = $request->fullUrl();
        $params = $request->except('Signature');
        $expected = base64_encode(
            hash_hmac('sha256', $url . implode('', $params), config('services.twilio.auth_token'), true)
        );

        if (!hash_equals($expected, $signature)) {
            abort(403, 'Invalid Twilio signature');
        }

        return $next($request);
    }
}
```

---

## Vulnerability #9: Authorization Bypass — Incomplete Permission Mapping

| Attribute | Details |
|---|---|
| **OWASP** | A1 — Broken Access Control |
| **Severity** | 🟡 Medium |
| **CVSS 3.1** | 6.5 (AV:N/AC:L/PR:L/UI:N/S:U/C:H/I:N/A:N) |

### Affected Files & Lines

| File | Line(s) |
|---|---|
| `app/Http/Middleware/TeamsPermission.php` | 35–63 |

### Vulnerable Code

```php
// TeamsPermission.php:47-63
private function getPermissionForAction($actionName)
{
    $permissionMap = [
        'ClientController@index' => 'view_any_client',
        'ClientController@show' => 'view_client',
        'ClientController@create' => 'create_client',
        'ClientController@edit' => 'update_client',
        'LeadController@index' => 'view_any_lead',
        'LeadController@show' => 'view_lead',
        'LeadController@create' => 'create_lead',
        'LeadController@edit' => 'update_lead',
        // Add more mappings as needed
    ];

    return $permissionMap[$actionName] ?? null;  // Returns null for any unmapped action!
}
```

### Issue

When `getPermissionForAction()` returns `null` for an unmapped route, the permission check at line 40 becomes a no-op:

```php
if ($permission && !$user->hasPermissionTo($permission)) {
    // This is NEVER reached when $permission is null
    return redirect()->route('home')->with('error', 'You do not have permission ...');
}
```

Since only ~8 out of 30+ controllers are mapped in `$permissionMap`, the vast majority of routes have no permission enforcement. The system **fails open** instead of **failing closed**.

### Remediation

```php
private function getPermissionForAction($actionName): string
{
    $permissionMap = [
        'ClientController@index' => 'view_any_client',
        // ... all controller actions must be mapped
    ];

    return $permissionMap[$actionName]
        ?? abort(403, 'No permission mapping defined for this action.');
}
```

Or implement a default-deny approach:

```php
public function handle(Request $request, Closure $next)
{
    $user = Auth::user();

    // Admins bypass all checks
    if ($user?->hasRole('admin')) {
        return $next($request);
    }

    // All non-admin users must have explicit permission
    $route = $request->route();
    $actionName = $route->getActionName();
    $permission = $this->getPermissionForAction($actionName);

    if (!$permission) {
        // Default: deny access for unmapped routes
        return redirect()->route('home')
            ->with('error', 'Access denied: no permission mapping.');
    }

    if (!$user->hasPermissionTo($permission)) {
        return redirect()->route('home')
            ->with('error', 'You do not have permission to access this area.');
    }

    return $next($request);
}
```

---

## Vulnerability #10: JSON Injection in Workflow Configuration

| Attribute | Details |
|---|---|
| **OWASP** | A8 — Software and Data Integrity Failures |
| **Severity** | 🟡 Medium |
| **CVSS 3.1** | 6.5 (AV:N/AC:L/PR:L/UI:N/S:U/C:N/I:H/A:N) |

### Affected Files & Lines

| File | Line(s) |
|---|---|
| `app/Http/Controllers/WorkflowController.php` | 21–22 |

### Vulnerable Code

```php
// WorkflowController.php:16-25
public function store(Request $request)
{
    $validatedData = $request->validate([
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'triggers' => 'required|json',        // Only validates valid JSON syntax
        'actions' => 'required|json',          // Only validates valid JSON syntax
    ]);

    $workflow = Workflow::create($validatedData);
    return response()->json($workflow, 201);
}
```

The `triggers` and `actions` fields are JSON-typed columns in the database (cast as `json`). The validation only checks that the input is valid JSON syntax — there is no structural validation. An attacker can inject arbitrary action types and configurations, including SSRF payloads via `TYPE_WEBHOOK` (see Vulnerability #5).

### Remediation

Add structural validation after JSON syntax validation:

```php
public function store(Request $request)
{
    $validatedData = $request->validate([
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'triggers' => 'required|json',
        'actions' => 'required|json',
    ]);

    // Validate JSON structure
    $triggers = json_decode($validatedData['triggers'], true);
    $actions = json_decode($validatedData['actions'], true);

    $errors = [];

    if (!$this->isValidTriggers($triggers)) {
        $errors['triggers'] = 'Invalid trigger structure.';
    }

    if (!$this->isValidActions($actions)) {
        $errors['actions'] = 'Invalid action structure.';
    }

    if (!empty($errors)) {
        return response()->json(['errors' => $errors], 422);
    }

    $workflow = Workflow::create($validatedData);
    return response()->json($workflow, 201);
}

private function isValidTriggers(array $triggers): bool
{
    $allowedTypes = ['lead_created', 'contact_created', 'deal_created', 'task_created', 'email_received'];

    foreach ($triggers as $trigger) {
        if (!isset($trigger['type']) || !in_array($trigger['type'], $allowedTypes)) {
            return false;
        }
    }

    return true;
}

private function isValidActions(array $actions): bool
{
    $allowedTypes = [
        'send_email', 'update_contact', 'create_task',
        'add_tag', 'remove_tag', 'change_stage',
        'send_sms', 'create_deal', 'assign_to_user',
        'add_to_list', 'remove_from_list',
    ];

    foreach ($actions as $action) {
        if (!isset($action['type']) || !in_array($action['type'], $allowedTypes)) {
            return false;
        }

        // Additional validation for URL-based actions
        if ($action['type'] === 'webhook' && isset($action['config']['url'])) {
            $this->validateUrl($action['config']['url']);  // SSRF protection
        }
    }

    return true;
}
```

---

## Vulnerability #11: Webhook Secret Exposure Without Ownership Verification

| Attribute | Details |
|---|---|
| **OWASP** | A5 — Security Misconfiguration |
| **Severity** | 🟡 Medium |
| **CVSS 3.1** | 5.3 (AV:N/AC:L/PR:L/UI:N/S:U/C:H/I:N/A:N) |

### Affected Files & Lines

| File | Line(s) |
|---|---|
| `app/Http/Controllers/Api/WebhookController.php` | 52–55 |

### Vulnerable Code

```php
// WebhookController.php:52-55
public function show(Webhook $webhook)
{
    return response()->json($webhook->makeVisible('secret'));
}
```

The webhook's `secret` field is hidden from serialization by default (`$hidden = ['secret']` on the model). The `show()` method explicitly makes it visible, but does not verify that the requesting user owns the webhook (i.e., belongs to the same team). Any authenticated user can enumerate webhook IDs and read secrets for webhooks belonging to other teams.

### Remediation

```php
public function show(Request $request, Webhook $webhook)
{
    if ($webhook->team_id !== $request->user()?->currentTeam?->id) {
        abort(403, 'You do not own this webhook.');
    }

    return response()->json($webhook->makeVisible('secret'));
}
```

---

## Vulnerability #12: Dynamic Validation Rules from Database

| Attribute | Details |
|---|---|
| **OWASP** | A1 — Broken Access Control |
| **Severity** | 🟡 Medium |
| **CVSS 3.1** | 4.3 (AV:N/AC:L/PR:L/UI:N/S:U/C:N/I:L/A:N) |

### Affected Files & Lines

| File | Line(s) |
|---|---|
| `app/Http/Controllers/LeadFormController.php` | 50–57 |

### Vulnerable Code

```php
// LeadFormController.php:50-57
private function getValidationRules(LeadForm $leadForm): array
{
    $rules = [];
    foreach ($leadForm->fields as $field) {
        $rules[$field['name']] = $field['validation'] ?? 'required';
    }
    return $rules;
}
```

The validation rules for lead form submissions are stored in the database as part of the `LeadForm` model's `fields` JSON column. An attacker who can create or modify form configurations (e.g., via IDOR in FormBuilderController) can set arbitrary validation rule strings.

An attacker could:
- Set `validation` to `nullable` to bypass required field checks
- Set `validation` to an empty string to allow any data
- Set `validation` to a custom callback that throws exceptions

### Remediation

```php
private function getValidationRules(LeadForm $leadForm): array
{
    $allowedRules = [
        'required', 'nullable', 'email', 'string', 'numeric',
        'integer', 'boolean', 'max:255', 'max:1000',
        'min:1', 'url', 'date',
    ];

    $rules = [];
    foreach ($leadForm->fields as $field) {
        $fieldName = $field['name'] ?? '';
        $validation = $field['validation'] ?? 'required';

        if (empty($fieldName)) {
            continue;
        }

        // Validate each rule part
        $parts = explode('|', $validation);
        $filtered = [];
        foreach ($parts as $part) {
            $baseRule = explode(':', $part)[0];
            if (in_array($baseRule, $allowedRules, true)) {
                $filtered[] = $part;
            }
        }

        $rules[$fieldName] = !empty($filtered) ? implode('|', $filtered) : 'required';
    }

    return $rules;
}
```

---

## Vulnerability #13: Unprotected Email Tracking Link Route

| Attribute | Details |
|---|---|
| **OWASP** | A1 — Broken Access Control |
| **Severity** | 🟢 Low |
| **CVSS 3.1** | 3.7 (AV:N/AC:H/PR:N/UI:N/S:U/C:N/I:L/A:N) |

### Affected Files & Lines

| File | Line(s) |
|---|---|
| `app/Http/Controllers/EmailTrackingController.php` | 46–63 |
| `routes/web.php` | 33 |

### Issue

The email tracking link route responds to GET requests that:
1. Decode a user-supplied URL
2. Create a database record (link click event)
3. Redirect the user

Since this is a GET endpoint with state-changing side effects (writing to DB), it is technically a CSRF-like issue, though the tracking pixel use case makes this low severity (GET-based tracking is standard).

### Remediation (Optional — Low Priority)

Add URL signing to ensure only legitimate tracking links work:

```php
// routes/web.php
Route::get('/email/track/link/{tracking_id}', [EmailTrackingController::class, 'link'])
    ->name('email.tracking.link')
    ->middleware('signed');  // Laravel's built-in signed URL middleware
```

---

## Cross-Cutting Recommendations

### 1. Implement Global Tenant Scoping

Rather than relying on each controller to apply team scopes, use a global approach:

**Option A — Global Scope (Model Level):**
```php
// app/Models/Scopes/TenantScope.php
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $user = auth()->user();
        if ($user && $user->currentTeam) {
            $builder->where('team_id', $user->currentTeam->id);
        }
    }
}

// Applied in each tenant model's booted():
protected static function booted(): void
{
    static::addGlobalScope(new TenantScope);
}
```

**Option B — Middleware (Request Level):**
```php
// app/Http/Middleware/EnforceTenancy.php
class EnforceTenancy
{
    public function handle(Request $request, Closure $next): Response
    {
        $teamId = $request->user()?->currentTeam?->id;
        if ($teamId) {
            // Set as a request attribute for manual use
            $request->attributes->set('team_id', $teamId);

            // Or use Laravel's dynamic where
            TenantScopedModel::addGlobalScope('team', fn($q) => $q->where('team_id', $teamId));
        }

        $response = $next($request);

        // Clean up after response
        TenantScopedModel::hasGlobalScope('team')
            && TenantScopedModel::hasGlobalScope('team', fn($q) => $q->removeScope('team'));

        return $response;
    }
}
```

### 2. Default-Deny Authorization

Replace the open-permit pattern in `TeamsPermission.php` with a closed-denial pattern:

```php
// Instead of:
$permission = $this->getPermissionForAction($actionName);
if ($permission && !$user->hasPermissionTo($permission)) {
    abort(403);
}

// Use:
$permission = $this->getPermissionForAction($actionName);
if (!$permission || !$user->hasPermissionTo($permission)) {
    abort(403);
}
```

### 3. SSRF Protection Layer

Create a reusable SSRF validator for all outbound HTTP calls:

```php
// app/Security/UrlValidator.php
class UrlValidator
{
    private const BLOCKED_RANGES = [
        '127.0.0.0/8',
        '10.0.0.0/8',
        '172.16.0.0/12',
        '192.168.0.0/16',
        '169.254.0.0/16',
        '::1/128',
        'fc00::/7',
        'fe80::/10',
    ];

    public static function assertExternal(string $url): void
    {
        $host = parse_url($url, PHP_URL_HOST);
        if ($host === null) {
            throw new \InvalidArgumentException('Invalid URL.');
        }

        $ip = gethostbyname($host);

        foreach (self::BLOCKED_RANGES as $range) {
            if (self::ipInRange($ip, $range)) {
                throw new \RuntimeException("URL resolves to a blocked private IP range: {$range}");
            }
        }
    }

    private static function ipInRange(string $ip, string $range): bool
    {
        // Use a library like symfony/http-foundation or implement CIDR matching
        // Example using ip2long:
        [$subnet, $bits] = explode('/', $range);
        $ip = ip2long($ip);
        $subnet = ip2long($subnet);
        $mask = -1 << (32 - $bits);

        return ($ip & $mask) === ($subnet & $mask);
    }
}
```

### 4. File Upload Hardening

```php
// DocumentController.php — complete validation
$request->validate([
    'file' => [
        'required',
        'file',
        'max:10240',
        'mimes:pdf,doc,docx,xls,xlsx,csv,png,jpg,jpeg,gif,svg',
        'mimetypes:application/pdf,image/jpeg,image/png,image/gif',
    ],
    'documentable_id' => ['required', 'integer', 'exists:' . $documentableType . ',id'],
    'documentable_type' => ['required', 'string', Rule::in(config('crm.documentable_models'))],
]);

// Store outside webroot or use private storage with signed URLs
$path = $file->store('documents', 'private');  // 'private' disk not in webroot
```

### 5. Audit Logging

Enable logging for all security-relevant events:
- Failed authorization attempts
- Bulk data operations
- Webhook creation/modification
- Workflow configuration changes
- File uploads
- API token operations

The existing `AuditLog` model (`app/Models/AuditLog.php`) and `AuditLogService` (`app/Services/AuditLogService.php`) should be integrated into all security-sensitive operations.

---

## Tools & Techniques Used

This review was conducted through manual static analysis with the following methodologies:

| Technique | Usage |
|---|---|
| Data flow tracing | Source-to-sink analysis for each vulnerability |
| Access control review | Tracing auth middleware, team scope checks, and permission validation |
| Input validation audit | Checking all `$request->validate()` calls and their completeness |
| SSRF pattern detection | Searching for `Http::`, `curl_`, `file_get_contents()` with user-controlled URLs |
| XSS pattern detection | Checking `{!! !!}` usage in Blade templates and stored content rendering |
| File upload analysis | Reviewing validation rules, storage paths, and filename handling |
| Mass assignment review | Checking `$fillable` properties and `create()` / `update()` calls |
| Configuration review | Examining middleware stacks, CORS, session settings |

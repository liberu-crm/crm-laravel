# Liberu CRM — QA Guidelines

## Table of Contents

1. [QA Principles](#1-qa-principles)
2. [Test Environment Setup](#2-test-environment-setup)
3. [Test Types & Coverage](#3-test-types--coverage)
4. [Module-by-Module QA Checklist](#4-module-by-module-qa-checklist)
   - [4.1 CRM Core](#41-crm-core)
   - [4.2 Sales & Pipeline](#42-sales--pipeline)
   - [4.3 Marketing](#43-marketing)
   - [4.4 Communications & Helpdesk](#44-communications--helpdesk)
   - [4.5 Advertising](#45-advertising)
   - [4.6 Social Media](#46-social-media)
   - [4.7 Workflow Automation](#47-workflow-automation)
   - [4.8 Integrations & OAuth](#48-integrations--oauth)
   - [4.9 Reporting & Analytics](#49-reporting--analytics)
   - [4.10 Administration](#410-administration)
   - [4.11 Infrastructure](#411-infrastructure)
5. [Bug Reporting Template](#5-bug-reporting-template)
6. [Regression Testing](#6-regression-testing)
7. [Performance Testing](#7-performance-testing)
8. [Security Testing](#8-security-testing)
9. [Test Automation Guidelines](#9-test-automation-guidelines)
10. [QA Sign-off Checklist](#10-qa-sign-off-checklist)

---

## 1. QA Principles

| Principle | Description |
|-----------|-------------|
| **Shift left** | Test as early as possible — unit tests before feature tests, feature tests before manual QA |
| **Isolation** | Each test should be independent; use SQLite `:memory:` + `RefreshDatabase` |
| **Realistic data** | Use model factories with meaningful faker data, not generic placeholders |
| **Edge cases** | Always test empty states, pagination boundaries, invalid inputs, concurrent access |
| **Regression first** | Before QAing a new feature, run the full test suite to confirm nothing is broken |
| **Traceability** | Every bug report must link to the specific module, version, and reproduction steps |

### Entry Criteria

Before QA begins on any module:
- All existing tests pass (`php artisan test`)
- No new PHPStan errors (`./vendor/bin/phpstan analyse`)
- Code style passes (`./vendor/bin/pint`)
- Feature is behind a feature flag or module toggle if applicable

### Exit Criteria

Module is considered QA-passed when:
- All critical and high severity bugs are fixed and verified
- All automated tests for the module pass
- No regression in related modules
- QA checklist for the module is 100% signed off
- Performance benchmarks are within acceptable thresholds

---

## 2. Test Environment Setup

### Local Environment

```bash
# Fresh environment
cp .env.example .env
composer install
npm install && npm run build
php artisan key:generate
php artisan migrate --seed

# Run all tests
php artisan test

# Run with coverage (requires Xdebug)
php artisan test --coverage-clover

# Run specific test suite
php artisan test --filter=ContactApiTest

# Watch for file changes (requires HMR)
npm run dev
```

### Test Database Configuration (`phpunit.xml`)

| Setting | Value |
|---------|-------|
| DB_CONNECTION | sqlite |
| DB_DATABASE | :memory: |
| CACHE_DRIVER | array |
| SESSION_DRIVER | array |
| QUEUE_CONNECTION | sync |
| MAIL_MAILER | array |

### Environment Variants

| Environment | Database | Cache | Queue | Purpose |
|-------------|----------|-------|-------|---------|
| `testing` (default) | SQLite :memory: | array | sync | Unit/feature tests |
| `staging` | MySQL 8.0 | Redis | database | Integration/E2E tests |
| `production` | MySQL 8.0 | Redis | redis | Smoke tests only |

### Available Test Stubs

The project provides stubs in `tests/Stubs/` for external services:

| Stub | Services |
|------|----------|
| `TwilioStubs.php` | Twilio SDK (Calls, Recordings, Exceptions) |
| `GoogleApiStubs.php` | Google APIs |
| `MicrosoftGraphStubs.php` | Microsoft Graph API |

Use these stubs to test integration code without hitting real endpoints.

---

## 3. Test Types & Coverage

### Test Pyramid

```
         ╱╲
        ╱ E2E ╲           ← Browser tests (Dusk), integration flows
       ╱────────╲
      ╱ Feature  ╲        ← HTTP tests, Filament resource tests, API tests
     ╱──────────────╲
    ╱    Unit        ╲    ← Model tests, Service tests, Job tests
   ╱────────────────────╲
  ╱ Static Analysis       ╲ ← PHPStan, Pint, Rector
 ╱──────────────────────────╲
```

### Required Coverage by Layer

| Layer | Min Coverage | Tools | What to Test |
|-------|-------------|-------|-------------|
| **Models** | 90%+ | PHPUnit | Scopes, relationships, casts, validation, factory state |
| **Services** | 85%+ | PHPUnit + Mockery | Business logic, error handling, edge cases |
| **Controllers** | 80%+ | PHPUnit | HTTP status codes, JSON structure, auth, validation |
| **Filament Resources** | 70%+ | PHPUnit | CRUD operations, form validation, table filters |
| **Livewire Components** | 70%+ | PHPUnit | Mount, render, actions, validation, event emitting |
| **Jobs** | 90%+ | PHPUnit | Handle method, failure handling, queue interactions |
| **Events/Listeners** | 90%+ | PHPUnit | Event is dispatched, listener handles correctly |
| **JS Frontend** | Manual | — | UI interactions, responsive design, browser compatibility |

### Test Naming Conventions

```php
// Unit tests
public function test_it_creates_a_contact(): void
public function test_it_validates_required_fields(): void
public function test_it_scopes_by_team(): void

// Feature tests
public function test_authenticated_user_can_create_contact(): void
public function test_unauthenticated_request_returns_401(): void
public function test_it_paginates_results(): void
```

---

## 4. Module-by-Module QA Checklist

### 4.1 CRM Core

#### 4.1.1 Contacts

| # | Test Case | Type | Expected Result |
|---|-----------|------|-----------------|
| C-001 | Create contact with all fields | Functional | Contact is created, 201 response, data matches input |
| C-002 | Create contact with only required fields | Functional | Contact is created with defaults |
| C-003 | Create contact with invalid email | Validation | 422 error, email validation message |
| C-004 | Create contact with duplicate email per team | Functional | Succeeds (teams are isolated) |
| C-005 | Create contact with duplicate email in same team | Validation | 422 error, unique email per team |
| C-006 | Edit contact — update name, email, phone | Functional | Fields updated, 200 response |
| C-007 | Edit contact — clear optional fields | Functional | Fields set to null |
| C-008 | Delete contact | Functional | Soft-deleted, 200 response |
| C-009 | Delete contact — restore | Functional | Restored, visible again |
| C-010 | Delete contact — force delete | Functional | Permanently removed |
| C-011 | List contacts — pagination | Functional | Paginated results, default per-page |
| C-012 | List contacts — search by name | Functional | Filtered results match query |
| C-013 | List contacts — search by email | Functional | Filtered results match query |
| C-014 | List contacts — filter by company | Functional | Only contacts in that company |
| C-015 | List contacts — sort by created_at | Functional | Correct sort order |
| C-016 | Bulk delete contacts | Functional | All selected contacts soft-deleted |
| C-017 | Bulk assign contacts to user | Functional | All selected contacts reassigned |
| C-018 | Autocomplete endpoint | Functional | Returns matching contacts by name/email |
| C-019 | Contact timeline — activity appears | Functional | Notes, tasks, emails appear in timeline |
| C-020 | Contact timeline — ordering | Functional | Most recent first |
| C-021 | Contact custom fields — create | Functional | Custom field saved and displayed |
| C-022 | Contact custom fields — edit | Functional | Custom field updated |
| C-023 | Contact custom fields — delete | Functional | Custom field removed |
| C-024 | Contact lifecycle stage transitions | Functional | Stage updates correctly, history tracked |
| C-025 | Contact engagement score updates | Functional | Score recalculated on activity |
| C-026 | Contact collaboration — team members | Functional | Multi-user editing visible |
| C-027 | Contact collaboration — change tracking | Functional | Changes attributed to correct user |
| C-028 | Contact UI — create via Filament | UI | Form renders, validation works, success notification |
| C-029 | Contact UI — edit via Filament | UI | Form pre-filled, save works, cancel works |
| C-030 | Contact UI — table filters | UI | Filters work (status, company, assigned to) |
| C-031 | Contact API — rate limiting | Security | 429 after exceeding throttle limit |
| C-032 | Contact API — unauthenticated | Security | 401 for all endpoints |

#### 4.1.2 Companies

| # | Test Case | Type | Expected Result |
|---|-----------|------|-----------------|
| CO-001 | Create company with all fields | Functional | Company created, data matches |
| CO-002 | Create company with missing name | Validation | 422 error |
| CO-003 | Edit company | Functional | Updates persist |
| CO-004 | Delete company | Functional | Soft-deleted |
| CO-005 | List companies — search, filter, sort | Functional | All query params work |
| CO-006 | Company with contacts — cascade on delete | Integration | Contacts unlinked, not deleted |
| CO-007 | Company UI via Filament | UI | CRUD flows work end-to-end |

#### 4.1.3 Leads

| # | Test Case | Type | Expected Result |
|---|-----------|------|-----------------|
| L-001 | Create lead from form submission | Functional | Lead created, source tracked |
| L-002 | Create lead manually via Filament | Functional | Lead created, all fields saved |
| L-003 | Qualify lead → convert to contact | Functional | Lead converted, contact created, status updated |
| L-004 | Lead quality report — filters | Functional | Report shows correct data per filter |
| L-005 | Lead scoring — engagement based | Functional | Score updates on email open, click |
| L-006 | Lead scoring — threshold triggers | Integration | Workflow triggered when score crosses threshold |
| L-007 | Lead form — public submission | Functional | Lead created from external form |
| L-008 | Lead form — spam protection | Security | Blocked if honeypot filled or too fast |
| L-009 | Lead form — custom field mapping | Functional | Custom fields populated correctly |
| L-010 | Lead UI — Filament resource | UI | CRUD, filters, quality report page all work |

#### 4.1.4 Tasks

| # | Test Case | Type | Expected Result |
|---|-----------|------|-----------------|
| T-001 | Create task with all fields | Functional | Task created, assigned user notified |
| T-002 | Create task with due date in past | Validation | Warning or rejection |
| T-003 | Update task status | Functional | Status transitions correctly |
| T-004 | Mark task as complete | Functional | Completed date set |
| T-005 | Reopen completed task | Functional | Status back to open/pending |
| T-006 | Assign task to user | Functional | Assigned user gets notification |
| T-007 | Task list — filters (status, assignee, due date) | Functional | All filters work |
| T-008 | Task list — sort by due date | Functional | Correct ordering |
| T-009 | Task reminder — sends at due time | Integration | Notification sent, email dispatched |
| T-010 | Task reminder — recurring tasks | Integration | Reminder resets after completion |
| T-011 | Task calendar sync — Google Calendar | Integration | Task appears in Google Calendar |
| T-012 | Task calendar sync — Outlook Calendar | Integration | Task appears in Outlook Calendar |
| T-013 | Task UI — Livewire component | UI | TaskForm and TaskList render and update |
| T-014 | Task overdue detection | Functional | Flagged as overdue after due date passes |
| T-015 | Task bulk operations | Functional | Bulk update/delete/assign work |

#### 4.1.5 Activities & Notes

| # | Test Case | Type | Expected Result |
|---|-----------|------|-----------------|
| AN-001 | Add note to contact | Functional | Note appears in timeline |
| AN-002 | Add note to deal | Functional | Note appears in deal timeline |
| AN-003 | Add note to lead | Functional | Note appears in lead timeline |
| AN-004 | Add note to opportunity | Functional | Note appears in opportunity timeline |
| AN-005 | Edit note | Functional | Updated in timeline |
| AN-006 | Delete note | Functional | Removed from timeline |
| AN-007 | Activity search — by type | Functional | Filtered results |
| AN-008 | Activity search — by date range | Functional | Correct date scoping |
| AN-009 | Activity search — by user | Functional | Filtered by activity author |

#### 4.1.6 Documents

| # | Test Case | Type | Expected Result |
|---|-----------|------|-----------------|
| D-001 | Upload document (PDF) | Functional | File stored, record created |
| D-002 | Upload document (image) | Functional | File stored with thumbnail |
| D-003 | Upload document (unsupported type) | Validation | 422 error |
| D-004 | Upload document (file too large) | Validation | 422 error |
| D-005 | Download document | Functional | Correct file returned |
| D-006 | Delete document | Functional | File and record removed |
| D-007 | List documents — filter by type | Functional | Filter works |
| D-008 | Document metadata — edit | Functional | Custom metadata saved |
| D-009 | Document metadata — version history | Functional | Previous versions accessible |

---

### 4.2 Sales & Pipeline

#### 4.2.1 Deals

| # | Test Case | Type | Expected Result |
|---|-----------|------|-----------------|
| DE-001 | Create deal with all fields | Functional | Deal created, pipeline default stage assigned |
| DE-002 | Move deal between stages | Functional | Stage changes, history tracked |
| DE-003 | Move deal backwards in pipeline | Functional | Allowed (no restrictions) |
| DE-004 | Close deal (won) | Functional | Status = won, closed_at set, pipeline completed |
| DE-005 | Close deal (lost) | Functional | Status = lost, lost reason captured |
| DE-006 | Reopen closed deal | Functional | Status back to open, moved to appropriate stage |
| DE-007 | Deal amount forecasting | Functional | Amount aggregated correctly in forecast |
| DE-008 | Deal bulk update | Functional | Stage, owner, amount batch-updated |
| DE-009 | Deal API — CRUD | Functional | All endpoints return correct data |
| DE-010 | Visual pipeline — drag and drop | UI | Stage changes on drop, AJAX save |
| DE-011 | Visual pipeline — real-time updates | Integration | Other users see changes via Reverb |
| DE-012 | Deal — team scoping | Security | Only own team's deals visible |

#### 4.2.2 Pipelines & Stages

| # | Test Case | Type | Expected Result |
|---|-----------|------|-----------------|
| PS-001 | Create pipeline | Functional | Pipeline created with default stages |
| PS-002 | Create pipeline with custom stages | Functional | All custom stages created in order |
| PS-003 | Edit pipeline name | Functional | Updated |
| PS-004 | Add stage to pipeline | Functional | Stage added at correct position |
| PS-005 | Reorder stages | Functional | Order persists |
| PS-006 | Delete stage — deals in it | Integration | Prompt to reassign or error |
| PS-007 | Delete pipeline — with deals | Integration | Blocked or cascading behavior |
| PS-008 | Switch deal pipeline | Functional | Deal moves to new pipeline's first stage |

#### 4.2.3 Opportunities

| # | Test Case | Type | Expected Result |
|---|-----------|------|-----------------|
| OP-001 | Create opportunity | Functional | Created, linked to contact/company |
| OP-002 | Opportunity pipeline view | UI | Visual pipeline renders, drag-and-drop works |
| OP-003 | Opportunity — add message | Functional | Message linked to opportunity |
| OP-004 | Opportunity — add note | Functional | Note linked to opportunity |
| OP-005 | Opportunity — timeline | Functional | All activities visible |
| OP-006 | Opportunity — stage probability | Functional | Probability percentage maps to stage |

#### 4.2.4 Sales Forecasting

| # | Test Case | Type | Expected Result |
|---|-----------|------|-----------------|
| SF-001 | Forecast — pipeline-based | Functional | Sum of open deals by stage |
| SF-002 | Forecast — historical comparison | Functional | Previous period data loads |
| SF-003 | Forecast — weighted forecast | Functional | Amount × stage probability = weighted value |
| SF-004 | Forecast — confidence level | Functional | Manual override of confidence percentage |
| SF-005 | Forecast — date range filters | Functional | Filters constrain data correctly |
| SF-006 | Forecast — UI | UI | Charts, tables render correctly |

#### 4.2.5 Quote Requests

| # | Test Case | Type | Expected Result |
|---|-----------|------|-----------------|
| QR-001 | Create quote request (public) | Functional | Stored, notification sent |
| QR-002 | Create quote request — validation | Validation | Required fields enforced |
| QR-003 | Manage quote request (admin) | Functional | Status updates, notes added |
| QR-004 | Convert quote to deal | Functional | Deal created from quote data |

#### 4.2.6 Accounting Integration

| # | Test Case | Type | Expected Result |
|---|-----------|------|-----------------|
| AI-001 | Connect accounting integration | Functional | OAuth flow completes |
| AI-002 | Sync invoices to CRM | Integration | Invoices appear in CRM |
| AI-003 | Sync payments to CRM | Integration | Payments matched to deals |
| AI-004 | Disconnect integration | Functional | Data retained, no new sync |

---

### 4.3 Marketing

#### 4.3.1 Marketing Campaigns

| # | Test Case | Type | Expected Result |
|---|-----------|------|-----------------|
| MC-001 | Create marketing campaign | Functional | Campaign created with type, audience |
| MC-002 | Add recipients to campaign | Functional | Recipients added, duplicates removed |
| MC-003 | Launch campaign | Functional | Campaign status = sending |
| MC-004 | Campaign — email delivery | Integration | Emails queued and sent |
| MC-005 | Campaign — SMS delivery | Integration | SMS sent via Twilio |
| MC-006 | Campaign — analytics | Functional | Opens, clicks, bounces tracked |
| MC-007 | Campaign — abort sending | Functional | Remaining sends cancelled |

#### 4.3.2 Mailchimp Integration

| # | Test Case | Type | Expected Result |
|---|-----------|------|-----------------|
| ML-001 | Connect Mailchimp account | Functional | OAuth flow, API key saved |
| ML-002 | Sync Mailchimp campaigns to CRM | Integration | Campaigns appear in CRM |
| ML-003 | Create campaign in CRM → push to Mailchimp | Integration | Campaign created in Mailchimp |
| ML-004 | A/B test campaign — create | Functional | A/B test configured |
| ML-005 | A/B test campaign — view results | Functional | Winner determined, stats shown |
| ML-006 | View campaign performance | Functional | Opens, clicks, CTR, bounce rate |
| ML-007 | Campaign performance report | UI | Charts, export work |
| ML-008 | A/B test results report | UI | Statistical significance shown |
| ML-009 | Disconnect Mailchimp | Functional | Clean disconnect |

#### 4.3.3 Email Templates

| # | Test Case | Type | Expected Result |
|---|-----------|------|-----------------|
| ET-001 | Create email template — WYSIWYG | Functional | HTML content saved |
| ET-002 | Create email template — variables | Functional | Merge tags render correctly |
| ET-003 | Preview template | UI | Rendered preview accurate |
| ET-004 | Use template in campaign | Integration | Content rendered with merge tags replaced |
| ET-005 | Categorize templates | Functional | Categories work |
| ET-006 | Edit template | Functional | Changes saved |
| ET-007 | Delete template | Functional | Removed, not available in campaigns |

#### 4.3.4 Landing Pages

| # | Test Case | Type | Expected Result |
|---|-----------|------|-----------------|
| LP-001 | Create landing page | Functional | Page created with slug, content |
| LP-002 | Landing page — public access | Functional | Renders at correct URL |
| LP-003 | Landing page — form integration | Functional | Form submissions captured |
| LP-004 | Landing page — analytics | Functional | Views, conversions tracked |
| LP-005 | Landing page — SEO fields | Functional | Meta title, description render |
| LP-006 | Landing page — publish/unpublish | Functional | Visibility toggle works |

#### 4.3.5 Lead Forms & Form Builder

| # | Test Case | Type | Expected Result |
|---|-----------|------|-----------------|
| LF-001 | Create form via form builder | Functional | Form created with fields |
| LF-002 | Form builder — drag-and-drop fields | UI | Field order saves |
| LF-003 | Form builder — field types (text, email, select, etc.) | Functional | All field types render |
| LF-004 | Public form submission | Functional | Data saved, lead created |
| LF-005 | Form — file upload field | Functional | File uploaded and stored |
| LF-006 | Form — conditional fields | Functional | Conditional logic works |
| LF-007 | Form — reCAPTCHA/spam protection | Functional | Spam submissions blocked |
| LF-008 | Form submissions — view in CRM | Functional | Submission data visible |
| LF-009 | Form submissions — export | Functional | CSV/XLS export works |

#### 4.3.6 Custom Fields

| # | Test Case | Type | Expected Result |
|---|-----------|------|-----------------|
| CF-001 | Create custom field for contacts | Functional | Field appears on contact form |
| CF-002 | Create custom field for leads | Functional | Field appears on lead form |
| CF-003 | Custom field types (text, number, date, select) | Functional | All types work |
| CF-004 | Custom field — required | Validation | Enforced on save |
| CF-005 | Custom field — update field options | Functional | Select options update |
| CF-006 | Custom field — delete | Functional | Field removed, data preserved in audit |
| CF-007 | Custom field — reorder | Functional | Display order persists |

---

### 4.4 Communications & Helpdesk

#### 4.4.1 Unified Helpdesk

| # | Test Case | Type | Expected Result |
|---|-----------|------|-----------------|
| UH-001 | Connect Gmail inbox | Integration | OAuth flow, emails fetching |
| UH-002 | Connect Outlook inbox | Integration | OAuth flow, emails fetching |
| UH-003 | Connect IMAP inbox | Integration | Config saved, emails fetching |
| UH-004 | Connect POP3 inbox | Integration | Config saved, emails fetching |
| UH-005 | Connect Facebook Messenger | Integration | Messages appear as tickets |
| UH-006 | Connect WhatsApp number | Integration | Messages appear as tickets |
| UH-007 | Incoming email → auto-create ticket | Integration | Ticket created from email |
| UH-008 | Incoming message → auto-create ticket | Integration | Ticket created from message |
| UH-009 | Reply to ticket → reply sent via original channel | Integration | Reply routed correctly |
| UH-010 | Ticket — assign to agent | Functional | Agent assigned, notified |
| UH-011 | Ticket — change status | Functional | Status updated (open/pending/resolved/closed) |
| UH-012 | Ticket — priority settings | Functional | Priority levels work |
| UH-013 | Ticket — source filtering | Functional | Filter by channel source |
| UH-014 | Ticket — add internal note | Functional | Note visible to agents only |
| UH-015 | Ticket — merge duplicate tickets | Functional | Conversations merged |
| UH-016 | Unified inbox — real-time updates | Integration | New messages appear via Reverb |

#### 4.4.2 Live Chat & Chatbots

| # | Test Case | Type | Expected Result |
|---|-----------|------|-----------------|
| LC-001 | Live chat widget — visitor loads page | UI | Widget appears, greeting shows |
| LC-002 | Live chat — send message | Functional | Message delivered to agent |
| LC-003 | Live chat — agent replies | Functional | Reply visible to visitor |
| LC-004 | Live chat — auto-create contact | Integration | New visitor → contact created |
| LC-005 | Live chat — transfer to another agent | Functional | Transfer works, context preserved |
| LC-006 | Chatbot — create definition | Functional | Bot flows configured |
| LC-007 | Chatbot — trigger on keyword | Functional | Bot responds to keywords |
| LC-008 | Chatbot — lead qualification flow | Functional | Bot collects info → lead created |
| LC-009 | Chatbot — handoff to human | Integration | Escalated to agent with context |
| LC-010 | Chatbot — interaction history | Functional | Transcript saved |

#### 4.4.3 Email Tracking

| # | Test Case | Type | Expected Result |
|---|-----------|------|-----------------|
| ETR-001 | Email sent — tracking pixel embedded | Functional | Pixel URL in email body |
| ETR-002 | Email opened — pixel loaded | Integration | Open event recorded |
| ETR-003 | Email opened — duplicate open | Integration | Not double-counted |
| ETR-004 | Email link clicked — link rewritten | Functional | Tracking URL redirects to original |
| ETR-005 | Email link clicked — click recorded | Integration | Click event recorded |
| ETR-006 | Email link clicked — duplicate click | Integration | Counted each time |
| ETR-007 | Engagement scoring — open | Functional | Score increases on open |
| ETR-008 | Engagement scoring — multiple opens | Functional | Diminishing returns on score |
| ETR-009 | Campaign — open rate calculation | Functional | Correct percentage |
| ETR-010 | Campaign — click-through rate | Functional | Correct percentage |
| ETR-011 | Email tracking — API endpoint | API | Events accessible via API |

#### 4.4.4 Twilio Integration

| # | Test Case | Type | Expected Result |
|---|-----------|------|-----------------|
| TW-001 | Connect Twilio account | Functional | API credentials validated and saved |
| TW-002 | Send SMS via Twilio | Integration | SMS sent, status tracked |
| TW-003 | Receive SMS → create ticket | Integration | New ticket from SMS |
| TW-004 | Make outbound call | Integration | Call initiated, recording started |
| TW-005 | Incoming call → CRM lookup | Integration | Caller ID matched to contact |
| TW-006 | Call recording — callback stored | Integration | Recording URL saved |
| TW-007 | Twilio call settings — configure | Functional | Settings saved, validated |
| TW-008 | Twilio integration page — UI | UI | All configurations accessible |

#### 4.4.5 WhatsApp

| # | Test Case | Type | Expected Result |
|---|-----------|------|-----------------|
| WA-001 | Connect WhatsApp Business number | Functional | Number verified, webhook configured |
| WA-002 | Send WhatsApp message | Integration | Message sent, delivery confirmed |
| WA-003 | Receive WhatsApp message | Integration | Message → ticket created |
| WA-004 | WhatsApp — template messages | Functional | Templates work |
| WA-005 | WhatsApp — media messages | Integration | Images/documents sent and received |

#### 4.4.6 Messages

| # | Test Case | Type | Expected Result |
|---|-----------|------|-----------------|
| MS-001 | Create message | Functional | Message stored with source tracking |
| MS-002 | Message — team scoping | Security | Only same-team messages visible |
| MS-003 | Message — search by content | Functional | Full-text search works |
| MS-004 | Message — filter by source | Functional | Channel filter works |

---

### 4.5 Advertising

#### 4.5.1 Advertising Accounts

| # | Test Case | Type | Expected Result |
|---|-----------|------|-----------------|
| AA-001 | Connect Facebook Ads account | Integration | OAuth, account linked |
| AA-002 | Connect Google Ads account | Integration | API connection established |
| AA-003 | Connect LinkedIn Ads account | Integration | OAuth, account linked |
| AA-004 | Connect Instagram account | Integration | OAuth, account linked |
| AA-005 | Sync ad accounts — all platforms | Integration | Campaigns, ad sets, ads synced |
| AA-006 | Disconnect ad account | Functional | Data retained, no further sync |
| AA-007 | Last sync timestamp shown | Functional | UI shows last sync time |
| AA-008 | Metadata — account name, currency, timezone | Functional | Displayed correctly |

#### 4.5.2 Campaigns (Ad)

| # | Test Case | Type | Expected Result |
|---|-----------|------|-----------------|
| AC-001 | View ad campaigns — list | Functional | Paginated, sorted by date |
| AC-002 | Ad campaign — platform badge | UI | Platform icon/name shown |
| AC-003 | Ad campaign — metrics (impressions, clicks, spend) | Functional | Aggregated from platform API |

#### 4.5.3 Ad Sets & Ads

| # | Test Case | Type | Expected Result |
|---|-----------|------|-----------------|
| AS-001 | Ad set — metrics display | Functional | Performance metrics shown |
| AS-002 | Ad — creative preview | UI | Image/text preview works |
| AS-003 | Ad — performance breakdown | Functional | CTR, CPC, CPM calculated |

#### 4.5.4 Ad Performance

| # | Test Case | Type | Expected Result |
|---|-----------|------|-----------------|
| AP-001 | Ad performance dashboard — overview | UI | Metrics aggregated across platforms |
| AP-002 | Ad performance — date range filters | Functional | Data constrained correctly |
| AP-003 | Ad performance — platform filter | Functional | Filter works |
| AP-004 | Ad performance widget — on dashboard | UI | Widget loads with correct data |
| AP-005 | Analytics dashboard — advertising tab | Functional | All charts and tables render |

---

### 4.6 Social Media

#### 4.6.1 Social Media Posts

| # | Test Case | Type | Expected Result |
|---|-----------|------|-----------------|
| SP-001 | Create post — text only | Functional | Post saved, status = draft |
| SP-002 | Create post — with image | Functional | Image uploaded |
| SP-003 | Create post — with link | Functional | Link preview generated |
| SP-004 | Schedule post | Functional | Post status = scheduled |
| SP-005 | Scheduled post — publishes at correct time | Integration | Console command publishes it |
| SP-006 | Post — edit before publish | Functional | Changes saved |
| SP-007 | Post — cancel scheduled | Functional | Status back to draft |
| SP-008 | Post analytics — update after publish | Integration | Likes, shares, comments fetched |
| SP-009 | Post list — filter by platform | Functional | Filter works |
| SP-010 | Post list — filter by status | Functional | Draft/scheduled/published filter |

#### 4.6.2 Social Auth (Socialstream)

| # | Test Case | Type | Expected Result |
|---|-----------|------|-----------------|
| SS-001 | Login via Google | Functional | User created/logged in |
| SS-002 | Login via Facebook | Functional | User created/logged in |
| SS-003 | Login via LinkedIn | Functional | User created/logged in |
| SS-004 | Login via Twitter | Functional | User created/logged in |
| SS-005 | Login via GitHub | Functional | User created/logged in |
| SS-006 | Connect social account to existing user | Functional | Account linked |
| SS-007 | Disconnect social account | Functional | Unlinked, password required |
| SS-008 | Registration via social provider | Functional | New user, team created |

---

### 4.7 Workflow Automation

#### 4.7.1 Workflow CRUD

| # | Test Case | Type | Expected Result |
|---|-----------|------|-----------------|
| WA-001 | Create workflow with name, description | Functional | Workflow saved, disabled by default |
| WA-002 | Create workflow — duplicate name | Validation | Error on duplicate within team |
| WA-003 | Enable workflow | Functional | Status = active |
| WA-004 | Disable workflow | Functional | Status = inactive |
| WA-005 | Delete workflow | Functional | Soft-deleted, executions preserved |
| WA-006 | Clone workflow | Functional | All triggers, actions, conditions copied |

#### 4.7.2 Triggers

| # | Test Case | Type | Expected Result |
|---|-----------|------|-----------------|
| WT-001 | Trigger — contact created | Integration | Workflow fires on contact creation |
| WT-002 | Trigger — contact lifecycle stage change | Integration | Fires on stage transition |
| WT-003 | Trigger — deal stage change | Integration | Fires on deal stage update |
| WT-004 | Trigger — email opened | Integration | Fires on pixel load |
| WT-005 | Trigger — email link clicked | Integration | Fires on link click |
| WT-006 | Trigger — form submitted | Integration | Fires on form submission |
| WT-007 | Trigger — task completed | Integration | Fires on task status = completed |
| WT-008 | Trigger — date-based (specific date) | Integration | Fires on given date |
| WT-009 | Trigger — date-based (relative) | Integration | Fires X days after event |
| WT-010 | Trigger — scheduled (cron) | Integration | Fires on schedule |
| WT-011 | Multiple triggers — OR logic | Integration | Any trigger activates workflow |
| WT-012 | Trigger conditions — evaluate correctly | Integration | Condition AND/OR logic works |

#### 4.7.3 Actions

| # | Test Case | Type | Expected Result |
|---|-----------|------|-----------------|
| WX-001 | Action — send email | Integration | Email sent via configured provider |
| WX-002 | Action — send SMS | Integration | SMS sent via Twilio |
| WX-003 | Action — update contact field | Integration | Contact field updated |
| WX-004 | Action — update deal field | Integration | Deal field updated |
| WX-005 | Action — create task | Integration | Task created, assigned |
| WX-006 | Action — create deal | Integration | Deal created in pipeline |
| WX-007 | Action — add/remove tags | Integration | Tags modified |
| WX-008 | Action — assign to user | Integration | Owner changed |
| WX-009 | Action — webhook (outgoing) | Integration | HTTP request made |
| WX-010 | Action — time delay | Integration | Next action executes after delay |
| WX-011 | Action — conditional branch | Integration | Correct branch taken |

#### 4.7.4 Conditions

| # | Test Case | Type | Expected Result |
|---|-----------|------|-----------------|
| WC-001 | Condition — equals | Functional | Exact match evaluation |
| WC-002 | Condition — not equals | Functional | Non-match evaluation |
| WC-003 | Condition — contains | Functional | Partial match evaluation |
| WC-004 | Condition — greater than (number/date) | Functional | Comparison evaluation |
| WC-005 | Condition — less than (number/date) | Functional | Comparison evaluation |
| WC-006 | Condition — is set / is not set | Functional | Null check evaluation |
| WC-007 | Condition — AND group | Functional | All conditions must pass |
| WC-008 | Condition — OR group | Functional | Any condition must pass |
| WC-009 | Nested condition groups | Functional | Deep nesting evaluates correctly |

#### 4.7.5 Execution & Monitoring

| # | Test Case | Type | Expected Result |
|---|-----------|------|-----------------|
| WM-001 | Workflow execution — success | Functional | Status = completed, no errors |
| WM-002 | Workflow execution — partial failure | Functional | Failed action logged, execution continues |
| WM-003 | Workflow execution — all actions fail | Functional | Status = failed, errors recorded |
| WM-004 | Execution log — view details | UI | Trigger, actions, timestamps visible |
| WM-005 | Workflow — rate limiting | Functional | Max executions per time window |
| WM-006 | Workflow — recursion prevention | Security | Infinite loop detection |
| WM-007 | Workflow — test mode | Functional | Dry run, no side effects |

---

### 4.8 Integrations & OAuth

#### 4.8.1 OAuth Configuration

| # | Test Case | Type | Expected Result |
|---|-----------|------|-----------------|
| OA-001 | Add OAuth config — valid provider | Functional | Config saved, encrypted secrets |
| OA-002 | Add OAuth config — invalid provider | Validation | Error |
| OA-003 | Edit OAuth config | Functional | Updated |
| OA-004 | Delete OAuth config | Functional | Removed, tokens revoked |
| OA-005 | Initiate OAuth flow | Functional | Redirects to provider |
| OA-006 | OAuth callback — success | Integration | Token received and stored |
| OA-007 | OAuth callback — failure (denied) | Integration | Error handled gracefully |
| OA-008 | Token refresh — auto-refresh | Integration | Expired token refreshed silently |
| OA-009 | Token refresh — failure | Integration | Notified to reconnect |

#### 4.8.2 Calendar Integrations

| # | Test Case | Type | Expected Result |
|---|-----------|------|-----------------|
| GC-001 | Connect Google Calendar | Integration | Events sync to CRM |
| GC-002 | Create event in CRM → Google Calendar | Integration | Created in Google Calendar |
| GC-003 | Connect Outlook Calendar | Integration | Events sync to CRM |
| GC-004 | Create event in CRM → Outlook Calendar | Integration | Created in Outlook Calendar |
| GC-005 | Calendar — two-way sync | Integration | Changes in provider reflected in CRM |

#### 4.8.3 Payment (Stripe)

| # | Test Case | Type | Expected Result |
|---|-----------|------|-----------------|
| ST-001 | Connect Stripe | Integration | Account linked, webhook configured |
| ST-002 | Sync subscription data | Integration | Plans, subscriptions visible |
| ST-003 | Webhook — invoice paid | Integration | Payment recorded |
| ST-004 | Webhook — subscription cancelled | Integration | Subscription status updated |

#### 4.8.4 YouTube

| # | Test Case | Type | Expected Result |
|---|-----------|------|-----------------|
| YT-001 | Connect YouTube account | Integration | Channel data accessible |
| YT-002 | View YouTube analytics | Functional | Views, subscribers data |

---

### 4.9 Reporting & Analytics

#### 4.9.1 Report Builder

| # | Test Case | Type | Expected Result |
|---|-----------|------|-----------------|
| RB-001 | Create report — select entity (contacts, deals, etc.) | Functional | Report created |
| RB-002 | Create report — add filters | Functional | Filters constrain data |
| RB-003 | Create report — chart type (bar, line, pie, table) | Functional | All chart types render |
| RB-004 | Create report — date range | Functional | Date filter works |
| RB-005 | Report — scheduled export | Functional | Report sent on schedule |
| RB-006 | Report — export to CSV | Functional | CSV downloads with correct data |
| RB-007 | Report — export to PDF | Functional | PDF downloads with chart |
| RB-008 | Report — share with team | Functional | Shared report visible to others |
| RB-009 | Report — clone | Functional | Duplicated with all config |

#### 4.9.2 Dashboard Widgets

| # | Test Case | Type | Expected Result |
|---|-----------|------|-----------------|
| DW-001 | Add widget to dashboard | Functional | Widget appears in correct position |
| DW-002 | Widget types — stats, chart, list | Functional | All types work |
| DW-003 | Widget — resize | UI | Grid reflows correctly |
| DW-004 | Widget — remove | Functional | Removed from dashboard |
| DW-005 | Dashboard — persist layout per user | Functional | Layout saved and restored |
| DW-006 | Dashboard — team isolation | Security | Each team has own dashboard state |

---

### 4.10 Administration

#### 4.10.1 User Management

| # | Test Case | Type | Expected Result |
|---|-----------|------|-----------------|
| UM-001 | Create user | Functional | User created, activation email sent |
| UM-002 | Create user with specific role | Functional | Role assigned |
| UM-003 | Edit user | Functional | Profile updated |
| UM-004 | Disable user account | Functional | Cannot log in |
| UM-005 | Delete user | Functional | Soft-deleted, data reassigned |
| UM-006 | User list — filters (role, status, team) | Functional | All filters work |
| UM-007 | User list — search | Functional | Search by name/email |

#### 4.10.2 Roles & Permissions

| # | Test Case | Type | Expected Result |
|---|-----------|------|-----------------|
| RP-001 | Create role | Functional | Role created with name |
| RP-002 | Assign permissions to role | Functional | Permissions saved |
| RP-003 | Assign role to user | Functional | User inherits permissions |
| RP-004 | Remove role from user | Functional | Permissions revoked |
| RP-005 | Permission check — API middleware | Security | 403 for unauthorized |
| RP-006 | Permission check — Filament | Security | Resource hidden/disabled |
| RP-007 | Permission check — Livewire component | Security | Action blocked |

#### 4.10.3 Audit Logs

| # | Test Case | Type | Expected Result |
|---|-----------|------|-----------------|
| AL-001 | Contact created — audit entry | Functional | Logged with actor, timestamp |
| AL-002 | Deal stage changed — audit entry | Functional | Before/after values recorded |
| AL-003 | User login — audit entry | Functional | Login event logged |
| AL-004 | Audit log — view via Filament | UI | Logs paginated, searchable |
| AL-005 | Audit log — filter by event type | Functional | Filter works |
| AL-006 | Audit log — filter by user | Functional | Filter works |
| AL-007 | Audit log — date range | Functional | Filter works |

#### 4.10.4 Site Settings

| # | Test Case | Type | Expected Result |
|---|-----------|------|-----------------|
| SS-001 | Update site name | Functional | Name reflected across app |
| SS-002 | Update logo | Functional | Logo uploaded and displayed |
| SS-003 | Update timezone | Functional | Times displayed in new timezone |
| SS-004 | Update default language | Functional | UI language changes |
| SS-005 | Settings — persistence across sessions | Functional | Settings persist |
| SS-006 | Settings — team isolation | Security | Each team has own settings |

#### 4.10.5 Menu Management

| # | Test Case | Type | Expected Result |
|---|-----------|------|-----------------|
| MM-001 | Create menu item | Functional | Appears in navigation |
| MM-002 | Create menu item — external link | Functional | Opens in new tab |
| MM-003 | Create submenu | Functional | Dropdown renders |
| MM-004 | Reorder menu items | Functional | Order persists |
| MM-005 | Delete menu item | Functional | Removed from navigation |

#### 4.10.6 Team Management

| # | Test Case | Type | Expected Result |
|---|-----------|------|-----------------|
| TM-001 | Create team | Functional | Team created, creator = owner |
| TM-002 | Invite user to team | Functional | Invitation sent |
| TM-003 | Accept team invitation | Functional | User added to team |
| TM-004 | Decline team invitation | Functional | Invitation deleted |
| TM-005 | Remove member from team | Functional | Member removed, data retained |
| TM-006 | Change member role (owner/admin/member) | Functional | Permissions adjusted |
| TM-007 | Leave team | Functional | User removed |
| TM-008 | Delete team | Functional | Team removed, data cleanup |
| TM-009 | Team switching | Functional | Context switches correctly |
| TM-010 | Team subscriptions — plan limits | Functional | Limits enforced per plan |

#### 4.10.7 Module System

| # | Test Case | Type | Expected Result |
|---|-----------|------|-----------------|
| MO-001 | List modules via CLI | Functional | All modules shown with status |
| MO-002 | Enable module | Functional | Module enabled, services registered |
| MO-003 | Disable module | Functional | Module disabled, routes removed |
| MO-004 | Install module | Functional | Migrations run, assets published |
| MO-005 | Uninstall module | Functional | Migrations rolled back |
| MO-006 | Module — dependency check | Functional | Blocked if dependencies missing |
| MO-007 | Module — dependent check on disable | Functional | Warns if other modules depend on it |
| MO-008 | Module health check | Functional | All modules report healthy |
| MO-009 | Module — auto-discovery | Functional | New modules detected automatically |
| MO-010 | Module command — create scaffold | Functional | Directory structure created |

#### 4.10.8 Knowledge Base

| # | Test Case | Type | Expected Result |
|---|-----------|------|-----------------|
| KB-001 | Create article | Functional | Article saved, published |
| KB-002 | Create article with categories | Functional | Categorization works |
| KB-003 | Create article — draft | Functional | Not publicly visible |
| KB-004 | Edit article | Functional | Updated |
| KB-005 | Delete article | Functional | Removed |
| KB-006 | Knowledge base — public view | UI | Articles listed, searchable |
| KB-007 | Knowledge base — search | Functional | Full-text search works |

---

### 4.11 Infrastructure

#### 4.11.1 Authentication & Security

| # | Test Case | Type | Expected Result |
|---|-----------|------|-----------------|
| AU-001 | Login with valid credentials | Functional | Authenticated, redirect to dashboard |
| AU-002 | Login with invalid credentials | Functional | Error message, not authenticated |
| AU-003 | Login — remember me | Functional | Persistent session |
| AU-004 | Login — rate limiting | Security | Locked after N attempts |
| AU-005 | Logout | Functional | Session cleared |
| AU-006 | Password reset — request | Functional | Email sent |
| AU-007 | Password reset — valid token | Functional | Password changed |
| AU-008 | Password reset — expired token | Functional | Error, re-request |
| AU-009 | Registration | Functional | User created, team created |
| AU-010 | Two-factor authentication | Functional | 2FA challenge after login |
| AU-011 | Two-factor — recovery codes | Functional | Recovery works |
| AU-012 | API token — create via Sanctum | Functional | Token generated |
| AU-013 | API token — authenticate request | Functional | Request succeeds |
| AU-014 | API token — expired/revoked | Functional | 401 returned |
| AU-015 | Security headers — XSS protection | Security | Headers present in response |
| AU-016 | Security headers — CSP | Security | Content-Security-Policy header |
| AU-017 | Security headers — HSTS | Security | Strict-Transport-Security header |
| AU-018 | CORS — allowed origins | Security | Cross-origin requests from allowed domains |
| AU-019 | CORS — disallowed origins | Security | Blocked |

#### 4.11.2 Multi-Tenancy

| # | Test Case | Type | Expected Result |
|---|-----------|------|-----------------|
| MT-001 | Team A cannot see Team B contacts | Security | Data isolation enforced |
| MT-002 | Team A cannot access Team B API data | Security | 403 or empty results |
| MT-003 | Central table accessible by all | Functional | Global data (plans, etc.) accessible |
| MT-004 | Tenant domain routing | Integration | Correct tenant resolved from domain |
| MT-005 | Tenant database isolation (if separate DB) | Integration | Separate DB per tenant |

#### 4.11.3 Health Checks

| # | Test Case | Type | Expected Result |
|---|-----------|------|-----------------|
| HC-001 | GET /health/startup | Functional | 200, app initialized |
| HC-002 | GET /health/live | Functional | 200, app alive |
| HC-003 | GET /health/ready | Functional | 200, DB connected, queue responsive |

#### 4.11.4 API Endpoints

| # | Test Case | Type | Expected Result |
|---|-----------|------|-----------------|
| API-001 | GET /api/user — returns authenticated user | Functional | User data returned |
| API-002 | All v1 endpoints — authentication required | Security | 401 without token |
| API-003 | All v1 endpoints — rate limited | Security | 429 after limit |
| API-004 | Contact API — full CRUD cycle | Functional | Create → Read → Update → Delete |
| API-005 | Deal API — full CRUD cycle | Functional | Create → Read → Update → Delete |
| API-006 | Task API — full CRUD cycle | Functional | Create → Read → Update → Delete |
| API-007 | Webhook API — create, events, secret | Functional | All endpoints work |
| API-008 | Workflow API — CRUD | Functional | All endpoints work |
| API-009 | Bulk endpoints — delete | Functional | Bulk delete works |
| API-010 | Bulk endpoints — update | Functional | Bulk update works |
| API-011 | Bulk endpoints — assign | Functional | Bulk assign works |
| API-012 | API — pagination metadata | Functional | Links, total, per_page, current_page |
| API-013 | API — JSON structure consistency | Functional | Consistent envelope format |
| API-014 | API — validation errors format | Functional | Consistent error structure |

#### 4.11.5 Webhooks

| # | Test Case | Type | Expected Result |
|---|-----------|------|-----------------|
| WH-001 | Create webhook endpoint | Functional | Webhook registered |
| WH-002 | Webhook — event triggers delivery | Integration | POST sent to URL |
| WH-003 | Webhook — retry on failure | Integration | Retried up to N times |
| WH-004 | Webhook — signature verification | Security | Valid signature in header |
| WH-005 | Webhook — secret regeneration | Functional | New secret Key, old invalidated |
| WH-006 | Webhook — event types | Functional | Only subscribed events delivered |

#### 4.11.6 Queues & Jobs

| # | Test Case | Type | Expected Result |
|---|-----------|------|-----------------|
| QJ-001 | Job dispatched — processed successfully | Integration | Job processed, side effects applied |
| QJ-002 | Job dispatched — fails | Integration | Job retried, logged to failed_jobs |
| QJ-003 | Job — max attempts exceeded | Integration | Moved to failed_jobs permanently |
| QJ-004 | Horizon dashboard — viewable | UI | Queue metrics visible |
| QJ-005 | Horizon — pause/resume queue | Functional | Queue pauses and resumes |

#### 4.11.7 WebSockets (Reverb)

| # | Test Case | Type | Expected Result |
|---|-----------|------|-----------------|
| WS-001 | Reverb server starts | Integration | Server listens on configured port |
| WS-002 | Channel — private auth | Integration | Only authorized users subscribe |
| WS-003 | Event broadcast — received | Integration | Connected clients receive event |
| WS-004 | Reconnect on disconnect | Integration | Client reconnects automatically |

---

## 5. Bug Reporting Template

```markdown
## Bug Report

**Module:** [e.g., Contacts / Deals / Workflow Automation]
**Severity:** Critical / High / Medium / Low
**Environment:** Local / Staging / Production
**Branch/Version:** [e.g., main / v2.1.0]
**Test Case Reference:** [e.g., C-012]

### Description
[Clear, concise description of the bug]

### Steps to Reproduce
1. Go to '...'
2. Click on '...'
3. Scroll down to '...'
4. See error

### Expected Behavior
[What should happen]

### Actual Behavior
[What actually happens]

### Screenshots / Logs
[Attach or paste relevant content]

### Technical Details
- **PHP Version:** 8.5.x
- **Database:** MySQL 8.0 / SQLite
- **Browser:** Chrome 12x / Firefox 13x (if UI bug)

### Related Tests
- [ ] Existing test covers this scenario (test name: ...)
- [ ] New test needed
```

### Severity Definitions

| Severity | Definition | Response Time | Fix Time |
|----------|------------|---------------|----------|
| **Critical** | Data loss, security breach, core feature completely broken | 1 hour | 24 hours |
| **High** | Major feature broken, no workaround | 4 hours | 3 days |
| **Medium** | Feature works but has incorrect behavior, has workaround | 24 hours | 1 week |
| **Low** | Cosmetic issue, minor UI glitch, edge case | 1 week | Next release |

---

## 6. Regression Testing

### Critical Regression Suite

Run these before every release:

```bash
# 1. Full test suite
php artisan test

# 2. Static analysis
./vendor/bin/phpstan analyse

# 3. Code style
./vendor/bin/pint --test

# 4. Manual smoke test
```

### Smoke Test Checklist

| Area | Critical Paths |
|------|---------------|
| **Auth** | Login, logout, password reset, 2FA |
| **Contacts** | Create, edit, delete, list, search |
| **Deals** | Create, move stage, close, pipeline view |
| **Tasks** | Create, complete, filter |
| **API** | Token auth, contact create/read/update/delete |
| **Admin** | User create, role assignment |
| **Multi-tenancy** | Switch team, verify data isolation |

### Automated Regression Triggers

| Trigger | Action |
|---------|--------|
| Push to `main` | GitHub Actions: `tests.yml`, `install.yml`, `main.yml`, `security.yml` |
| PR to `main` | GitHub Actions: `tests.yml`, `security.yml` |
| Weekly (Sunday) | GitHub Actions: `security.yml` |
| Daily (Dependabot) | Automated PRs for dependency updates |

---

## 7. Performance Testing

### Baseline Metrics

| Endpoint | Target p95 | Target p99 |
|----------|-----------|-----------|
| Contact list (paginated) | < 200ms | < 500ms |
| Contact create | < 500ms | < 1000ms |
| Deal pipeline load | < 300ms | < 800ms |
| API contact list | < 150ms | < 400ms |
| Dashboard load | < 500ms | < 1200ms |
| Report generation | < 2000ms | < 5000ms |
| Login | < 500ms | < 1000ms |

### Load Testing Scenarios

| Scenario | Users | Actions | Duration |
|----------|-------|---------|----------|
| CRM Power Users | 50 concurrent | Mix of CRUD, search, pipeline drag | 15 min |
| API Integration | 100 concurrent | API CRUD operations | 10 min |
| Email Campaign | 10 concurrent | Send 10K emails (queued) | 30 min |
| Dashboard Burst | 200 concurrent | Dashboard load | 5 min |

### Performance Test Checklist

- [ ] N+1 queries eliminated (use Laravel Debugbar or Clockwork)
- [ ] Eager loading applied on all listing endpoints
- [ ] Database indexes exist on all foreign keys and frequently queried columns
- [ ] API responses paginated (default 15 per page)
- [ ] Queueable jobs for all email/sms/notification sends
- [ ] Caching applied to settings, menus, permissions
- [ ] Asset compilation minified (`npm run build`)
- [ ] Octane cache warm

---

## 8. Security Testing

### OWASP Top 10 Checklist

| Category | Test | Expected |
|----------|------|----------|
| **A01: Broken Access Control** | Team isolation — Team A accesses Team B data | 403/404, no data leak |
| **A01: Broken Access Control** | Unauthenticated API access | 401 |
| **A01: Broken Access Control** | Role escalation — member does admin action | 403 |
| **A02: Cryptographic Failures** | OAuth tokens stored encrypted | Not plaintext in DB |
| **A02: Cryptographic Failures** | Passwords hashed (bcrypt) | Hash not reversible |
| **A03: Injection** | SQL injection attempt on search | Parameterized query, no injection |
| **A03: Injection** | XSS attempt in contact name | Escaped on output |
| **A04: Insecure Design** | Rate limiting on login | Blocked after N attempts |
| **A04: Insecure Design** | Rate limiting on API | 429 after threshold |
| **A05: Security Misconfiguration** | Debug mode off in production | APP_DEBUG=false |
| **A05: Security Misconfiguration** | CORS configured restrictively | Only allowed origins |
| **A06: Vulnerable Components** | `composer audit` | No known vulnerabilities |
| **A06: Vulnerable Components** | `npm audit` | No known vulnerabilities |
| **A07: Auth Failures** | Weak password policy | Enforced min length/complexity |
| **A07: Auth Failures** | Session timeout | Inactive session expires |
| **A08: Data Integrity** | Webhook signature verification | Invalid signature rejected |
| **A09: Monitoring** | Failed login logged | Audit log entry created |
| **A10: SSRF** | Webhook URL validation | Internal IPs blocked |

### Security Tools

```bash
# Run locally
composer audit                                   # Check for vulnerable deps
./vendor/bin/phpinsights                         # Code quality (security checks)
./vendor/bin/phpstan analyse                     # Static analysis
```

---

## 9. Test Automation Guidelines

### Writing Tests

```php
<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ContactApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()
            ->withPersonalTeam()
            ->create();
    }

    #[Test]
    public function authenticated_user_can_create_contact(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/contacts', [
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('contacts', [
            'email' => 'john@example.com',
            'team_id' => $this->user->currentTeam->id,
        ]);
    }

    #[Test]
    public function unauthenticated_request_returns_401(): void
    {
        $response = $this->postJson('/api/v1/contacts', [
            'name' => 'John Doe',
        ]);

        $response->assertStatus(401);
    }
}
```

### Test Patterns

| Pattern | When to Use |
|---------|------------|
| `RefreshDatabase` | Feature tests that modify the database |
| `DatabaseTransactions` | Tests where rollback is preferred over full refresh |
| `Mockery::mock()` | Service tests that depend on external APIs |
| `Http::fake()` | HTTP client calls (Guzzle) |
| `Queue::fake()` | Jobs that should not actually dispatch |
| `Notification::fake()` | Notifications that should not actually send |
| `Mail::fake()` | Emails that should not actually deliver |
| `Event::fake()` | Events that should not trigger listeners |
| `Storage::fake()` | File uploads that should not hit disk |

### Mocking External Services

```php
use Tests\Stubs\TwilioStubs;
use Twilio\Rest\Client;

// In test setup
$this->mock(TwilioService::class, function ($mock) {
    $mock->shouldReceive('sendSms')
        ->once()
        ->with('+1234567890', 'Hello')
        ->andReturn(true);
});

// Or use stubs
$client = new TwilioStubs\Client('sid', 'token');
$this->instance(Client::class, $client);
```

### Data Factories

```php
// Use existing factories
Contact::factory()->create();
Contact::factory()->count(5)->create();
Contact::factory()->withCompany()->create();
Contact::factory()->withCustomFields()->create();

// Custom states
Contact::factory()->unqualified()->create();
Deal::factory()->won()->create();
Task::factory()->overdue()->create();
```

---

## 10. QA Sign-off Checklist

### Per Module Sign-off

```markdown
## Module Sign-off: [Module Name]

**QA Engineer:** _______________
**Date:** _______________
**Build/Version:** _______________

### Test Execution Summary
- [ ] All automated tests pass (____ of ____)
- [ ] Manual test cases executed (____ of ____)
- [ ] New test coverage added (____ new tests)

### Bug Status
- [ ] All Critical bugs fixed and verified
- [ ] All High bugs fixed and verified
- [ ] Medium bugs documented (____ remaining)
- [ ] Low bugs documented (____ remaining)

### Regression Checks
- [ ] Smoke test on related modules passed
- [ ] Full test suite passes
- [ ] PHPStan reports no new errors
- [ ] Pint reports no style violations

### Performance
- [ ] API response times within baseline
- [ ] Page load times within baseline
- [ ] No N+1 queries detected

### Sign-off
**QA Approved:** Yes / No
**Comments:** ________________________________________
```

### Release Sign-off

- [ ] All modules sign-off complete
- [ ] Regression test suite passed
- [ ] Security scan passed (OWASP Top 10)
- [ ] Performance benchmarks met
- [ ] PHPStan — no errors above baseline
- [ ] Pint — clean
- [ ] Rector dry run — no unexpected changes
- [ ] Code coverage ≥ 80%
- [ ] Documentation updated
- [ ] CHANGELOG updated
- [ ] Release tagged in git

---

*This document is maintained by the QA team. Update it as new modules, features, and test cases are added.*

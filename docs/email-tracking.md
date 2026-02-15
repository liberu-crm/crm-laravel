# Email Tracking Guide

## Overview

The email tracking system provides comprehensive insights into email engagement, including open rates, click-through rates, and recipient behavior.

## Features

- **Open Tracking** - Know when recipients open your emails
- **Click Tracking** - Track which links recipients click
- **Engagement Scoring** - Automatic scoring based on email interactions
- **Bounce Detection** - Identify and handle bounced emails
- **Unsubscribe Tracking** - Monitor opt-outs
- **Multi-Device Tracking** - Track across desktop, mobile, and tablets

## How It Works

### 1. Tracking Pixel

When an email is sent, a 1x1 transparent pixel is embedded at the bottom of the HTML:

```html
<img src="https://your-domain.com/email/track/pixel/{tracking_id}" 
     width="1" height="1" alt="" style="display:none" />
```

When the recipient opens the email, the pixel loads and records:
- Timestamp of open
- IP address
- User agent (device/browser)

### 2. Link Tracking

All links in the email are replaced with tracked versions:

**Original Link:**
```html
<a href="https://example.com/page">Click Here</a>
```

**Tracked Link:**
```html
<a href="https://your-domain.com/email/track/link/{tracking_id}?url=base64_encoded_url">Click Here</a>
```

When clicked, the system:
1. Records the click
2. Redirects to the original URL

### 3. Engagement Scoring

Contacts receive engagement points based on interactions:
- Email open: +1 point
- Link click: +2 points
- Multiple opens/clicks increase score

## Usage

### Setting Up Email Tracking

```php
use App\Services\EmailTrackingService;
use App\Models\Email;
use App\Models\Contact;

$trackingService = app(EmailTrackingService::class);

// Create tracking record
$tracking = $trackingService->createTracking($email, $contact);

// Process HTML to add tracking
$trackedHtml = $trackingService->processEmailHtml($originalHtml, $tracking);

// Send the tracked email
Mail::send($trackedHtml);
```

### Using Email Templates

```php
use App\Models\EmailTemplate;

$template = EmailTemplate::find(1);

// Render with variables
$rendered = $template->render([
    'first_name' => $contact->first_name,
    'company' => $contact->company->name,
    'link' => 'https://example.com/offer',
]);

// Create tracking and process
$tracking = $trackingService->createTracking($email, $contact);
$trackedHtml = $trackingService->processEmailHtml($rendered['html_body'], $tracking);
```

### Viewing Engagement Stats

```php
use App\Services\EmailTrackingService;

$trackingService = app(EmailTrackingService::class);

// Get stats for a contact
$stats = $trackingService->getContactEngagementStats($contact);

/*
Returns:
[
    'total_sent' => 50,
    'total_opened' => 35,
    'total_clicked' => 12,
    'total_bounced' => 1,
    'open_rate' => 70.00,
    'click_rate' => 24.00,
    'engagement_score' => 59,
]
*/
```

## Email Templates

### Creating Templates

1. Navigate to Marketing > Email Templates
2. Click "Create Template"
3. Fill in:
   - Name
   - Subject line
   - Category
   - Plain text body (fallback)
   - HTML body

### Using Variables

Templates support dynamic variables using `{{variable_name}}` syntax:

```html
<p>Hi {{first_name}},</p>

<p>Thank you for your interest in {{product_name}}.</p>

<p>Your account at {{company_name}} is ready!</p>

<a href="{{activation_link}}">Activate Your Account</a>
```

### Available Categories

- **Welcome** - New customer onboarding
- **Follow Up** - Post-interaction follow-ups
- **Newsletter** - Regular updates
- **Promotional** - Special offers and promotions
- **Transactional** - Order confirmations, receipts
- **Notification** - System notifications

## Analytics & Reporting

### Contact Engagement Dashboard

View engagement metrics for each contact:
- Total emails sent
- Open rate %
- Click-through rate %
- Engagement score
- Last interaction date

### Campaign Performance

Track performance of email campaigns:
- Total recipients
- Delivery rate
- Open rate
- Click rate
- Bounce rate
- Unsubscribe rate

### Best Time to Send

Analyze when contacts are most likely to open emails:
- Day of week breakdown
- Hour of day breakdown
- Timezone considerations

## Privacy & Compliance

### GDPR Compliance

- **Consent** - Only track users who have given consent
- **Right to Erasure** - Delete tracking data on request
- **Data Minimization** - Only collect necessary data
- **Transparency** - Inform users about tracking

### Unsubscribe Handling

```php
// Record unsubscribe
$tracking->recordUnsubscribe();

// Update contact preference
$contact->update([
    'email_opt_in' => false,
    'unsubscribed_at' => now(),
]);
```

## Troubleshooting

### Opens Not Being Tracked

**Possible Causes:**
1. Email client blocks images (Gmail, Outlook)
2. Privacy settings enabled
3. Plain text email sent instead of HTML

**Solutions:**
- Include call-to-action links as primary tracking method
- Use link clicks as engagement indicator
- Send text + HTML multipart emails

### Inflated Open Counts

**Possible Causes:**
1. Email forwarding
2. Email prefetching by email clients
3. Bots/crawlers

**Solutions:**
- Track unique opens separately from total opens
- Implement bot detection based on user agents
- Use first open time as primary metric

### Link Tracking Issues

**Problem:** Links not redirecting properly

**Solution:**
- Ensure base64 encoding of URLs
- Check route definitions
- Verify URL decoding in controller

## Best Practices

1. **Test Before Sending** - Send test emails to verify tracking
2. **Monitor Bounce Rates** - Clean lists regularly
3. **Respect Unsubscribes** - Honor opt-out requests immediately
4. **Segment by Engagement** - Target engaged subscribers differently
5. **A/B Testing** - Test subject lines and content
6. **Mobile Optimization** - Ensure tracking works on mobile devices
7. **Fallback Content** - Always include plain text version

## API Reference

### EmailTrackingService Methods

```php
// Create tracking record
createTracking(Email $email, Contact $contact): EmailTracking

// Get tracking pixel URL
getTrackingPixelUrl(EmailTracking $tracking): string

// Get tracked link URL
getTrackedLinkUrl(EmailTracking $tracking, string $originalUrl): string

// Inject tracking pixel
injectTrackingPixel(string $html, EmailTracking $tracking): string

// Replace links with tracked versions
replaceLinksWithTracked(string $html, EmailTracking $tracking): string

// Process email HTML (adds pixel + tracked links)
processEmailHtml(string $html, EmailTracking $tracking): string

// Record email open
recordOpen(string $trackingId, ?string $userAgent, ?string $ipAddress): bool

// Record link click
recordClick(string $trackingId, string $url, ?string $userAgent, ?string $ipAddress): bool

// Get engagement stats for contact
getContactEngagementStats(Contact $contact): array
```

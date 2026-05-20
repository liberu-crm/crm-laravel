# Unified Helpdesk Integration Guide

This document provides information on how to configure and use the unified helpdesk system with support for multiple communication channels.

## Supported Channels

The unified helpdesk now supports the following communication channels:

1. **WhatsApp Business API**
2. **Gmail** (via OAuth)
3. **Microsoft 365 / Outlook** (via Microsoft Graph API)
4. **IMAP Email Accounts** (Generic email accounts)
5. **POP3 Email Accounts** (Generic email accounts)
6. **Facebook Messenger**

## Configuration

### WhatsApp Business API

Add the following environment variables to your `.env` file:

```env
WHATSAPP_API_URL=https://graph.facebook.com/v17.0/YOUR_PHONE_NUMBER_ID
WHATSAPP_ACCESS_TOKEN=your_whatsapp_access_token
```

Or configure via OAuth Configuration model:
- Service Name: `whatsapp`
- Client Secret: Your WhatsApp access token
- Additional Settings:
  - `api_url`: WhatsApp API URL
  - `access_token`: WhatsApp access token

### Gmail

Configure via OAuth Configuration model:
- Service Name: `gmail`
- Additional Settings:
  - `access_token`: Gmail OAuth access token

Or use environment variables:
```env
GMAIL_CLIENT_ID=your_gmail_client_id
GMAIL_CLIENT_SECRET=your_gmail_client_secret
GMAIL_REDIRECT_URI=http://localhost:8000/oauth/google/callback
```

### Microsoft 365 / Outlook

Configure via OAuth Configuration model:
- Service Name: `outlook` or `microsoft365`
- Additional Settings:
  - `access_token`: Microsoft Graph API access token

Or use environment variables:
```env
OUTLOOK_CLIENT_ID=your_outlook_client_id
OUTLOOK_CLIENT_SECRET=your_outlook_client_secret
OUTLOOK_REDIRECT_URI=http://localhost:8000/oauth/outlook/callback
```

### IMAP Email Accounts

Configure via OAuth Configuration model:
- Service Name: `imap`
- Client ID: Your email address
- Client Secret: Your email password
- Additional Settings:
  - `host`: IMAP server hostname (e.g., `imap.gmail.com`)
  - `port`: IMAP port (default: 993)
  - `ssl`: Use SSL (default: true)
  - `username`: Email address
  - `password`: Email password
  - `smtp_host`: SMTP server for sending (e.g., `smtp.gmail.com`)
  - `smtp_port`: SMTP port (default: 587)
  - `from_email`: Email address to use as sender

Or use environment variables:
```env
IMAP_HOST=imap.example.com
IMAP_PORT=993
IMAP_USERNAME=your_email@example.com
IMAP_PASSWORD=your_password
IMAP_SSL=true
SMTP_HOST=smtp.example.com
SMTP_PORT=587
```

### POP3 Email Accounts

Configure via OAuth Configuration model:
- Service Name: `pop3`
- Client ID: Your email address
- Client Secret: Your email password
- Additional Settings:
  - `host`: POP3 server hostname
  - `port`: POP3 port (default: 110)
  - `ssl`: Use SSL (default: false)
  - `username`: Email address
  - `password`: Email password
  - `smtp_host`: SMTP server for sending
  - `smtp_port`: SMTP port (default: 587)
  - `from_email`: Email address to use as sender

Or use environment variables:
```env
POP3_HOST=pop3.example.com
POP3_PORT=110
POP3_USERNAME=your_email@example.com
POP3_PASSWORD=your_password
POP3_SSL=false
SMTP_HOST=smtp.example.com
SMTP_PORT=587
```

## Database Setup

Run the migration to add required fields to the `oauth_configurations` table:

```bash
php artisan migrate
```

This adds:
- `is_active`: Boolean flag to enable/disable a configuration
- `account_name`: Human-readable name for the account

## Using the Unified Helpdesk Service

### Fetching Messages from All Channels

```php
use App\Services\UnifiedHelpDeskService;

$helpdeskService = app(UnifiedHelpDeskService::class);

// Get all messages from all active accounts
$messages = $helpdeskService->getAllMessages();

// Get messages from a specific account
$messages = $helpdeskService->getAllMessages($accountId);

// Get messages without using cache
$messages = $helpdeskService->getAllMessages(null, false);
```

### Sending Replies

```php
use App\Services\UnifiedHelpDeskService;

$helpdeskService = app(UnifiedHelpDeskService::class);

// Send reply
$result = $helpdeskService->sendReply(
    $messageId,      // Original message ID
    $content,        // Reply content
    $channel,        // Channel: 'gmail', 'outlook', 'whatsapp', 'imap', 'pop3', 'facebook'
    $accountId       // OAuth configuration ID
);
```

## Scheduled Jobs

You can schedule jobs to automatically fetch tickets from email accounts:

### Gmail
```php
use App\Jobs\FetchGmailTickets;

// Fetch from all active Gmail accounts
FetchGmailTickets::dispatch();

// Fetch from specific account
FetchGmailTickets::dispatch($configId);
```

### Outlook
```php
use App\Jobs\FetchOutlookTickets;

FetchOutlookTickets::dispatch();
// or
FetchOutlookTickets::dispatch($configId);
```

### IMAP
```php
use App\Jobs\FetchImapTickets;

FetchImapTickets::dispatch();
// or
FetchImapTickets::dispatch($configId);
```

### POP3
```php
use App\Jobs\FetchPop3Tickets;

FetchPop3Tickets::dispatch();
// or
FetchPop3Tickets::dispatch($configId);
```

### Schedule in Laravel

Add to `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Fetch emails every 5 minutes
    $schedule->job(new FetchGmailTickets())->everyFiveMinutes();
    $schedule->job(new FetchOutlookTickets())->everyFiveMinutes();
    $schedule->job(new FetchImapTickets())->everyFiveMinutes();
    $schedule->job(new FetchPop3Tickets())->everyFiveMinutes();
}
```

## Message Format

All messages returned by the unified helpdesk service follow a standardized format:

```php
[
    'id' => 'message_id',
    'channel' => 'gmail|outlook|whatsapp|imap|pop3|facebook',
    'account_id' => 1,
    'account_name' => 'Support Email',
    'from' => 'sender@example.com',
    'content' => 'Message content',
    'timestamp' => Carbon instance,
    'thread_id' => 'thread_id',
    'attachments' => [],
    'status' => 'received',
    'priority' => 'normal|high|low',
    'metadata' => [
        'service_specific_data' => [...],
        'config_id' => 1,
        'platform_specific' => [
            // For email channels:
            'subject' => 'Email subject',
            'cc' => ['cc@example.com'],
            'bcc' => ['bcc@example.com'],
            
            // For WhatsApp:
            'message_type' => 'text',
            'phone_number' => '+1234567890',
            
            // For Facebook:
            'page_id' => 'page_id',
            'sender_id' => 'sender_id',
        ]
    ]
]
```

## Events

The unified helpdesk service dispatches the following events:

- `NewMessageReceived`: Fired when a new message is received from any channel
- `MessageReplySent`: Fired when a reply is sent to any channel

## Security Considerations

1. **OAuth Tokens**: Store OAuth access tokens securely in the `oauth_configurations` table with encrypted `additional_settings`.
2. **IMAP/POP3 Passwords**: Use environment variables or encrypted database storage for passwords.
3. **API Keys**: Never commit API keys or tokens to version control.
4. **SSL/TLS**: Always use SSL/TLS for IMAP connections and SMTP when available.

## Troubleshooting

### IMAP Connection Issues

If you encounter IMAP connection errors:
1. Ensure the IMAP extension is enabled in PHP (`php -m | grep imap`)
2. Check firewall rules allow connections to the IMAP server
3. Verify credentials and server settings
4. Enable "Less secure app access" for Gmail or use app-specific passwords

### POP3 Connection Issues

If you encounter POP3 connection errors:
1. Verify the POP3 server address and port
2. Check if SSL is required for your POP3 server
3. Ensure the server supports POP3 protocol

### Microsoft Graph API Issues

If you encounter Microsoft Graph API errors:
1. Verify the access token is valid and not expired
2. Ensure the app has the required permissions (Mail.Read, Mail.Send)
3. Check that the redirect URI matches the one configured in Azure

## Testing

Run the test suite to verify the helpdesk integration:

```bash
php artisan test --filter=UnifiedHelpDeskServiceTest
php artisan test --filter=OutlookServiceTest
php artisan test --filter=ImapServiceTest
php artisan test --filter=Pop3ServiceTest
```

## Support

For issues or questions, please refer to the main project documentation or open an issue on GitHub.

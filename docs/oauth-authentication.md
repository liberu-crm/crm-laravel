# OAuth Authentication Guide

## Overview

The Liberu CRM now supports OAuth 2.0 authentication for all major integrations, eliminating the need for hardcoded API keys and providing a more secure authentication flow.

## Supported Providers

- **MailChimp** - Email marketing platform
- **Stripe** - Payment processing
- **Google/Gmail** - Email and calendar services
- **Microsoft/Outlook** - Email and calendar services
- **Facebook** - Social media and advertising
- **LinkedIn** - Social media and advertising
- **Twitter** - Social media
- **Zoom** - Video conferencing

## Configuration

### 1. Environment Variables

Add OAuth credentials to your `.env` file:

```env
# MailChimp OAuth
MAILCHIMP_CLIENT_ID=your_client_id
MAILCHIMP_CLIENT_SECRET=your_client_secret
MAILCHIMP_REDIRECT_URI=https://your-domain.com/oauth/mailchimp/callback

# Stripe Connect
STRIPE_CLIENT_ID=your_client_id
STRIPE_PUBLISHABLE_KEY=your_publishable_key
STRIPE_REDIRECT_URI=https://your-domain.com/oauth/stripe/callback

# Google Services
GMAIL_CLIENT_ID=your_client_id
GMAIL_CLIENT_SECRET=your_client_secret
GMAIL_REDIRECT_URI=https://your-domain.com/oauth/google/callback
```

### 2. Database Configuration

Create OAuth configuration records using the admin panel or directly in the database:

```php
use App\Models\OAuthConfiguration;

OAuthConfiguration::create([
    'service_name' => 'mailchimp',
    'client_id' => env('MAILCHIMP_CLIENT_ID'),
    'client_secret' => env('MAILCHIMP_CLIENT_SECRET'),
    'additional_settings' => [
        'redirect_uri' => env('MAILCHIMP_REDIRECT_URI'),
    ],
]);
```

### 3. Provider Setup

#### MailChimp

1. Go to https://admin.mailchimp.com/account/oauth2/
2. Create a new OAuth app
3. Set redirect URI to: `https://your-domain.com/oauth/mailchimp/callback`
4. Copy Client ID and Client Secret to your `.env`

#### Stripe Connect

1. Go to https://dashboard.stripe.com/settings/applications
2. Create a connected application
3. Set redirect URI to: `https://your-domain.com/oauth/stripe/callback`
4. Copy Client ID to your `.env`

#### Google/Gmail

1. Go to https://console.cloud.google.com/
2. Create a new project or select existing
3. Enable Gmail API and Google Calendar API
4. Create OAuth 2.0 credentials
5. Add authorized redirect URI: `https://your-domain.com/oauth/google/callback`
6. Copy Client ID and Client Secret to your `.env`

## Usage

### Connecting an Account

1. Navigate to Settings > Integrations
2. Select the provider you want to connect
3. Click "Connect Account"
4. Authorize the application in the provider's OAuth flow
5. You'll be redirected back to the CRM with the account connected

### Using Connected Accounts

The CRM automatically uses connected accounts for:
- Sending emails through Gmail/Outlook
- Syncing calendar events
- Running advertising campaigns
- Processing payments via Stripe

### Token Refresh

OAuth tokens are automatically refreshed when needed. The system:
- Checks token expiration before each API call
- Automatically refreshes tokens that expire within 5 minutes
- Stores new refresh tokens securely in the database

### Disconnecting an Account

1. Navigate to Settings > Connected Accounts
2. Find the account you want to disconnect
3. Click "Disconnect"
4. Confirm the action

## API Usage

### Programmatic OAuth Flow

```php
use App\Services\OAuth\OAuthManager;

$oauthManager = app(OAuthManager::class);

// Get authorization URL
$authUrl = $oauthManager->getAuthorizationUrl('mailchimp');

// Exchange code for token (in callback)
$tokenData = $oauthManager->exchangeCodeForToken('mailchimp', $code);

// Save connected account
$account = $oauthManager->saveConnectedAccount('mailchimp', $tokenData);

// Check if token needs refresh
if ($oauthManager->needsRefresh($account)) {
    $oauthManager->refreshToken($account);
}
```

## Security Best Practices

1. **Always use HTTPS** - OAuth requires secure connections
2. **Store tokens securely** - Tokens are encrypted in the database
3. **Implement state parameter** - Prevents CSRF attacks
4. **Limit scopes** - Only request necessary permissions
5. **Rotate secrets regularly** - Update OAuth client secrets periodically

## Troubleshooting

### "OAuth provider not configured" Error

**Solution**: Ensure OAuth configuration exists in the database for the provider.

```php
php artisan tinker
>>> App\Models\OAuthConfiguration::where('service_name', 'mailchimp')->first();
```

### Token Refresh Failures

**Solution**: Check that:
1. The provider supports refresh tokens
2. The refresh token is stored in the database
3. OAuth credentials are valid

### Redirect URI Mismatch

**Solution**: Ensure redirect URIs match exactly between:
1. Your `.env` file
2. Provider's OAuth app settings
3. The route defined in your application

## Migration from API Keys

### MailChimp

**Before** (API Key):
```env
MAILCHIMP_API_KEY=your_api_key
MAILCHIMP_SERVER_PREFIX=us1
```

**After** (OAuth):
```env
MAILCHIMP_CLIENT_ID=your_client_id
MAILCHIMP_CLIENT_SECRET=your_client_secret
```

### Stripe

**Before** (API Key):
```env
STRIPE_SECRET=sk_test_xxx
```

**After** (OAuth):
```env
STRIPE_CLIENT_ID=ca_xxx
STRIPE_PUBLISHABLE_KEY=pk_test_xxx
```

The system falls back to API keys if OAuth is not configured, allowing gradual migration.

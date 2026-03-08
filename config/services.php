<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'gmail' => [
        'application_name' => env('GMAIL_APPLICATION_NAME'),
        'credentials_path' => env('GMAIL_CREDENTIALS_PATH'),
    ],

    'twilio' => [
        'account_sid' => env('TWILIO_ACCOUNT_SID', env('TWILIO_SID')),
        'auth_token' => env('TWILIO_AUTH_TOKEN', env('TWILIO_CLIENT_SECRET')),
        'phone_number' => env('TWILIO_PHONE_NUMBER'),
        'client_id' => env('TWILIO_CLIENT_ID'),
        'client_secret' => env('TWILIO_CLIENT_SECRET'),
        'redirect_uri' => env('TWILIO_REDIRECT_URI'),
        'sid' => env('TWILIO_SID'),
        'app_sid' => env('TWILIO_APP_SID'),
        'twiml_app_sid' => env('TWILIO_TWIML_APP_SID'),
        'webhook_url' => env('TWILIO_WEBHOOK_URL'),
    ],

    // Facebook: unified config for both Socialite (OAuth login/connect) and Graph API (posting)
    'facebook' => [
        'client_id' => env('FACEBOOK_CLIENT_ID', env('FACEBOOK_APP_ID')),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET', env('FACEBOOK_APP_SECRET')),
        'redirect' => env('APP_URL') . '/oauth/facebook/callback',
        'app_id' => env('FACEBOOK_APP_ID', env('FACEBOOK_CLIENT_ID')),
        'app_secret' => env('FACEBOOK_APP_SECRET', env('FACEBOOK_CLIENT_SECRET')),
        'page_id' => env('FACEBOOK_PAGE_ID'),
        'page_access_token' => env('FACEBOOK_PAGE_ACCESS_TOKEN'),
        'graph_version' => env('FACEBOOK_GRAPH_VERSION', 'v18.0'),
    ],

    // Google: unified config for Socialite, Gmail, Google Ads, and YouTube
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('APP_URL') . '/oauth/google/callback',
        'credentials_path' => env('GOOGLE_CREDENTIALS_PATH'),
        'developer_token' => env('GOOGLE_ADS_DEVELOPER_TOKEN'),
    ],

    // Twitter/X OAuth 2.0 for posting tweets, images, and videos
    'twitter-oauth-2' => [
        'client_id' => env('TWITTER_CLIENT_ID'),
        'client_secret' => env('TWITTER_CLIENT_SECRET'),
        'redirect' => env('APP_URL') . '/oauth/twitter-oauth-2/callback',
    ],

    // LinkedIn for posting text, images, and videos
    'linkedin-openid' => [
        'client_id' => env('LINKEDIN_CLIENT_ID'),
        'client_secret' => env('LINKEDIN_CLIENT_SECRET'),
        'redirect' => env('APP_URL') . '/oauth/linkedin-openid/callback',
    ],

    'mailchimp' => [
        'api_key' => env('MAILCHIMP_API_KEY'),
        'server_prefix' => env('MAILCHIMP_SERVER_PREFIX'),
    ],

    'whatsapp' => [
        'api_url' => env('WHATSAPP_API_URL'),
        'access_token' => env('WHATSAPP_ACCESS_TOKEN'),
    ],

    'quickbooks' => [
        'client_id' => env('QUICKBOOKS_CLIENT_ID'),
        'client_secret' => env('QUICKBOOKS_CLIENT_SECRET'),
        'redirect_uri' => env('QUICKBOOKS_REDIRECT_URI'),
    ],

    'xero' => [
        'client_id' => env('XERO_CLIENT_ID'),
        'client_secret' => env('XERO_CLIENT_SECRET'),
        'redirect_uri' => env('XERO_REDIRECT_URI'),
    ],

    'outlook' => [
        'client_id' => env('OUTLOOK_CLIENT_ID'),
        'client_secret' => env('OUTLOOK_CLIENT_SECRET'),
        'redirect_uri' => env('OUTLOOK_REDIRECT_URI'),
    ],

    'microsoft' => [
        'client_id' => env('MICROSOFT_CLIENT_ID'),
        'client_secret' => env('MICROSOFT_CLIENT_SECRET'),
        'redirect' => env('APP_URL') . '/oauth/microsoft/callback',
    ],

    'youtube' => [
        'client_id' => env('YOUTUBE_CLIENT_ID'),
        'client_secret' => env('YOUTUBE_CLIENT_SECRET'),
        'redirect' => env('APP_URL') . '/oauth/youtube/callback',
    ],

    'imap' => [
        'host' => env('IMAP_HOST'),
        'port' => env('IMAP_PORT', 993),
        'username' => env('IMAP_USERNAME'),
        'password' => env('IMAP_PASSWORD'),
        'ssl' => env('IMAP_SSL', true),
        'smtp_host' => env('SMTP_HOST'),
        'smtp_port' => env('SMTP_PORT', 587),
    ],

    'pop3' => [
        'host' => env('POP3_HOST'),
        'port' => env('POP3_PORT', 110),
        'username' => env('POP3_USERNAME'),
        'password' => env('POP3_PASSWORD'),
        'ssl' => env('POP3_SSL', false),
        'smtp_host' => env('SMTP_HOST'),
        'smtp_port' => env('SMTP_PORT', 587),
    ],

];

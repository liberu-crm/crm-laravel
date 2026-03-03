<?php

use App\Models\OAuthConfiguration;

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

    'twilio' => function () {
        $config = OAuthConfiguration::getConfig('twilio');
        return [
            'client_id' => $config ? $config->client_id : env('TWILIO_CLIENT_ID'),
            'client_secret' => $config ? $config->client_secret : env('TWILIO_CLIENT_SECRET'),
            'redirect_uri' => $config && isset($config->additional_settings['redirect_uri']) ? $config->additional_settings['redirect_uri'] : env('TWILIO_REDIRECT_URI'),
            'sid' => $config && isset($config->additional_settings['sid']) ? $config->additional_settings['sid'] : env('TWILIO_SID'),
            'phone_number' => $config && isset($config->additional_settings['phone_number']) ? $config->additional_settings['phone_number'] : env('TWILIO_PHONE_NUMBER'),
            'app_sid' => $config && isset($config->additional_settings['app_sid']) ? $config->additional_settings['app_sid'] : env('TWILIO_APP_SID'),
            'twiml_app_sid' => $config && isset($config->additional_settings['twiml_app_sid']) ? $config->additional_settings['twiml_app_sid'] : env('TWILIO_TWIML_APP_SID'),
            'webhook_url' => $config && isset($config->additional_settings['webhook_url']) ? $config->additional_settings['webhook_url'] : env('TWILIO_WEBHOOK_URL'),
        ];
    },

    // Facebook: unified config for both Socialite (OAuth login/connect) and Graph API (posting)
    'facebook' => function () {
        $config = OAuthConfiguration::getConfig('facebook');
        return [
            // Socialite OAuth keys
            'client_id' => $config ? $config->client_id : env('FACEBOOK_CLIENT_ID', env('FACEBOOK_APP_ID')),
            'client_secret' => $config ? $config->client_secret : env('FACEBOOK_CLIENT_SECRET', env('FACEBOOK_APP_SECRET')),
            'redirect' => env('APP_URL') . '/oauth/facebook/callback',
            // Graph API page posting keys
            'app_id' => $config ? $config->client_id : env('FACEBOOK_APP_ID', env('FACEBOOK_CLIENT_ID')),
            'app_secret' => $config ? $config->client_secret : env('FACEBOOK_APP_SECRET', env('FACEBOOK_CLIENT_SECRET')),
            'page_id' => $config && isset($config->additional_settings['page_id']) ? $config->additional_settings['page_id'] : env('FACEBOOK_PAGE_ID'),
            'page_access_token' => $config && isset($config->additional_settings['page_access_token']) ? $config->additional_settings['page_access_token'] : env('FACEBOOK_PAGE_ACCESS_TOKEN'),
            'graph_version' => env('FACEBOOK_GRAPH_VERSION', 'v18.0'),
        ];
    },

    // Google: unified config for Socialite, Gmail, Google Ads, and YouTube
    'google' => function () {
        $config = OAuthConfiguration::getConfig('google');
        return [
            // Socialite OAuth keys
            'client_id' => $config ? $config->client_id : env('GOOGLE_CLIENT_ID'),
            'client_secret' => $config ? $config->client_secret : env('GOOGLE_CLIENT_SECRET'),
            'redirect' => env('APP_URL') . '/oauth/google/callback',
            // Additional keys for Google APIs
            'credentials_path' => env('GOOGLE_CREDENTIALS_PATH'),
            'developer_token' => env('GOOGLE_ADS_DEVELOPER_TOKEN'),
        ];
    },

    // Twitter/X OAuth 2.0 for posting tweets, images, and videos
    'twitter-oauth-2' => function () {
        $config = OAuthConfiguration::getConfig('twitter');
        return [
            'client_id' => $config ? $config->client_id : env('TWITTER_CLIENT_ID'),
            'client_secret' => $config ? $config->client_secret : env('TWITTER_CLIENT_SECRET'),
            'redirect' => env('APP_URL') . '/oauth/twitter-oauth-2/callback',
        ];
    },

    // LinkedIn for posting text, images, and videos
    'linkedin-openid' => function () {
        $config = OAuthConfiguration::getConfig('linkedin');
        return [
            'client_id' => $config ? $config->client_id : env('LINKEDIN_CLIENT_ID'),
            'client_secret' => $config ? $config->client_secret : env('LINKEDIN_CLIENT_SECRET'),
            'redirect' => env('APP_URL') . '/oauth/linkedin-openid/callback',
        ];
    },

    'mailchimp' => function () {
        $config = OAuthConfiguration::getConfig('mailchimp');
        return [
            'api_key' => $config ? $config->client_secret : env('MAILCHIMP_API_KEY'),
            'server_prefix' => $config && isset($config->additional_settings['server_prefix']) ? $config->additional_settings['server_prefix'] : env('MAILCHIMP_SERVER_PREFIX'),
        ];
    },

    'whatsapp' => function () {
        $config = OAuthConfiguration::getConfig('whatsapp');
        return [
            'api_url' => $config && isset($config->additional_settings['api_url']) ? $config->additional_settings['api_url'] : env('WHATSAPP_API_URL'),
            'access_token' => $config ? $config->client_secret : env('WHATSAPP_ACCESS_TOKEN'),
        ];
    },

    'quickbooks' => function () {
        $config = OAuthConfiguration::getConfig('quickbooks');
        return [
            'client_id' => $config ? $config->client_id : env('QUICKBOOKS_CLIENT_ID'),
            'client_secret' => $config ? $config->client_secret : env('QUICKBOOKS_CLIENT_SECRET'),
            'redirect_uri' => $config && isset($config->additional_settings['redirect_uri']) ? $config->additional_settings['redirect_uri'] : env('QUICKBOOKS_REDIRECT_URI'),
        ];
    },

    'xero' => function () {
        $config = OAuthConfiguration::getConfig('xero');
        return [
            'client_id' => $config ? $config->client_id : env('XERO_CLIENT_ID'),
            'client_secret' => $config ? $config->client_secret : env('XERO_CLIENT_SECRET'),
            'redirect_uri' => $config && isset($config->additional_settings['redirect_uri']) ? $config->additional_settings['redirect_uri'] : env('XERO_REDIRECT_URI'),
        ];
    },

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

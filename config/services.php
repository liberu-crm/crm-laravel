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

    'whatsapp' => [
        'api_url' => env('WHATSAPP_API_URL'),
        'access_token' => env('WHATSAPP_ACCESS_TOKEN'),
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

    'facebook' => function () {
        $config = OAuthConfiguration::getConfig('facebook');
        return [
            'app_id' => $config ? $config->client_id : env('FACEBOOK_APP_ID'),
            'app_secret' => $config ? $config->client_secret : env('FACEBOOK_APP_SECRET'),
            'page_id' => $config && isset($config->additional_settings['page_id']) ? $config->additional_settings['page_id'] : env('FACEBOOK_PAGE_ID'),
            'page_access_token' => $config && isset($config->additional_settings['page_access_token']) ? $config->additional_settings['page_access_token'] : env('FACEBOOK_PAGE_ACCESS_TOKEN'),
            'graph_version' => env('FACEBOOK_GRAPH_VERSION', 'v12.0'),
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

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect_uri' => env('GOOGLE_REDIRECT_URI'),
        'credentials_path' => env('GOOGLE_CREDENTIALS_PATH'),
    ],

    'outlook' => [
        'client_id' => env('OUTLOOK_CLIENT_ID'),
        'client_secret' => env('OUTLOOK_CLIENT_SECRET'),
        'redirect_uri' => env('OUTLOOK_REDIRECT_URI'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('APP_URL') . '/oauth/google/callback',
        'developer_token' => env('GOOGLE_ADS_DEVELOPER_TOKEN'),
    ],

    'facebook' => [
        'client_id' => env('FACEBOOK_CLIENT_ID'),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
        'redirect' => env('APP_URL') . '/oauth/facebook/callback',
    ],

    'linkedin' => [
        'client_id' => env('LINKEDIN_CLIENT_ID'),
        'client_secret' => env('LINKEDIN_CLIENT_SECRET'),
        'redirect' => env('APP_URL') . '/oauth/linkedin/callback',
    ],

    'microsoft' => [
        'client_id' => env('MICROSOFT_CLIENT_ID'),
        'client_secret' => env('MICROSOFT_CLIENT_SECRET'),
        'redirect' => env('APP_URL') . '/oauth/microsoft/callback',
    ],

    'Google_Service_YouTube' => [
        'client_id' => env('GOOGLE_SERVICE_YOUTUBE_ID'),
        'client_secret' => env('GOOGLE_SERVICE_YOUTUBE_SECRET'),
        'redirect' => env('APP_URL') . 'oauth/google_service_youtube/callback'
    ]

];

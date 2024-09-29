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

    'twilio' => [
        'sid' => env('TWILIO_SID'),
        'auth_token' => env('TWILIO_AUTH_TOKEN'),
        'phone_number' => env('TWILIO_PHONE_NUMBER'),
        'app_sid' => env('TWILIO_APP_SID'),
        'twiml_app_sid' => env('TWILIO_TWIML_APP_SID'),
        'webhook_url' => env('TWILIO_WEBHOOK_URL'),
    ],

    'facebook' => function () {
        $config = OAuthConfiguration::getConfig('facebook');
        return [
            'app_id' => $config ? $config->client_id : env('FACEBOOK_APP_ID'),
            'app_secret' => $config ? $config->client_secret : env('FACEBOOK_APP_SECRET'),
            'page_id' => $config && isset($config->additional_settings['page_id']) ? $config->additional_settings['page_id'] : env('FACEBOOK_PAGE_ID'),
            'page_access_token' => $config && isset($config->additional_settings['page_access_token']) ? $config->additional_settings['page_access_token'] : env('FACEBOOK_PAGE_ACCESS_TOKEN'),
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

];

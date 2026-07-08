<?php

return [
    /*
    | The name shown in the customer portal's top bar. Override per deployment
    | (white-labelling) via the PORTAL_BRAND_NAME env var.
    */
    'brand_name' => env('PORTAL_BRAND_NAME', 'Customer Portal'),

    /*
    | Portal logo + favicon URLs (white-labelling). Null falls back to the
    | brand-name text / Filament default favicon.
    */
    'logo' => env('PORTAL_LOGO_URL'),

    'favicon' => env('PORTAL_FAVICON_URL'),
];

<?php

return [

    /*
    |--------------------------------------------------------------------------
    | External Modules Directory
    |--------------------------------------------------------------------------
    |
    | The directory (relative to base_path) where external composer-installed
    | modules live. Enabled via MODULES_LOAD_COMPOSER=true in .env.
    |
    */

    'module_directory' => env('MODULES_DIRECTORY', 'app-modules'),

    /*
    |--------------------------------------------------------------------------
    | Module Namespace
    |--------------------------------------------------------------------------
    */

    'namespace' => 'Modules',

    /*
    |--------------------------------------------------------------------------
    | Filament Auto-Discovery
    |--------------------------------------------------------------------------
    |
    | Automatically register Filament resources found in module Filament/ dirs.
    |
    */

    'filament_auto_discovery' => env('MODULES_FILAMENT_DISCOVERY', true),

    /*
    |--------------------------------------------------------------------------
    | IDE Helper
    |--------------------------------------------------------------------------
    */

    'ide_helper' => env('APP_DEBUG', false),

];

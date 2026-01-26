<?php

/**
 * CAS Authentication Configuration (SATUID)
 * Optimized for Treasury eHelp System
 *
 * Note: This implementation uses manual CAS authentication without phpCAS library.
 * Configuration is simplified to include only required settings.
 */
return [
    /*
    |--------------------------------------------------------------------------
    | CAS Server Configuration
    |--------------------------------------------------------------------------
    */
    'cas_hostname' => env('CAS_HOSTNAME', 'satuid.treasury.gov.my'),
    'cas_port' => env('CAS_PORT', 443),
    'cas_uri' => env('CAS_URI', '/gk'),

    /*
    |--------------------------------------------------------------------------
    | Client Service Configuration
    |--------------------------------------------------------------------------
    | The base URL of this application that CAS will redirect back to.
    | Example: 'http://localhost:8001', 'https://ehelp.treasury.gov.my'
    */
    'cas_client_service' => env('CAS_CLIENT_SERVICE', 'http://ehelp-dev.treasury.gov.my'),

    /*
    |--------------------------------------------------------------------------
    | Redirect Path After Login
    |--------------------------------------------------------------------------
    | Where to redirect users after successful CAS authentication.
    */
    'cas_redirect_path' => env('CAS_REDIRECT_PATH', '/dashboard'),

    /*
    |--------------------------------------------------------------------------
    | CAS Logout Configuration
    |--------------------------------------------------------------------------
    */
    'cas_logout_url' => env('CAS_LOGOUT_URL', 'https://satuid.treasury.gov.my/gk/logout'),
    'cas_logout_redirect' => env('CAS_LOGOUT_REDIRECT', 'http://ehelp-dev.treasury.gov.my'),
];

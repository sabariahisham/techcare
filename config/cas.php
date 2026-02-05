<?php

/**
 * CAS Authentication Configuration (SATUID)
 * For Treasury eHelp System
 *
 * This configuration uses manual CAS authentication without phpCAS library.
 * Only essential settings are included to avoid duplicates.
 */
return [
    /*
    |--------------------------------------------------------------------------
    | CAS Server Hostname
    |--------------------------------------------------------------------------
    | The hostname of your CAS server (without https://)
    | Example: satuid.treasury.gov.my
    */
    'cas_hostname' => env('CAS_HOSTNAME', 'satuid.treasury.gov.my'),

    /*
    |--------------------------------------------------------------------------
    | CAS Server Port
    |--------------------------------------------------------------------------
    | Usually 443 for HTTPS
    */
    'cas_port' => env('CAS_PORT', 443),

    /*
    |--------------------------------------------------------------------------
    | CAS URI
    |--------------------------------------------------------------------------
    | The base URI path for CAS endpoints
    | Example: /cas or /gk
    */
    'cas_uri' => env('CAS_URI', '/gk'),

    /*
    |--------------------------------------------------------------------------
    | Client Service URL
    |--------------------------------------------------------------------------
    | The base URL of your application that CAS will redirect back to.
    | This should match exactly what's registered with SATUID.
    | Example: 'http://techcare.test'
    */
    'cas_client_service' => env('CAS_CLIENT_SERVICE', 'http://techcare-dev.treasury.gov.my'),

    /*
    |--------------------------------------------------------------------------
    | Redirect Path After Login
    --------------------------------------------------------------------------
    | Where to redirect users after successful CAS authentication.
    | This is a relative path from your application root.
    */
    'cas_redirect_path' => env('CAS_REDIRECT_PATH', '/dashboard'),

    /*
    |--------------------------------------------------------------------------
    | CAS Logout URL
    |--------------------------------------------------------------------------
    | The full URL to the CAS logout endpoint
    */
    'cas_logout_url' => env('CAS_LOGOUT_URL', 'https://satuid.treasury.gov.my/gk/logout'),

    /*
    |--------------------------------------------------------------------------
    | Logout Redirect URL
    |--------------------------------------------------------------------------
    | Where to redirect after logging out from CAS
    | If not specified, will redirect to CAS logout page
    */
    'cas_logout_redirect' => env('CAS_LOGOUT_REDIRECT', 'http://techcare-dev.treasury.gov.my'),

    /*
    |--------------------------------------------------------------------------
    | CAS Validation Path
    |--------------------------------------------------------------------------
    | The endpoint used to validate CAS tickets
    */
    'cas_validate_path' => env('CAS_VALIDATE_PATH', '/gk/serviceValidate'),

    /*
    |--------------------------------------------------------------------------
    | SSL Certificate Validation
    |--------------------------------------------------------------------------
    | Options: 'ca' (validate with CA), 'self' (self-signed), 'none' (no validation)
    |
    | WARNING: Use 'none' only for development. Production should use 'ca'.
    */
    'cas_validation' => env('CAS_VALIDATION', 'none'),

    /*
    |--------------------------------------------------------------------------
    | CA Certificate Path
    |--------------------------------------------------------------------------
    | Path to the CA certificate file if using 'ca' validation
    */
    'cas_cert' => env('CAS_CERT', null),

    /*
    |--------------------------------------------------------------------------
    | Validate CN (Common Name)
    |--------------------------------------------------------------------------
    | Whether to validate the certificate's common name
    */
    'cas_validate_cn' => env('CAS_VALIDATE_CN', false),

    /*
    |--------------------------------------------------------------------------
    | Enable SAML 1.1
    |--------------------------------------------------------------------------
    | If your CAS server supports SAML, you can retrieve additional user attributes
    */
    'cas_enable_saml' => env('CAS_ENABLE_SAML', true),

    /*
    |--------------------------------------------------------------------------
    | CAS Proxy
    |--------------------------------------------------------------------------
    | Enable if using CAS as a proxy
    */
    'cas_proxy' => env('CAS_PROXY', false),

    /*
    |--------------------------------------------------------------------------
    | Control Session
    |--------------------------------------------------------------------------
    | Whether CAS should control the session
    */
    'cas_control_session' => env('CAS_CONTROL_SESSION', false),

    /*
    |--------------------------------------------------------------------------
    | Debug Mode
    |--------------------------------------------------------------------------
    | Enable detailed logging for CAS operations
    */
    'cas_debug' => env('CAS_DEBUG', true),
];

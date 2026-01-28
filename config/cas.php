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
  'cas_control_session' => false,

  /*
    |--------------------------------------------------------------------------
    | CAS Hostname
    |--------------------------------------------------------------------------
    */
  'cas_hostname' => env('CAS_HOSTNAME', 'satuid.treasury.gov.my'),

  /*
    |--------------------------------------------------------------------------
    | Enable using this as a cas proxy
    |--------------------------------------------------------------------------
    */
  'cas_proxy' => false,

  /*
    |--------------------------------------------------------------------------
    | Cas Port
    |--------------------------------------------------------------------------
    | Usually 443
    */
  //'cas_port' => 443,
  'cas_port' => env('CAS_PORT', 443),

  /*
    |--------------------------------------------------------------------------
    | CAS URI
    |--------------------------------------------------------------------------
    | Sometimes is /cas
    */
  //'cas_uri' => '/gk',
  'cas_uri' => env('CAS_URI', '/gk'),

  /*
    |--------------------------------------------------------------------------
    | Client service: host, port, protocol.
    |--------------------------------------------------------------------------
    | Example: 'http://localhost', 'https://example.com:8888'
    */
  // 'cas_client_service' => 'http://ehelp-dev.treasury.gov.my',
  'cas_client_service' => env('CAS_CLIENT_SERVICE', 'http://ehelp-dev.treasury.gov.my'),

  /*
    |--------------------------------------------------------------------------
    | CAS Validation
    |--------------------------------------------------------------------------
    | CAS server SSL validation: 'self' for self-signed certificate, 'ca' for
    | certificate from a CA, 'none' for no SSL validation.
    |
    | VALIDATING THE CAS SERVER IS CRUCIAL TO THE SECURITY OF THE CAS PROTOCOL
    */
  'cas_validation' => 'none',

  /*
    |--------------------------------------------------------------------------
    | CA Certificate
    |--------------------------------------------------------------------------
    | Path to the CA certificate file.  For production use set
    | the CA certificate that is the issuer of the cert
    */
  'cas_cert' => null,

  /*
    |--------------------------------------------------------------------------
    | CN Validation (if you are using CA certs)
    |--------------------------------------------------------------------------
    | If for some reason you want to disable validating the certificate
    | intermediaries, here is where you can.  Recommended to leave
    | this set with default (true).
    */
  'cas_validate_cn' => false,

  /*
    |--------------------------------------------------------------------------
    | CAS Login URI
    |--------------------------------------------------------------------------
    | Empty is fine
    */
  'cas_login_url' => '',

  /*
    |--------------------------------------------------------------------------
    | CAS Logout URI
    |--------------------------------------------------------------------------
    */
  'cas_logout_url' => 'https://satuid.treasury.gov.my/gk/logout',

  /*
    |--------------------------------------------------------------------------
    | CAS Logout Redirect Services
    |--------------------------------------------------------------------------
    | If your server supports redirection services, enter the redirect url
    | in this section.  If left blank, it will default to disabled.
    */
  // 'cas_logout_redirect' => 'http://ehelp-dev.treasury.gov.my',
  'cas_logout_redirect' => env('CAS_LOGOUT_REDIRECT', 'http://ehelp-dev.treasury.gov.my'),

  /*
    |--------------------------------------------------------------------------
    | CAS Successful Logon Redirection Url
    |--------------------------------------------------------------------------
    | By default, CAS will assume that the user should be redirected to the
    | page in which the call was initiated.  You can override this method
    | and force the user to be redirected to a specific URL here.
    */
  //'cas_redirect_path' => 'http://ehelp-dev.treasury.gov.my/dashboard',
  'cas_redirect_path' => env('CAS_REDIRECT_PATH', '/dashboard'),

  /*
    |--------------------------------------------------------------------------
    | CAS Supports SAML 1.1, allowing you to retrieve more than just the
    | user identifier.  If your CAS authentication service supports
    | this feature, you may be able to retrieve user meta data.
    |--------------------------------------------------------------------------
    */
  'cas_enable_saml' => true,

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

<?php

namespace App\Services;

use phpCAS;
use Illuminate\Support\Facades\Log;

class CasService
{
    protected static $initialized = false;

    public function __construct()
    {
        $this->initialize();
    }

    /**
     * Initialize phpCAS
     */
    protected function initialize(): void
    {
        if (self::$initialized) {
            return;
        }

        // Determine CAS version
        $version = match(config('cas.cas_version')) {
            '1.0' => CAS_VERSION_1_0,
            '3.0' => CAS_VERSION_3_0,
            default => CAS_VERSION_2_0,
        };

        // Initialize phpCAS
        phpCAS::client(
            $version,
            config('cas.cas_hostname'),
            (int) config('cas.cas_port'),
            config('cas.cas_uri')
        );

        // Handle SSL Validation based on configuration
        $validation = config('cas.cas_validation');
        if ($validation === 'ca' && config('cas.cas_cert')) {
            phpCAS::setCasServerCACert(config('cas.cas_cert'));
        } elseif ($validation === 'self') {
            phpCAS::setNoCasServerValidation();
        } else {
            phpCAS::setNoCasServerValidation();
        }

        // Enable debug logging if configured
        if (config('cas.cas_log')) {
            if (config('cas.cas_log') === 'laravel') {
                phpCAS::setLogger(new \Illuminate\Log\Logger(app('log')));
            } else {
                phpCAS::setDebug(config('cas.cas_log'));
            }
        }

        // Enable SAML if configured
        if (config('cas.cas_enable_saml')) {
            phpCAS::setSingleSignoutCallback(function() {
                auth()->logout();
            });
        }

        self::$initialized = true;
    }

    /**
     * Force CAS authentication
     */
    public function authenticate(): void
    {
        phpCAS::forceAuthentication();
    }

    /**
     * Check if user is authenticated
     */
    public function isAuthenticated(): bool
    {
        return phpCAS::isAuthenticated();
    }

    /**
     * Get current authenticated user
     */
    public function getCurrentUser(): ?string
    {
        if (!$this->isAuthenticated()) {
            return null;
        }

        return phpCAS::getUser();
    }

    /**
     * Get user attributes
     */
    public function getAttributes(): array
    {
        if (!$this->isAuthenticated()) {
            return [];
        }

        return phpCAS::getAttributes();
    }

    /**
     * Logout from CAS
     */
    public function logout(?string $url = null): void
    {
        $url = $url ?? config('cas.cas_logout_redirect') ?? config('cas.cas_logout_url');
        
        if ($url) {
            phpCAS::logoutWithRedirectService($url);
        } else {
            phpCAS::logout();
        }
    }

    /**
     * Check authentication (without forcing login)
     */
    public function checkAuthentication(): bool
    {
        return phpCAS::checkAuthentication();
    }
}

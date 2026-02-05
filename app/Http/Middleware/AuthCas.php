<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

/**
 * Middleware for CAS Authentication with SATUID
 * Maps authenticated users from LDAP to local User model
 */
class AuthCas
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            // Check if user is already authenticated
            if (Auth::check()) {
                Log::info('CAS: User already authenticated', [
                    'user_id' => Auth::id(),
                    'username' => Auth::user()->nama ?? 'N/A'
                ]);
                return $next($request);
            }

            // Check if we have a CAS ticket (returning from SATUID)
            $ticket = $request->query('ticket');

            if ($ticket) {
                Log::info('CAS: Ticket received', [
                    'ticket_preview' => substr($ticket, 0, 20) . '...',
                    'full_url' => $request->fullUrl()
                ]);

                // Validate ticket and authenticate user
                $user = $this->validateTicketAndGetUser($request, $ticket);

                if (!$user) {
                    return $this->errorResponse('SATUID authentication gagal. Sila cuba log masuk semula.');
                }

                // Login user
                Auth::login($user, true);
                Log::info('CAS: User logged in successfully', [
                    'user_id' => $user->id,
                    'nama' => $user->nama,
                    'ldap_id' => $user->id_pgn_ldap
                ]);

                // Redirect to clean URL without ticket parameter
                $cleanUrl = $request->url();
                Log::info('CAS: Redirecting to clean URL', ['url' => $cleanUrl]);

                return redirect($cleanUrl);
            }

            // No ticket, redirect to CAS login
            return $this->redirectToCasLogin($request);

        } catch (\Throwable $e) {
            Log::error('CAS: Unexpected error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Authentication error: ' . $e->getMessage());
        }
    }

    /**
     * Validate CAS ticket and retrieve user
     *
     * @param Request $request
     * @param string $ticket
     * @return User|null
     */
    private function validateTicketAndGetUser(Request $request, string $ticket): ?User
    {
        try {
            // Build service URL (without query parameters)
            $serviceUrl = $request->url();

            // Build CAS server URL
            $casServer = 'https://'
                . config('cas.cas_hostname')
                . ':' . config('cas.cas_port')
                . config('cas.cas_uri');

            // Build validation URL
            $validateUrl = $casServer
                . config('cas.cas_validate_path')
                . '?service=' . urlencode($serviceUrl)
                . '&ticket=' . urlencode($ticket);

            Log::info('CAS: Validating ticket', [
                'validate_url' => $validateUrl,
                'service_url' => $serviceUrl
            ]);

            // Create SSL context for HTTPS request
            $context = stream_context_create([
                'ssl' => [
                    'verify_peer' => config('cas.cas_validation') !== 'none',
                    'verify_peer_name' => config('cas.cas_validate_cn', false),
                ],
                'http' => [
                    'timeout' => 30,
                    'user_agent' => 'eHelp CAS Client/1.0'
                ]
            ]);

            // Call CAS server to validate ticket
            $response = @file_get_contents($validateUrl, false, $context);

            if ($response === false) {
                Log::error('CAS: Failed to contact CAS server', [
                    'validate_url' => $validateUrl,
                    'error' => error_get_last()
                ]);
                return null;
            }

            Log::info('CAS: Server response received', [
                'response_preview' => substr($response, 0, 300)
            ]);

            // Parse XML response
            $xml = simplexml_load_string($response);
            if ($xml === false) {
                Log::error('CAS: Failed to parse XML response', [
                    'response' => $response,
                    'xml_errors' => libxml_get_errors()
                ]);
                return null;
            }

            // Register CAS namespace
            $xml->registerXPathNamespace('cas', 'http://www.yale.edu/tp/cas');

            // Check for authentication success
            $success = $xml->xpath('//cas:authenticationSuccess');
            if (empty($success)) {
                // Check for authentication failure
                $failure = $xml->xpath('//cas:authenticationFailure');
                $failureMessage = !empty($failure) ? (string)$failure[0] : 'Unknown error';

                Log::warning('CAS: Authentication failed', [
                    'failure_message' => $failureMessage,
                    'response' => $response
                ]);
                return null;
            }

            // Get username (LDAP ID)
            $users = $xml->xpath('//cas:user');
            if (empty($users)) {
                Log::error('CAS: No user in CAS response', [
                    'response' => $response
                ]);
                return null;
            }

            $ldapId = (string)$users[0];
            Log::info('CAS: Authentication successful', ['ldap_id' => $ldapId]);

            // Get additional attributes if SAML is enabled
            $attributes = [];
            if (config('cas.cas_enable_saml')) {
                $attrs = $xml->xpath('//cas:attributes');
                if (!empty($attrs)) {
                    foreach ($attrs[0]->children('cas', true) as $name => $value) {
                        $attributes[$name] = (string)$value;
                    }
                    Log::info('CAS: SAML attributes retrieved', ['attributes' => $attributes]);
                }
            }

            // Find user in database by LDAP ID
            $user = User::where('id_pgn_ldap', $ldapId)->first();

            if (!$user) {
                Log::warning('CAS: User not found in database', [
                    'ldap_id' => $ldapId,
                    'attributes' => $attributes
                ]);
                return null;
            }

            Log::info('CAS: User found in database', [
                'user_id' => $user->id,
                'nama' => $user->nama,
                'email' => $user->email
            ]);

            return $user;

        } catch (\Exception $e) {
            Log::error('CAS: Error validating ticket', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return null;
        }
    }

    /**
     * Redirect to CAS login page
     *
     * @param Request $request
     * @return Response
     */
    private function redirectToCasLogin(Request $request): Response
    {
        // Build service URL (current page URL)
        $serviceUrl = $request->url();

        // Build CAS server URL
        $casServer = 'https://'
            . config('cas.cas_hostname')
            . ':' . config('cas.cas_port')
            . config('cas.cas_uri');

        // Build login URL
        $loginUrl = $casServer . '/login?service=' . urlencode($serviceUrl);

        Log::info('CAS: Redirecting to login', [
            'login_url' => $loginUrl,
            'service_url' => $serviceUrl
        ]);

        return redirect($loginUrl);
    }

    /**
     * Return error response
     *
     * @param string $message
     * @return Response
     */
    private function errorResponse(string $message): Response
    {
        Log::error('CAS: Returning error response', ['message' => $message]);

        // Check if error view exists, otherwise return JSON
        if (view()->exists('errors.401')) {
            return response()->view('errors.401', ['message' => $message], 401);
        }

        return response()->json([
            'error' => $message,
            'code' => 401
        ], 401);
    }
}

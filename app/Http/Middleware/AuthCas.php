<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

/**
 * Middleware untuk authenticate dan map User model ke LDAP
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
    // Simple CAS authentication without phpCAS library

    try {
      // Check if user is already logged in
      if (Auth::check()) {
        Log::info('User already authenticated', ['user_id' => Auth::id()]);
        return $next($request);
      }

      // Check if we have a CAS ticket (returning from SATUID)
      $ticket = $request->query('ticket');

      if ($ticket) {
        Log::info('CAS ticket received', ['ticket' => substr($ticket, 0, 20) . '...']);

        // Validate ticket with CAS server
        $serviceUrl = $request->url(); // Use full URL without query parameters
        $casServer = 'https://' . config('cas.cas_hostname') . ':' . config('cas.cas_port') . config('cas.cas_uri');
        $validateUrl = $casServer . '/serviceValidate?service=' . urlencode($serviceUrl) . '&ticket=' . urlencode($ticket);

        Log::info('Validating ticket', ['url' => $validateUrl]);

        // Call CAS server to validate ticket
        $context = stream_context_create([
          'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
          ],
        ]);

        $response = @file_get_contents($validateUrl, false, $context);

        if ($response === false) {
          Log::error('Failed to contact CAS server');
          return response()->view('errors.401', [
            'message' => 'Tidak dapat menghubungi SATUID server. Sila cuba lagi.'
          ], 401);
        }

        Log::info('CAS server response received', ['response' => substr($response, 0, 200)]);

        // Parse XML response to get username
        $xml = simplexml_load_string($response);
        if ($xml === false) {
          Log::error('Failed to parse CAS response');
          return response()->view('errors.401', [
            'message' => 'Invalid CAS response format.'
          ], 401);
        }

        // Register namespaces
        $xml->registerXPathNamespace('cas', 'http://www.yale.edu/tp/cas');

        // Check for authentication success
        $success = $xml->xpath('//cas:authenticationSuccess');
        if (empty($success)) {
          Log::warning('CAS authentication failed', ['response' => $response]);
          return response()->view('errors.401', [
            'message' => 'SATUID authentication failed. Sila cuba log masuk semula.'
          ], 401);
        }

        // Get username
        $users = $xml->xpath('//cas:user');
        if (empty($users)) {
          Log::error('No user in CAS response');
          return response()->view('errors.401', [
            'message' => 'SATUID tidak mengembalikan user ID.'
          ], 401);
        }

        $ldapId = (string)$users[0];
        Log::info('CAS authentication successful', ['ldap_id' => $ldapId]);

        // Find user in database
        $user = User::where('id_pgn_ldap', $ldapId)->first();

        if (!$user) {
          Log::warning("User not found for LDAP ID: {$ldapId}");
          return response()->view('errors.401', [
            'message' => "Akaun tidak dijumpai untuk LDAP ID: {$ldapId}. Sila hubungi pentadbir sistem."
          ], 401);
        }

        // Login user
        Auth::login($user, true);
        Log::info('User logged in successfully', ['user_id' => $user->id, 'nama' => $user->nama]);

        // Redirect to clean URL without ticket parameter
        $cleanUrl = $request->url();
        Log::info('Redirecting to clean URL', ['url' => $cleanUrl]);
        return redirect($cleanUrl);
      }

      // No ticket, redirect to CAS login
      $serviceUrl = $request->url(); // Use full current URL
      $casServer = 'https://' . config('cas.cas_hostname') . ':' . config('cas.cas_port') . config('cas.cas_uri');
      $loginUrl = $casServer . '/login?service=' . urlencode($serviceUrl);

      Log::info('Redirecting to CAS login', ['url' => $loginUrl]);

      return redirect($loginUrl);
    } catch (\Throwable $e) {
      Log::error("CAS Error: " . $e->getMessage(), [
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
      ]);

      return response()->view('errors.401', [
        'message' => 'Authentication error: ' . $e->getMessage()
      ], 401);
    }
  }
}

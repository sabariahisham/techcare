<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ImpersonateMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is impersonating and add a header/log entry for audit purposes
        $user = Auth::user();

        if ($user && $user->isImpersonated()) {
            // Add logging for impersonation actions
            $manager = app(\Lab404\Impersonate\Services\ImpersonateManager::class);
            $impersonatorId = $manager->getImpersonatorId();
            $impersonatedId = $user->getKey();

            // Log to Laravel log file
            logger()->info('Impersonation action performed', [
                'impersonator_id' => $impersonatorId,
                'impersonated_id' => $impersonatedId,
                'action' => $request->method() . ' ' . $request->path(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            // Also log to audit trail table (only for non-GET requests to avoid clutter)
            if (!$request->isMethod('GET') && !str_contains($request->path(), 'livewire')) {
                try {
                    DB::table('audits')->insert([
                        'user_type' => 'App\\Models\\User',
                        'user_id' => $impersonatorId, // Log under impersonator's ID
                        'event' => 'impersonate_action',
                        'auditable_type' => 'App\\Models\\User',
                        'auditable_id' => $impersonatedId,
                        'old_values' => json_encode([]),
                        'new_values' => json_encode([
                            'impersonator_id' => $impersonatorId,
                            'impersonated_id' => $impersonatedId,
                            'action' => $request->method() . ' ' . $request->path(),
                        ]),
                        'url' => $request->fullUrl(),
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                        'tags' => 'impersonation',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } catch (\Exception $e) {
                    // Silently fail if audit logging fails to not break the application
                    logger()->error('Failed to log impersonation action to audit trail', [
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        return $next($request);
    }
}

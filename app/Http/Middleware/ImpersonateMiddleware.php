<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
            logger()->info('Impersonation action performed', [
                'impersonator_id' => $manager->getImpersonatorId(),
                'impersonated_id' => $user->getKey(),
                'action' => $request->method() . ' ' . $request->path(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);
        }

        return $next($request);
    }
}

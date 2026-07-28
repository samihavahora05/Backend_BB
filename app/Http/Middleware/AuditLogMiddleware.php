<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class AuditLogMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // We only audit modifying requests (POST, PUT, PATCH, DELETE) that are successful
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE']) && $response->isSuccessful()) {
            
            // Mask sensitive data
            $payload = $request->except(['password', 'password_confirmation', 'token', 'otp']);

            try {
                AuditLog::create([
                    'user_id' => Auth::check() ? Auth::id() : null,
                    'action' => $request->method() . ' ' . $request->path(),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'payload' => $payload,
                ]);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('AuditLog Error: ' . $e->getMessage());
            }
        }

        return $response;
    }
}

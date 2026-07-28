<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProfileIsVerified
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if ($user->hasRole('super-admin')) {
            return $next($request);
        }

        $role = $user->roles()->first()?->name;
        
        $profile = match($role) {
            'expert' => $user->expertProfile()->first(),
            'company' => $user->companyProfile()->first(),
            'college' => $user->collegeProfile()->first(),
            default => null
        };

        if ($profile && !$profile->is_verified) {
            return response()->json(['message' => 'Your profile is pending admin verification.'], 403);
        }

        return $next($request);
    }
}

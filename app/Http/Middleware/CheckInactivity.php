<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckInactivity
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        if ($user && $request->bearerToken()) {
            $token = $user->currentAccessToken();
            $inactivityTimeout = config('sanctum.inactivity_timeout', 30) * 60; // Minutes to seconds

            if ($token && $token->last_used_at && now()->diffInSeconds($token->last_used_at) > $inactivityTimeout) {
                $token->delete();
                return response()->json([
                    'message' => 'Session expired due to inactivity',
                    'logout_reason' => 'inactivity'
                ], 401);
            }

            // Update last_used_at if token exists
            if ($token) {
                $token->forceFill(['last_used_at' => now()])->save();
            }
        }

        return $next($request);
    }
}
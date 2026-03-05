<?php

namespace App\Http\Middleware;

use Closure;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next, $role)
    {
        $user = auth('api')->user();

        if (!$user || $user->role != $role) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        return $next($request);
    }
}

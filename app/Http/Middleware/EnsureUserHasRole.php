<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Allow the request only if the authenticated user has one of the given roles.
     * Usage on a route: ->middleware('role:owner') or 'role:owner,admin'
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role?->value, $roles, true)) {
            abort(403);
        }

        return $next($request);
    }
}

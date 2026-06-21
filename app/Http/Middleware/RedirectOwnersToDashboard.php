<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keep owners inside their sidebar area: a logged-in owner who hits a public or
 * customer-facing page is redirected to their dashboard. Guests and customers
 * pass through untouched.
 */
class RedirectOwnersToDashboard
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->role === UserRole::Owner) {
            return redirect()->route('dashboard');
        }

        return $next($request);
    }
}

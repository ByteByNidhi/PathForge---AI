<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectOrganizationUsers
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && ! $user->isAdmin() && $user->isOrganizationUser()) {
            return redirect()->route('organization.dashboard');
        }

        return $next($request);
    }
}

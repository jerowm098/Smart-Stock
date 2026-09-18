<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    public function __invoke(Request $request, Closure $next, string $guard = null): Response
    {
        if (auth()->guard($guard)->check()) {
            return redirect()->route('dashboard');
        }

        return $next($request);
    }
}

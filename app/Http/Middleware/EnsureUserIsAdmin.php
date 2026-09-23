<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Restrict supplier management to administrator accounts.
     */
    public function handle(Request $request, Closure $next): Response|JsonResponse
    {
        if (! $request->user()?->isAdmin()) {
            return response()->json(['message' => 'Administrator access required.'], 403);
        }

        return $next($request);
    }
}
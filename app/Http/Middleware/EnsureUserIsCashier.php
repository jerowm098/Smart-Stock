<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsCashier
{
    /**
     * Restrict POS checkout to cashier accounts.
     */
    public function handle(Request $request, Closure $next): Response|JsonResponse
    {
        if (! $request->user()?->isCashier()) {
            return response()->json(['message' => 'Cashier access required.'], 403);
        }

        return $next($request);
    }
}
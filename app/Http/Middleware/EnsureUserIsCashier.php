<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsCashier
{
    /**
     * Restrict POS checkout to sales roles (cashier + admin).
     * BRD: Staff restricted to sales interface only; Admin retains full access including sales.
     */
    public function handle(Request $request, Closure $next): Response|JsonResponse
    {
        $user = $request->user();
        if (! $user || (! $user->isCashier() && ! $user->isAdmin())) {
            return response()->json(['message' => 'Cashier access required.'], 403);
        }

        return $next($request);
    }
}
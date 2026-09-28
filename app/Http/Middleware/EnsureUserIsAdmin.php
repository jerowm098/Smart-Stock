<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Restrict admin-level pages and endpoints to administrator accounts.
     *
     * BRD: "Staff accounts shall be unable to view or modify Admin-level data."
     * BRD: "The system shall restrict Staff accounts to the sales interface only."
     *
     * API/JSON callers get a 403 JSON payload; browser page requests are
     * redirected back to their own Overview tab so a cashier never lands on
     * a raw error screen.
     */
    public function handle(Request $request, Closure $next): Response|JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Authentication required.'], 401);
        }

        if (! $user->isAdmin()) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'Administrator access required.'], 403);
            }

            return redirect()
                ->route('dashboard')
                ->with('error', 'Administrator access required for that page.');
        }

        return $next($request);
    }
}

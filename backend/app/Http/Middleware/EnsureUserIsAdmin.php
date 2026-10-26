<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Restrict admin-level endpoints to administrator accounts.
     *
     * BRD: "Staff accounts shall be unable to view or modify Admin-level data."
     * BRD: "The system shall restrict Staff accounts to the sales interface only."
     *
     * Pure API middleware: hindi na nagre-redirect sa Blade pages — 403 JSON
     * ang sagot sa lahat ng non-admin na user.
     */
    public function handle(Request $request, Closure $next): Response|JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Authentication required.'], 401);
        }

        if (! $user->isAdmin()) {
            return response()->json(['message' => 'Administrator access required.'], 403);
        }

        return $next($request);
    }
}


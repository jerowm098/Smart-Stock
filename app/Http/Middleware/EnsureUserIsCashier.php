<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsCashier
{
    /**
     * Restrict the POS checkout surface to sales roles (cashier + admin).
     *
     * BRD: "The system shall restrict Staff accounts to the sales interface
     * only." Admin retains full access, including selling.
     */
    public function handle(Request $request, Closure $next): Response|JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Authentication required.'], 401);
        }

        if (! $user->isCashier() && ! $user->isAdmin()) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'Cashier access required.'], 403);
            }

            return redirect()
                ->route('dashboard')
                ->with('error', 'Cashier access required for that page.');
        }

        return $next($request);
    }
}

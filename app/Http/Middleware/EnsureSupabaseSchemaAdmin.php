<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSupabaseSchemaAdmin
{
    public function __invoke(Request $request, Closure $next): Response
    {
        $expectedToken = (string) config('services.supabase.schema_admin_token');

        if ($expectedToken === '' || ! hash_equals($expectedToken, (string) $request->cookie('supabase_schema_admin'))) {
            return redirect()
                ->route('schema.login')
                ->with('error', 'Administrator access required.');
        }

        return $next($request);
    }
}

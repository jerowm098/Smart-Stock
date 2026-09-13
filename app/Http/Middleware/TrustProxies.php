<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrustProxies
{
    /**
     * Trusted proxies that Render uses for its Load Balancer.
     *
     * All modern cloud LBs send X-Forwarded-* headers:
     *   X-Forwarded-For  = original client IP
     *   X-Forwarded-Proto = http or https
     *   X-Forwarded-Host = original host
     *
     * By default Laravel only trusts localhost proxies. On Render,
     * the incoming request from the LB to our container is over plain
     * HTTP on $PORT — but the original client-to-LB connection was HTTPS.
     * We need to tell Laravel to trust those headers.
     *
     * See: https://laravel.com/docs/12.x/deployment#reverse-proxies
     */
    protected $proxies = '*';

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $request->setTrustedProxies(
            $this->resolveProxies(),
            Request::HEADER_X_FORWARDED_AWS_ELB
                | Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO
        );

        return $next($request);
    }

    /**
     * Resolve the trusted proxy addresses.
     *
     * '*' trusts all proxies — this is correct for Render because
     * the LB is always the immediate predecessor of our app.
     */
    protected function resolveProxies(): array
    {
        if ($this->proxies === '*') {
            return ['*'];
        }

        return array_map('trim', explode(',', (string) $this->proxies));
    }
}

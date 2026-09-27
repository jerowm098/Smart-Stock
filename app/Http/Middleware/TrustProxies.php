<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as BaseTrustProxies;

class TrustProxies extends BaseTrustProxies
{
    /**
     * Trusted proxies that Render uses for its Load Balancer.
     *
     * All modern cloud LBs send X-Forwarded-* headers:
     *   X-Forwarded-For   = original client IP
     *   X-Forwarded-Proto = http or https
     *   X-Forwarded-Host  = original host
     *
     * By default Laravel only trusts localhost proxies. On Render,
     * the incoming request from the LB to our container is over plain
     * HTTP on $PORT — but the original client-to-LB connection was HTTPS.
     * Kailangan i-trust ang LB para makita ng Laravel ang https://,
     * kung hindi secure-cookie + APP_URL mismatch ang magdudulot ng 500/loop.
     *
     * '*' = trust the calling IP (Render LB). Ito ang tamang paraan
     * sa Laravel 12 — ang base class ang bahala sa REMOTE_ADDR mapping,
     * hindi Symfony direkta (na hindi marunong sa '*' string).
     *
     * See: https://laravel.com/docs/12.x/deployment#reverse-proxies
     */
    protected $proxies = '*';

    protected $headers =
        \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR |
        \Illuminate\Http\Request::HEADER_X_FORWARDED_HOST |
        \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT |
        \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO |
        \Illuminate\Http\Request::HEADER_X_FORWARDED_PREFIX |
        \Illuminate\Http\Request::HEADER_X_FORWARDED_AWS_ELB;
}

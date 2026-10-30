<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Render (at Cloudflare's edge) terminates TLS, then talks plain HTTP
        // to this container. Laravel's trustProxies() resolves the '*' wildcard
        // into the *calling* IP, which is what Symfony's setTrustedProxies()
        // actually understands - a literal ['*'] is silently ignored, so
        // X-Forwarded-Proto:https was dropped and route('login.post') rendered
        // as http://, which Chrome blocks with "form is not secure" and which
        // then 419s on CSRF. Do NOT replace this with a hand-rolled
        // setTrustedProxies(['*']) call.
        $middleware->trustProxies(at: '*', headers:
            \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR
            | \Illuminate\Http\Request::HEADER_X_FORWARDED_HOST
            | \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT
            | \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO
            | \Illuminate\Http\Request::HEADER_X_FORWARDED_PREFIX
        );

        // Pure API backend: walang Blade pages, kaya wala nang guest/auth page
        // redirects. Ang auth middleware ay sumasagot ng 401 JSON sa mga JSON
        // request, at ang named /login route (401 JSON din) ang fallback para
        // sa mga non-JSON na request tulad ng backup download links.
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Pure JSON API: lahat ng error (404, 500, validation atbp.) ay sumasagot
        // na JSON — hindi HTML error page. Kaya kahit saan ka tumama sa backend
        // habang nag-`php artisan serve`, WALANG makikitang UI/HTML mula sa Laravel.
        $exceptions->shouldRenderJsonWhen(fn () => true);
    })->create();

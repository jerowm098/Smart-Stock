<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
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

        // An authenticated user hitting a guest-only route (e.g. /login) is sent
        // to the surface their role allows, matching the post-login rule in
        // AuthController::redirectForRole():
        //   Admin -> dashboard, Staff (cashier) -> POS.
        $middleware->redirectUsersTo(function ($request) {
            $user = $request->user();

            if ($user?->isAdmin()) {
                return route('dashboard');
            }

            if ($user?->isCashier()) {
                return route('pos');
            }

            return route('home');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

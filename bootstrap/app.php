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
        // Render LB fix: palitan ang default Illuminate TrustProxies ng
        // App TrustProxies (proxies='*'). Huwag prepend/append — pag
        // dalawa ang TrustProxies, ang pangalawa (default, proxies=null)
        // ay nagre-reset sa [] at binubura ang '*' kaya https:// ay
        // nakikita bilang http:// (secure cookie + APP_URL mismatch = 500).
        $middleware->replace(
            \Illuminate\Http\Middleware\TrustProxies::class,
            \App\Http\Middleware\TrustProxies::class,
        );
        $middleware->redirectUsersTo('home');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

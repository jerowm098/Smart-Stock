<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Http\Request;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Force HTTPS when the request was forwarded over HTTPS (e.g. ngrok, Render, any reverse proxy).
        // This prevents the browser "form is not secure" warning by ensuring all generated
        // URLs use https:// scheme even when artisan serve itself runs on plain HTTP.
        if (request()->server('HTTP_X_FORWARDED_PROTO') === 'https'
            || request()->server('HTTPS') === 'on'
            || app()->environment('production')
        ) {
            URL::forceScheme('https');
        }
    }
}

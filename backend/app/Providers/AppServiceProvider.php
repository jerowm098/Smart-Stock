<?php

namespace App\Providers;

use App\Database\PostgresConnection;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Connection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->useNativePostgresBooleans();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Defense in depth against plain-HTTP URLs behind Render's TLS proxy.
        // trustProxies() already makes $request->isSecure() true, so this is
        // normally a no-op - but if the X-Forwarded-Proto chain ever breaks,
        // every generated URL (CSRF cookie domain, signed URLs, redirects)
        // stays on https instead of silently degrading to http://, which
        // produced Chrome "form is not secure" blocks + 419 on CSRF.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        $this->configureRateLimiting();
    }

    /**
     * Security hardening (OWASP ZAP / OWASP ASVS V2.2): brute-force at
     * credential-stuffing protection sa POST /api/auth/login.
     *
     * Baseline ZAP run: 10 sunod-sunod na maling login = 10x 401, walang 429.
     * Ngayon, tatlong sabay na limit (alinmang maabot = 429):
     *   - 5 attempts/min bawat username + IP   (normal na typo allowance)
     *   - 10 attempts/min bawat username        (kahit iba-ibang IP)
     *   - 30 attempts/min bawat IP              (password spraying)
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('login', function (Request $request) {
            $identifier = Str::lower((string) ($request->input('username') ?: $request->input('email')));

            $tooMany = fn (Request $request, array $headers) => response()->json([
                'message' => 'Too many login attempts. Please wait a minute and try again.',
            ], 429, $headers);

            return [
                Limit::perMinute(5)->by('login:'.$identifier.'|'.$request->ip())->response($tooMany),
                Limit::perMinute(10)->by('login-user:'.$identifier)->response($tooMany),
                Limit::perMinute(30)->by('login-ip:'.$request->ip())->response($tooMany),
            ];
        });
    }

    /**
     * Keep PostgreSQL BOOLEAN columns working with Eloquent.
     *
     * This schema stores two real boolean columns (`suppliers.is_active` and
     * `alerts.is_resolved`). Laravel rewrites boolean bindings to integers,
     * which PostgreSQL rejects, so a dedicated connection class converts them
     * to native literals instead. Fixing it here keeps every model, seeder
     * and query free of DB::raw() workarounds.
     */
    protected function useNativePostgresBooleans(): void
    {
        Connection::resolverFor(
            'pgsql',
            fn ($connection, $database, $prefix, $config) => new PostgresConnection(
                $connection,
                $database,
                $prefix,
                $config,
            ),
        );
    }
}

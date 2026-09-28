<?php

namespace App\Providers;

use App\Database\PostgresConnection;
use Illuminate\Database\Connection;
use Illuminate\Support\ServiceProvider;

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
        //
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

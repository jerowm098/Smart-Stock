<?php

use Illuminate\Support\Str;
use Pdo\Mysql;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Local development runs on XAMPP MariaDB (DB_CONNECTION=mysql,
    | DB_DATABASE=smartstock_db). Production targets Supabase
    | PostgreSQL (DB_CONNECTION=pgsql).
    |
    | Supabase mode: set DB_CONNECTION=pgsql + SUPABASE_DB_HOST/
    | SUPABASE_DB_PORT/SUPABASE_DB_DATABASE/SUPABASE_DB_USERNAME/
    | SUPABASE_DB_PASSWORD/SUPABASE_DB_SSLMODE=require
    | Kunin sa Supabase Dashboard > Connect > Session pooler string.
    | Hindi ito SUPABASE_URL + ANON_KEY (pang-JS client lang yun).
    |
    | NAMING RULE: kapag Supabase, SUPABASE_DB_* palagi — same names sa
    | local .env at sa Render env vars (kaya walang kalituhan kung alin
    | ang MySQL credentials at kung alin ang Supabase).
    |
    | DALAWANG MODE (parehong Supabase, magkaibang host/user/port):
    |  1) DIRECT (pang-migrate sa IPv6-ready network):
    |     SUPABASE_DB_HOST=db.xxxxx.supabase.co  PORT=5432  USERNAME=postgres
    |  2) POOLER (pang-Globe IPv4 / pang-prod Render):
    |     SUPABASE_DB_HOST=aws-0-ap-southeast-1.pooler.supabase.com
    |     PORT=6543 (Transaction) o 5432 (Session)
    |     USERNAME=postgres.xxxxx  (may tuldok + project ref!)
    |
    */

    'default' => env('DB_CONNECTION', 'pgsql'),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | pgsql = live Supabase (primary).
    | sqlite = testing only (phpunit :memory:), hindi primary.
    |
    */

    'connections' => [

        'pgsql' => [
            'driver' => 'pgsql',
            // Supabase connection. SINASADYANG SUPABASE_DB_* ang mga variable
            // names (hindi DB_*) para pareho ang naming sa local .env at sa
            // Render: kung Supabase, SUPABASE_DB_* palagi. Ang DB_* keys ay
            // para lang sa local XAMPP MySQL (DB_CONNECTION=mysql).
            'url' => env('SUPABASE_DB_URL'),
            'host' => env('SUPABASE_DB_HOST', '127.0.0.1'),
            'port' => env('SUPABASE_DB_PORT', '5432'),
            'database' => env('SUPABASE_DB_DATABASE', 'postgres'),
            'username' => env('SUPABASE_DB_USERNAME', 'postgres'),
            'password' => env('SUPABASE_DB_PASSWORD', ''),
            'charset' => env('DB_CHARSET', 'utf8'),
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => env('SUPABASE_DB_SSLMODE', 'require'),
            // Supabase pooler (Supavisor) fix:
            // Transaction/Session pooler ayaw sa native PREPARE,
            // kaya emulate prepares + maikling timeout.
            'options' => extension_loaded('pdo_pgsql') ? array_filter([
                PDO::ATTR_EMULATE_PREPARES => true,
                PDO::ATTR_TIMEOUT => 10,
            ]) : [],
        ],

        'supabase' => [
            // Read-only source used by `php artisan db:copy-from-supabase`.
            // Local development runs on XAMPP MariaDB (see the mysql block);
            // this connection keeps a handle on the hosted PostgreSQL data so
            // it can be copied down without touching the original rows.
            'driver' => 'pgsql',
            'host' => env('SUPABASE_DB_HOST', ''),
            'port' => env('SUPABASE_DB_PORT', '5432'),
            'database' => env('SUPABASE_DB_DATABASE', 'postgres'),
            'username' => env('SUPABASE_DB_USERNAME', 'postgres'),
            'password' => env('SUPABASE_DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => env('SUPABASE_DB_SSLMODE', 'require'),
            'options' => extension_loaded('pdo_pgsql') ? array_filter([
                PDO::ATTR_EMULATE_PREPARES => true,
                PDO::ATTR_TIMEOUT => 30,
            ]) : [],
        ],

        'sqlite' => [
            // Testing only: ginagamit ng phpunit.xml (DB_DATABASE=:memory:).
            // Hindi ito primary database. Huwag gamitin sa .env o render.yaml.
            'driver' => 'sqlite',
            'url' => env('DB_URL'),
            'database' => env('DB_DATABASE', ':memory:'),
            'prefix' => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
            'busy_timeout' => null,
            'journal_mode' => null,
            'synchronous' => null,
            'transaction_mode' => 'DEFERRED',
        ],

        'mysql' => [
            'driver' => 'mysql',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => env('DB_CHARSET', 'utf8mb4'),
            'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                (PHP_VERSION_ID >= 80500 ? Mysql::ATTR_SSL_CA : PDO::MYSQL_ATTR_SSL_CA) => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'mariadb' => [
            'driver' => 'mariadb',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => env('DB_CHARSET', 'utf8mb4'),
            'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                (PHP_VERSION_ID >= 80500 ? Mysql::ATTR_SSL_CA : PDO::MYSQL_ATTR_SSL_CA) => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'sqlsrv' => [
            'driver' => 'sqlsrv',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', '1433'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => env('DB_CHARSET', 'utf8'),
            'prefix' => '',
            'prefix_indexes' => true,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    */

    'migrations' => [
        'table' => 'migrations',
        'update_date_on_publish' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    */

    'redis' => [

        'client' => env('REDIS_CLIENT', 'phpredis'),

        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix' => env('REDIS_PREFIX', Str::slug((string) env('APP_NAME', 'laravel')).'-database-'),
            'persistent' => env('REDIS_PERSISTENT', false),
        ],

        'default' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
            'max_retries' => env('REDIS_MAX_RETRIES', 3),
            'backoff_algorithm' => env('REDIS_BACKOFF_ALGORITHM', 'decorrelated_jitter'),
            'backoff_base' => env('REDIS_BACKOFF_BASE', 100),
            'backoff_cap' => env('REDIS_BACKOFF_CAP', 1000),
        ],

        'cache' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
            'max_retries' => env('REDIS_MAX_RETRIES', 3),
            'backoff_algorithm' => env('REDIS_BACKOFF_ALGORITHM', 'decorrelated_jitter'),
            'backoff_base' => env('REDIS_BACKOFF_BASE', 100),
            'backoff_cap' => env('REDIS_BACKOFF_CAP', 1000),
        ],

    ],

];

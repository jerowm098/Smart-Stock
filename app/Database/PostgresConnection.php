<?php

namespace App\Database;

use Illuminate\Database\PostgresConnection as BasePostgresConnection;

/**
 * PostgreSQL connection that keeps boolean bindings native.
 *
 * Laravel's base Connection::prepareBindings() rewrites every boolean into an
 * integer (true => 1, false => 0). That is correct for MySQL/SQLite, but a
 * real PostgreSQL BOOLEAN column refuses to be compared with an integer, so
 * every query touching one failed with either:
 *
 *   write: column "is_active" is of type boolean but expression is of type integer
 *   read:  operator does not exist: boolean = integer
 *
 * Rather than sprinkling DB::raw() across the codebase, booleans are bound as
 * their native PostgreSQL literals ('t' / 'f') right where Laravel rewrites
 * them. Registered in App\Providers\AppServiceProvider.
 */
class PostgresConnection extends BasePostgresConnection
{
    /**
     * Prepare the bindings for the database connection.
     *
     * Identical to the parent implementation except that booleans become
     * native PostgreSQL literals instead of integers.
     *
     * @param  array<int, mixed>  $bindings
     * @return array<int, mixed>
     */
    public function prepareBindings(array $bindings)
    {
        $driver = $this->getDriverName();

        foreach ($bindings as $key => $value) {
            if (is_bool($value)) {
                $bindings[$key] = $value ? 'true' : 'false';
            } elseif (is_float($value)) {
                // Keep the numeric representation stable across drivers.
                $bindings[$key] = match ($driver) {
                    'pgsql' => (string) $value,
                    default => $value,
                };
            }
        }

        return $bindings;
    }
}

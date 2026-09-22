<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * SS-16: Adds a unique `username` column to the users table so that
     * duplicate username registrations can be prevented. Existing users
     * are backfilled with a username derived from their email address.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 255)->nullable()->after('name');
        });

        // Backfill existing users with a username derived from their email
        // so the unique constraint can be applied safely.
        $users = DB::table('users')->whereNull('username')->get();
        foreach ($users as $user) {
            $base = strtolower((string) explode('@', $user->email)[0]);
            $username = $base ?: 'user_'.$user->id;
            $candidate = $username;
            $suffix = 2;
            while (DB::table('users')->where('username', $candidate)->where('id', '!=', $user->id)->exists()) {
                $candidate = $username.'_'.$suffix++;
            }
            DB::table('users')->where('id', $user->id)->update(['username' => $candidate]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 255)->unique()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn('username');
        });
    }
};

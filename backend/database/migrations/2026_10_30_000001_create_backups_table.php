<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * SS-39: keep backup dumps in the database.
     *
     * Render (and most container hosts) use an ephemeral filesystem — anything
     * written to storage/ is wiped on every deploy or restart, so backups made
     * on the deployed site disappeared. Storing the dump in the database means
     * the Backups page keeps working after a redeploy. A copy is still written
     * to storage/app/private/backups for local use.
     */
    public function up(): void
    {
        if (Schema::hasTable('backups')) {
            return;
        }

        Schema::create('backups', function (Blueprint $table) {
            $table->id();
            $table->string('filename')->unique();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->longText('content');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backups');
    }
};

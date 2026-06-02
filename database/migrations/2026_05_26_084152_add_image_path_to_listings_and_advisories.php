<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            // Relative path inside the `public` disk, e.g. `listings/abc.jpg`.
            // Nullable — listings without uploaded photos fall back to the
            // variety's gradient tile as before.
            $table->string('image_path', 255)->nullable()->after('contact_phone');
        });

        Schema::table('advisories', function (Blueprint $table) {
            $table->string('image_path', 255)->nullable()->after('body');
        });
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->dropColumn('image_path');
        });
        Schema::table('advisories', function (Blueprint $table) {
            $table->dropColumn('image_path');
        });
    }
};

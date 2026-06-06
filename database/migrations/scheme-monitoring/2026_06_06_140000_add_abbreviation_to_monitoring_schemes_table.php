<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('monitoring_schemes', function (Blueprint $table): void {
            // Nullable — the model falls back to auto-generated initials
            // when blank, so existing rows don't need backfill.
            $table->string('abbreviation', 12)->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('monitoring_schemes', function (Blueprint $table): void {
            $table->dropColumn('abbreviation');
        });
    }
};

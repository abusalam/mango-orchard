<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('telemetry_events', function (Blueprint $table) {
            // Laravel session IDs are 40 chars by default; 64 leaves room for
            // custom session-id hashers or migrating to a longer scheme.
            // Indexed because the most common forensic query is "show me every
            // event from this session".
            $table->string('session_id', 64)->nullable()->after('user_agent')->index();
        });
    }

    public function down(): void
    {
        Schema::table('telemetry_events', function (Blueprint $table) {
            $table->dropIndex(['session_id']);
            $table->dropColumn('session_id');
        });
    }
};

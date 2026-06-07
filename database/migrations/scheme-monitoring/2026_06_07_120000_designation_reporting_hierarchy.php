<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Move the reporting hierarchy from user-level (monitoring_profiles.parent_user_id)
 * to designation-level (monitoring_designations.parent_id). A user's effective
 * reporting parents are now derived: the users holding any designation that is
 * the parent of any designation the user holds.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('monitoring_designations', function (Blueprint $table): void {
            $table->foreignId('parent_id')
                ->nullable()
                ->after('level')
                ->constrained('monitoring_designations')
                ->nullOnDelete();
        });

        Schema::table('monitoring_profiles', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('parent_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('monitoring_profiles', function (Blueprint $table): void {
            $table->foreignId('parent_user_id')
                ->nullable()
                ->after('user_id')
                ->constrained('users')
                ->nullOnDelete();
        });

        Schema::table('monitoring_designations', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('parent_id');
        });
    }
};

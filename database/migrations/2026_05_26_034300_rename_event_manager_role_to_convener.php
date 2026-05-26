<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/**
 * Renames the `event-manager` role to `convener` in any database that has
 * the old row. Idempotent — bails cleanly if the old role doesn't exist
 * (fresh installs are seeded with the new name from the start).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('roles')
            ->where('name', 'event-manager')
            ->where('guard_name', 'web')
            ->update(['name' => 'convener', 'updated_at' => now()]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        DB::table('roles')
            ->where('name', 'convener')
            ->where('guard_name', 'web')
            ->update(['name' => 'event-manager', 'updated_at' => now()]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};

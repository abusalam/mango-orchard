<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        // In-place rename so model_has_roles assignments stay valid — dropping
        // and re-creating the role would orphan every "editor" user.
        DB::table('roles')->where('name', 'editor')->update(['name' => 'curator']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        DB::table('roles')->where('name', 'curator')->update(['name' => 'editor']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};

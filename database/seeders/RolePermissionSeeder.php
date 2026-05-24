<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Permissions;
use App\Roles;
use App\Telemetry\Telemetry;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Suppress telemetry just for this seeder — it's infrastructural
        // bootstrap, not user activity worth recording.
        Telemetry::withoutRecording(function (): void {
            foreach (array_keys(Permissions::ALL) as $name) {
                Permission::findOrCreate($name, 'web');
            }

            $superuser = Role::findOrCreate(Roles::SUPERUSER, 'web');
            $superuser->syncPermissions(array_keys(Permissions::ALL));

            $editor = Role::findOrCreate(Roles::EDITOR, 'web');
            $editor->syncPermissions([Permissions::VARIETIES_MANAGE]);

            $grower = Role::findOrCreate(Roles::GROWER, 'web');
            $grower->syncPermissions([Permissions::LISTINGS_MANAGE]);

            $impersonator = Role::findOrCreate(Roles::IMPERSONATOR, 'web');
            $impersonator->syncPermissions([Permissions::USERS_IMPERSONATE]);

            Role::findOrCreate(Roles::VIEWER, 'web');
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}

<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Permissions;
use App\Roles;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (array_keys(Permissions::ALL) as $name) {
            Permission::findOrCreate($name, 'web');
        }

        $superuser = Role::findOrCreate(Roles::SUPERUSER, 'web');
        $superuser->syncPermissions(array_keys(Permissions::ALL));

        $editor = Role::findOrCreate(Roles::EDITOR, 'web');
        $editor->syncPermissions([Permissions::VARIETIES_MANAGE]);

        Role::findOrCreate(Roles::VIEWER, 'web');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}

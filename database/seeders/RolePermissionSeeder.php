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

            $curator = Role::findOrCreate(Roles::CURATOR, 'web');
            $curator->syncPermissions([Permissions::VARIETIES_MANAGE, Permissions::MPCP_MANAGE]);

            $grower = Role::findOrCreate(Roles::GROWER, 'web');
            $grower->syncPermissions([Permissions::LISTINGS_MANAGE]);

            $impersonator = Role::findOrCreate(Roles::IMPERSONATOR, 'web');
            $impersonator->syncPermissions([Permissions::USERS_IMPERSONATE]);

            $convener = Role::findOrCreate(Roles::CONVENER, 'web');
            $convener->syncPermissions([Permissions::EVENTS_MANAGE]);

            $advisor = Role::findOrCreate(Roles::ADVISOR, 'web');
            $advisor->syncPermissions([Permissions::ADVISORIES_MANAGE]);

            // Scheme/Project Monitoring module
            $monitor = Role::findOrCreate(Roles::MONITOR, 'web');
            $monitor->syncPermissions([Permissions::MONITORING_VIEW]);

            $monitorAdmin = Role::findOrCreate(Roles::MONITOR_ADMIN, 'web');
            $monitorAdmin->syncPermissions([
                Permissions::MONITORING_VIEW,
                Permissions::MONITORING_MANAGE,
            ]);

            // Mango Orchard module membership — gates self-apply for
            // grower / curator / convener / advisor. No permissions of its
            // own; it's the enrolment flag.
            Role::findOrCreate(Roles::MANGO_ORCHARD_MEMBER, 'web');

            Role::findOrCreate(Roles::VIEWER, 'web');
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}

<?php

namespace Database\Seeders;

use App\Models\User;
use App\Roles;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);

        // Seeded super user. Idempotent via firstOrCreate so re-running
        // db:seed without a fresh migrate doesn't blow up on the unique
        // email constraint. The HTTP register listener
        // (AssignSuperuserToFirstUser) doesn't fire for factory / direct
        // creates, so the superuser role is assigned explicitly.
        $admin = User::firstOrCreate(
            ['email' => 'admin@malda.gov.in'],
            [
                'name' => 'Malda Admin',
                'password' => Hash::make('Malda@2026'),
                'email_verified_at' => now(),
                'onboarding_completed_at' => now(),
                'notify_seasonal' => true,
                'subscribe_newsletter' => false,
            ],
        );
        if (! $admin->hasRole(Roles::SUPERUSER)) {
            $admin->assignRole(Roles::SUPERUSER);
        }

        $this->call(EmailTemplateSeeder::class);
        $this->call(MangoVarietySeeder::class);
        $this->call(EventSeeder::class);
        $this->call(AdvisorySeeder::class);
        $this->call(SchemeMonitoringSeeder::class);
        $this->call(MpcpSeeder::class);
        $this->call(GallerySeeder::class);
    }
}

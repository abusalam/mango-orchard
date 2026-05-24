<?php

declare(strict_types=1);

use App\Models\MangoVariety;
use App\Roles;
use Database\Seeders\DatabaseSeeder;
use Spatie\Permission\Models\Role;

it('runs the full DatabaseSeeder without error and populates all expected rows', function () {
    $this->seed(DatabaseSeeder::class);

    expect(MangoVariety::count())->toBe(12)
        ->and(MangoVariety::firstWhere('name', 'Alphonso')->slug)->toBe('alphonso')
        ->and(Role::findByName(Roles::SUPERUSER))->not->toBeNull()
        ->and(Role::findByName(Roles::EDITOR))->not->toBeNull()
        ->and(Role::findByName(Roles::VIEWER))->not->toBeNull();
});

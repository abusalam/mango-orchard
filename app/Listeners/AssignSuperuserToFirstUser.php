<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\User;
use App\Roles;
use Illuminate\Auth\Events\Registered;
use Spatie\Permission\Models\Role;

class AssignSuperuserToFirstUser
{
    public function handle(Registered $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $superuser = Role::where('name', Roles::SUPERUSER)->where('guard_name', 'web')->first();

        if ($superuser === null) {
            return;
        }

        if (! $superuser->users()->exists()) {
            $event->user->assignRole($superuser);
        }
    }
}

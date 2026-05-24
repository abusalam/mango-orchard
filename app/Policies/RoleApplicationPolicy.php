<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\RoleApplication;
use App\Models\User;
use App\Permissions;

class RoleApplicationPolicy
{
    /** Cancelling: only the applicant, and only while still pending. */
    public function cancel(User $user, RoleApplication $application): bool
    {
        return $user->id === $application->user_id && $application->isPending();
    }

    /** Reviewing (approve/reject): anyone with users.manage. */
    public function review(User $user): bool
    {
        return $user->can(Permissions::USERS_MANAGE);
    }
}

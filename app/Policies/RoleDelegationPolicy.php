<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\RoleDelegation;
use App\Models\User;
use App\Permissions;

class RoleDelegationPolicy
{
    /**
     * Who may revoke an active delegation:
     *   - the original delegator (taking back what they granted),
     *   - the recipient (renouncing the role),
     *   - any admin with users.manage (oversight / cleanup).
     * A revoked delegation can't be revoked again.
     */
    public function revoke(User $user, RoleDelegation $delegation): bool
    {
        if (! $delegation->isActive()) {
            return false;
        }

        return $user->id === $delegation->delegated_by
            || $user->id === $delegation->user_id
            || $user->can(Permissions::USERS_MANAGE);
    }
}

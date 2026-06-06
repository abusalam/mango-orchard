<?php

declare(strict_types=1);

namespace App\Modules\MangoOrchard\Policies;

use App\Modules\MangoOrchard\Models\Listing;
use App\Models\User;
use App\Permissions;
use App\Roles;

class ListingPolicy
{
    /** Gated behind the listings.manage permission (held by grower / superuser roles). */
    public function create(User $user): bool
    {
        return $user->can(Permissions::LISTINGS_MANAGE);
    }

    public function update(User $user, Listing $listing): bool
    {
        return $user->id === $listing->user_id || $user->hasRole(Roles::SUPERUSER);
    }

    public function delete(User $user, Listing $listing): bool
    {
        return $this->update($user, $listing);
    }
}

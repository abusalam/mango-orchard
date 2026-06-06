<?php

declare(strict_types=1);

namespace App\Modules\SchemeMonitoring\Policies;

use App\Models\User;
use App\Modules\SchemeMonitoring\Hierarchy;
use App\Modules\SchemeMonitoring\Models\Scheme;
use App\Permissions;
use App\Roles;

class SchemePolicy
{
    public function __construct(private Hierarchy $hierarchy) {}

    /**
     * Superusers and monitoring-managers bypass per-row checks.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole(Roles::SUPERUSER)) {
            return true;
        }
        if ($user->can(Permissions::MONITORING_MANAGE)) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can(Permissions::MONITORING_VIEW);
    }

    public function view(User $user, Scheme $scheme): bool
    {
        if (! $user->can(Permissions::MONITORING_VIEW)) {
            return false;
        }

        // A non-manager sees a scheme if its owner is themselves or any of
        // their hierarchy descendants.
        $visible = $this->hierarchy->descendantUserIds($user->id);

        return in_array($scheme->owner_id, $visible, true);
    }

    public function create(User $user): bool
    {
        return $user->can(Permissions::MONITORING_VIEW);
    }

    public function update(User $user, Scheme $scheme): bool
    {
        return $this->view($user, $scheme) && $scheme->owner_id === $user->id;
    }

    public function delete(User $user, Scheme $scheme): bool
    {
        return $this->update($user, $scheme);
    }
}

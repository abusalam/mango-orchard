<?php

declare(strict_types=1);

namespace App\Modules\SchemeMonitoring\Policies;

use App\Models\User;
use App\Modules\SchemeMonitoring\Hierarchy;
use App\Modules\SchemeMonitoring\Models\Task;
use App\Permissions;
use App\Roles;

class TaskPolicy
{
    public function __construct(private Hierarchy $hierarchy) {}

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

    public function view(User $user, Task $task): bool
    {
        if (! $user->can(Permissions::MONITORING_VIEW)) {
            return false;
        }

        $visible = $this->hierarchy->descendantUserIds($user->id);

        return in_array($task->assigned_to, $visible, true);
    }

    public function create(User $user): bool
    {
        return $user->can(Permissions::MONITORING_VIEW);
    }

    /**
     * Update is allowed for the assignee themselves OR anyone above them
     * in the hierarchy. Below-them users cannot reach up.
     */
    public function update(User $user, Task $task): bool
    {
        return $this->view($user, $task);
    }

    /**
     * Status-flip (the inline dropdown on the dashboard) is intentionally
     * NARROWER than `update` — only the person the task is assigned to can
     * advance it through the pipeline. Supervisors observe via the
     * dashboard; the assignee owns the state machine. Superusers and
     * monitoring-managers still bypass via `before()`.
     */
    public function updateStatus(User $user, Task $task): bool
    {
        return $user->can(Permissions::MONITORING_VIEW)
            && $task->assigned_to === $user->id;
    }

    public function delete(User $user, Task $task): bool
    {
        // Stricter than update — only the task's creator or assignee can
        // delete; supervisors can edit but not destroy.
        return $user->can(Permissions::MONITORING_VIEW)
            && ($task->created_by === $user->id || $task->assigned_to === $user->id);
    }
}

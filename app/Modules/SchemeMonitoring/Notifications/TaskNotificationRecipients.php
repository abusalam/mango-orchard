<?php

declare(strict_types=1);

namespace App\Modules\SchemeMonitoring\Notifications;

use App\Models\User;
use App\Modules\SchemeMonitoring\Models\Task;
use Illuminate\Support\Collection;

/**
 * Resolves the set of users who should be emailed about a task event.
 * Default rules: assignee + creator, minus the actor (whoever triggered
 * the change — they already know). If `$previousAssigneeId` is passed,
 * that user is also included so a freshly-detached assignee gets the
 * heads-up that the task is no longer theirs.
 *
 * Centralising the lookup here keeps the controller small and gives one
 * obvious place to extend later (e.g. notify supervisors up the
 * designation chain).
 */
class TaskNotificationRecipients
{
    /**
     * @return Collection<int, User>
     */
    public static function for(Task $task, ?User $actor = null, ?int $previousAssigneeId = null): Collection
    {
        $ids = collect([$task->assigned_to, $task->created_by, $previousAssigneeId])
            ->filter()
            ->unique()
            ->reject(fn ($id) => $actor !== null && $id === $actor->id);

        if ($ids->isEmpty()) {
            return collect();
        }

        return User::whereIn('id', $ids->all())->get();
    }
}

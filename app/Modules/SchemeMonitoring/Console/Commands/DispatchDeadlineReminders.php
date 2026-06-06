<?php

declare(strict_types=1);

namespace App\Modules\SchemeMonitoring\Console\Commands;

use App\Modules\SchemeMonitoring\Models\Task;
use App\Modules\SchemeMonitoring\Notifications\TaskDeadlineReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

/**
 * Walks all open tasks once a day and dispatches reminder notifications:
 *
 *   T-7 / T-3 / T-1   →   exactly N days before deadline
 *   overdue           →   any open task whose deadline has passed
 *
 * Upcoming reminders fire only when (deadline - today) === N, so a daily
 * cron naturally sends each window exactly once. Overdue uses
 * `last_overdue_reminder_at` as a same-day guard so re-runs of the cron
 * don't double-nag.
 */
class DispatchDeadlineReminders extends Command
{
    protected $signature = 'monitoring:dispatch-deadline-reminders {--dry-run : Print counts without sending notifications}';

    protected $description = 'Send deadline reminders (T-7/T-3/T-1) and overdue nags for open monitoring tasks.';

    public function handle(): int
    {
        $today = now()->startOfDay();
        $dryRun = (bool) $this->option('dry-run');

        $sent = ['t-7' => 0, 't-3' => 0, 't-1' => 0, 'overdue' => 0];

        foreach ([7, 3, 1] as $window) {
            $target = $today->copy()->addDays($window);
            $tasks = Task::query()
                ->whereIn('status', Task::OPEN_STATUSES)
                ->whereDate('deadline', $target)
                ->with(['assignee', 'scheme'])
                ->get();

            foreach ($tasks as $task) {
                if (! $task->assignee) {
                    continue;
                }
                if (! $dryRun) {
                    Notification::send($task->assignee, new TaskDeadlineReminder($task, "t-{$window}", $window));
                }
                $sent["t-{$window}"]++;
            }
        }

        $overdueTasks = Task::query()
            ->whereIn('status', Task::OPEN_STATUSES)
            ->whereDate('deadline', '<', $today)
            ->where(function ($q) use ($today): void {
                $q->whereNull('last_overdue_reminder_at')
                    ->orWhereDate('last_overdue_reminder_at', '<', $today);
            })
            ->with(['assignee', 'scheme'])
            ->get();

        foreach ($overdueTasks as $task) {
            if (! $task->assignee) {
                continue;
            }
            if (! $dryRun) {
                Notification::send($task->assignee, new TaskDeadlineReminder($task, 'overdue'));
                $task->forceFill(['last_overdue_reminder_at' => now()])->save();
            }
            $sent['overdue']++;
        }

        foreach ($sent as $kind => $count) {
            $this->info("{$kind}: {$count} reminder".($count === 1 ? '' : 's').($dryRun ? ' (dry run)' : ' sent'));
        }

        return self::SUCCESS;
    }
}

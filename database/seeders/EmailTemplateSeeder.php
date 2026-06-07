<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;

/**
 * Seeds the editable email templates with the same copy that used to live
 * in the notification classes. Idempotent via updateOrCreate keyed on the
 * stable `key` column so re-seeding a live DB doesn't wipe admin edits —
 * the seeder only touches rows whose key is in the list AND that don't
 * already exist.
 *
 * To force-restore the default copy for a template, delete its row from
 * email_templates and re-run this seeder.
 */
class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->templates() as $row) {
            // firstOrCreate (not updateOrCreate) — preserves any admin edits.
            EmailTemplate::firstOrCreate(['key' => $row['key']], $row);
        }
    }

    /**
     * @return list<array{key: string, name: string, description: string, subject: string, body: string}>
     */
    private function templates(): array
    {
        return [
            [
                'key' => 'task.status_changed',
                'name' => 'Task — status changed',
                'description' => 'Sent when a task’s status is updated. Recipients: assignee + creator, minus the actor.',
                'subject' => 'Status changed: {task_title}',
                'body' => <<<'MD'
{actor_name} changed the status of a task you're involved in.

**Task:** {task_title}

**Scheme:** {scheme_name}

**Status:** {old_status} → **{new_status}**

**Deadline:** {deadline}
MD,
            ],
            [
                'key' => 'task.updated',
                'name' => 'Task — fields updated',
                'description' => 'Sent when a task is edited (title / description / deadline / priority / assignee).',
                'subject' => 'Task updated: {task_title}',
                'body' => <<<'MD'
{actor_name} updated a task you're involved in.

**Task:** {task_title}

**Scheme:** {scheme_name}

**Deadline:** {deadline}
MD,
            ],
            [
                'key' => 'task.deadline_reminder.t-7',
                'name' => 'Deadline reminder — 7 days out',
                'description' => 'Sent to assignees when a task is exactly 7 days from its deadline.',
                'subject' => 'Reminder: {task_title} is due in 7 days',
                'body' => <<<'MD'
This is a reminder that the task below is due in 7 days.

**Scheme:** {scheme_name}

**Deadline:** {deadline}
MD,
            ],
            [
                'key' => 'task.deadline_reminder.t-3',
                'name' => 'Deadline reminder — 3 days out',
                'description' => 'Sent to assignees when a task is exactly 3 days from its deadline.',
                'subject' => 'Reminder: {task_title} is due in 3 days',
                'body' => <<<'MD'
This is a reminder that the task below is due in 3 days.

**Scheme:** {scheme_name}

**Deadline:** {deadline}
MD,
            ],
            [
                'key' => 'task.deadline_reminder.t-1',
                'name' => 'Deadline reminder — 1 day out',
                'description' => 'Sent to assignees when a task is exactly 1 day from its deadline.',
                'subject' => 'Reminder: {task_title} is due tomorrow',
                'body' => <<<'MD'
This is a reminder that the task below is due in 1 day.

**Scheme:** {scheme_name}

**Deadline:** {deadline}
MD,
            ],
            [
                'key' => 'task.deadline_reminder.overdue',
                'name' => 'Deadline reminder — overdue',
                'description' => 'Daily nag while an assignee’s task is past its deadline and still open.',
                'subject' => 'Overdue: {task_title}',
                'body' => <<<'MD'
This task has passed its deadline and is still open. Please update its status or reschedule.

**Scheme:** {scheme_name}

**Deadline:** {deadline}
MD,
            ],
            [
                'key' => 'mango.variety_in_season',
                'name' => 'Variety in season',
                'description' => 'Sent to subscribers (notify_seasonal=true) when a variety enters its peak month.',
                'subject' => '{variety_name} is in season',
                'body' => <<<'MD'
**{variety_name}** ({variety_origin}) is entering its peak window.

Season runs **{season_window}** — open the variety page below for flavor notes, tasting tips, and listings from growers offering it this year.
MD,
            ],
        ];
    }
}

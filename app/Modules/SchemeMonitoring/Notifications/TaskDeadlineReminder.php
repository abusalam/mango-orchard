<?php

declare(strict_types=1);

namespace App\Modules\SchemeMonitoring\Notifications;

use App\Mail\EmailTemplateRenderer;
use App\Modules\SchemeMonitoring\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Single notification class covering all four reminder windows:
 *   - t-7 / t-3 / t-1 — heads-up that a deadline is approaching
 *   - overdue — task is past its deadline and still open
 *
 * Email copy is sourced from one of four editable templates keyed by
 * `task.deadline_reminder.{kind}` so each window can be tuned
 * separately. Both email + database channels fire so the assignee
 * gets an inbox copy and an in-app indicator they can dismiss.
 */
class TaskDeadlineReminder extends Notification
{
    use Queueable;

    public function __construct(
        public Task $task,
        public string $kind,
        public ?int $daysUntil = null,
    ) {}

    /**
     * @return list<string>
     */
    public function via(): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $task = $this->task;
        $vars = [
            'task_title' => $task->title,
            'scheme_name' => $task->scheme?->name ?? '—',
            'deadline' => $task->deadline->format('d M Y'),
            'days_until' => (string) ($this->daysUntil ?? ''),
        ];

        $msg = EmailTemplateRenderer::render($this->templateKey(), $vars, $notifiable->name)
            ?? $this->fallbackMail($notifiable->name, $vars);

        $msg->action('Open task', url(route('monitoring.tasks.edit', $task)));

        if ($this->kind === 'overdue') {
            $msg->error();
        }

        return $msg;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'task_id' => $this->task->id,
            'task_title' => $this->task->title,
            'scheme_id' => $this->task->scheme_id,
            'kind' => $this->kind,
            'days_until' => $this->daysUntil,
            'deadline' => $this->task->deadline->toDateString(),
        ];
    }

    /**
     * Per-kind template — admins can edit the four reminder windows
     * independently.
     */
    public function templateKey(): string
    {
        return 'task.deadline_reminder.'.$this->kind;
    }

    /**
     * @return array<string, string>
     */
    public static function availablePlaceholders(): array
    {
        return [
            'task_title' => 'Title of the task.',
            'scheme_name' => 'Name of the parent scheme.',
            'deadline' => 'Task deadline formatted as "dd MMM yyyy".',
            'days_until' => 'Number of days until the deadline (empty for the overdue template).',
        ];
    }

    /**
     * @param  array<string, scalar>  $vars
     */
    private function fallbackMail(string $recipientName, array $vars): MailMessage
    {
        $subject = $this->kind === 'overdue'
            ? "Overdue: {$vars['task_title']}"
            : "Reminder: {$vars['task_title']} is due in {$vars['days_until']} day".($vars['days_until'] === '1' ? '' : 's');
        $line = $this->kind === 'overdue'
            ? 'This task has passed its deadline and is still open. Please update its status or reschedule.'
            : "This is a reminder that the task below is due in {$vars['days_until']} day".($vars['days_until'] === '1' ? '' : 's').'.';

        return (new MailMessage())
            ->subject($subject)
            ->greeting("Hi {$recipientName},")
            ->line($line)
            ->line("**Scheme:** {$vars['scheme_name']}")
            ->line("**Deadline:** {$vars['deadline']}");
    }
}

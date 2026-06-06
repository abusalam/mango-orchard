<?php

declare(strict_types=1);

namespace App\Modules\SchemeMonitoring\Notifications;

use App\Modules\SchemeMonitoring\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Single notification class covering all four reminder windows:
 *   - upcoming (T-7 / T-3 / T-1) — heads-up that a deadline is approaching
 *   - overdue — task is past its deadline and still open
 *
 * Both email + database channels fire so the assignee gets an inbox copy
 * and an in-app indicator they can dismiss.
 */
class TaskDeadlineReminder extends Notification
{
    use Queueable;

    public function __construct(
        public Task $task,
        public string $kind,
        public ?int $daysUntil = null,
    ) {}

    public function via(): array
    {
        return ['mail', 'database'];
    }

    public function toMail(): MailMessage
    {
        $msg = (new MailMessage())
            ->subject($this->subject())
            ->greeting("Hi {$this->task->assignee?->name},")
            ->line($this->body())
            ->line("**Scheme:** {$this->task->scheme?->name}")
            ->line("**Deadline:** {$this->task->deadline->format('d M Y')}")
            ->action('Open task', url(route('monitoring.tasks.edit', $this->task)));

        if ($this->kind === 'overdue') {
            $msg->error();
        }

        return $msg;
    }

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

    private function subject(): string
    {
        return match ($this->kind) {
            'overdue' => "Overdue: {$this->task->title}",
            default => "Reminder: {$this->task->title} is due in {$this->daysUntil} day".($this->daysUntil === 1 ? '' : 's'),
        };
    }

    private function body(): string
    {
        return match ($this->kind) {
            'overdue' => 'This task has passed its deadline and is still open. Please update its status or reschedule.',
            default => "This is a reminder that the task below is due in {$this->daysUntil} day".($this->daysUntil === 1 ? '' : 's').'.',
        };
    }
}

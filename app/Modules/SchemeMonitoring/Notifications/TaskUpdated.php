<?php

declare(strict_types=1);

namespace App\Modules\SchemeMonitoring\Notifications;

use App\Mail\EmailTemplateRenderer;
use App\Models\User;
use App\Modules\SchemeMonitoring\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent when a task is edited (title, description, deadline, priority,
 * assignee) — anything other than a pure status flip, which has its own
 * {@see TaskStatusChanged} notification.
 *
 * Editable copy is sourced from the `task.updated` template; the
 * "what changed" diff and attachment block are programmatic.
 *
 * Queued so the controller doesn't block on SMTP.
 */
class TaskUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    public const TEMPLATE_KEY = 'task.updated';

    /**
     * @param  array<string, array{from: mixed, to: mixed}>  $changes  field => {from, to}
     */
    public function __construct(
        public Task $task,
        public array $changes,
        public ?User $actor = null,
    ) {}

    /**
     * @return list<string>
     */
    public function via(): array
    {
        $channels = ['database'];
        if (app(\App\Settings\Settings::class)->mailEnabledForSchemeMonitoring()) {
            array_unshift($channels, 'mail');
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $task = $this->task;
        $vars = [
            'actor_name' => $this->actor?->name ?? 'A teammate',
            'task_title' => $task->title,
            'scheme_name' => $task->scheme?->name ?? '—',
            'deadline' => $task->deadline->format('d M Y'),
        ];

        $msg = EmailTemplateRenderer::render(self::TEMPLATE_KEY, $vars, $notifiable->name)
            ?? $this->fallbackMail($notifiable->name, $vars);

        if ($this->changes !== []) {
            $msg->line('**What changed:**');
            foreach ($this->changes as $field => $change) {
                $label = $this->labelFor($field);
                $from = $this->formatValue($field, $change['from']);
                $to = $this->formatValue($field, $change['to']);
                $msg->line("- **{$label}:** {$from} → {$to}");
            }
        }

        $attachments = $task->attachments()->get();
        if ($attachments->isNotEmpty()) {
            $msg->line('**Attachments:**');
            foreach ($attachments as $a) {
                $msg->line("- [{$a->original_name}]({$a->url()}) — {$a->humanSize()}");
            }
        }

        return $msg->action('Open task', url(route('monitoring.tasks.edit', $task)));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'kind' => 'task.updated',
            'task_id' => $this->task->id,
            'task_title' => $this->task->title,
            'scheme_id' => $this->task->scheme_id,
            'changes' => $this->changes,
            'actor_id' => $this->actor?->id,
            'actor_name' => $this->actor?->name,
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function availablePlaceholders(): array
    {
        return [
            'actor_name' => 'Display name of whoever triggered the edit.',
            'task_title' => 'Title of the task (post-edit).',
            'scheme_name' => 'Name of the parent scheme.',
            'deadline' => 'Task deadline formatted as "dd MMM yyyy".',
        ];
    }

    private function labelFor(string $field): string
    {
        return match ($field) {
            'title' => 'Title',
            'description' => 'Description',
            'deadline' => 'Deadline',
            'priority' => 'Priority',
            'assigned_to' => 'Assignee',
            'scheme_id' => 'Scheme',
            default => ucwords(str_replace('_', ' ', $field)),
        };
    }

    private function formatValue(string $field, mixed $value): string
    {
        if ($value === null || $value === '') {
            return '_(empty)_';
        }

        return match ($field) {
            'deadline' => $value instanceof \DateTimeInterface
                ? $value->format('d M Y')
                : (is_string($value) ? \Illuminate\Support\Carbon::parse($value)->format('d M Y') : (string) $value),
            'priority' => Task::PRIORITIES[$value] ?? (string) $value,
            'assigned_to' => optional(User::find($value))->name ?? "User #{$value}",
            'scheme_id' => optional(\App\Modules\SchemeMonitoring\Models\Scheme::find($value))->name ?? "Scheme #{$value}",
            default => (string) $value,
        };
    }

    /**
     * @param  array<string, scalar>  $vars
     */
    private function fallbackMail(string $recipientName, array $vars): MailMessage
    {
        return (new MailMessage())
            ->subject("Task updated: {$vars['task_title']}")
            ->greeting("Hi {$recipientName},")
            ->line("{$vars['actor_name']} updated a task you're involved in.")
            ->line("**Task:** {$vars['task_title']}")
            ->line("**Scheme:** {$vars['scheme_name']}")
            ->line("**Deadline:** {$vars['deadline']}");
    }
}

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
 * Sent when a task's status flips (pending → in_progress → completed,
 * cancelled, etc.). Recipients get both an email and an in-app record.
 * The actor (whoever triggered the change) is excluded by the caller
 * via {@see TaskNotificationRecipients}.
 *
 * Email copy is sourced from the editable `task.status_changed` template
 * in email_templates so admins can adjust wording without a deploy.
 * Structural extras (attachment list, action button, status-tinted
 * action bar) stay in code.
 *
 * Queued so the controller doesn't block on SMTP — workers drain via
 * `php artisan queue:work`.
 */
class TaskStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public const TEMPLATE_KEY = 'task.status_changed';

    public function __construct(
        public Task $task,
        public string $oldStatus,
        public string $newStatus,
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
            'old_status' => Task::STATUSES[$this->oldStatus] ?? $this->oldStatus,
            'new_status' => Task::STATUSES[$this->newStatus] ?? $this->newStatus,
            'deadline' => $task->deadline->format('d M Y'),
        ];

        $msg = EmailTemplateRenderer::render(self::TEMPLATE_KEY, $vars, $notifiable->name)
            ?? $this->fallbackMail($notifiable->name, $vars);

        // Attachments listed inline so the recipient knows what material
        // is on file. Newest first to match the dashboard view.
        $attachments = $task->attachments()->get();
        if ($attachments->isNotEmpty()) {
            $msg->line('**Attachments:**');
            foreach ($attachments as $a) {
                $msg->line("- [{$a->original_name}]({$a->url()}) — {$a->humanSize()}");
            }
        }

        $msg->action('Open task', url(route('monitoring.tasks.edit', $task)));

        if ($this->newStatus === Task::STATUS_CANCELLED) {
            $msg->error();
        } elseif ($this->newStatus === Task::STATUS_COMPLETED) {
            $msg->success();
        }

        return $msg;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'kind' => 'task.status_changed',
            'task_id' => $this->task->id,
            'task_title' => $this->task->title,
            'scheme_id' => $this->task->scheme_id,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'actor_id' => $this->actor?->id,
            'actor_name' => $this->actor?->name,
        ];
    }

    /**
     * Documented placeholders for the admin template editor.
     *
     * @return array<string, string>
     */
    public static function availablePlaceholders(): array
    {
        return [
            'actor_name' => 'Display name of whoever triggered the change ("A teammate" if anonymous).',
            'task_title' => 'Title of the task.',
            'scheme_name' => 'Name of the parent scheme, or "—" if orphaned.',
            'old_status' => 'Human label of the previous status (Pending / In progress / Completed / Cancelled).',
            'new_status' => 'Human label of the new status.',
            'deadline' => 'Task deadline formatted as "dd MMM yyyy".',
        ];
    }

    /**
     * Fallback wording used only if the DB template row is missing —
     * keeps the queue from poisoning when an admin accidentally deletes
     * the row before re-seeding.
     *
     * @param  array<string, scalar>  $vars
     */
    private function fallbackMail(string $recipientName, array $vars): MailMessage
    {
        return (new MailMessage())
            ->subject("Status changed: {$vars['task_title']}")
            ->greeting("Hi {$recipientName},")
            ->line("{$vars['actor_name']} changed the status of a task you're involved in.")
            ->line("**Task:** {$vars['task_title']}")
            ->line("**Scheme:** {$vars['scheme_name']}")
            ->line("**Status:** {$vars['old_status']} → **{$vars['new_status']}**")
            ->line("**Deadline:** {$vars['deadline']}");
    }
}

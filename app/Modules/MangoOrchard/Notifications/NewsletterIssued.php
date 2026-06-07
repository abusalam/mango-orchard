<?php

declare(strict_types=1);

namespace App\Modules\MangoOrchard\Notifications;

use App\Modules\MangoOrchard\Models\NewsletterIssue;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

/**
 * Per-recipient delivery of a newsletter issue. Subject + body come
 * from the issue itself (admin-authored markdown), so there's no
 * editable template — the issue IS the content. Every send embeds a
 * signed unsubscribe URL keyed on the user id; clicking it flips
 * `subscribe_newsletter` off and shows a confirmation page.
 *
 * Mail channel only (no `database` channel — newsletters would flood
 * the in-app notification bell otherwise). Queued so the admin's Send
 * click returns immediately even with thousands of recipients.
 */
class NewsletterIssued extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public NewsletterIssue $issue) {}

    /**
     * @return list<string>
     */
    public function via(): array
    {
        // Mail-only flow: when Mango Orchard mail is disabled there's
        // nothing to deliver — return [] and the notification queue
        // becomes a no-op. The send controller refuses upfront so this
        // is only a defence-in-depth path (e.g. mid-flight queue drain
        // after an admin flips the switch).
        return app(\App\Settings\Settings::class)->mailEnabledForMangoOrchard()
            ? ['mail']
            : [];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $unsubscribe = URL::signedRoute('preferences.unsubscribe-newsletter', ['user' => $notifiable->id]);

        $msg = (new MailMessage())
            ->subject($this->issue->subject)
            ->greeting("Hi {$notifiable->name},");

        // Drop the issue body in paragraph-by-paragraph so MailMessage's
        // markdown renderer can format each one (links, bold, etc.).
        foreach (preg_split('/\R{2,}/', trim($this->issue->body)) ?: [] as $paragraph) {
            $paragraph = trim($paragraph);
            if ($paragraph !== '') {
                $msg->line($paragraph);
            }
        }

        return $msg
            ->action('Open the orchard', url(route('home')))
            ->line('---')
            ->line("Not interested in the monthly newsletter? [Unsubscribe here]({$unsubscribe}). You'll keep getting any other notifications you opted into.");
    }
}
